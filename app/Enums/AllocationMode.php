<?php

namespace App\Enums;

enum AllocationMode: string
{
    case Random = 'random';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Random => 'Números aleatórios',
            self::Manual => 'Escolha manual',
        };
    }
}
