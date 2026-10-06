<?php

namespace App\Enums;

enum OrganizerRole: string
{
    case Master = 'master';
    case Manager = 'manager';

    public function label(): string
    {
        return match ($this) {
            self::Master => 'Master',
            self::Manager => 'Gestor',
        };
    }
}
