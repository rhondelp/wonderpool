<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\UserRole;
use App\Models\User;

/**
 * Admin account lifecycle: create, edit, enable/disable, password changes and sign-in bookkeeping.
 * Authorization is done by UserPolicy / Form Requests before these methods are called.
 * Every change is written to the activity log (passwords are never logged).
 */
class UserService
{
    /**
     * @param  ActivityLogger  $logger  Audit trail
     */
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    /**
     * Creates an active admin who must change the temporary password on first sign-in.
     *
     * @param  array{name: string, email: string, role: string|UserRole, password: string}  $data  Validated input
     */
    public function create(array $data): User
    {
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => $data['password'],
            'is_active' => true,
            'must_change_password' => true,
        ]);

        $this->logger->log(ActivityAction::UserCreated, $user, ['role' => $user->role->value]);

        return $user;
    }

    /**
     * Updates name, email and role; logs the changed fields.
     *
     * @param  array{name: string, email: string, role: string|UserRole}  $data  Validated input
     */
    public function update(User $user, array $data): User
    {
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
        ]);

        $changed = array_keys($user->getDirty());

        if ($changed !== []) {
            $user->save();
            $this->logger->log(ActivityAction::UserUpdated, $user, ['fields' => $changed]);
        }

        return $user;
    }

    /**
     * Enables or disables an account. A disabled user is signed out on their next request
     * (EnsureUserIsActive) and cannot sign in again.
     */
    public function setActive(User $user, bool $active): User
    {
        if ($user->is_active === $active) {
            return $user;
        }

        $user->forceFill(['is_active' => $active])->save();
        $this->logger->log($active ? ActivityAction::UserActivated : ActivityAction::UserDeactivated, $user);

        return $user;
    }

    /**
     * Owner sets a temporary password for another admin; they must change it on next sign-in.
     */
    public function resetPassword(User $user, string $temporaryPassword): User
    {
        $user->forceFill([
            'password' => $temporaryPassword,
            'must_change_password' => true,
        ])->save();

        $this->logger->log(ActivityAction::UserPasswordReset, $user);

        return $user;
    }

    /**
     * The user sets their own new password and clears the forced-change flag.
     */
    public function changeOwnPassword(User $user, string $newPassword): User
    {
        $user->forceFill([
            'password' => $newPassword,
            'must_change_password' => false,
        ])->save();

        $this->logger->log(ActivityAction::PasswordChanged, $user, [], $user);

        return $user;
    }

    /**
     * Stamps last_login_at and logs the sign-in.
     */
    public function recordLogin(User $user): void
    {
        $user->forceFill(['last_login_at' => now()])->save();
        $this->logger->log(ActivityAction::Login, $user, [], $user);
    }

    /**
     * Logs a sign-out (call before the session is destroyed).
     */
    public function recordLogout(User $user): void
    {
        $this->logger->log(ActivityAction::Logout, $user, [], $user);
    }
}
