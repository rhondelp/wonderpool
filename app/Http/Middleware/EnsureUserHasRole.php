<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route group to the given roles, e.g. `role:owner` or `role:owner,staff` (alias: `role`).
 */
class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     * @param  string  ...$roles  UserRole values allowed through
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $allowed = array_map(fn (string $role): UserRole => UserRole::from($role), $roles);

        abort_unless($user instanceof User && in_array($user->role, $allowed, true), 403);

        return $next($request);
    }
}
