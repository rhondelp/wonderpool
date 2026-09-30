<?php

namespace App\Enums;

/**
 * Gallery image category (PLAN.md §2.1).
 */
enum GalleryCategory: string
{
    case Pools = 'pools';
    case Rooms = 'rooms';
    case Hall = 'hall';
    case Events = 'events';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pools => 'Pools',
            self::Rooms => 'Rooms',
            self::Hall => 'Function Hall',
            self::Events => 'Events',
        };
    }
}
