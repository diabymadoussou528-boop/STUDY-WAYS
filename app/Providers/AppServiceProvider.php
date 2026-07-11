<?php

namespace App\Providers;

use App\Http\View\Composers\AdminSidebarComposer;
use App\Http\View\Composers\NotificationPanelComposer;
use App\Http\View\Composers\ProfessorSidebarComposer;
use App\Http\View\Composers\StudentSidebarComposer;
use App\Listeners\LogAuthenticationEvents;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        View::composer('layouts.admin', AdminSidebarComposer::class);
        View::composer('layouts.student', StudentSidebarComposer::class);

        View::composer('layouts.professor', ProfessorSidebarComposer::class);

        View::composer(['layouts.admin', 'layouts.student', 'layouts.professor'], NotificationPanelComposer::class);

        Event::listen(Login::class, [LogAuthenticationEvents::class, 'handleLogin']);
        Event::listen(Logout::class, [LogAuthenticationEvents::class, 'handleLogout']);

        Gate::define('view-homepage-upgrade', function (?User $user = null): bool {
            if ($user === null) {
                return true;
            }

            return $user->canViewHomepageUpgradeSection();
        });
    }
}
