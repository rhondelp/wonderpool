<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Owners: a guest uploaded (or replaced) a payment proof. Links to the login-protected admin
 * booking page only, never to the proof file (D-032).
 */
class PaymentProofReceived extends BookingNotification
{
    /**
     * Toggle: "Payment proof uploaded (to owners)".
     */
    public function type(): NotificationType
    {
        return NotificationType::PaymentProofReceived;
    }

    /**
     * The email (proof-received.blade.php).
     */
    public function toMail(object $notifiable): MailMessage
    {
        return $this->message('Payment proof to review: '.$this->booking->reference_code, 'proof-received', [
            'guestName' => $this->booking->guest_name,
            'adminUrl' => route('admin.bookings.show', $this->booking),
        ]);
    }
}
