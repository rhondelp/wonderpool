<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Admin sign-in and sign-out (owner and staff; guests have no accounts).
 */
class LoginController extends Controller
{
    /**
     * Sign-in form.
     */
    public function create(): View
    {
        return view('admin.auth.login');
    }

    /**
     * Authenticates, rotates the session and records the sign-in.
     */
    public function store(LoginRequest $request, UserService $users): RedirectResponse
    {
        $user = $request->authenticate();
        $request->session()->regenerate();
        $users->recordLogin($user);

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Signs out and invalidates the session.
     */
    public function destroy(Request $request, UserService $users): RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof User) {
            $users->recordLogout($user);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'You have been signed out.');
    }
}
