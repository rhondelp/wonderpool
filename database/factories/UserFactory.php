<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Database\Factories\Concerns\PhilippineData;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    use PhilippineData;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Staff user by default.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->phName();

        return [
            'name' => $name,
            'email' => $this->phEmail($name),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Staff,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Owner (full access) role.
     */
    public function owner(): static
    {
        return $this->state(fn () => ['role' => UserRole::Owner]);
    }

    /**
     * Disabled account.
     */
    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * Must set a new password before using the admin panel.
     */
    public function mustChangePassword(): static
    {
        return $this->state(fn () => ['must_change_password' => true]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
