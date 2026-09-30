<?php

namespace App\Actions\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\InterPixSettings;
use App\Services\Payments\StaticPixPayload;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CreatePixPayment
{
    public function __construct(
        private readonly StaticPixPayload $payload,
        private readonly InterPixSettings $interSettings,
        private readonly CreateInterPixPayment $createInterPixPayment,
    ) {}

    public function handle(Order $order): Payment
    {
        $existing = $order->payments()
            ->whereIn('gateway', ['inter_pix', 'pix_static'])
            ->whereIn('status', [
                PaymentStatus::Creating->value,
                PaymentStatus::Pending->value,
                PaymentStatus::InProcess->value,
                PaymentStatus::Approved->value,
            ])
            ->latest('id')
            ->first();

        if ($existing) {
            if ($existing->gateway === 'inter_pix' && ($existing->status === PaymentStatus::Creating || blank($existing->qr_code))) {
                return $this->createInterPixPayment->handle($order);
            }

            return $existing;
        }

        if ($this->interSettings->getBool('enabled')) {
            if (! $this->interSettings->configurationStatus()['ready']) {
                throw new RuntimeException('O Pix Banco Inter está ativado, mas a configuração ainda está incompleta.');
            }

            return $this->createInterPixPayment->handle($order);
        }

        return $this->createStaticPayment($order);
    }

    private function createStaticPayment(Order $order): Payment
    {
        return DB::transaction(function () use ($order): Payment {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->with('customer')->findOrFail($order->getKey());

            if ($locked->status !== OrderStatus::AwaitingPayment) {
                throw new RuntimeException('O pedido não está aguardando pagamento.');
            }

            if ($locked->expires_at?->isPast()) {
                throw new RuntimeException('O pedido já expirou.');
            }

            $existing = $locked->payments()
                ->where('gateway', 'pix_static')
                ->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::Approved->value])
                ->latest('id')
                ->first();

            if ($existing) {
                return $existing;
            }

            $key = trim((string) config('pix.key'));
            $merchantName = trim((string) config('pix.merchant_name'));
            $merchantCity = trim((string) config('pix.merchant_city'));

            if ($key === '' || $merchantName === '' || $merchantCity === '') {
                throw new RuntimeException('Pix estático não configurado. Preencha PIX_KEY, PIX_MERCHANT_NAME e PIX_MERCHANT_CITY no .env.');
            }

            $txid = $this->payload->txidForOrder($locked->uuid);
            $brCode = $this->payload->make(
                key: $key,
                merchantName: $merchantName,
                merchantCity: $merchantCity,
                amountCents: (int) $locked->total_cents,
                txid: $txid,
            );

            return $locked->payments()->create([
                'gateway' => 'pix_static',
                'status' => PaymentStatus::Pending,
                'status_detail' => 'Aguardando confirmação manual',
                'amount_cents' => $locked->total_cents,
                'currency' => 'BRL',
                'payment_method' => 'pix',
                'external_reference' => $txid,
                'qr_code' => $brCode,
                'expires_at' => $locked->expires_at,
            ]);
        }, 3);
    }
}
