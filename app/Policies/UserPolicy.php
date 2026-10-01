<?php

namespace App\Policies;

use App\Models\User;

/**
 * Admin user management. Only owners manage accounts, and nobody can
 * disable or demote themselves (so at least one active owner always remains).
 */
class UserPolicy
{
    /**
     * List users.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->isOwner();
    }

    /**
     * Add a user.
     */
    public function create(User $actor): bool
    {
        return $actor->isOwner();
    }

    /**
     * Edit name/email/role. Role changes on oneself are blocked in UpdateUserRequest.
     */
    public function update(User $actor, User $target): bool
    {
        return $actor->isOwner();
    }

    /**
     * Enable or disable an account (never one's own).
     */
    public function toggleActive(User $actor, User $target): bool
    {
        return $actor->isOwner() && ! $actor->is($target);
    }

    /**
     * Set a temporary password for someone else (own password: change-password screen).
     */
    public function resetPassword(User $actor, User $target): bool
    {
        return $actor->isOwner() && ! $actor->is($target);
    }
}
