<?php

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Appointment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;

function lmsCategory(): Category
{
    return Category::query()->create(['name' => 'Test Cat', 'slug' => 'test-'.uniqid()]);
}

function lmsPublishedCourse(?User $professor = null): Course
{
    $professor ??= User::factory()->create(['role' => 'professor']);

    return Course::factory()->published()->create([
        'category_id' => lmsCategory()->id,
        'user_id' => $professor->id,
        'price' => 0,
    ]);
}

test('catalog page lists published courses from database', function () {
    $course = lmsPublishedCourse();
    Course::factory()->draft()->create(['category_id' => lmsCategory()->id]);

    $this->get(route('catalog.index'))
        ->assertSuccessful()
        ->assertSee($course->title);
});

test('student can enroll in a free published course', function () {
    $student = User::factory()->create(['role' => 'student']);
    $course = lmsPublishedCourse();

    $this->actingAs($student)
        ->post(route('student.enrollment.store', $course))
        ->assertRedirect();

    expect(Enrollment::query()->where('user_id', $student->id)->where('course_id', $course->id)->exists())->toBeTrue();
});

test('enrolled course appears in student courses dashboard', function () {
    $student = User::factory()->create(['role' => 'student']);
    $course = lmsPublishedCourse();

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
    ]);

    $this->actingAs($student)
        ->get(route('student.courses'))
        ->assertSuccessful()
        ->assertSee($course->title);
});

test('student can request an appointment for enrolled course', function () {
    $student = User::factory()->create(['role' => 'student']);
    $professor = User::factory()->create(['role' => 'professor']);
    $course = lmsPublishedCourse($professor);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
    ]);

    $this->actingAs($student)
        ->post(route('student.appointments.store'), [
            'course_id' => $course->id,
            'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'message' => 'Besoin d\'aide',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Appointment::query()->where('student_id', $student->id)->count())->toBe(1);
});

test('professor can accept appointment request', function () {
    $student = User::factory()->create(['role' => 'student']);
    $professor = User::factory()->create(['role' => 'professor']);
    $course = lmsPublishedCourse($professor);

    $appointment = Appointment::query()->create([
        'student_id' => $student->id,
        'professor_id' => $professor->id,
        'course_id' => $course->id,
        'scheduled_at' => now()->addDay(),
        'status' => 'pending',
    ]);

    $this->actingAs($professor)
        ->post(route('professor.appointments.accept', $appointment), [
            'meeting_link' => 'https://meet.example.com/abc',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($appointment->fresh()->status->value)->toBe('accepted');
});

test('student can subscribe to premium plan', function () {
    $student = User::factory()->create(['role' => 'student', 'is_premium' => false]);

    $this->actingAs($student)
        ->post(route('student.premium.subscribe'), [
            'plan' => 'monthly',
            'provider' => 'manual',
        ])
        ->assertRedirect(route('student.premium'))
        ->assertSessionHas('success');

    expect($student->fresh()->is_premium)->toBeTrue();
    expect(Payment::query()->where('user_id', $student->id)->count())->toBe(1);
});

test('super admin can view audit logs page', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.audit-logs'))
        ->assertSuccessful()
        ->assertSee('Journal d\'audit');
});

test('simple admin cannot view audit logs', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => false]);

    $this->actingAs($admin)
        ->get(route('admin.audit-logs'))
        ->assertForbidden();
});

test('admin can publish a course', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);
    $course = Course::factory()->draft()->create(['category_id' => lmsCategory()->id]);

    $this->actingAs($admin)
        ->post(route('admin.courses.publish', $course))
        ->assertRedirect();

    expect($course->fresh()->status)->toBe(CourseStatus::Published);
});

test('learn slug redirects to course show page', function () {
    $course = lmsPublishedCourse();

    $this->get(route('learn.show', $course->slug))
        ->assertRedirect(route('courses.show', $course));
});
