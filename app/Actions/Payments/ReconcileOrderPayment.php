<?php

namespace App\Actions\Payments;

use App\Contracts\Payments\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;

final class ReconcileOrderPayment
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly ApplyGatewayPayment $applyGatewayPayment,
    ) {}

    public function handle(Order $order): ?Payment
    {
        /** @var Payment|null $payment */
        $payment = $order->payments()
            ->where('gateway', 'mercadopago')
            ->whereNotNull('gateway_payment_id')
            ->whereIn('status', [
                PaymentStatus::Creating->value,
                PaymentStatus::Pending->value,
                PaymentStatus::InProcess->value,
                PaymentStatus::Approved->value,
            ])
            ->latest('id')
            ->first();

        if (! $payment) {
            return null;
        }

        $remote = $this->gateway->getPayment((string) $payment->gateway_payment_id);

        return $this->applyGatewayPayment->handle($payment, $remote);
    }
}
