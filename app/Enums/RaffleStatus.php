<?php

namespace App\Enums;

enum RaffleStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Paused = 'paused';
    case SoldOut = 'sold_out';
    case Drawing = 'drawing';
    case Finished = 'finished';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Scheduled => 'Agendada',
            self::Active => 'Ativa',
            self::Paused => 'Pausada',
            self::SoldOut => 'Esgotada',
            self::Drawing => 'Em apuração',
            self::Finished => 'Finalizada',
            self::Archived => 'Arquivada',
        };
    }
}
