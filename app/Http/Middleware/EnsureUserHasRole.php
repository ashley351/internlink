<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Restrict a route to one or more account roles.
     *
     * @param  array<int, string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless(
            $user
                && $user->hasAnyRole($roles)
                && (! in_array($user->role, ['supervisor', 'organisation'], true)
                    || ($user->is_approved && $user->approval_status === 'approved'))
                && ($user->role !== 'admin' || $user->is_approved),
            403,
        );

        return $next($request);
    }
}
