<?php

namespace App\Http\Requests\Admin\Content;

use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Add-on fields shared by StoreAddOnRequest and UpdateAddOnRequest.
 * Price is entered in pesos and stored in centavos (D-001).
 */
abstract class AddOnRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999.99'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Validated data mapped to model attributes (pesos → centavos).
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->validated();
        $data['price_cents'] = Money::fromPesos((string) $data['price']);
        unset($data['price']);

        return $data;
    }
}
