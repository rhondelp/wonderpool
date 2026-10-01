<?php

namespace App\Enums;

/**
 * Lifecycle status of a booking (PLAN.md §5.5).
 *
 * Transitions are validated in BookingService (M4); this enum only describes the states.
 */
enum BookingStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::Completed => 'Completed',
        };
    }

    /**
     * Badge color key understood by x-ui.badge (PLAN.md §7).
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Approved => 'garden',
            self::Rejected => 'rose',
            self::Cancelled => 'slate',
            self::Completed => 'pool',
        };
    }

    /**
     * Statuses that occupy the resort's time window (PLAN.md §5.1).
     *
     * @return list<self>
     */
    public static function blocking(): array
    {
        return [self::Pending, self::Approved];
    }
}
