<?php

namespace App\Http\Controllers;

use App\Exceptions\MediaUploadException;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdateAvatarRequest;
use App\Services\AvatarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return view('admin.profile', [
                'user' => $user,
            ]);
        }

        if ($user->isStudent()) {
            return view('profile.student', [
                'user' => $user,
                'isPremium' => (bool) $user->is_premium,
            ]);
        }

        if ($user->isTeacher()) {
            return view('profile.professor', [
                'user' => $user,
            ]);
        }

        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->safe()->only([
            'name',
            'email',
            'bio',
            'specialization',
        ]));

        if (! $request->user()->isTeacher()) {
            $request->user()->specialization = null;
        }

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('success', 'Votre profil a été mis à jour.');
    }

    public function updateAvatar(UpdateAvatarRequest $request, AvatarService $avatarService): RedirectResponse
    {
        try {
            $path = $avatarService->store($request->file('avatar'), $request->user());
            $request->user()->update(['avatar' => $path]);
        } catch (MediaUploadException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Photo de profil mise à jour.');
    }

    public function destroyAvatar(Request $request, AvatarService $avatarService): RedirectResponse
    {
        $avatarService->delete($request->user());

        return back()->with('success', 'Photo de profil supprimée.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
