<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOwnPasswordRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The signed-in user's own password (voluntary or forced via must_change_password).
 */
class PasswordController extends Controller
{
    /**
     * Change-password form.
     */
    public function edit(): View
    {
        return view('admin.auth.change-password');
    }

    /**
     * Saves the new password and clears the forced-change flag.
     */
    public function update(UpdateOwnPasswordRequest $request, UserService $users): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $users->changeOwnPassword($user, $request->string('password')->toString());
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('success', 'Your password has been updated.');
    }
}
