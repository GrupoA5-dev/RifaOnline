<?php

namespace App\Services\Payments;

use App\Models\Raffle;
use Carbon\CarbonImmutable;

final class PixReservationWindow
{
    public function __construct(private readonly InterPixSettings $settings) {}

    public function expiresAt(Raffle $raffle): CarbonImmutable
    {
        $minutes = (int) $raffle->reservation_minutes;

        return now()->addMinutes(max(1, min(1440, $minutes)))->toImmutable();
    }
}
        
