<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy for owner-only content modules (packages, add-ons, amenities, gallery, FAQs).
 * Each module has its own empty subclass so Laravel auto-discovers it and rules can diverge later.
 * Business guards (e.g. "package has bookings") live in the model/service, not here.
 */
abstract class ContentPolicy
{
    /**
     * List records.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Add a record.
     */
    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Edit a record.
     */
    public function update(User $user, Model $model): bool
    {
        return $user->isOwner();
    }

    /**
     * Delete a record.
     */
    public function delete(User $user, Model $model): bool
    {
        return $user->isOwner();
    }

    /**
     * Change the display order.
     */
    public function reorder(User $user): bool
    {
        return $user->isOwner();
    }
}
