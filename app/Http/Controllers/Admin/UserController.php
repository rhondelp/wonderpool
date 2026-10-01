<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetUserPasswordRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Owner-only management of admin accounts (owner/staff).
 */
class UserController extends Controller
{
    /**
     * All admin accounts, owners first.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('admin.users.index', [
            'users' => User::query()->orderBy('role')->orderBy('name')->paginate(20),
        ]);
    }

    /**
     * New-user form.
     */
    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.create', ['roles' => UserRole::cases()]);
    }

    /**
     * Creates the account with a temporary password.
     */
    public function store(StoreUserRequest $request, UserService $users): RedirectResponse
    {
        /** @var array{name: string, email: string, role: string, password: string} $data */
        $data = $request->validated();
        $user = $users->create($data);

        return redirect()->route('admin.users.index')
            ->with('success', "{$user->name} was added. Share the temporary password privately; they must change it on first sign-in.");
    }

    /**
     * Edit form (details + password reset).
     */
    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.edit', ['user' => $user, 'roles' => UserRole::cases()]);
    }

    /**
     * Saves name, email and role.
     */
    public function update(UpdateUserRequest $request, User $user, UserService $users): RedirectResponse
    {
        /** @var array{name: string, email: string, role: string} $data */
        $data = $request->validated();
        $users->update($user, $data);

        return redirect()->route('admin.users.index')->with('success', "{$user->name} was updated.");
    }

    /**
     * Enables or disables the account.
     */
    public function toggleActive(User $user, UserService $users): RedirectResponse
    {
        Gate::authorize('toggleActive', $user);

        $users->setActive($user, ! $user->is_active);

        return back()->with('success', $user->name.($user->is_active ? ' can sign in again.' : ' was disabled and can no longer sign in.'));
    }

    /**
     * Sets a temporary password the user must change on next sign-in.
     */
    public function resetPassword(ResetUserPasswordRequest $request, User $user, UserService $users): RedirectResponse
    {
        $users->resetPassword($user, $request->string('password')->toString());

        return redirect()->route('admin.users.index')
            ->with('success', "Temporary password set for {$user->name}. They must change it on next sign-in.");
    }
}
