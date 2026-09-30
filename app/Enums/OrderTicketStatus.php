<?php

namespace App\Enums;

enum OrderTicketStatus: string
{
    case Reserved = 'reserved';
    case Paid = 'paid';
    case Released = 'released';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Reserved => 'Reservado',
            self::Paid => 'Pago',
            self::Released => 'Liberado',
            self::Cancelled => 'Cancelado',
        };
    }
}
