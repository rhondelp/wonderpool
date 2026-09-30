<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates (or updates) the initial owner account from config/wonderpool.php → .env
 * (OWNER_NAME, OWNER_EMAIL, OWNER_PASSWORD). Credentials are never hard-coded.
 */
class OwnerSeeder extends Seeder
{
    /**
     * Skips with a warning when OWNER_EMAIL or OWNER_PASSWORD is not set.
     */
    public function run(): void
    {
        $email = config('wonderpool.owner.email');
        $password = config('wonderpool.owner.password');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            $this->command->warn('OwnerSeeder skipped: set OWNER_EMAIL and OWNER_PASSWORD in .env.');

            return;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => (string) config('wonderpool.owner.name', 'Resort Owner'),
                'password' => $password,
                'role' => UserRole::Owner,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
