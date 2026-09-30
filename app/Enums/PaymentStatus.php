<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Creating = 'creating';
    case Pending = 'pending';
    case InProcess = 'in_process';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case ChargedBack = 'charged_back';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Creating => 'Criando pagamento',
            self::Pending => 'Pendente',
            self::InProcess => 'Em processamento',
            self::Approved => 'Aprovado',
            self::Rejected => 'Rejeitado',
            self::Cancelled => 'Cancelado',
            self::Refunded => 'Reembolsado',
            self::ChargedBack => 'Chargeback',
            self::Unknown => 'Desconhecido',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Creating, self::Pending, self::InProcess], true);
    }

    public static function fromGateway(?string $status): self
    {
        return match (mb_strtolower((string) $status)) {
            'pending' => self::Pending,
            'in_process', 'authorized' => self::InProcess,
            'approved' => self::Approved,
            'rejected' => self::Rejected,
            'cancelled', 'canceled' => self::Cancelled,
            'refunded' => self::Refunded,
            'charged_back' => self::ChargedBack,
            default => self::Unknown,
        };
    }
}
