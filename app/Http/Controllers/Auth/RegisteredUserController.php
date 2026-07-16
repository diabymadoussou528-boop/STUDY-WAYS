<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\User;
use App\Services\AvatarService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterUserRequest $request, AvatarService $avatarService): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $avatarPath = null;

            if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
                $avatarPath = $avatarService->store($request->file('avatar'), new User([
                    'name' => $validated['name'],
                ]));
            }

            $role = in_array($validated['role'] ?? null, ['student', 'professor'], true)
                ? $validated['role']
                : 'student';

            // Pass the plain password — the User model "hashed" cast hashes it once.
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => $role,
                'specialization' => $role === 'professor' ? ($validated['specialization'] ?? null) : null,
                'avatar' => $avatarPath,
                'is_active' => true,
                'is_super_admin' => false,
                'first_login' => false,
                'email_verified_at' => now(),
            ]);

            event(new Registered($user));

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended($this->redirectPathFor($user));
        } catch (Throwable $e) {
            Log::error('Registration failed', [
                'email' => $validated['email'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors([
                    'email' => 'Impossible de créer le compte pour le moment. Veuillez réessayer.',
                ]);
        }
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
