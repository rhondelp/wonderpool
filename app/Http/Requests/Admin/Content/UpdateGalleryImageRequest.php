<?php

namespace App\Http\Requests\Admin\Content;

use App\Enums\GalleryCategory;
use App\Models\GalleryImage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Edit one gallery image's caption, category and visibility (the file itself is not replaced).
 */
class UpdateGalleryImageRequest extends FormRequest
{
    /**
     * Delegates to GalleryImagePolicy::update.
     */
    public function authorize(): bool
    {
        /** @var GalleryImage $image */
        $image = $this->route('gallery');

        return $this->user()?->can('update', $image) ?? false;
    }

    /**
     * Normalizes the visibility checkbox.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['is_visible' => $this->boolean('is_visible')]);
    }

    /**
     * @return array<string, list<string|ValidationRule|Enum>>
     */
    public function rules(): array
    {
        return [
            'caption' => ['nullable', 'string', 'max:255'],
            'category' => ['required', Rule::enum(GalleryCategory::class)],
            'is_visible' => ['boolean'],
        ];
    }
}
