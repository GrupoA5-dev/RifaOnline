<?php

namespace App\Enums;

enum OrderStatus: string
{
    case AwaitingPayment = 'awaiting_payment';
    case Paid = 'paid';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case Chargeback = 'chargeback';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingPayment => 'Aguardando pagamento',
            self::Paid => 'Pago',
            self::Expired => 'Expirado',
            self::Cancelled => 'Cancelado',
            self::Refunded => 'Reembolsado',
            self::Chargeback => 'Chargeback',
        };
    }
}
