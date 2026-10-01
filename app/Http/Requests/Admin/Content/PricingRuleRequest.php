<?php

namespace App\Http\Requests\Admin\Content;

use App\Enums\PricingAdjustmentType;
use App\Enums\PricingRuleType;
use App\Models\Package;
use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;

/**
 * Pricing-rule fields shared by StorePricingRuleRequest and UpdatePricingRuleRequest.
 * Percent values are whole points (-100..500); fixed values are entered in pesos and stored
 * as signed centavos (D-001). Holiday/season rules need dates; weekend rules need weekdays (ISO 1–7).
 */
abstract class PricingRuleRequest extends FormRequest
{
    /**
     * Normalizes checkboxes, blank package ("All packages") and a single-day holiday.
     */
    protected function prepareForValidation(): void
    {
        $merge = [
            'is_active' => $this->boolean('is_active'),
            'package_id' => $this->filled('package_id') ? $this->input('package_id') : null,
        ];

        if ($this->input('type') === PricingRuleType::Holiday->value && $this->filled('starts_on') && ! $this->filled('ends_on')) {
            $merge['ends_on'] = $this->input('starts_on');
        }

        $this->merge($merge);
    }

    /**
     * @return array<string, list<string|ValidationRule|Enum|Exists>>
     */
    public function rules(): array
    {
        $datesRequired = in_array($this->input('type'), [PricingRuleType::Holiday->value, PricingRuleType::Season->value], true);

        return [
            'name' => ['required', 'string', 'max:100'],
            'package_id' => ['nullable', 'integer', Rule::exists(Package::class, 'id')],
            'type' => ['required', Rule::enum(PricingRuleType::class)],
            'starts_on' => [$datesRequired ? 'required' : 'nullable', 'date_format:Y-m-d'],
            'ends_on' => [$datesRequired ? 'required' : 'nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'days_of_week' => [$this->input('type') === PricingRuleType::Weekend->value ? 'required' : 'nullable', 'array'],
            'days_of_week.*' => ['integer', 'between:1,7', 'distinct'],
            'adjustment_type' => ['required', Rule::enum(PricingAdjustmentType::class)],
            'adjustment_value' => ['required', 'numeric'],
            'priority' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Range checks for the adjustment value, which depend on its type.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['adjustment_type', 'adjustment_value'])) {
                return;
            }

            $value = (string) $this->input('adjustment_value');

            if ($this->input('adjustment_type') === PricingAdjustmentType::Percent->value) {
                if (! preg_match('/^-?\d+$/', $value) || (int) $value < -100 || (int) $value > 500) {
                    $validator->errors()->add('adjustment_value', 'Enter a whole percentage between -100 and 500.');
                }
            } elseif (! preg_match('/^-?\d+(\.\d{1,2})?$/', $value) || abs((float) $value) > 9_999_999.99) {
                $validator->errors()->add('adjustment_value', 'Enter an amount in pesos (up to 2 decimals), e.g. 1000 or -500.50.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'days_of_week.required' => 'Pick at least one day for a weekend / day-of-week rule.',
            'starts_on.required' => 'Holiday and season rules need a start date.',
            'ends_on.required' => 'Season rules need an end date.',
        ];
    }

    /**
     * Model attributes: value converted (percent points or centavos), weekdays sorted or null.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->validated();
        $days = array_map('intval', (array) ($data['days_of_week'] ?? []));
        sort($days);

        $data['days_of_week'] = $days === [] ? null : $days;
        $data['adjustment_value'] = $data['adjustment_type'] === PricingAdjustmentType::Percent->value
            ? (int) $data['adjustment_value']
            : Money::fromPesos((string) $data['adjustment_value']);

        return $data;
    }
}
