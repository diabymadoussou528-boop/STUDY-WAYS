<?php

use App\Models\Category;
use App\Services\CategoryService;

test('category service reuses existing categories ignoring case and spaces', function () {
    $service = app(CategoryService::class);

    $first = $service->findOrCreate('  Programming ');
    $second = $service->findOrCreate('PROGRAMMING');
    $third = $service->findOrCreate('programming');

    expect($first->id)->toBe($second->id)
        ->and($second->id)->toBe($third->id)
        ->and(Category::query()->where('normalized_name', 'programming')->count())->toBe(1)
        ->and($first->name)->toBe('Programming');
});

test('category suggestions return matching names', function () {
    app(CategoryService::class)->findOrCreate('Data Science');
    app(CategoryService::class)->findOrCreate('Design');

    $suggestions = app(CategoryService::class)->suggestions('data');

    expect($suggestions)->toContain('Data Science')
        ->and($suggestions)->not->toContain('Design');
});
