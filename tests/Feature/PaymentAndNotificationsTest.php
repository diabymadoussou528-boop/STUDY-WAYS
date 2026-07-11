<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\PlatformNotification;
use App\Models\User;
use App\Services\NotificationDispatchService;

test('student can purchase and enroll in a paid course via manual payment', function () {
    $student = User::factory()->create(['role' => 'student']);
    $professor = User::factory()->create(['role' => 'professor']);
    $category = Category::query()->create(['name' => 'Paid', 'slug' => 'paid-'.uniqid()]);

    $course = Course::factory()->published()->create([
        'category_id' => $category->id,
        'user_id' => $professor->id,
        'price' => 15000,
    ]);

    $this->actingAs($student)
        ->post(route('student.checkout.pay', $course), ['provider' => 'manual'])
        ->assertRedirect();

    expect(Enrollment::query()->where('user_id', $student->id)->where('course_id', $course->id)->exists())->toBeTrue();
    expect(Payment::query()->where('user_id', $student->id)->where('status', 'completed')->count())->toBe(1);
});

test('paid course enrollment redirects to checkout from store route', function () {
    $student = User::factory()->create(['role' => 'student']);
    $category = Category::query()->create(['name' => 'C', 'slug' => 'c-'.uniqid()]);
    $course = Course::factory()->published()->create([
        'category_id' => $category->id,
        'price' => 5000,
    ]);

    $this->actingAs($student)
        ->post(route('student.enrollment.store', $course))
        ->assertRedirect(route('student.checkout.course', $course));
});

test('notification dispatch service tracks unread notifications for user', function () {
    $student = User::factory()->create(['role' => 'student']);
    $service = app(NotificationDispatchService::class);

    $service->notify($student, 'payment_received', 'Paiement reçu', 'Votre paiement a été confirmé.');

    expect($service->unreadCount($student))->toBe(1)
        ->and($service->feed($student)->first()?->title)->toBe('Paiement reçu');
});

test('admin course submit notifies admins', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);
    $professor = User::factory()->create(['role' => 'professor']);
    $category = Category::query()->create(['name' => 'X', 'slug' => 'x-'.uniqid()]);
    $course = Course::factory()->draft()->create([
        'category_id' => $category->id,
        'user_id' => $professor->id,
    ]);

    $this->actingAs($professor)
        ->post(route('professor.courses.submit-review', $course))
        ->assertRedirect();

    expect(PlatformNotification::query()->where('user_id', $admin->id)->count())->toBeGreaterThan(0);
});
