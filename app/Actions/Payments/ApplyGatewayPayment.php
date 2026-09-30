<?php

namespace App\Actions\Payments;

use App\Actions\Orders\MarkOrderPaid;
use App\Data\Payments\GatewayPaymentData;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentGatewayException;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

final class ApplyGatewayPayment
{
    public function __construct(
        private readonly MarkOrderPaid $markOrderPaid,
    ) {}

    public function handle(Payment $payment, GatewayPaymentData $remote): Payment
    {
        /** @var Payment $updated */
        $updated = DB::transaction(function () use ($payment, $remote): Payment {
            /** @var Payment $locked */
            $locked = Payment::query()->lockForUpdate()->with('order')->findOrFail($payment->getKey());
            $order = $locked->order;

            if ($remote->externalReference !== null && ! hash_equals((string) $order->uuid, $remote->externalReference)) {
                throw new PaymentGatewayException('A referência externa do pagamento não corresponde ao pedido.');
            }

            if ($remote->amountCents !== (int) $locked->amount_cents || $remote->amountCents !== (int) $order->total_cents) {
                throw new PaymentGatewayException('O valor confirmado pelo gateway não corresponde ao valor do pedido.');
            }

            $status = PaymentStatus::fromGateway($remote->status);
            $now = now();

            $locked->forceFill([
                'gateway_payment_id' => $remote->id,
                'status' => $status,
                'status_detail' => $remote->statusDetail,
                'external_reference' => $remote->externalReference ?? $order->uuid,
                'qr_code' => $remote->qrCode ?? $locked->qr_code,
                'qr_code_base64' => $remote->qrCodeBase64 ?? $locked->qr_code_base64,
                'ticket_url' => $remote->ticketUrl ?? $locked->ticket_url,
                'expires_at' => $remote->expiresAt ?? $locked->expires_at,
                'paid_at' => $remote->paidAt ?? $locked->paid_at,
                'refunded_at' => $status === PaymentStatus::Refunded ? ($locked->refunded_at ?? $now) : $locked->refunded_at,
                'last_synced_at' => $now,
            ])->save();

            if ($status === PaymentStatus::Refunded && $order->status === OrderStatus::Paid) {
                $order->forceFill(['status' => OrderStatus::Refunded])->save();
            }

            if ($status === PaymentStatus::ChargedBack && in_array($order->status, [OrderStatus::Paid, OrderStatus::Refunded], true)) {
                $order->forceFill(['status' => OrderStatus::Chargeback])->save();
            }

            return $locked;
        }, 3);

        if ($updated->status === PaymentStatus::Approved) {
            $this->markOrderPaid->handle($updated->order, $remote->paidAt);
        }

        return $updated->fresh(['order']) ?? $updated;
    }
}
