<?php

namespace App\Support;

/**
 * Philippine mobile numbers in one canonical form, E.164 "+639XXXXXXXXX" (D-013, D-025).
 * Accepts what guests type: 09171234567, 9171234567, 639171234567, +63 917 123 4567, 0917-123-4567.
 */
final class PhoneNumber
{
    /**
     * Canonical +639XXXXXXXXX, or null when the input is not a PH mobile number.
     */
    public static function normalize(?string $input): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $input) ?? '';

        $national = match (true) {
            strlen($digits) === 12 && str_starts_with($digits, '639') => substr($digits, 2),
            strlen($digits) === 11 && str_starts_with($digits, '09') => substr($digits, 1),
            strlen($digits) === 10 && str_starts_with($digits, '9') => $digits,
            default => null,
        };

        return $national === null ? null : '+63'.$national;
    }

    /**
     * Friendly local format, e.g. "0917 123 4567" (input returned unchanged if not a PH mobile).
     */
    public static function display(?string $input): string
    {
        $canonical = self::normalize($input);

        if ($canonical === null) {
            return (string) $input;
        }

        $local = '0'.substr($canonical, 3);

        return substr($local, 0, 4).' '.substr($local, 4, 3).' '.substr($local, 7);
    }
}
