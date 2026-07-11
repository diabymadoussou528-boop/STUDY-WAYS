<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->mustChangePassword() && ! $request->routeIs('password.force.*', 'logout')) {
            return redirect()->route('password.force.edit');
        }

        return $next($request);
    }
}
