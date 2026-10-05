<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Guest: booking confirmed, with the schedule and the track link.
 */
class BookingApproved extends BookingNotification
{
    /**
     * Toggle: "Booking approved (to guest)".
     */
    public function type(): NotificationType
    {
        return NotificationType::BookingApproved;
    }

    /**
     * The email (approved.blade.php).
     */
    public function toMail(object $notifiable): MailMessage
    {
        return $this->message('Booking confirmed: '.$this->booking->reference_code, 'approved');
    }
}
