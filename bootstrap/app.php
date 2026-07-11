<?php

use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsurePremium;
use App\Http\Middleware\IsSuperAdmin;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\TrackVisit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'superadmin' => IsSuperAdmin::class,
            'password.changed' => EnsurePasswordChanged::class,
            'premium' => EnsurePremium::class,
        ]);

        $middleware->web(append: [
            TrackVisit::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })

    ->create();
