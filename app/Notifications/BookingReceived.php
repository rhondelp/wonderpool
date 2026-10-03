<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * "We received your booking request" (guest) or "New booking request" (owners).
 * One class, two audiences; the audience picks the toggle, the template and the link.
 */
class BookingReceived extends BookingNotification
{
    /**
     * @param  Booking  $booking  The new pending booking
     * @param  bool  $forOwner  True = owner alert, false = guest confirmation
     */
    public function __construct(Booking $booking, public bool $forOwner = false)
    {
        parent::__construct($booking);
    }

    /**
     * Toggle: "Booking received (to guest)" or "New booking request (to owners)".
     */
    public function type(): NotificationType
    {
        return $this->forOwner ? NotificationType::BookingReceivedOwner : NotificationType::BookingReceivedGuest;
    }

    /**
     * The email (received-guest / received-owner.blade.php).
     */
    public function toMail(object $notifiable): MailMessage
    {
        return $this->forOwner
            ? $this->message('New booking request: '.$this->booking->reference_code, 'received-owner', [
                'guestName' => $this->booking->guest_name,
                'adminUrl' => route('admin.bookings.show', $this->booking),
            ])
            : $this->message('We received your booking request: '.$this->booking->reference_code, 'received-guest');
    }
}
