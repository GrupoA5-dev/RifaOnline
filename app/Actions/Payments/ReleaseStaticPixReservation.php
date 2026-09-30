<?php

namespace App\Actions\Payments;

use App\Actions\Orders\ExpireOrderReservation;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Support\AuditService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ReleaseStaticPixReservation
{
    public function __construct(private readonly ExpireOrderReservation $expireOrder) {}

    public function handle(Payment $payment): Payment
    {
        $released = DB::transaction(function () use ($payment): Payment {
            /** @var Payment $locked */
            $locked = Payment::query()->lockForUpdate()->with('order')->findOrFail($payment->getKey());

            if ($locked->gateway !== 'pix_static' || $locked->status !== PaymentStatus::Pending) {
                throw new RuntimeException('A reserva não está disponível para liberação manual.');
            }

            if (! $locked->order?->expires_at?->isPast()) {
                throw new RuntimeException('A reserva ainda está dentro do prazo.');
            }

            if (! $this->expireOrder->handle($locked->order)) {
                throw new RuntimeException('Não foi possível expirar o pedido. Atualize a tela e confira o status.');
            }

            $locked->forceFill([
                'status' => PaymentStatus::Cancelled,
                'status_detail' => 'Reserva liberada manualmente após conferência bancária',
                'last_synced_at' => now(),
            ])->save();

            return $locked->fresh(['order']) ?? $locked;
        }, 3);

        AuditService::record('payment.pix_static.released', $released, after: [
            'payment_id' => $released->getKey(),
            'order_id' => $released->order_id,
        ]);

        return $released;
    }
}
