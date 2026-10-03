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

    /**
     * Plain decimal pesos for CSV exports, e.g. 700050 → "7000.50" (no symbol, no thousands separator).
     */
    public static function decimal(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';

        return $sign.intdiv(abs($cents), 100).'.'.str_pad((string) (abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Short whole-peso label for chart axes, e.g. 1250000 → "₱12.5k", 250000000 → "₱2.5M".
     */
    public static function compact(int $cents): string
    {
        $pesos = abs($cents) / 100;
        $sign = $cents < 0 ? '-' : '';

        return $sign.'₱'.match (true) {
            $pesos >= 1_000_000 => rtrim(rtrim(number_format($pesos / 1_000_000, 1), '0'), '.').'M',
            $pesos >= 1_000 => rtrim(rtrim(number_format($pesos / 1_000, 1), '0'), '.').'k',
            default => number_format($pesos),
        };
    }
}
