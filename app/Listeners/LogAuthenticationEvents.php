<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class LogAuthenticationEvents
{
    public function __construct(private AuditLogService $auditLog) {}

    public function handleLogin(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->auditLog->log(
            action: 'auth.login',
            module: 'authentication',
            description: 'Connexion réussie',
            actor: $event->user,
        );
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->auditLog->log(
            action: 'auth.logout',
            module: 'authentication',
            description: 'Déconnexion',
            actor: $event->user,
        );
    }
}
