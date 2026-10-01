<?php

namespace App\Support;

/**
 * Money helpers. All amounts are stored as integer centavos (HISTORY.md D-001).
 */
final class Money
{
    /**
     * Convert a peso amount to centavos, e.g. 7000 → 700000.
     */
    public static function fromPesos(int|float|string $pesos): int
    {
        return (int) round(((float) $pesos) * 100);
    }

    /**
     * Convert centavos to pesos as a float, for display/export only (never for arithmetic).
     */
    public static function toPesos(int $cents): float
    {
        return $cents / 100;
    }

    /**
     * Format centavos for display, e.g. 700000 → "₱7,000.00".
     */
    public static function format(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';

        return $sign.'₱'.number_format(abs($cents) / 100, 2);
    }
}
