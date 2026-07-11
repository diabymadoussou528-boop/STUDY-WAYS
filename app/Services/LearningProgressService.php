<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\LearningSession;
use App\Models\Lesson;
use App\Models\LessonCompletion;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class LearningProgressService
{
    public function __construct(private EnrollmentService $enrollmentService) {}

    public function recordLessonComplete(User $student, Lesson $lesson, int $secondsSpent = 0): void
    {
        $course = $lesson->course;
        if (! $course) {
            return;
        }

        LessonCompletion::query()->updateOrCreate(
            ['user_id' => $student->id, 'lesson_id' => $lesson->id],
            [
                'course_id' => $course->id,
                'time_spent_seconds' => $secondsSpent,
                'completed_at' => now(),
            ],
        );

        $enrollment = Enrollment::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->first();

        if ($enrollment) {
            $this->enrollmentService->recordProgress($enrollment, $lesson);
        }

        $this->updateStreak($student, $secondsSpent);
    }

    public function startSession(User $student, ?int $courseId = null, ?int $lessonId = null): LearningSession
    {
        return LearningSession::query()->create([
            'user_id' => $student->id,
            'course_id' => $courseId,
            'lesson_id' => $lessonId,
            'started_at' => now(),
        ]);
    }

    public function endSession(LearningSession $session): LearningSession
    {
        $duration = $session->started_at ? now()->diffInSeconds($session->started_at) : 0;

        $session->update([
            'ended_at' => now(),
            'duration_seconds' => $duration,
        ]);

        $session->user?->increment('total_study_minutes', (int) ceil($duration / 60));

        return $session->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardPayload(User $student): array
    {
        $enrollments = Enrollment::query()
            ->where('user_id', $student->id)
            ->with(['course.user:id,name'])
            ->get();

        $lessonCompletions = Schema::hasTable('lesson_completions')
            ? LessonCompletion::query()->where('user_id', $student->id)->count()
            : 0;

        $certificates = $enrollments->where('certificate_eligible', true)->count();
        $avgQuiz = Schema::hasTable('quiz_attempts')
            ? (int) round(QuizAttempt::query()->where('user_id', $student->id)->where('status', 'submitted')->avg('percentage') ?? 0)
            : 0;

        return [
            'enrollments' => $enrollments,
            'lessonCompletions' => $lessonCompletions,
            'certificatesEarned' => $certificates,
            'avgQuizScore' => $avgQuiz,
            'currentStreak' => $student->current_streak ?? 0,
            'longestStreak' => $student->longest_streak ?? 0,
            'totalStudyMinutes' => $student->total_study_minutes ?? 0,
            'weeklyActivity' => $this->weeklyActivity($student),
            'monthlyActivity' => $this->monthlyActivity($student),
            'achievements' => $this->achievements($student, $enrollments, $certificates, $avgQuiz),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function weeklyActivity(User $student): array
    {
        if (! Schema::hasTable('learning_sessions')) {
            return [];
        }

        return collect(range(6, 0))->map(function (int $daysAgo) use ($student) {
            $date = Carbon::today()->subDays($daysAgo);
            $minutes = (int) LearningSession::query()
                ->where('user_id', $student->id)
                ->whereDate('started_at', $date)
                ->sum('duration_seconds') / 60;

            return [
                'label' => $date->translatedFormat('D'),
                'minutes' => $minutes,
            ];
        })->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function monthlyActivity(User $student): array
    {
        if (! Schema::hasTable('learning_sessions')) {
            return [];
        }

        return collect(range(11, 0))->map(function (int $monthsAgo) use ($student) {
            $month = Carbon::now()->subMonths($monthsAgo);
            $minutes = (int) LearningSession::query()
                ->where('user_id', $student->id)
                ->whereYear('started_at', $month->year)
                ->whereMonth('started_at', $month->month)
                ->sum('duration_seconds') / 60;

            return [
                'label' => $month->translatedFormat('M'),
                'minutes' => $minutes,
            ];
        })->all();
    }

    /** @return array<int, array<string, string>> */
    private function achievements(User $student, Collection $enrollments, int $certificates, int $avgQuiz): array
    {
        $items = [];

        if ($enrollments->count() >= 1) {
            $items[] = ['title' => 'Premier pas', 'icon' => 'fa-shoe-prints', 'desc' => 'Première inscription'];
        }

        if ($certificates >= 1) {
            $items[] = ['title' => 'Certifié', 'icon' => 'fa-certificate', 'desc' => 'Premier certificat obtenu'];
        }

        if (($student->current_streak ?? 0) >= 7) {
            $items[] = ['title' => 'Régularité', 'icon' => 'fa-fire', 'desc' => '7 jours consécutifs'];
        }

        if ($avgQuiz >= 80) {
            $items[] = ['title' => 'Excellence', 'icon' => 'fa-star', 'desc' => 'Moyenne quiz ≥ 80%'];
        }

        return $items;
    }

    private function updateStreak(User $student, int $secondsSpent): void
    {
        $today = Carbon::today();
        $lastStudy = $student->last_study_date ? Carbon::parse($student->last_study_date) : null;

        $current = (int) ($student->current_streak ?? 0);

        if (! $lastStudy || $lastStudy->lt($today->copy()->subDay())) {
            $current = 1;
        } elseif ($lastStudy->lt($today)) {
            $current++;
        }

        $student->update([
            'last_study_date' => $today,
            'current_streak' => $current,
            'longest_streak' => max((int) ($student->longest_streak ?? 0), $current),
            'total_study_minutes' => (int) ($student->total_study_minutes ?? 0) + (int) ceil($secondsSpent / 60),
        ]);
    }
}
