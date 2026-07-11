<?php

use App\Enums\AdminActionStatus;
use App\Models\AdminActionRequest;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;

test('super admin can access all management pages', function () {
    $admin = User::factory()->superAdmin()->create();

    $routes = [
        'admin.dashboard',
        'admin.students',
        'admin.teachers',
        'admin.courses',
        'admin.testimonials',
        'admin.ai',
        'admin.reports',
        'admin.notifications',
        'admin.approvals',
        'admin.admins',
    ];

    foreach ($routes as $routeName) {
        $this->actingAs($admin)
            ->get(route($routeName))
            ->assertSuccessful();
    }
});

test('simple admin can access shared management pages but not super admin sections', function () {
    $admin = User::factory()->simpleAdmin()->create();

    foreach (['admin.students', 'admin.teachers', 'admin.courses', 'admin.testimonials', 'admin.ai', 'admin.reports', 'admin.notifications'] as $routeName) {
        $this->actingAs($admin)
            ->get(route($routeName))
            ->assertSuccessful();
    }

    foreach (['admin.approvals', 'admin.admins'] as $routeName) {
        $this->actingAs($admin)
            ->get(route($routeName))
            ->assertForbidden();
    }
});

test('simple admin destructive action creates approval request instead of executing immediately', function () {
    $simpleAdmin = User::factory()->simpleAdmin()->create();
    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($simpleAdmin)
        ->delete(route('admin.students.destroy', $student))
        ->assertRedirect();

    expect(User::query()->find($student->id))->not->toBeNull();

    $request = AdminActionRequest::query()->first();

    expect($request)->not->toBeNull()
        ->and($request->action)->toBe('delete_user')
        ->and($request->status)->toBe(AdminActionStatus::Pending)
        ->and($request->requested_by)->toBe($simpleAdmin->id);
});

test('super admin can approve simple admin delete request', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $simpleAdmin = User::factory()->simpleAdmin()->create();
    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($simpleAdmin)
        ->delete(route('admin.students.destroy', $student));

    $approval = AdminActionRequest::query()->firstOrFail();

    $this->actingAs($superAdmin)
        ->post(route('admin.approvals.approve', $approval))
        ->assertRedirect(route('admin.approvals'));

    expect(User::query()->find($student->id))->toBeNull()
        ->and($approval->fresh()->status)->toBe(AdminActionStatus::Approved);
});

test('admin students and teachers show pages render for super admin', function () {
    $admin = User::factory()->superAdmin()->create();
    $student = User::factory()->create(['role' => 'student']);
    $teacher = User::factory()->create(['role' => 'professor']);

    $this->actingAs($admin)
        ->get(route('admin.students.show', $student))
        ->assertSuccessful()
        ->assertSee($student->name, false);

    $this->actingAs($admin)
        ->get(route('admin.teachers.show', $teacher))
        ->assertSuccessful()
        ->assertSee($teacher->email, false);
});

test('super admin dashboard shows expanded hero stats', function () {
    User::factory()->count(2)->create(['role' => 'student']);
    User::factory()->create(['role' => 'professor']);
    User::factory()->simpleAdmin()->create();
    $category = Category::query()->create(['name' => 'Test', 'slug' => 'test']);
    Course::query()->create(['title' => 'Test Course A', 'description' => 'Demo', 'category_id' => $category->id]);
    Course::query()->create(['title' => 'Test Course B', 'description' => 'Demo', 'category_id' => $category->id]);

    $admin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertSuccessful();
    $response->assertSee('Simple Admins', false);
    $response->assertSee('Revenus', false);
});
