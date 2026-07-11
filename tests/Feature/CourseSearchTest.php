<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\User;

test('course search page finds react courses grouped by category', function () {
    $prof = User::factory()->create(['role' => 'professor']);
    $reactCat = Category::query()->create(['name' => 'React', 'slug' => 'react-'.uniqid()]);
    $laravelCat = Category::query()->create(['name' => 'Laravel', 'slug' => 'laravel-'.uniqid()]);

    $reactCourse = Course::factory()->published()->create([
        'title' => 'ReactJS — Développement Frontend',
        'description' => 'Apprenez ReactJS de A à Z.',
        'meta_keywords' => 'ReactJS, JavaScript, Frontend',
        'category_id' => $reactCat->id,
        'user_id' => $prof->id,
    ]);

    Course::factory()->published()->create([
        'title' => 'Laravel Avancé',
        'description' => 'Framework PHP Laravel.',
        'meta_keywords' => 'Laravel, PHP',
        'category_id' => $laravelCat->id,
        'user_id' => $prof->id,
    ]);

    $this->get(route('courses.search', ['q' => 'ReactJS']))
        ->assertSuccessful()
        ->assertSee('Résultats les plus pertinents')
        ->assertSee('ReactJS')
        ->assertSee($reactCourse->title)
        ->assertDontSee('Laravel Avancé');
});

test('course search finds laravel courses by keyword', function () {
    $prof = User::factory()->create(['role' => 'professor']);
    $cat = Category::query()->create(['name' => 'PHP', 'slug' => 'php-'.uniqid()]);

    Course::factory()->published()->create([
        'title' => 'Maîtriser Laravel 13',
        'meta_keywords' => 'Laravel, PHP, Backend',
        'category_id' => $cat->id,
        'user_id' => $prof->id,
    ]);

    $this->get(route('courses.search', ['q' => 'Laravel']))
        ->assertSuccessful()
        ->assertSee('Maîtriser Laravel 13');
});

test('course search preview api returns json results', function () {
    $prof = User::factory()->create(['role' => 'professor']);
    $cat = Category::query()->create(['name' => 'Dev Web', 'slug' => 'dev-'.uniqid()]);

    Course::factory()->published()->create([
        'title' => 'Vue.js Essentials',
        'meta_keywords' => 'VueJS',
        'category_id' => $cat->id,
        'user_id' => $prof->id,
    ]);

    $this->getJson(route('courses.search.preview', ['q' => 'VueJS']))
        ->assertSuccessful()
        ->assertJsonPath('total', 1)
        ->assertJsonFragment(['title' => 'Vue.js Essentials']);
});

test('course search shows empty state when no match', function () {
    $this->get(route('courses.search', ['q' => 'InexistantXYZ123']))
        ->assertSuccessful()
        ->assertSee('Aucun cours trouvé');
});
