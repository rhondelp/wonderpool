<?php

namespace App\Http\Requests\Public;

use App\Models\Package;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Live quote input (POST /book/quote, JSON): package, date, guest count and optional add-ons.
 * Only shapes are validated here; business rules (pax, availability) come back in the quote.
 */
class QuoteRequest extends FormRequest
{
    /**
     * Public endpoint.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string|ValidationRule|Exists>>
     */
    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', Rule::exists(Package::class, 'id')->where('is_active', true)],
            'date' => ['required', 'date_format:Y-m-d'],
            'guest_count' => ['required', 'integer', 'min:1', 'max:1000'],
            'add_ons' => ['nullable', 'array', 'max:50'],
            'add_ons.*' => ['nullable', 'integer', 'min:0', 'max:99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'package_id.exists' => 'Please choose one of the available packages.',
            'date.required' => 'Please pick a date.',
        ];
    }

    /**
     * Add-ons with a quantity of at least 1, as [add_on_id => quantity].
     *
     * @return array<int, int>
     */
    public function addOns(): array
    {
        $addOns = [];
        foreach ((array) $this->validated('add_ons', []) as $id => $quantity) {
            if ((int) $quantity > 0) {
                $addOns[(int) $id] = (int) $quantity;
            }
        }

        return $addOns;
    }
}
