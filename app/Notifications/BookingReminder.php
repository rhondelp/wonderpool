<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Guest: reminder before the stay. Sent by bookings:send-reminders, at most once per booking
 * (bookings.reminded_at, D-038).
 */
class BookingReminder extends BookingNotification
{
    /**
     * Toggle: "Reminder before the stay (to guest)".
     */
    public function type(): NotificationType
    {
        return NotificationType::BookingReminder;
    }

    /**
     * The email (reminder.blade.php).
     */
    public function toMail(object $notifiable): MailMessage
    {
        return $this->message('See you soon: '.$this->booking->reference_code, 'reminder');
    }
}
