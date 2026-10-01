<?php

namespace App\Http\Requests\Admin\Content;

use App\Support\AmenityIcons;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

/**
 * Amenity fields shared by StoreAmenityRequest and UpdateAmenityRequest.
 * The icon must come from the curated AmenityIcons list; the photo is optional.
 */
abstract class AmenityRequest extends FormRequest
{
    /**
     * Normalizes the checkboxes.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    /**
     * @return array<string, list<string|ValidationRule|In>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['required', 'string', Rule::in(AmenityIcons::names())],
            'image' => ['nullable', ...ImageRules::file()],
            'remove_image' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:32767'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['icon.in' => 'Choose an icon from the list.'];
    }

    /**
     * Model attributes (the file and remove flag are handled separately).
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return collect($this->validated())->except(['image', 'remove_image'])->all();
    }

    /**
     * The uploaded photo, if any.
     */
    public function image(): ?UploadedFile
    {
        $file = $this->file('image');

        return $file instanceof UploadedFile ? $file : null;
    }
}
