<?php

namespace App\Enums;

/**
 * Audit action names written to activity_logs.action by ActivityLogger.
 * Add new cases per module (bookings in M6, content in M3, ...).
 */
enum ActivityAction: string
{
    case Login = 'auth.login';
    case Logout = 'auth.logout';
    case PasswordChanged = 'auth.password_changed';
    case UserCreated = 'user.created';
    case UserUpdated = 'user.updated';
    case UserActivated = 'user.activated';
    case UserDeactivated = 'user.deactivated';
    case UserPasswordReset = 'user.password_reset';
    case SettingsUpdated = 'settings.updated';
    case ContentCreated = 'content.created';
    case ContentUpdated = 'content.updated';
    case ContentDeleted = 'content.deleted';
    case ContentReordered = 'content.reordered';
    case BookingCreated = 'booking.created';
    case BookingStatusChanged = 'booking.status_changed';
    case BookingRescheduled = 'booking.rescheduled';
    case BookingExpired = 'booking.expired';
    case PaymentProofUploaded = 'payment.proof_uploaded';
    case BookingPriceOverridden = 'booking.price_overridden';
    case BookingNotesUpdated = 'booking.notes_updated';
    case PaymentRecorded = 'payment.recorded';
    case PaymentVerified = 'payment.verified';
    case PaymentRejected = 'payment.rejected';

    /**
     * Human-readable label for the activity log screen.
     */
    public function label(): string
    {
        return match ($this) {
            self::Login => 'Signed in',
            self::Logout => 'Signed out',
            self::PasswordChanged => 'Changed own password',
            self::UserCreated => 'Created user',
            self::UserUpdated => 'Updated user',
            self::UserActivated => 'Enabled user',
            self::UserDeactivated => 'Disabled user',
            self::UserPasswordReset => 'Reset user password',
            self::SettingsUpdated => 'Updated settings',
            self::ContentCreated => 'Created content',
            self::ContentUpdated => 'Updated content',
            self::ContentDeleted => 'Deleted content',
            self::ContentReordered => 'Reordered content',
            self::BookingCreated => 'Created booking',
            self::BookingStatusChanged => 'Changed booking status',
            self::BookingRescheduled => 'Rescheduled booking',
            self::BookingExpired => 'Expired unpaid booking',
            self::PaymentProofUploaded => 'Uploaded payment proof',
            self::BookingPriceOverridden => 'Overrode booking price',
            self::BookingNotesUpdated => 'Updated internal notes',
            self::PaymentRecorded => 'Recorded payment',
            self::PaymentVerified => 'Verified payment',
            self::PaymentRejected => 'Rejected payment',
        };
    }
}
