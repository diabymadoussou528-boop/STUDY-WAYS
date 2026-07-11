<?php

use App\Models\User;

test('student can view premium student dashboard layout', function () {
    $student = User::factory()->create(['role' => 'student']);

    $response = $this->actingAs($student)->get(route('student.dashboard'));

    $response->assertOk();
    $response->assertSee('StudyWays Étudiant', false);
    $response->assertSee('Cours en cours', false);
    $response->assertSee('admin-premium.css', false);
});

test('professor can view premium professor dashboard layout', function () {
    $professor = User::factory()->create(['role' => 'professor']);

    $response = $this->actingAs($professor)->get(route('professor.dashboard'));

    $response->assertOk();
    $response->assertSee('StudyWays Professeur', false);
    $response->assertSee('Mes cours', false);
    $response->assertSee('admin.js', false);
});

test('student stub pages render with student layout', function () {
    $student = User::factory()->create(['role' => 'student']);

    $routes = [
        'student.courses',
        'student.ai-tutor',
        'student.premium',
        'student.messages',
        'student.appointments',
    ];

    foreach ($routes as $routeName) {
        $this->actingAs($student)
            ->get(route($routeName))
            ->assertOk()
            ->assertSee('widget-card', false);
    }
});
