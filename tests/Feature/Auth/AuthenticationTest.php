<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('student.dashboard', absolute: false));
});

test('super admin can authenticate and access admin dashboard', function () {
    $superAdmin = User::factory()->superAdmin()->create([
        'email' => 'diabymadossou528@gmail.com',
        'password' => 'Super@26',
    ]);

    $response = $this->post('/login', [
        'email' => $superAdmin->email,
        'password' => 'Super@26',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('admin.dashboard', absolute: false));
});

test('professor can authenticate and reach professor dashboard', function () {
    $professor = User::factory()->professor()->create([
        'password' => 'password',
    ]);

    $this->post('/login', [
        'email' => $professor->email,
        'password' => 'password',
    ])->assertRedirect(route('professor.dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->from(route('login'))
        ->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('suspended users cannot login', function () {
    $user = User::factory()->create([
        'is_active' => false,
        'password' => 'password',
    ]);

    $this->from(route('login'))
        ->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('remember me keeps the user authenticated', function () {
    $user = User::factory()->create([
        'password' => 'password',
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'remember' => '1',
    ])->assertRedirect(route('student.dashboard', absolute: false));

    $this->assertAuthenticated();
    expect($user->fresh()->remember_token)->not->toBeNull();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('passwords remain hashed in the database', function () {
    $user = User::factory()->create([
        'password' => 'SecretPass1!',
    ]);

    expect($user->fresh()->password)->not->toBe('SecretPass1!')
        ->and(Hash::isHashed($user->fresh()->password))->toBeTrue()
        ->and(Hash::check('SecretPass1!', $user->fresh()->password))->toBeTrue();
});
