<?php

namespace App\Http\Requests\Admin;

use App\Enums\ActivityAction;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Activity log filters (GET): actor, module, action, date range, free-text search.
 */
class ActivityLogRequest extends FormRequest
{
    /**
     * Owners only (the route group is role:owner as well).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('view-activity-log') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user' => ['nullable', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value !== ActivityLogService::SYSTEM_ACTOR && ! ctype_digit((string) $value)) {
                    $fail('Choose a user from the list.');
                }
            }],
            'module' => ['nullable', Rule::in(array_keys(ActivityAction::modules()))],
            'action' => ['nullable', Rule::enum(ActivityAction::class)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'q' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Validated filters for ActivityLogService::listing().
     *
     * @return array{user?: string|null, module?: string|null, action?: string|null, from?: string|null, to?: string|null, q?: string|null}
     */
    public function filters(): array
    {
        /** @var array{user?: string|null, module?: string|null, action?: string|null, from?: string|null, to?: string|null, q?: string|null} $filters */
        $filters = $this->safe()->only(['user', 'module', 'action', 'from', 'to', 'q']);

        return $filters;
    }
}
