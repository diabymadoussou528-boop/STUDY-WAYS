<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('user can upload and remove profile avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $file = UploadedFile::fake()->image('avatar.jpg', 800, 600);

    $this->actingAs($user)
        ->post(route('profile.avatar.update'), ['avatar' => $file])
        ->assertRedirect()
        ->assertSessionHas('success');

    $user->refresh();

    expect($user->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar);

    $this->actingAs($user)
        ->delete(route('profile.avatar.destroy'))
        ->assertRedirect()
        ->assertSessionHas('success');

    $user->refresh();

    expect($user->avatar)->toBeNull();
});

test('avatar upload rejects invalid files', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->actingAs($user)
        ->post(route('profile.avatar.update'), ['avatar' => $file])
        ->assertSessionHasErrors('avatar');
});

test('admin profile page uses admin layout', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('profile.edit'))
        ->assertSuccessful()
        ->assertViewIs('admin.profile');
});
