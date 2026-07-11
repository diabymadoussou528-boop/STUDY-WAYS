<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Afficher login
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Connexion utilisateur
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'role' => ['nullable', Rule::in(['admin', 'professor', 'student'])],
        ]);

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            if (! $user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Ce compte est suspendu. Contactez le super administrateur.',
                ])->withInput($request->except('password'));
            }

            $selectedRole = $validated['role'] ?? null;

            if ($selectedRole !== null && $user->role !== $selectedRole) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'role' => 'Le role selectionne ne correspond pas a votre compte.',
                ])->withInput($request->except('password'));
            }

            if ($user->mustChangePassword()) {
                return redirect()->route('password.force.edit');
            }

            return redirect()->intended(match ($user->role) {
                'admin' => route('admin.dashboard', absolute: false),
                'professor' => route('professor.dashboard', absolute: false),
                default => route('student.dashboard', absolute: false),
            });
        }

        return back()->withErrors([
            'email' => 'Identifiants incorrects.',
        ])->withInput($request->except('password'));
    }

    /**
     * Déconnexion
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
