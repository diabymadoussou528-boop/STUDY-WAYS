<?php

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Notifications\SimpleAdminWelcomeNotification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('super admin can create a simple admin with generated credentials', function () {
    Notification::fake();

    $superAdmin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($superAdmin)->post(route('admin.admins.store'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@studyways.test',
        'phone' => '+22370000000',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $response->assertSessionHas('temporary_password_display');

    $admin = User::query()->where('email', 'john.doe@studyways.test')->first();

    expect($admin)->not->toBeNull();
    expect($admin->name)->toBe('John Doe');
    expect($admin->isSimpleAdmin())->toBeTrue();
    expect($admin->first_login)->toBeTrue();
    expect($admin->is_active)->toBeTrue();

    Notification::assertSentTo($admin, SimpleAdminWelcomeNotification::class);
    expect(AdminAuditLog::query()->where('action', 'simple_admin.created')->exists())->toBeTrue();
});

test('each simple admin receives a unique temporary password at their own email', function () {
    Notification::fake();

    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)->post(route('admin.admins.store'), [
        'first_name' => 'Kamado',
        'last_name' => 'Admin',
        'email' => 'kamado@example.com',
    ]);

    $this->actingAs($superAdmin)->post(route('admin.admins.store'), [
        'first_name' => 'Naty',
        'last_name' => 'Diabi',
        'email' => 'natydiabi@example.com',
    ]);

    $kamado = User::query()->where('email', 'kamado@example.com')->first();
    $naty = User::query()->where('email', 'natydiabi@example.com')->first();

    $kamadoPassword = null;
    $natyPassword = null;

    Notification::assertSentTo($kamado, SimpleAdminWelcomeNotification::class, function ($notification) use (&$kamadoPassword) {
        $kamadoPassword = $notification->temporaryPassword;

        return strlen($notification->temporaryPassword) >= 14;
    });

    Notification::assertSentTo($naty, SimpleAdminWelcomeNotification::class, function ($notification) use (&$natyPassword) {
        $natyPassword = $notification->temporaryPassword;

        return strlen($notification->temporaryPassword) >= 14;
    });

    expect($kamadoPassword)->not->toBe($natyPassword);
});

test('super admin sees error when mail configuration is invalid', function () {
    Config::set('mail.from.address', '');

    $superAdmin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($superAdmin)->post(route('admin.admins.store'), [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane.smith@studyways.test',
        'phone' => '+22370000001',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $response->assertSessionHas('temporary_password_display');

    expect(User::query()->where('email', 'jane.smith@studyways.test')->exists())->toBeTrue();
    expect(AdminAuditLog::query()->where('action', 'simple_admin.welcome_email_failed')->exists())->toBeTrue();
});

test('regenerating temporary password flashes password for super admin', function () {
    Notification::fake();

    $superAdmin = User::factory()->superAdmin()->create();
    $admin = User::factory()->simpleAdmin()->create();

    $response = $this->actingAs($superAdmin)
        ->post(route('admin.admins.temporary-password', $admin));

    $response->assertRedirect();
    $response->assertSessionHas('temporary_password_display');
    $response->assertSessionHas('success');

    Notification::assertSentTo($admin->fresh(), SimpleAdminWelcomeNotification::class);
});

test('simple admin is redirected to force password change on first login', function () {
    $admin = User::factory()->pendingFirstLogin()->create([
        'password' => Hash::make('TempPass123!'),
    ]);

    $response = $this->post('/login', [
        'email' => $admin->email,
        'password' => 'TempPass123!',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('password.force.edit'));
});

test('simple admin must change password before accessing dashboard', function () {
    $admin = User::factory()->pendingFirstLogin()->create([
        'password' => Hash::make('TempPass123!'),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('password.force.edit'));
});

test('simple admin can activate account and access dashboard', function () {
    $admin = User::factory()->pendingFirstLogin()->create([
        'password' => Hash::make('TempPass123!'),
    ]);

    $this->actingAs($admin)
        ->post(route('password.force.update'), [
            'current_password' => 'TempPass123!',
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ])
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('success');

    $admin->refresh();

    expect($admin->first_login)->toBeFalse();

    $this->post('/logout');

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'NewSecurePass123!',
    ])->assertRedirect(route('admin.dashboard'));

    expect(auth()->user()->mustChangePassword())->toBeFalse();
});

test('suspended simple admin cannot login', function () {
    $admin = User::factory()->simpleAdmin()->create([
        'is_active' => false,
        'password' => Hash::make('password'),
    ]);

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('super admin can suspend and reactivate a simple admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $admin = User::factory()->simpleAdmin()->create();

    $this->actingAs($superAdmin)
        ->post(route('admin.admins.toggle-status', $admin))
        ->assertRedirect();

    expect($admin->fresh()->is_active)->toBeFalse();

    $this->actingAs($superAdmin)
        ->post(route('admin.admins.toggle-status', $admin))
        ->assertRedirect();

    expect($admin->fresh()->is_active)->toBeTrue();
});
