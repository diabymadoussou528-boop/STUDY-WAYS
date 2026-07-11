<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSimpleAdminRequest;
use App\Http\Requests\UpdateSimpleAdminRequest;
use App\Models\User;
use App\Services\SimpleAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SimpleAdminController extends Controller
{
    public function index(): View
    {
        $admins = User::query()
            ->where('role', 'admin')
            ->orderByDesc('is_super_admin')
            ->orderBy('name')
            ->get();

        $editingAdmin = null;
        if (request()->filled('edit')) {
            $candidate = User::query()->find(request('edit'));
            if ($candidate?->isSimpleAdmin()) {
                $editingAdmin = $candidate;
            }
        }

        $temporaryPasswordDisplay = session('temporary_password_display');

        return view('admin.admins', compact('admins', 'editingAdmin', 'temporaryPasswordDisplay'));
    }

    public function store(StoreSimpleAdminRequest $request, SimpleAdminService $service): RedirectResponse
    {
        $result = $service->create($request->validated());
        $admin = $result['admin'];

        $redirect = redirect()
            ->route('admin.admins', ['edit' => $admin->id])
            ->with('temporary_password_display', [
                'user_id' => $admin->id,
                'password' => $result['temporary_password'],
                'name' => $admin->name,
                'email_sent' => $result['email_sent'],
            ]);

        if ($result['email_sent']) {
            return $redirect->with(
                'success',
                "Le compte administrateur de {$admin->name} a été créé. Un e-mail avec le mot de passe temporaire a été envoyé à {$admin->email}."
            );
        }

        return $redirect->with(
            'error',
            "Le compte de {$admin->name} a été créé, mais l'e-mail n'a pas pu être envoyé. Vérifiez la configuration MAIL_* dans votre fichier .env. Le mot de passe temporaire est affiché ci-dessous — copiez-le et transmettez-le manuellement."
        );
    }

    public function update(UpdateSimpleAdminRequest $request, User $admin, SimpleAdminService $service): RedirectResponse
    {
        $service->update($admin, $request->validated());

        return back()->with('success', 'Les informations de l\'administrateur ont été mises à jour.');
    }

    public function toggleStatus(User $admin, SimpleAdminService $service): RedirectResponse
    {
        if ($admin->is_active) {
            $service->suspend($admin);

            return back()->with('success', "{$admin->name} a été suspendu.");
        }

        $service->activate($admin);

        return back()->with('success', "{$admin->name} a été réactivé.");
    }

    public function destroy(User $admin, SimpleAdminService $service): RedirectResponse
    {
        if ((int) auth()->id() === $admin->id) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $name = $admin->name;
        $service->delete($admin);

        return back()->with('success', "Le compte de {$name} a été supprimé.");
    }

    public function sendTemporaryPassword(User $admin, SimpleAdminService $service): RedirectResponse
    {
        $result = $service->sendNewTemporaryPassword($admin);

        $redirect = redirect()
            ->route('admin.admins', ['edit' => $admin->id])
            ->with('temporary_password_display', [
                'user_id' => $admin->id,
                'password' => $result['temporary_password'],
                'name' => $admin->name,
                'email_sent' => $result['email_sent'],
            ]);

        if ($result['email_sent']) {
            return $redirect->with(
                'success',
                "Un nouveau mot de passe temporaire a été généré et envoyé à {$admin->email}."
            );
        }

        return $redirect->with(
            'error',
            "Un nouveau mot de passe temporaire a été généré, mais l'e-mail n'a pas pu être envoyé. Vérifiez la configuration MAIL_* dans votre fichier .env."
        );
    }

    public function sendResetLink(User $admin, SimpleAdminService $service): RedirectResponse
    {
        $service->sendPasswordResetLink($admin);

        return back()->with('success', "Un lien de réinitialisation a été envoyé à {$admin->email}.");
    }

    public function forceLogout(User $admin): RedirectResponse
    {
        if ($admin->isSuperAdmin()) {
            return back()->with('error', 'Impossible de déconnecter le super administrateur.');
        }

        DB::table('sessions')->where('user_id', $admin->id)->delete();

        return back()->with('success', "{$admin->name} a été déconnecté.");
    }
}
