<?php

use App\Models\User;
use App\Services\AdminAnalyticsService;

test('admin can view premium dashboard with analytics payload', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'is_super_admin' => true,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Bonjour,');
    $response->assertSee('Vues du site', false);
    $response->assertSee('window.__ADMIN_CHARTS__', false);
    $response->assertSee('admin-dashboard.js', false);
});

test('student cannot access admin dashboard', function () {
    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($student)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('admin analytics service returns dashboard chart payload', function () {
    User::factory()->count(2)->create(['role' => 'student']);
    User::factory()->create(['role' => 'professor']);

    $payload = app(AdminAnalyticsService::class)->dashboardPayload();

    expect($payload)->toHaveKeys(['stats', 'heroStats', 'platformMetrics', 'charts']);
    expect($payload['charts'])->toHaveKeys([
        'websiteViews',
        'studentGrowth',
        'courseEngagement',
        'teacherPerformance',
        'categories',
        'aiRecommendations',
    ]);
    expect($payload['stats'])->not->toBeEmpty();
});
