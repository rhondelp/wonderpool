<?php

namespace App\Http\Requests\Admin\Content;

use App\Models\BlockedDate;

/**
 * Create a blocked date (rules in BlockedDateRequest).
 */
class StoreBlockedDateRequest extends BlockedDateRequest
{
    /**
     * Delegates to BlockedDatePolicy::create.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', BlockedDate::class) ?? false;
    }
}
