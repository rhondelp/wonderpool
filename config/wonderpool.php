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

];
