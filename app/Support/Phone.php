<?php

namespace App\Support;

use InvalidArgumentException;

final class Phone
{
    public static function normalize(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if (strlen($digits) < 10 || strlen($digits) > 13) {
            throw new InvalidArgumentException('Telefone inválido.');
        }

        return $digits;
    }
}
