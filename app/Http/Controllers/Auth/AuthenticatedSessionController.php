<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
            'role' => ['nullable', Rule::in(['admin', 'professor', 'student', 'teacher'])],
        ], [
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'Veuillez saisir une adresse e-mail valide.',
            'password.required' => 'Le mot de passe est obligatoire.',
        ]);

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            Log::warning('Login failed: invalid credentials', [
                'email' => $validated['email'],
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'Identifiants incorrects.',
            ]);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        if ($user->is_active === false) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Log::warning('Login blocked: suspended account', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return back()->withErrors([
                'email' => 'Ce compte est suspendu. Contactez le super administrateur.',
            ])->withInput($request->except('password'));
        }

        $selectedRole = $validated['role'] ?? null;

        if ($selectedRole !== null) {
            $normalizedSelected = $selectedRole === 'teacher' ? 'professor' : $selectedRole;
            $normalizedUser = $user->role === 'teacher' ? 'professor' : $user->role;

            if ($normalizedUser !== $normalizedSelected) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'role' => 'Le rôle sélectionné ne correspond pas à votre compte.',
                ])->withInput($request->except('password'));
            }
        }

        if ($user->mustChangePassword()) {
            return redirect()->route('password.force.edit');
        }

        return redirect()->intended($this->redirectPathFor($user));
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

    private function redirectPathFor(User $user): string
    {
        return match ($user->role) {
            'admin' => route('admin.dashboard', absolute: false),
            'professor', 'teacher' => route('professor.dashboard', absolute: false),
            default => route('student.dashboard', absolute: false),
        };
    }
}
