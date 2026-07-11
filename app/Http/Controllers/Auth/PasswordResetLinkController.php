<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => "Cette adresse e-mail n'est pas enregistrée.",
            'email.required' => 'Veuillez saisir votre adresse e-mail.',
            'email.email' => 'Veuillez saisir une adresse e-mail valide.',
        ]);

        // Send the password reset link via Laravel's password broker.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Un lien de réinitialisation a été envoyé à votre adresse e-mail.');
        }

        $message = $status === Password::RESET_THROTTLED
            ? 'Veuillez patienter avant de demander un nouveau lien.'
            : "Impossible d'envoyer le lien de réinitialisation. Réessayez plus tard.";

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => $message]);
    }
}
