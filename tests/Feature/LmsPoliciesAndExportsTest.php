<?php

use App\Enums\AppointmentStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Appointment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\ProfessorDashboardService;

test('student cannot cancel another users enrollment', function () {
    $student = User::factory()->create(['role' => 'student']);
    $other = User::factory()->create(['role' => 'student']);
    $enrollment = Enrollment::factory()->create(['user_id' => $other->id]);

    $this->actingAs($student)
        ->delete(route('student.enrollment.cancel', $enrollment))
        ->assertForbidden();
});

test('professor cannot respond to another professors appointment', function () {
    $professor = User::factory()->create(['role' => 'professor']);
    $other = User::factory()->create(['role' => 'professor']);
    $appointment = Appointment::query()->create([
        'student_id' => User::factory()->create(['role' => 'student'])->id,
        'professor_id' => $other->id,
        'course_id' => Course::factory()->create(['user_id' => $other->id])->id,
        'scheduled_at' => now()->addDay(),
        'status' => AppointmentStatus::Pending,
    ]);

    $this->actingAs($professor)
        ->post(route('professor.appointments.accept', $appointment))
        ->assertForbidden();
});

test('super admin can export audit logs as csv', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.audit-logs.export', 'csv'))
        ->assertSuccessful()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

test('super admin can export audit logs as excel', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.audit-logs.export', 'excel'))
        ->assertSuccessful();
});

test('professor dashboard shows distinct student count', function () {
    $professor = User::factory()->create(['role' => 'professor']);
    $student = User::factory()->create(['role' => 'student']);
    $courseA = Course::factory()->create(['user_id' => $professor->id]);
    $courseB = Course::factory()->create(['user_id' => $professor->id]);

    Enrollment::factory()->create(['user_id' => $student->id, 'course_id' => $courseA->id, 'status' => EnrollmentStatus::Active]);
    Enrollment::factory()->create(['user_id' => $student->id, 'course_id' => $courseB->id, 'status' => EnrollmentStatus::Active]);

    $payload = app(ProfessorDashboardService::class)->dashboardPayload($professor);

    expect(collect($payload['heroStats'])->firstWhere('key', 'students')['value'])->toBe(1);
});
