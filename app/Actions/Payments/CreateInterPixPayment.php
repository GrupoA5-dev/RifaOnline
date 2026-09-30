<?php

namespace App\Actions\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\InterPixGateway;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class CreateInterPixPayment
{
    public function __construct(private readonly InterPixGateway $gateway) {}

    public function handle(Order $order): Payment
    {
        /** @var Payment $payment */
        $payment = DB::transaction(function () use ($order): Payment {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if ($locked->status !== OrderStatus::AwaitingPayment) {
                throw new RuntimeException('O pedido não está aguardando pagamento.');
            }

            if ($locked->expires_at?->isPast()) {
                throw new RuntimeException('O prazo de pagamento do pedido já encerrou.');
            }

            $existing = $locked->payments()
                ->where('gateway', 'inter_pix')
                ->whereIn('status', [
                    PaymentStatus::Creating->value,
                    PaymentStatus::Pending->value,
                    PaymentStatus::InProcess->value,
                    PaymentStatus::Approved->value,
                ])
                ->latest('id')
                ->first();

            if ($existing) {
                return $existing;
            }

            $txid = $this->gateway->txidForOrder($locked);

            return $locked->payments()->create([
                'gateway' => 'inter_pix',
                'status' => PaymentStatus::Creating,
                'status_detail' => 'Criando cobrança Pix no Banco Inter',
                'amount_cents' => (int) $locked->total_cents,
                'currency' => 'BRL',
                'payment_method' => 'pix',
                'external_reference' => $txid,
                'expires_at' => $locked->expires_at,
            ]);
        }, 3);

        if ($payment->status === PaymentStatus::Approved || ($payment->status === PaymentStatus::Pending && filled($payment->qr_code))) {
            return $payment;
        }

        $payment->loadMissing('order');
        $secondsRemaining = (int) now()->diffInSeconds($payment->order->expires_at, false);
        if ($secondsRemaining < 60) {
            throw new RuntimeException('O prazo restante da reserva é insuficiente para gerar uma nova cobrança Pix. Faça uma nova reserva.');
        }

        try {
            $charge = $this->gateway->createImmediateCharge(
                (string) $payment->external_reference,
                $payment->order,
                $secondsRemaining,
            );
        } catch (Throwable $createError) {
            // PUT /cob/{txid} é idempotente. Se a resposta se perdeu depois de o Inter
            // criar a cobrança, uma consulta pelo mesmo txid recupera o estado real.
            try {
                $charge = $this->gateway->getCharge((string) $payment->external_reference);
            } catch (Throwable) {
                $payment->forceFill([
                    'status' => PaymentStatus::Creating,
                    'status_detail' => mb_substr('Falha temporária ao criar Pix Inter: '.$createError->getMessage(), 0, 120),
                    'last_synced_at' => now(),
                ])->save();

                throw $createError;
            }
        }

        $qrCode = trim((string) ($charge['pixCopiaECola'] ?? ''));
        if ($qrCode === '') {
            throw new RuntimeException('O Banco Inter criou a cobrança, mas não retornou o Pix Copia e Cola.');
        }

        $expiresAt = $this->gateway->chargeExpiresAt(
            $charge,
            $payment->expires_at?->toImmutable(),
        );

        $payment->forceFill([
            'status' => PaymentStatus::Pending,
            'status_detail' => 'Aguardando pagamento no Banco Inter',
            'qr_code' => $qrCode,
            'expires_at' => $expiresAt ?? $payment->expires_at,
            'last_synced_at' => now(),
        ])->save();

        return $payment->fresh(['order']) ?? $payment;
    }
}
