<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\User;
use App\Services\AvatarService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterUserRequest $request, AvatarService $avatarService): RedirectResponse
    {
        $validated = $request->validated();

        $avatarPath = null;

        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            $avatarPath = $avatarService->store($request->file('avatar'), new User([
                'name' => $validated['name'],
            ]));
        }

        $role = in_array($validated['role'] ?? null, ['student', 'professor'], true)
            ? $validated['role']
            : 'student';

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $role,
            'specialization' => $role === 'professor' ? ($validated['specialization'] ?? null) : null,
            'avatar' => $avatarPath,
            'is_active' => true,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
