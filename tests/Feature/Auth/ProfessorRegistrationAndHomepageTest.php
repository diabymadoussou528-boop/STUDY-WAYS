<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('professor registration requires specialization field', function () {
    $this->from(route('register'))
        ->post(route('register'), [
            'name' => 'Prof Test',
            'email' => 'prof-missing@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'professor',
        ])->assertSessionHasErrors('specialization');

    expect(User::query()->where('email', 'prof-missing@example.com')->exists())->toBeFalse();
});

test('professor registration stores specialization', function () {
    $this->from(route('register'))
        ->post(route('register'), [
            'name' => 'Prof Laravel',
            'email' => 'prof-laravel@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'professor',
            'specialization' => 'Laravel / PHP',
        ])->assertRedirect(route('professor.dashboard', absolute: false));

    $professor = User::query()->where('email', 'prof-laravel@example.com')->first();

    expect($professor)->not->toBeNull()
        ->and($professor->role)->toBe('professor')
        ->and($professor->specialization)->toBe('Laravel / PHP');
});

test('student registration does not require specialization', function () {
    $this->from(route('register'))
        ->post(route('register'), [
            'name' => 'Student Test',
            'email' => 'student-spec@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'student',
        ])->assertRedirect(route('student.dashboard', absolute: false));

    expect(User::query()->where('email', 'student-spec@example.com')->value('specialization'))->toBeNull();
});

test('guest sees homepage upgrade section', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Choisissez votre formule StudyWays')
        ->assertSee('Premium Mensuel');
});

test('student sees homepage upgrade section', function () {
    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($student)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Choisissez votre formule StudyWays')
        ->assertSee('Premium Mensuel');
});

test('professor does not see homepage upgrade section', function () {
    $professor = User::factory()->professor()->create();

    $this->actingAs($professor)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertDontSee('Choisissez votre formule StudyWays')
        ->assertDontSee('Premium Mensuel');
});

test('simple admin does not see homepage upgrade section', function () {
    $admin = User::factory()->simpleAdmin()->create();

    $this->actingAs($admin)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertDontSee('Choisissez votre formule StudyWays');
});

test('super admin does not see homepage upgrade section', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertDontSee('Choisissez votre formule StudyWays');
});

test('professor can update specialization on profile', function () {
    $professor = User::factory()->professor()->create([
        'specialization' => 'ReactJS',
    ]);

    $this->actingAs($professor)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => $professor->name,
            'email' => $professor->email,
            'specialization' => 'Vue.js & Frontend',
            'bio' => 'Formateur frontend expérimenté.',
        ])
        ->assertRedirect(route('profile.edit'));

    $professor->refresh();

    expect($professor->specialization)->toBe('Vue.js & Frontend')
        ->and($professor->bio)->toBe('Formateur frontend expérimenté.');
});

test('view-homepage-upgrade gate allows guests and students only', function () {
    expect(Gate::forUser(null)->allows('view-homepage-upgrade'))->toBeTrue()
        ->and(Gate::forUser(User::factory()->create(['role' => 'student']))->allows('view-homepage-upgrade'))->toBeTrue()
        ->and(Gate::forUser(User::factory()->professor()->create())->allows('view-homepage-upgrade'))->toBeFalse()
        ->and(Gate::forUser(User::factory()->simpleAdmin()->create())->allows('view-homepage-upgrade'))->toBeFalse();
});
