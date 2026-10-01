<?php

namespace App\Services\Booking;

use App\Exceptions\Booking\ReferenceCodeExhaustedException;
use App\Models\Booking;
use Carbon\CarbonInterface;
use Closure;

/**
 * Human-friendly booking references (D-023): PREFIX-YYMM-XXXX, e.g. "WP-2610-A7K3",
 * where YYMM is the stay's start month and XXXX uses an alphabet without look-alike
 * characters (no 0/O, 1/I/L). 31^4 ≈ 923k codes per month.
 * Uniqueness is checked against all bookings (incl. soft-deleted); BookingService calls this
 * under its advisory lock, so two creations cannot pick the same free code concurrently.
 */
class ReferenceCodeGenerator
{
    public const PREFIX = 'WP';

    public const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public const RANDOM_LENGTH = 4;

    public const MAX_ATTEMPTS = 10;

    /** @var Closure(int, int): int */
    private readonly Closure $random;

    /**
     * @param  (Closure(int, int): int)|null  $random  Random int source (min, max); default random_int (tests inject a fixed sequence)
     */
    public function __construct(?Closure $random = null)
    {
        $this->random = $random ?? fn (int $min, int $max): int => random_int($min, $max);
    }

    /**
     * A reference code not used by any booking.
     *
     * @param  CarbonInterface  $startsAt  Stay start (provides YYMM)
     *
     * @throws ReferenceCodeExhaustedException After MAX_ATTEMPTS collisions
     */
    public function generate(CarbonInterface $startsAt): string
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $code = self::PREFIX.'-'.$startsAt->format('ym').'-'.$this->randomPart();

            if (! Booking::withTrashed()->where('reference_code', $code)->exists()) {
                return $code;
            }
        }

        throw new ReferenceCodeExhaustedException('Could not generate a unique booking reference. Please try again.');
    }

    /**
     * Regex matching a valid code (for lookups/validation).
     */
    public static function pattern(): string
    {
        return '/^'.self::PREFIX.'-\d{4}-['.self::ALPHABET.']{'.self::RANDOM_LENGTH.'}$/';
    }

    /**
     * RANDOM_LENGTH characters from ALPHABET.
     */
    private function randomPart(): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $part = '';

        for ($i = 0; $i < self::RANDOM_LENGTH; $i++) {
            $part .= self::ALPHABET[($this->random)(0, $max)];
        }

        return $part;
    }
}
