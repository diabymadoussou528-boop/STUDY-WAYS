<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForcePasswordChangeRequest;
use App\Services\SimpleAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ForcePasswordChangeController extends Controller
{
    public function edit(): View|RedirectResponse
    {
        if (! auth()->user()?->mustChangePassword()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.force-password-change');
    }

    public function update(ForcePasswordChangeRequest $request, SimpleAdminService $service): RedirectResponse
    {
        $user = auth()->user();
        $service->completeFirstLogin($user, $request->validated('password'));
        $request->session()->regenerate();

        return redirect()
            ->route('admin.dashboard')
            ->with('success', "Bienvenue, {$user->name} ! Votre compte a été activé avec succès.");
    }
}
