<?php

namespace App\Http\Requests\Admin\Content;

use App\Models\Package;
use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator;

/**
 * Package fields shared by StorePackageRequest and UpdatePackageRequest.
 * Price is entered in pesos and stored in centavos (D-001); times are HH:MM.
 * Times must agree with crosses_midnight: same-day packages end after they start,
 * overnight packages end at or before their start time (on the next day).
 */
abstract class PackageRequest extends FormRequest
{
    /**
     * Uppercases the code and normalizes the checkboxes.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => is_string($this->input('code')) ? mb_strtoupper(trim($this->input('code'))) : $this->input('code'),
            'crosses_midnight' => $this->boolean('crosses_midnight'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, list<string|ValidationRule|Unique>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9-]+$/', Rule::unique(Package::class, 'code')->ignore($this->route('package'))],
            'description' => ['nullable', 'string', 'max:2000'],
            'base_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999.99'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'crosses_midnight' => ['boolean'],
            'max_pax' => ['required', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:32767'],
        ];
    }

    /**
     * Start/end time coherence with crosses_midnight.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['start_time', 'end_time'])) {
                return;
            }

            $start = (string) $this->input('start_time');
            $end = (string) $this->input('end_time');

            if (! $this->boolean('crosses_midnight') && $end <= $start) {
                $validator->errors()->add('end_time', 'The end time must be after the start time. Tick "Ends the next day" for overnight packages.');
            }

            if ($this->boolean('crosses_midnight') && $end > $start) {
                $validator->errors()->add('end_time', 'For a package that ends the next day, the end time must be at or before the start time.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['code.regex' => 'The code may only contain capital letters, numbers and dashes.'];
    }

    /**
     * Validated data mapped to model attributes (pesos → centavos, HH:MM → HH:MM:00).
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->validated();
        $data['base_price_cents'] = Money::fromPesos((string) $data['base_price']);
        $data['start_time'] .= ':00';
        $data['end_time'] .= ':00';
        unset($data['base_price']);

        return $data;
    }
}
