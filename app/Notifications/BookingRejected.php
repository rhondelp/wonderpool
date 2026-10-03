<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Guest: booking request declined, with the reason the admin entered.
 */
class BookingRejected extends BookingNotification
{
    /**
     * @param  Booking  $booking  The rejected booking
     * @param  string|null  $reason  Rejection reason (also shown on the track page)
     */
    public function __construct(Booking $booking, public ?string $reason = null)
    {
        parent::__construct($booking);
    }

    /**
     * Toggle: "Booking rejected, with reason (to guest)".
     */
    public function type(): NotificationType
    {
        return NotificationType::BookingRejected;
    }

    /**
     * The email (rejected.blade.php).
     */
    public function toMail(object $notifiable): MailMessage
    {
        return $this->message('Booking request declined: '.$this->booking->reference_code, 'rejected', [
            'reason' => $this->reason ?? $this->booking->rejection_reason,
        ]);
    }
}
