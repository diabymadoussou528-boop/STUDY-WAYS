<?php

use App\Models\Testimonial;
use App\Models\User;

test('home page renders premium testimonials carousel section', function () {
    $user = User::factory()->create(['role' => 'student']);
    Testimonial::query()->create([
        'user_id' => $user->id,
        'message' => 'Une expérience d apprentissage exceptionnelle sur StudyWays.',
        'rating' => 5,
        'is_approved' => true,
    ]);

    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertSee('testi-heading', false);
    $response->assertSee('Ils nous', false);
    $response->assertSee('font confiance', false);
    $response->assertSee('testimonials-section', false);
    $response->assertSee('testi-stage', false);
    $response->assertSee('Une expérience d apprentissage exceptionnelle', false);
    $response->assertSee('testi-card-quote-icon', false);
    $response->assertSee('testi-card-divider', false);
    $response->assertSee('testi-dots', false);
});

test('home page shows demo testimonials when none are approved', function () {
    Testimonial::query()->create([
        'user_id' => User::factory()->create()->id,
        'message' => 'En attente de modération.',
        'rating' => 5,
        'is_approved' => false,
    ]);

    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertSee('Thomas L.', false);
    $response->assertSee('testi-carousel', false);
});
