<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Message;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StudentDashboardService
{
    public function __construct(
        private MessagingService $messaging,
        private RecommendationService $recommendations,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboardPayload(User $student): array
    {
        $enrollments = Schema::hasTable('enrollments')
            ? Enrollment::query()
                ->where('user_id', $student->id)
                ->where('status', EnrollmentStatus::Active)
                ->with(['course.user:id,name,avatar', 'course.category:id,name'])
                ->latest('enrolled_at')
                ->get()
            : collect();

        $completedEnrollments = Schema::hasTable('enrollments')
            ? Enrollment::query()
                ->where('user_id', $student->id)
                ->where(function ($query) {
                    $query->where('status', EnrollmentStatus::Completed)
                        ->orWhere('progress', '>=', 100);
                })
                ->count()
            : 0;

        $certificateCount = Schema::hasTable('enrollments')
            ? Enrollment::query()
                ->where('user_id', $student->id)
                ->where('certificate_eligible', true)
                ->count()
            : 0;

        $inProgress = $enrollments->filter(fn (Enrollment $e) => ! $e->isCompleted());
        $avgProgress = (int) round($enrollments->avg('progress') ?? 0);
        $unreadMessages = Schema::hasTable('messages')
            ? $this->messaging->unreadCount($student)
            : 0;

        $enrolledIds = $enrollments->pluck('course_id');

        $recommended = collect($this->recommendations->forStudent($student))
            ->pluck('course');
        $recommendationDetails = $this->recommendations->forStudent($student);

        $recentMessages = Schema::hasTable('messages')
            ? Message::query()
                ->where(fn ($q) => $q->where('from_user_id', $student->id)->orWhere('to_user_id', $student->id))
                ->with(['sender:id,name,avatar', 'recipient:id,name,avatar', 'course:id,title'])
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        return [
            'heroStats' => [
                ['key' => 'enrolled', 'label' => 'Cours inscrits', 'value' => $enrollments->count(), 'icon' => 'fa-book-open', 'delta' => null, 'deltaDirection' => 'up', 'sparkline' => []],
                ['key' => 'completed', 'label' => 'Complétés', 'value' => $completedEnrollments, 'icon' => 'fa-circle-check', 'delta' => null, 'deltaDirection' => 'up', 'sparkline' => []],
                ['key' => 'progress', 'label' => 'Progression moy.', 'value' => $avgProgress.'%', 'icon' => 'fa-chart-line', 'delta' => null, 'deltaDirection' => 'up', 'sparkline' => []],
                ['key' => 'certificates', 'label' => 'Certificats', 'value' => $certificateCount, 'icon' => 'fa-certificate', 'delta' => null, 'deltaDirection' => 'up', 'sparkline' => []],
                ['key' => 'messages', 'label' => 'Messages non lus', 'value' => $unreadMessages, 'icon' => 'fa-envelope', 'delta' => null, 'deltaDirection' => 'up', 'sparkline' => []],
            ],
            'enrollments' => $enrollments,
            'inProgress' => $inProgress,
            'completed' => $completedEnrollments,
            'recommended' => $recommended,
            'recommendationDetails' => $recommendationDetails,
            'recentMessages' => $recentMessages,
            'isPremium' => $student->hasActivePremium(),
            'recentActivity' => $this->buildActivity($student, $enrollments),
        ];
    }

    /**
     * @param  Collection<int, Enrollment>  $enrollments
     * @return array<int, array<string, mixed>>
     */
    private function buildActivity(User $student, $enrollments): array
    {
        $items = collect();

        foreach ($enrollments->take(5) as $enrollment) {
            $items->push([
                'title' => $enrollment->course?->title ?? 'Cours',
                'desc' => 'Progression · '.$enrollment->progress.'%',
                'time' => $enrollment->last_accessed_at ?? $enrollment->enrolled_at,
                'icon' => 'fa-play-circle',
            ]);
        }

        $testimonial = Testimonial::query()->where('user_id', $student->id)->latest()->first();
        if ($testimonial) {
            $items->push([
                'title' => 'Témoignage publié',
                'desc' => Str::limit($testimonial->message, 48),
                'time' => $testimonial->created_at,
                'icon' => 'fa-quote-left',
            ]);
        }

        return $items->sortByDesc('time')->take(6)->values()->all();
    }
}
