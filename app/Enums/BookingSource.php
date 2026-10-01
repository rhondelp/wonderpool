<?php

namespace App\Enums;

/**
 * Who created a booking: a guest on the website, or an admin (walk-in / phone booking).
 */
enum BookingSource: string
{
    case Guest = 'guest';
    case Admin = 'admin';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Guest => 'Online',
            self::Admin => 'Walk-in / admin',
        };
    }
}
