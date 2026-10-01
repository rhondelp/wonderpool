<?php

namespace App\Http\Requests\Admin\Content;

use App\Models\BlockedDate;

/**
 * Edit a blocked date (rules in BlockedDateRequest).
 */
class UpdateBlockedDateRequest extends BlockedDateRequest
{
    /**
     * Delegates to BlockedDatePolicy::update.
     */
    public function authorize(): bool
    {
        /** @var BlockedDate $blocked_date */
        $blocked_date = $this->route('blocked_date');

        return $this->user()?->can('update', $blocked_date) ?? false;
    }
}
