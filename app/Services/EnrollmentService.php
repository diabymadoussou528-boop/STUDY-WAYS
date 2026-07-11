<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\AdminAuditLog;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EnrollmentService
{
    public function __construct(
        private NotificationDispatchService $notifications,
    ) {}

    public function enroll(User $student, Course $course): Enrollment
    {
        if (! $course->isPublished()) {
            throw new RuntimeException('Ce cours n\'est pas disponible à l\'inscription.');
        }

        if ($course->is_premium_only && ! $student->hasActivePremium()) {
            throw new RuntimeException('Ce cours nécessite un abonnement Premium.');
        }

        if ((float) $course->price > 0) {
            throw new RuntimeException('Ce cours est payant. Le paiement doit être effectué avant l\'inscription.');
        }

        $existing = Enrollment::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->where('status', EnrollmentStatus::Active)
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($student, $course) {
            $enrollment = Enrollment::query()->create([
                'user_id' => $student->id,
                'course_id' => $course->id,
                'status' => EnrollmentStatus::Active,
                'progress' => 0,
                'enrolled_at' => now(),
                'last_accessed_at' => now(),
            ]);

            AdminAuditLog::recordDetailed(
                'enrollment.created',
                'enrollment',
                $student->name.' s\'est inscrit au cours « '.$course->title.' »',
                null,
                ['course_id' => $course->id, 'user_id' => $student->id],
            );

            if ($course->user_id) {
                $this->notifications->notify(
                    User::query()->find($course->user_id),
                    'new_enrollment',
                    'Nouvelle inscription',
                    $student->name.' s\'est inscrit à « '.$course->title.' ».',
                    ['course_id' => $course->id, 'student_id' => $student->id],
                );
            }

            $this->notifications->notify(
                $student,
                'enrollment_confirmed',
                'Inscription confirmée',
                'Vous êtes inscrit au cours « '.$course->title.' ». Bon apprentissage !',
                ['course_id' => $course->id],
            );

            return $enrollment;
        });
    }

    public function enrollAfterPayment(User $student, Course $course): Enrollment
    {
        if (! $course->isPublished()) {
            throw new RuntimeException('Cours indisponible.');
        }

        return DB::transaction(function () use ($student, $course) {
            $enrollment = Enrollment::query()->updateOrCreate(
                [
                    'user_id' => $student->id,
                    'course_id' => $course->id,
                ],
                [
                    'status' => EnrollmentStatus::Active,
                    'enrolled_at' => now(),
                    'last_accessed_at' => now(),
                    'cancelled_at' => null,
                ],
            );

            if ($course->user_id) {
                $this->notifications->notify(
                    User::query()->find($course->user_id),
                    'new_enrollment',
                    'Nouvelle inscription',
                    $student->name.' s\'est inscrit à « '.$course->title.' ».',
                    ['course_id' => $course->id],
                );
            }

            $this->notifications->notify(
                $student,
                'enrollment_confirmed',
                'Inscription confirmée',
                'Votre paiement a été confirmé. Vous êtes inscrit au cours « '.$course->title.' ».',
                ['course_id' => $course->id],
            );

            return $enrollment;
        });
    }

    public function cancel(User $student, Enrollment $enrollment): void
    {
        if ((int) $enrollment->user_id !== (int) $student->id) {
            throw new RuntimeException('Inscription non autorisée.');
        }

        $enrollment->update([
            'status' => EnrollmentStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function recordProgress(Enrollment $enrollment, ?Lesson $lesson = null): void
    {
        $course = $enrollment->course()->withCount('lessons')->first();
        $totalLessons = max(1, $course?->lessons_count ?? 1);

        if ($lesson) {
            $lessonIndex = $course->lessons()->where('id', '<=', $lesson->id)->count();
            $progress = min(100, (int) round(($lessonIndex / $totalLessons) * 100));
        } else {
            $progress = min(100, $enrollment->progress + 5);
        }

        $updates = [
            'progress' => $progress,
            'last_accessed_at' => now(),
            'current_lesson_id' => $lesson?->id ?? $enrollment->current_lesson_id,
        ];

        if ($progress >= 100) {
            $updates['status'] = EnrollmentStatus::Completed;
            $updates['completed_at'] = now();
            $updates['certificate_eligible'] = true;
        }

        $enrollment->update($updates);
    }

    public function isEnrolled(User $student, Course $course): bool
    {
        return Enrollment::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->exists();
    }
}
