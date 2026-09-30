<?php

namespace App\Contracts\Payments;

use App\Data\Payments\GatewayPaymentData;
use App\Models\Payment;

interface PaymentGateway
{
    public function createPixPayment(Payment $payment): GatewayPaymentData;

    public function getPayment(string $gatewayPaymentId): GatewayPaymentData;

    public function validateWebhookSignature(
        ?string $xSignature,
        ?string $xRequestId,
        ?string $dataId,
    ): bool;
}
