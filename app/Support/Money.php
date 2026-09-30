<?php

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    public static function toCents(string|int|float|null $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $normalized = trim((string) $value);
        $normalized = str_replace(['R$', ' '], '', $normalized);

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new InvalidArgumentException('Valor monetário inválido.');
        }

        [$whole, $decimal] = array_pad(explode('.', $normalized, 2), 2, '');
        $decimal = str_pad($decimal, 2, '0');

        return ((int) $whole * 100) + (int) substr($decimal, 0, 2);
    }

    public static function format(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.');
    }
}
