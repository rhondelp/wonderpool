<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Events\BookingStatusChanged;
use App\Events\PaymentProofUploaded;
use App\Services\NotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * Turns booking events into notifications (M8). Runs only after the database transaction that
 * fired the event commits, so a rolled-back approval or walk-in never emails anyone. Channels are
 * chosen inside the notifications (NotificationChannelResolver), switches in NotificationService.
 * Registered by Laravel's listener discovery (handle* methods, app/Listeners).
 */
class SendBookingNotifications implements ShouldHandleEventsAfterCommit
{
    /**
     * @param  NotificationService  $notifications  Recipients and on/off switches
     */
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    /**
     * New booking → guest confirmation + owner alert.
     */
    public function handleBookingCreated(BookingCreated $event): void
    {
        $this->notifications->bookingCreated($event->booking);
    }

    /**
     * Approved / rejected / cancelled (incl. expiry) → guest.
     */
    public function handleBookingStatusChanged(BookingStatusChanged $event): void
    {
        $this->notifications->statusChanged($event->booking, $event->to, $event->reason, $event->actor === null);
    }

    /**
     * Payment proof uploaded → owners.
     */
    public function handlePaymentProofUploaded(PaymentProofUploaded $event): void
    {
        $this->notifications->proofUploaded($event->booking);
    }
}
