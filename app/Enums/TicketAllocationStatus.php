<?php

namespace App\Enums;

enum TicketAllocationStatus: string
{
    case Reserved = 'reserved';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Reserved => 'Reservado',
            self::Paid => 'Pago',
        };
    }
}
