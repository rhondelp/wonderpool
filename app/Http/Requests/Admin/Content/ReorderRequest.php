<?php

namespace App\Http\Requests\Admin\Content;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Drag-and-drop reorder payload: {"ids": [3, 1, 2]} in the new display order.
 * Authorization is checked per module in the controller (policy "reorder").
 */
class ReorderRequest extends FormRequest
{
    /**
     * Route groups already require an owner; the controller checks the module policy.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['required', 'integer', 'distinct', 'min:1'],
        ];
    }

    /**
     * Ids in their new order.
     *
     * @return list<int>
     */
    public function ids(): array
    {
        return array_values(array_map('intval', (array) $this->validated('ids')));
    }
}
