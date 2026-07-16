<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register as student and reach student dashboard', function () {
    $response = $this->from(route('register'))
        ->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'student',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('student.dashboard', absolute: false));

    $user = User::query()->where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe('student')
        ->and($user->is_active)->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

test('new users can register as professor and reach professor dashboard', function () {
    $response = $this->from(route('register'))
        ->post(route('register'), [
            'name' => 'Prof User',
            'email' => 'prof.register@example.com',
            'role' => 'professor',
            'specialization' => 'Laravel',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('professor.dashboard', absolute: false));

    expect(User::query()->where('email', 'prof.register@example.com')->value('role'))->toBe('professor');
});

test('registration rejects duplicate emails with a clear message', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->from(route('register'))
        ->post(route('register'), [
            'name' => 'Duplicate',
            'email' => 'taken@example.com',
            'role' => 'student',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});
