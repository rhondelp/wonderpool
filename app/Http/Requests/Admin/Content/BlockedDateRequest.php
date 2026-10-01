<?php

namespace App\Http\Requests\Admin\Content;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Blocked-date fields shared by StoreBlockedDateRequest and UpdateBlockedDateRequest.
 * Two input modes: whole days (start_date..end_date inclusive → 00:00 to the day after end_date 00:00)
 * or an exact time range (starts_at/ends_at from datetime-local inputs). Stored half-open [starts_at, ends_at).
 */
abstract class BlockedDateRequest extends FormRequest
{
    /**
     * Normalizes the whole-day checkbox.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['whole_days' => $this->boolean('whole_days')]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $rules = [
            'whole_days' => ['boolean'],
            'reason' => ['required', 'string', 'max:255'],
        ];

        if ($this->boolean('whole_days')) {
            return $rules + [
                'start_date' => ['required', 'date_format:Y-m-d'],
                'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            ];
        }

        return $rules + [
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'ends_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['starts_at' => 'start', 'ends_at' => 'end', 'start_date' => 'first day', 'end_date' => 'last day'];
    }

    /**
     * Model attributes: [starts_at, ends_at) in the app timezone, plus the reason.
     *
     * @return array{starts_at: CarbonImmutable, ends_at: CarbonImmutable, reason: string}
     */
    public function payload(): array
    {
        $tz = config('app.timezone');

        if ($this->boolean('whole_days')) {
            $start = CarbonImmutable::parse($this->string('start_date')->toString(), $tz)->startOfDay();
            $end = CarbonImmutable::parse($this->string('end_date')->toString(), $tz)->startOfDay()->addDay();
        } else {
            $start = CarbonImmutable::parse($this->string('starts_at')->toString(), $tz);
            $end = CarbonImmutable::parse($this->string('ends_at')->toString(), $tz);
        }

        return ['starts_at' => $start, 'ends_at' => $end, 'reason' => $this->string('reason')->trim()->toString()];
    }
}
