<?php

namespace App\Http\Requests\Admin\Content;

use App\Models\Amenity;

/**
 * Create a amenity (rules in AmenityRequest).
 */
class StoreAmenityRequest extends AmenityRequest
{
    /**
     * Delegates to AmenityPolicy::create.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Amenity::class) ?? false;
    }
}
