<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Check user role — super admins bypass all role checks.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $role
    ): Response {
        $user = Auth::user();

        if (! $user) {
            return redirect('/login');
        }

        // Super admins can access everything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if ($user->role !== $role && ! ($role === 'professor' && $user->role === 'teacher')) {
            abort(403, 'Accès refusé');
        }

        return $next($request);
    }
}
