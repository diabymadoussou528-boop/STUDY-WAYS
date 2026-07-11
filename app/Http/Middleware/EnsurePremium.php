<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePremium
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasActivePremium()) {
            return redirect()
                ->route('student.premium')
                ->with('error', 'Cette fonctionnalité nécessite un abonnement Premium.');
        }

        return $next($request);
    }
}
