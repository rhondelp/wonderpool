<?php

/*
|--------------------------------------------------------------------------
| Wonderpool application config
|--------------------------------------------------------------------------
| Deployment-level values read from .env. Runtime business settings
| (downpayment %, contact info, ...) live in the `settings` table instead.
*/

return [

    /*
    | Developer credit shown in the public footer, the admin sidebar and README.md.
    */
    'developer' => [
        'name' => 'Rhondel M. Pagobo',
        'email' => 'rhondelpagobo19@gmail.com',
    ],

    /*
    | Initial owner account created by Database\Seeders\OwnerSeeder.
    | Never hard-code credentials: set these in .env. The seeder skips when email/password are empty.
    */
    'owner' => [
        'name' => env('OWNER_NAME', 'Resort Owner'),
        'email' => env('OWNER_EMAIL'),
        'password' => env('OWNER_PASSWORD'),
    ],

    /*
    | Cloudflare Turnstile on public booking forms (D-028). Off by default; when enabled,
    | set both keys from the Cloudflare dashboard (never commit them).
    */
    'turnstile' => [
        'enabled' => (bool) env('TURNSTILE_ENABLED', false),
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    /*
    | Notifications (M8, D-037). Channels per NotificationType value; "default" applies to types
    | not listed. Only "mail" exists today. To add SMS later: create the channel class and a
    | toSms() method (docs/architecture.md), then list it here, e.g. 'booking_approved' => ['mail', 'sms'].
    | Reminders: bookings:send-reminders runs daily at `reminder_time` (Asia/Manila); how many days
    | before the stay is a setting (notifications.reminder_days_before).
    */
    'notifications' => [
        'channels' => [
            'default' => ['mail'],
        ],
        'reminder_time' => env('REMINDER_TIME', '09:00'),
    ],

];
