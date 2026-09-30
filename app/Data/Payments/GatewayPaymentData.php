<?php

namespace App\Data\Payments;

use Carbon\CarbonImmutable;

final readonly class GatewayPaymentData
{
    public function __construct(
        public string $id,
        public string $status,
        public ?string $statusDetail,
        public int $amountCents,
        public ?string $externalReference,
        public ?string $qrCode,
        public ?string $qrCodeBase64,
        public ?string $ticketUrl,
        public ?CarbonImmutable $expiresAt,
        public ?CarbonImmutable $paidAt,
    ) {}
}
