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
        };
    }
}
