<?php

use App\Enums\SubscriptionStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Subscription;
use App\Models\User;

function certCategory(): Category
{
    return Category::query()->create(['name' => 'Cert Cat', 'slug' => 'cert-'.uniqid()]);
}

function certPublishedCourse(): Course
{
    $professor = User::factory()->create(['role' => 'professor']);

    return Course::factory()->published()->create([
        'category_id' => certCategory()->id,
        'user_id' => $professor->id,
        'price' => 0,
    ]);
}

function makePremiumStudent(User $student): void
{
    $student->update(['is_premium' => true]);
    Subscription::query()->create([
        'user_id' => $student->id,
        'plan' => 'monthly',
        'status' => SubscriptionStatus::Active,
        'provider' => 'manual',
        'amount' => 9900,
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ]);
}

function completedEnrollment(User $student, Course $course): Enrollment
{
    return Enrollment::factory()->completed()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
    ]);
}

test('student certificates index lists completed eligible courses', function () {
    $student = User::factory()->create(['role' => 'student']);
    $course = certPublishedCourse();
    completedEnrollment($student, $course);

    $this->actingAs($student)
        ->get(route('student.certificates.index'))
        ->assertSuccessful()
        ->assertSee($course->title)
        ->assertSee('Premium requis');
});

test('premium student can download certificate for completed course', function () {
    $student = User::factory()->create(['role' => 'student']);
    makePremiumStudent($student);
    $course = certPublishedCourse();
    $enrollment = completedEnrollment($student, $course);

    $this->actingAs($student)
        ->get(route('student.certificates.show', $enrollment))
        ->assertSuccessful()
        ->assertSee('Certificat de réussite')
        ->assertSee($student->name)
        ->assertSee($course->title);

    $enrollment->refresh();
    expect($enrollment->certificate_number)->not->toBeNull();
    expect($enrollment->certificate_issued_at)->not->toBeNull();
});

test('non premium student cannot download certificate', function () {
    $student = User::factory()->create(['role' => 'student']);
    $course = certPublishedCourse();
    $enrollment = completedEnrollment($student, $course);

    $this->actingAs($student)
        ->get(route('student.certificates.show', $enrollment))
        ->assertForbidden();
});

test('student cannot download another users certificate', function () {
    $owner = User::factory()->create(['role' => 'student']);
    makePremiumStudent($owner);
    $intruder = User::factory()->create(['role' => 'student']);
    makePremiumStudent($intruder);
    $course = certPublishedCourse();
    $enrollment = completedEnrollment($owner, $course);

    $this->actingAs($intruder)
        ->get(route('student.certificates.show', $enrollment))
        ->assertForbidden();
});
