<?php

namespace App\Http\Requests\Admin\Content;

use App\Models\Package;

/**
 * Edit a package (rules in PackageRequest).
 */
class UpdatePackageRequest extends PackageRequest
{
    /**
     * Delegates to PackagePolicy::update.
     */
    public function authorize(): bool
    {
        /** @var Package $package */
        $package = $this->route('package');

        return $this->user()?->can('update', $package) ?? false;
    }
}
