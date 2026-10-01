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

];
