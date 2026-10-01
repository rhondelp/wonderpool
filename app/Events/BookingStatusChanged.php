<?php

namespace App\Events;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired by BookingService after a status change is committed. Notification listeners arrive in M8.
 */
class BookingStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  Booking  $booking  The booking (already saved with the new status)
     * @param  BookingStatus  $from  Previous status
     * @param  BookingStatus  $to  New status
     * @param  User|null  $actor  Admin who made the change (null = system, e.g. expiry)
     * @param  string|null  $reason  Rejection/cancellation reason, if any
     */
    public function __construct(
        public readonly Booking $booking,
        public readonly BookingStatus $from,
        public readonly BookingStatus $to,
        public readonly ?User $actor = null,
        public readonly ?string $reason = null,
    ) {
    }
}
