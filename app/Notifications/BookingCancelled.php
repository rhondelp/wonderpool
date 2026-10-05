<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Guest: booking cancelled by the resort, or expired because no payment proof arrived in time.
 */
class BookingCancelled extends BookingNotification
{
    /**
     * @param  Booking  $booking  The cancelled booking
     * @param  string|null  $reason  Cancellation/expiry reason (also shown on the track page)
     * @param  bool  $expired  True when the scheduler cancelled it (no payment proof)
     */
    public function __construct(Booking $booking, public ?string $reason = null, public bool $expired = false)
    {
        parent::__construct($booking);
    }

    /**
     * Toggle: "Booking cancelled or expired (to guest)".
     */
    public function type(): NotificationType
    {
        return NotificationType::BookingCancelled;
    }

    /**
     * The email (cancelled.blade.php).
     */
    public function toMail(object $notifiable): MailMessage
    {
        return $this->message(($this->expired ? 'Booking request expired: ' : 'Booking cancelled: ').$this->booking->reference_code, 'cancelled', [
            'reason' => $this->reason,
            'expired' => $this->expired,
        ]);
    }
}
