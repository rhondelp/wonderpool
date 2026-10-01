<?php

namespace App\Http\Requests\Admin\Content;

use App\Models\AddOn;

/**
 * Edit a add-on (rules in AddOnRequest).
 */
class UpdateAddOnRequest extends AddOnRequest
{
    /**
     * Delegates to AddOnPolicy::update.
     */
    public function authorize(): bool
    {
        /** @var AddOn $add_on */
        $add_on = $this->route('add_on');

        return $this->user()?->can('update', $add_on) ?? false;
    }
}
