<?php

namespace App\Http\Requests\Admin\Content;

use App\Enums\GalleryCategory;
use App\Models\GalleryImage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Upload up to MAX_FILES gallery images at once, sharing one category/caption/visibility.
 */
class StoreGalleryImagesRequest extends FormRequest
{
    /** Files accepted per upload. */
    public const MAX_FILES = 20;

    /**
     * Delegates to GalleryImagePolicy::create.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', GalleryImage::class) ?? false;
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
            'images' => ['required', 'array', 'min:1', 'max:'.self::MAX_FILES],
            'images.*' => ['required', ...ImageRules::file()],
            'category' => ['required', Rule::enum(GalleryCategory::class)],
            'caption' => ['nullable', 'string', 'max:255'],
            'is_visible' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['images.*' => 'image'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['images.max' => 'Upload at most '.self::MAX_FILES.' images at a time.'];
    }

    /**
     * The validated uploads.
     *
     * @return list<UploadedFile>
     */
    public function images(): array
    {
        return array_values(array_filter((array) $this->file('images'), fn ($file): bool => $file instanceof UploadedFile));
    }
}
