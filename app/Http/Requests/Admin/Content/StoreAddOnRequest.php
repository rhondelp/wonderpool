<?php

namespace App\Http\Requests\Admin\Content;

use App\Models\AddOn;

/**
 * Create a add-on (rules in AddOnRequest).
 */
class StoreAddOnRequest extends AddOnRequest
{
    /**
     * Delegates to AddOnPolicy::create.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', AddOn::class) ?? false;
    }
}
