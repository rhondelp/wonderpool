<?php

namespace App\Enums;

/**
 * Every notification the system can send (M8, D-037). Each one has an on/off switch in
 * Settings → Notifications (settingKey()) and a channel list in config/wonderpool.php.
 */
enum NotificationType: string
{
    case BookingReceivedGuest = 'booking_received_guest';
    case BookingReceivedOwner = 'booking_received_owner';
    case BookingApproved = 'booking_approved';
    case BookingRejected = 'booking_rejected';
    case BookingCancelled = 'booking_cancelled';
    case PaymentProofReceived = 'payment_proof_received';
    case BookingReminder = 'booking_reminder';

    /**
     * Settings key of the on/off switch, e.g. "notifications.booking_approved".
     */
    public function settingKey(): string
    {
        return 'notifications.'.$this->value;
    }

    /**
     * Label of the switch on the settings screen.
     */
    public function label(): string
    {
        return match ($this) {
            self::BookingReceivedGuest => 'Booking received (to guest)',
            self::BookingReceivedOwner => 'New booking request (to owners)',
            self::BookingApproved => 'Booking approved (to guest)',
            self::BookingRejected => 'Booking rejected, with reason (to guest)',
            self::BookingCancelled => 'Booking cancelled or expired (to guest)',
            self::PaymentProofReceived => 'Payment proof uploaded (to owners)',
            self::BookingReminder => 'Reminder before the stay (to guest)',
        };
    }
}
