<?php

use App\Models\Course;
use App\Models\User;
use App\Services\CategoryService;
use App\Services\RelatedCourseService;

test('related course service ranks same category and similar tags higher', function () {
    $professor = User::factory()->professor()->create();
    $programming = app(CategoryService::class)->findOrCreate('Programming');
    $design = app(CategoryService::class)->findOrCreate('Design');

    $source = Course::factory()->published()->create([
        'title' => 'PHP Advanced',
        'category_id' => $programming->id,
        'user_id' => $professor->id,
        'meta_keywords' => 'php, laravel, api',
        'difficulty' => 'avancé',
    ]);

    $sameCategory = Course::factory()->published()->create([
        'title' => 'Laravel API',
        'category_id' => $programming->id,
        'user_id' => $professor->id,
        'meta_keywords' => 'laravel, api',
        'difficulty' => 'avancé',
    ]);

    $other = Course::factory()->published()->create([
        'title' => 'UI Basics',
        'category_id' => $design->id,
        'user_id' => $professor->id,
        'meta_keywords' => 'figma, ui',
        'difficulty' => 'débutant',
    ]);

    $related = app(RelatedCourseService::class)->for($source, 5);

    expect($related->first()->id)->toBe($sameCategory->id)
        ->and($related->pluck('id'))->not->toContain($source->id);
});
