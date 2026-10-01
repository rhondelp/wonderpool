<?php

namespace App\Http\Requests\Admin\Content;

use App\Models\Amenity;

/**
 * Edit a amenity (rules in AmenityRequest).
 */
class UpdateAmenityRequest extends AmenityRequest
{
    /**
     * Delegates to AmenityPolicy::update.
     */
    public function authorize(): bool
    {
        /** @var Amenity $amenity */
        $amenity = $this->route('amenity');

        return $this->user()?->can('update', $amenity) ?? false;
    }
}
