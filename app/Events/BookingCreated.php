<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired by BookingService::create() inside its transaction; listeners implement
 * ShouldHandleEventsAfterCommit so they only run once the booking is committed (M8).
 */
class BookingCreated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  Booking  $booking  The new pending booking
     * @param  User|null  $actor  Admin who created it (walk-in), null for website guests
     */
    public function __construct(
        public readonly Booking $booking,
        public readonly ?User $actor = null,
    ) {
    }
}
