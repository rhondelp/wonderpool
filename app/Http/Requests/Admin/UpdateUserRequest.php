<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Unique;

/**
 * Owner edits an admin's name, email and role. Owners cannot change their own role.
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * Delegates to UserPolicy::update.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->target()) ?? false;
    }

    /**
     * Normalizes the email before the unique check (stored lowercase, D-014).
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    /**
     * @return array<string, list<string|ValidationRule|Enum|In|Unique>>
     */
    public function rules(): array
    {
        $target = $this->target();
        $role = ['required', Rule::enum(UserRole::class)];

        if ($this->user()?->is($target)) {
            $role[] = Rule::in([$target->role->value]);
        }

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($target)],
            'role' => $role,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['role.in' => 'You cannot change your own role.'];
    }

    /**
     * The user being edited (route model binding).
     */
    public function target(): User
    {
        /** @var User */
        return $this->route('user');
    }
}
