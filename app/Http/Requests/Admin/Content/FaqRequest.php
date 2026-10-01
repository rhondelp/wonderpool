<?php

namespace App\Http\Requests\Admin\Content;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FAQ fields shared by StoreFaqRequest and UpdateFaqRequest.
 */
abstract class FaqRequest extends FormRequest
{
    /**
     * Normalizes the active checkbox.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:5000'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:32767'],
        ];
    }

    /**
     * Model attributes.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->validated();
    }
}
