<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends users flagged `must_change_password` (first owner login, owner-reset password)
 * to the change-password screen before any other admin page (alias: `password.changed`).
 */
class EnsurePasswordIsChanged
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->must_change_password) {
            return redirect()->route('admin.password.edit')->with('warning', 'Please set a new password before continuing.');
        }

        return $next($request);
    }
}
