<?php

namespace App\Http\Requests\Admin;

use App\Enums\SettingGroup;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates one settings group. Rules come from SettingGroup::fields(); dotted keys
 * are submitted as nested inputs (e.g. booking[downpayment_percent]).
 */
class UpdateSettingsRequest extends FormRequest
{
    /**
     * Owners only (gate manage-settings).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage-settings') ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return array_map(fn (array $field): array => $field['rules'], $this->group()->fields());
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_map(fn (array $field): string => mb_strtolower($field['label']), $this->group()->fields());
    }

    /**
     * Validated values flattened back to dotted keys, e.g. ["booking.downpayment_percent" => "40"].
     *
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $values = [];

        foreach (array_keys($this->group()->fields()) as $key) {
            $values[$key] = $this->validated($key);
        }

        return $values;
    }

    /**
     * The settings group from the route ({group} enum binding).
     */
    public function group(): SettingGroup
    {
        /** @var SettingGroup */
        return $this->route('group');
    }
}
