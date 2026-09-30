<?php

namespace App\Actions\Payments;

use App\Actions\Orders\MarkOrderPaid;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Support\AuditService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ConfirmStaticPixPayment
{
    public function __construct(private readonly MarkOrderPaid $markOrderPaid) {}

    public function handle(Payment $payment, CarbonInterface $paidAt): Payment
    {
        $confirmed = DB::transaction(function () use ($payment, $paidAt): Payment {
            /** @var Payment $locked */
            $locked = Payment::query()->lockForUpdate()->with('order')->findOrFail($payment->getKey());

            if ($locked->gateway !== 'pix_static') {
                throw new RuntimeException('Este pagamento não é um Pix estático.');
            }

            if ($locked->status === PaymentStatus::Approved) {
                return $locked;
            }

            if ($locked->status !== PaymentStatus::Pending) {
                throw new RuntimeException('Somente pagamentos Pix pendentes podem ser confirmados manualmente.');
            }

            $this->markOrderPaid->handle($locked->order, $paidAt);

            $locked->forceFill([
                'status' => PaymentStatus::Approved,
                'status_detail' => 'Confirmado manualmente no painel',
                'paid_at' => $paidAt,
                'last_synced_at' => now(),
            ])->save();

            return $locked->fresh(['order']) ?? $locked;
        }, 3);

        AuditService::record('payment.pix_static.confirmed', $confirmed, after: [
            'payment_id' => $confirmed->getKey(),
            'order_id' => $confirmed->order_id,
            'amount_cents' => $confirmed->amount_cents,
            'paid_at' => $confirmed->paid_at?->toIso8601String(),
        ]);

        return $confirmed;
    }
}
