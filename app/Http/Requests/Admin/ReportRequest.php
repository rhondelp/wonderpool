<?php

namespace App\Http\Requests\Admin;

use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Report filters (GET): date range (default = this month, at most ReportService::MAX_RANGE_DAYS) and package.
 * Used by the reports page and both exports (owner only via role:owner + view-financials).
 */
class ReportRequest extends FormRequest
{
    /**
     * Owners only (financial figures).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('view-financials') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'package' => ['nullable', 'integer', 'exists:packages,id'],
        ];
    }

    /**
     * Rejects ranges longer than the maximum.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                if ($this->from()->diffInDays($this->to()) + 1 > ReportService::MAX_RANGE_DAYS) {
                    $validator->errors()->add('to', 'Choose a range of at most '.ReportService::MAX_RANGE_DAYS.' days.');
                }
            },
        ];
    }

    /**
     * First day of the range (default: first day of the "to" month, else of this month).
     */
    public function from(): CarbonImmutable
    {
        $from = $this->query('from');
        $to = $this->query('to');

        return match (true) {
            is_string($from) && $from !== '' => CarbonImmutable::createFromFormat('Y-m-d', $from)->startOfDay(),
            is_string($to) && $to !== '' => CarbonImmutable::createFromFormat('Y-m-d', $to)->startOfMonth(),
            default => CarbonImmutable::now()->startOfMonth(),
        };
    }

    /**
     * Last day of the range, inclusive (default: last day of the "from" month).
     */
    public function to(): CarbonImmutable
    {
        $to = $this->query('to');

        return is_string($to) && $to !== ''
            ? CarbonImmutable::createFromFormat('Y-m-d', $to)->startOfDay()
            : $this->from()->endOfMonth()->startOfDay();
    }

    /**
     * Selected package id, or null for all packages.
     */
    public function packageId(): ?int
    {
        return $this->filled('package') ? (int) $this->query('package') : null;
    }
}
