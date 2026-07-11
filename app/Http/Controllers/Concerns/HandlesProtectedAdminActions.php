<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use App\Services\AdminActionRequestService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

trait HandlesProtectedAdminActions
{
    protected function protectedAction(
        string $action,
        string $title,
        ?string $description = null,
        ?Model $target = null,
        ?array $payload = null,
        ?callable $immediate = null,
    ): RedirectResponse {
        $actor = auth()->user();
        $service = app(AdminActionRequestService::class);

        if ($service->requiresApproval($actor)) {
            $service->submit($actor, $action, $title, $description, $target, $payload);

            return back()->with(
                'success',
                'Demande envoyée au Super Admin. L\'action sera exécutée après approbation.'
            );
        }

        if ($immediate) {
            $immediate();
        }

        return back()->with('success', 'Action effectuée avec succès.');
    }

    protected function canManageUser(User $target): bool
    {
        if ($target->isSuperAdmin()) {
            return false;
        }

        if ((int) auth()->id() === $target->id) {
            return false;
        }

        return true;
    }
}
