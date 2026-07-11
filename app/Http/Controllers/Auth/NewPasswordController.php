<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'password.required' => 'Veuillez saisir un nouveau mot de passe.',
        ]);

        // Attempt the password reset through Laravel's broker. On success the token
        // is consumed (single-use) and the password is securely hashed.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('password.reset.success')->with('reset_success', true);
        }

        $message = $status === Password::INVALID_TOKEN
            ? 'Ce lien de réinitialisation est invalide ou a expiré.'
            : 'Impossible de réinitialiser le mot de passe. Réessayez.';

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => $message]);
    }
}
