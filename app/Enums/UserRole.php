<?php

namespace App\Enums;

/**
 * Admin panel role. Owner = full access; Staff = bookings only (PLAN.md §2.2).
 */
enum UserRole: string
{
    case Owner = 'owner';
    case Staff = 'staff';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Staff => 'Staff',
        };
    }

    /**
     * Badge color key understood by x-ui.badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Owner => 'pool',
            self::Staff => 'slate',
        };
    }
}
