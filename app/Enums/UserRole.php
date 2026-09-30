<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Financeiro = 'financeiro';
    case Suporte = 'suporte';
    case Marketing = 'marketing';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Administrador',
            self::Financeiro => 'Financeiro',
            self::Suporte => 'Suporte',
            self::Marketing => 'Marketing',
        };
    }
}
