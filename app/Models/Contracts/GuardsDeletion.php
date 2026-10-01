<?php

namespace App\Models\Contracts;

/**
 * A model that may refuse deletion (e.g. it is referenced by bookings).
 * ContentService::delete() checks this before deleting.
 */
interface GuardsDeletion
{
    /**
     * Why this record cannot be deleted, or null when deleting is allowed.
     */
    public function deletionBlockedReason(): ?string;
}
