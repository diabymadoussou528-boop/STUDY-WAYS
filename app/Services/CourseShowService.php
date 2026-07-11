<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\LessonCompletion;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class CourseShowService
{
    public function __construct(private EnrollmentService $enrollmentService) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Course $course, ?User $user = null): array
    {
        $course->load([
            'user:id,name,avatar,email,bio,specialization',
            'category:id,name',
            'modules.lessons',
            'lessons',
            'reviews' => fn ($q) => $q->latest()->with('user:id,name,avatar'),
        ])->loadCount(['enrollments', 'reviews', 'lessons'])
            ->loadAvg('reviews', 'rating');

        $isEnrolled = $user && $user->isStudent()
            ? $this->enrollmentService->isEnrolled($user, $course)
            : false;

        $canAccessFullContent = $this->canAccessFullContent($user, $course, $isEnrolled);

        $activeEnrollment = $user
            ? $course->enrollments()
                ->where('user_id', $user->id)
                ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
                ->first()
            : null;

        $completedLessonIds = $this->completedLessonIds($user, $course);
        $modules = $this->buildModules($course, $completedLessonIds, $canAccessFullContent);
        $totalDurationSeconds = $this->totalDurationSeconds($course);
        $totalLessons = $course->lessons_count ?: $course->lessons->count();

        return [
            'course' => $course,
            'isEnrolled' => $isEnrolled,
            'activeEnrollment' => $activeEnrollment,
            'canAccessFullContent' => $canAccessFullContent,
            'completedLessonIds' => $completedLessonIds,
            'progressPercent' => $activeEnrollment?->progress ?? 0,
            'modules' => $modules,
            'tags' => $this->parseTags($course),
            'specifications' => $this->buildSpecifications($course, $totalLessons, $totalDurationSeconds),
            'instructorStats' => $this->instructorStats($course),
            'heroStats' => [
                'enrollments' => $course->enrollments_count,
                'rating' => round((float) ($course->reviews_avg_rating ?? 0), 2),
                'reviewsCount' => $course->reviews_count,
                'lessons' => $totalLessons,
                'durationLabel' => $this->formatDurationLabel($course, $totalDurationSeconds),
            ],
            'totalDurationSeconds' => $totalDurationSeconds,
            'totalLessons' => $totalLessons,
            'shareUrl' => route('courses.show', $course),
        ];
    }

    public function canAccessFullContent(?User $user, Course $course, bool $isEnrolled = false): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin() || (int) $course->user_id === (int) $user->id) {
            return true;
        }

        return $isEnrolled && $user->isStudent();
    }

    /**
     * @return array<int, int>
     */
    private function completedLessonIds(?User $user, Course $course): array
    {
        if (! $user || ! Schema::hasTable('lesson_completions')) {
            return [];
        }

        return LessonCompletion::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->pluck('lesson_id')
            ->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function buildModules(Course $course, array $completedLessonIds, bool $canAccessFullContent): Collection
    {
        if ($course->modules->isNotEmpty()) {
            return $course->modules->map(function ($module) use ($completedLessonIds, $canAccessFullContent) {
                $lessons = $module->lessons->map(fn ($lesson) => $this->mapLesson($lesson, $completedLessonIds, $canAccessFullContent));

                return [
                    'id' => $module->id,
                    'title' => $module->title,
                    'description' => $module->description,
                    'lessons' => $lessons,
                    'lessonCount' => $lessons->count(),
                    'durationSeconds' => $lessons->sum('durationSeconds'),
                ];
            });
        }

        if ($course->lessons->isEmpty()) {
            return collect();
        }

        return collect([
            [
                'id' => null,
                'title' => 'Contenu du cours',
                'description' => null,
                'lessons' => $course->lessons->map(fn ($lesson) => $this->mapLesson($lesson, $completedLessonIds, $canAccessFullContent)),
                'lessonCount' => $course->lessons->count(),
                'durationSeconds' => $course->lessons->sum(fn ($l) => (int) ($l->duration_seconds ?? 0)),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapLesson($lesson, array $completedLessonIds, bool $canAccessFullContent): array
    {
        $isAccessible = $lesson->is_preview || $canAccessFullContent;

        return [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'type' => $lesson->lesson_type?->value ?? 'video',
            'typeLabel' => $lesson->lesson_type?->label() ?? 'Vidéo',
            'typeIcon' => $lesson->lesson_type?->icon() ?? 'fa-play-circle',
            'duration' => $lesson->formattedDuration(),
            'durationSeconds' => (int) ($lesson->duration_seconds ?? 0),
            'isPreview' => (bool) $lesson->is_preview,
            'isAccessible' => $isAccessible,
            'isCompleted' => in_array($lesson->id, $completedLessonIds, true),
            'hasVideo' => filled($lesson->video_url),
            'hasResource' => filled($lesson->resource_url) || filled($lesson->resource_path),
            'hasContent' => filled($lesson->content),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function parseTags(Course $course): array
    {
        $tags = [];

        if ($course->category?->name) {
            $tags[] = strtoupper($course->category->name);
        }

        if ($course->meta_keywords) {
            foreach (preg_split('/[,;]+/', $course->meta_keywords) as $tag) {
                $tag = trim($tag);
                if ($tag !== '') {
                    $tags[] = strtoupper($tag);
                }
            }
        }

        return array_values(array_unique($tags));
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildSpecifications(Course $course, int $totalLessons, int $totalDurationSeconds): array
    {
        return [
            ['icon' => 'fa-signal', 'label' => 'Niveau', 'value' => $this->difficultyLabel($course->difficulty)],
            ['icon' => 'fa-chalkboard-user', 'label' => 'Instructeur', 'value' => $course->user?->name ?? '—'],
            ['icon' => 'fa-graduation-cap', 'label' => 'Spécialisation', 'value' => $course->user?->specialization ?? '—'],
            ['icon' => 'fa-clock', 'label' => 'Durée', 'value' => $this->formatDurationLabel($course, $totalDurationSeconds)],
            ['icon' => 'fa-book-open', 'label' => 'Leçons', 'value' => (string) $totalLessons],
            ['icon' => 'fa-language', 'label' => 'Langue', 'value' => 'Français'],
            ['icon' => 'fa-certificate', 'label' => 'Certificat', 'value' => 'Oui'],
            ['icon' => 'fa-calendar', 'label' => 'Mis à jour', 'value' => $course->updated_at?->translatedFormat('j M Y') ?? '—'],
            ['icon' => 'fa-calendar-plus', 'label' => 'Créé le', 'value' => $course->created_at?->translatedFormat('j M Y') ?? '—'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function instructorStats(Course $course): array
    {
        $instructor = $course->user;

        if (! $instructor) {
            return [
                'name' => '—',
                'bio' => null,
                'specialization' => null,
                'avatar' => null,
                'coursesCount' => 0,
                'studentsCount' => 0,
                'rating' => 0,
                'reviewsCount' => 0,
            ];
        }

        $instructor->loadCount([
            'courses' => fn ($q) => $q->published(),
        ]);

        $studentsCount = $instructor->courses()
            ->withCount('enrollments')
            ->get()
            ->sum('enrollments_count');

        $instructorReviews = $instructor->courses()
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->get();

        return [
            'name' => $instructor->name,
            'bio' => $instructor->bio,
            'specialization' => $instructor->specialization,
            'avatar' => $instructor->avatarUrl(),
            'coursesCount' => $instructor->courses_count ?? 0,
            'studentsCount' => $studentsCount,
            'rating' => round((float) $instructorReviews->avg('reviews_avg_rating'), 2),
            'reviewsCount' => (int) $instructorReviews->sum('reviews_count'),
        ];
    }

    private function totalDurationSeconds(Course $course): int
    {
        $fromLessons = (int) $course->lessons->sum('duration_seconds');

        if ($fromLessons > 0) {
            return $fromLessons;
        }

        return (int) (($course->duration_minutes ?? 0) * 60);
    }

    private function formatDurationLabel(Course $course, int $totalDurationSeconds): string
    {
        if ($totalDurationSeconds > 0) {
            $hours = intdiv($totalDurationSeconds, 3600);
            $minutes = intdiv($totalDurationSeconds % 3600, 60);
            $seconds = $totalDurationSeconds % 60;

            if ($hours > 0) {
                return sprintf('%dh %02dm', $hours, $minutes);
            }

            if ($minutes > 0) {
                return sprintf('%d min', $minutes);
            }

            return sprintf('%d s', $seconds);
        }

        if ($course->duration_minutes) {
            return $course->duration_minutes.' min';
        }

        return '—';
    }

    private function difficultyLabel(?string $difficulty): string
    {
        return match (strtolower((string) $difficulty)) {
            'beginner', 'débutant', 'debutant' => 'Débutant',
            'intermediate', 'intermédiaire', 'intermediaire' => 'Intermédiaire',
            'advanced', 'avancé', 'avance' => 'Avancé',
            default => $difficulty ? ucfirst($difficulty) : 'Tous niveaux',
        };
    }
}
