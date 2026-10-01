<?php

namespace App\Http\Requests\Admin\Content;

use App\Models\Package;

/**
 * Create a package (rules in PackageRequest).
 */
class StorePackageRequest extends PackageRequest
{
    /**
     * Delegates to PackagePolicy::create.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Package::class) ?? false;
    }
}
