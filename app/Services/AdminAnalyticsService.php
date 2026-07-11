<?php

namespace App\Services;

use App\Enums\AdminActionStatus;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Models\AdminActionRequest;
use App\Models\AiChatMessage;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Testimonial;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminAnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboardPayload(): array
    {
        return [
            'stats' => $this->statsCards(),
            'heroStats' => $this->heroStats(),
            'platformMetrics' => $this->platformMetrics(),
            'charts' => [
                'websiteViews' => $this->websiteViewsSeries(),
                'studentGrowth' => $this->studentGrowthSeries(),
                'courseEngagement' => $this->courseEngagementSeries(),
                'teacherPerformance' => $this->teacherPerformanceSeries(),
                'categories' => $this->categoryDistribution(),
                'aiRecommendations' => $this->aiRecommendationsSeries(),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function statsCards(): array
    {
        $now = Carbon::now();
        $lastMonth = $now->copy()->subMonth();

        $students = User::where('role', 'student')->count();
        $studentsPrev = User::where('role', 'student')
            ->where('created_at', '<', $now->copy()->startOfMonth())
            ->where('created_at', '>=', $lastMonth->copy()->startOfMonth())
            ->count();

        $professors = User::where('role', 'professor')->count();
        $courses = Course::count();
        $views = (int) Course::sum('views');
        $admins = User::where('role', 'admin')->count();
        $testimonials = Testimonial::count();
        $pendingTestimonials = Testimonial::where('is_approved', false)->count();
        $publishedCourses = Schema::hasColumn('courses', 'status')
            ? Course::where('status', CourseStatus::Published)->count()
            : Course::where(function ($query) {
                $query->whereNotNull('video_url')->orWhereNotNull('video_path');
            })->count();
        $enrollmentCount = Schema::hasTable('enrollments') ? Enrollment::count() : 0;

        return [
            $this->buildStat('students', 'Étudiants', $students, $studentsPrev, 'fa-user-graduate', $this->sparklineForRole('student')),
            $this->buildStat('professors', 'Professeurs', $professors, null, 'fa-chalkboard-user', $this->sparklineForRole('professor')),
            $this->buildStat('courses', 'Cours', $courses, null, 'fa-book-open', $this->sparklineForCourses()),
            $this->buildStat('views', 'Vues totales', $views, null, 'fa-eye', $this->sparklineForViews()),
            $this->buildStat('admins', 'Administrateurs', $admins, null, 'fa-shield-halved', []),
            $this->buildStat('testimonials', 'Témoignages', $testimonials, null, 'fa-message-quote', $this->sparklineForTestimonials(), $pendingTestimonials),
            $this->buildStat('published', 'Cours publiés', $publishedCourses, null, 'fa-circle-check', []),
            $this->buildStat('enrollments', 'Inscriptions', $enrollmentCount, null, 'fa-users', []),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function heroStats(): array
    {
        $students = User::where('role', 'student')->count();
        $professors = User::where('role', 'professor')->count();
        $courses = Course::count();
        $views = (int) Course::sum('views');
        $simpleAdmins = User::where('role', 'admin')->where('is_super_admin', false)->count();
        $activeCourses = Schema::hasColumn('courses', 'status')
            ? Course::where('status', CourseStatus::Published)->count()
            : Course::where(function ($query) {
                $query->whereNotNull('video_url')->orWhereNotNull('video_path');
            })->count();
        $revenue = Schema::hasTable('payments')
            ? (float) Payment::where('status', 'completed')->sum('amount')
            : 0;
        $pendingApprovals = Schema::hasTable('admin_action_requests')
            ? AdminActionRequest::where('status', AdminActionStatus::Pending)->count()
            : 0;
        $aiTotal = $this->aiRecommendationsSeries()['total'];
        $pendingTestimonials = Testimonial::where('is_approved', false)->count();

        return [
            $this->buildStat('students', 'Étudiants', $students, null, 'fa-user-graduate', $this->sparklineForRole('student')),
            $this->buildStat('professors', 'Professeurs', $professors, null, 'fa-chalkboard-user', $this->sparklineForRole('professor')),
            $this->buildStat('courses', 'Cours', $courses, null, 'fa-book-open', $this->sparklineForCourses()),
            $this->buildStat('simple_admins', 'Simple Admins', $simpleAdmins, null, 'fa-user-shield', []),
            $this->buildStat('active_courses', 'Cours actifs', $activeCourses, null, 'fa-circle-check', []),
            $this->buildStat('pending_approvals', 'Approbations en attente', $pendingApprovals, null, 'fa-clipboard-check', [], $pendingApprovals),
            $this->buildStat('ai_recommendations', 'Recommandations IA', $aiTotal, null, 'fa-brain', []),
            $this->buildStat('views', 'Vues du site', $views, null, 'fa-eye', $this->sparklineForViews()),
            [
                'key' => 'revenue',
                'label' => 'Revenus',
                'value' => number_format($revenue, 0, ',', ' ').' XOF',
                'icon' => 'fa-coins',
                'sparkline' => [],
                'delta' => null,
                'deltaDirection' => 'up',
                'pending' => 0,
            ],
        ];
    }

    /**
     * @return array<string, int|float|string>
     */
    public function platformMetrics(): array
    {
        $avgRating = (float) (Review::avg('rating') ?? 0);
        $totalEnrollments = max(1, Enrollment::count());
        $completedEnrollments = Enrollment::query()
            ->where(function ($query) {
                $query->where('status', EnrollmentStatus::Completed)
                    ->orWhere('progress', '>=', 100)
                    ->orWhereNotNull('completed_at');
            })
            ->count();
        $completion = min(100, (int) round(($completedEnrollments / $totalEnrollments) * 100));

        $totalStudents = max(1, User::where('role', 'student')->count());
        $activeStudents = User::where('role', 'student')
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();
        $engagement = min(100, (int) round(($activeStudents / $totalStudents) * 100));

        return [
            'completion' => $completion,
            'rating' => $avgRating > 0 ? round($avgRating, 1) : 0,
            'engagement' => $engagement,
            'testimonialGrowth' => Testimonial::where('created_at', '>=', now()->subMonth())->count(),
        ];
    }

    /**
     * @return array<string, array<int, string|int>>
     */
    public function websiteViewsSeries(): array
    {
        return [
            'today' => $this->dailyActivitySeries(1, 'hour'),
            'week' => $this->dailyActivitySeries(7, 'day'),
            'month' => $this->dailyActivitySeries(30, 'day'),
            'year' => $this->monthlyActivitySeries(12),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function studentGrowthSeries(): array
    {
        $months = collect(range(11, 0))->map(fn (int $i) => Carbon::now()->subMonths($i));

        $labels = $months->map(fn (Carbon $d) => $d->translatedFormat('M'))->values()->all();

        $newStudents = $months->map(function (Carbon $month) {
            return User::where('role', 'student')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        })->values()->all();

        $activeStudents = $months->map(function (Carbon $month) {
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            return User::where('role', 'student')
                ->whereBetween('updated_at', [$start, $end])
                ->count();
        })->values()->all();

        $returning = array_map(
            fn (int $i) => max(0, ($activeStudents[$i] ?? 0) - ($newStudents[$i] ?? 0)),
            array_keys($newStudents)
        );

        return [
            'labels' => $labels,
            'series' => [
                ['name' => 'Nouveaux', 'data' => $newStudents],
                ['name' => 'Actifs', 'data' => $activeStudents],
                ['name' => 'Récurrents', 'data' => $returning],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function courseEngagementSeries(): array
    {
        $courses = Course::query()
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderByDesc('views')
            ->limit(6)
            ->get();

        return [
            'labels' => $courses->pluck('title')->map(fn ($t) => Str::limit($t, 18))->values()->all(),
            'views' => $courses->pluck('views')->values()->all(),
            'ratings' => $courses->map(fn ($c) => round((float) ($c->reviews_avg_rating ?? 0), 1))->values()->all(),
            'reviews' => $courses->pluck('reviews_count')->values()->all(),
            'completion' => $courses->map(fn ($c) => min(100, max(12, (int) round(($c->reviews_count * 18) + ($c->views > 0 ? 20 : 0)))))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function teacherPerformanceSeries(): array
    {
        if (! Schema::hasColumn('courses', 'user_id')) {
            return [
                'labels' => [],
                'students' => [],
                'ratings' => [],
                'courses' => [],
                'reviews' => [],
            ];
        }

        $teachers = User::query()
            ->where('role', 'professor')
            ->withCount('courses')
            ->with(['courses' => fn ($q) => $q->withCount(['reviews', 'enrollments'])->withAvg('reviews', 'rating')])
            ->limit(8)
            ->get();

        return [
            'labels' => $teachers->pluck('name')->map(fn ($n) => Str::limit($n, 14))->values()->all(),
            'students' => $teachers->map(fn ($t) => $t->courses->sum('enrollments_count'))->values()->all(),
            'ratings' => $teachers->map(function ($t) {
                $avg = $t->courses->avg('reviews_avg_rating');

                return round((float) ($avg ?? 0), 1);
            })->values()->all(),
            'courses' => $teachers->pluck('courses_count')->values()->all(),
            'reviews' => $teachers->map(fn ($t) => $t->courses->sum('reviews_count'))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function categoryDistribution(): array
    {
        if (! Schema::hasColumn('courses', 'category_id')) {
            return [
                'labels' => ['Cours'],
                'series' => [Course::count()],
            ];
        }

        $categories = Category::query()
            ->withCount('courses')
            ->orderByDesc('courses_count')
            ->limit(6)
            ->get();

        if ($categories->isEmpty()) {
            $uncategorized = Course::whereNull('category_id')->count();
            $categorized = Course::whereNotNull('category_id')->count();

            return [
                'labels' => ['Sans catégorie', 'Catégorisés'],
                'series' => [$uncategorized, $categorized],
            ];
        }

        $other = Course::whereNotIn('category_id', $categories->pluck('id'))->count();

        $labels = $categories->pluck('name')->values()->all();
        $series = $categories->pluck('courses_count')->values()->all();

        if ($other > 0) {
            $labels[] = 'Autres';
            $series[] = $other;
        }

        return compact('labels', 'series');
    }

    /**
     * @return array<string, mixed>
     */
    public function aiRecommendationsSeries(): array
    {
        $months = collect(range(5, 0))->map(fn (int $i) => Carbon::now()->subMonths($i));
        $labels = $months->map(fn (Carbon $d) => $d->translatedFormat('M'))->values()->all();

        if (Schema::hasTable('ai_chat_messages')) {
            $generated = $months->map(function (Carbon $month) {
                return AiChatMessage::query()
                    ->where('role', 'user')
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count();
            })->values()->all();

            $approved = $months->map(function (Carbon $month) {
                return AiChatMessage::query()
                    ->where('role', 'assistant')
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count();
            })->values()->all();

            $pending = array_map(
                fn (int $gen, int $appr) => max(0, $gen - $appr),
                $generated,
                $approved
            );

            if (Schema::hasTable('admin_action_requests')) {
                $pending = $months->map(function (Carbon $month) {
                    return AdminActionRequest::query()
                        ->where('status', AdminActionStatus::Pending)
                        ->whereYear('created_at', $month->year)
                        ->whereMonth('created_at', $month->month)
                        ->count();
                })->values()->all();
            }

            $totalGenerated = array_sum($generated);
            $totalApproved = array_sum($approved);
            $confidence = $totalGenerated > 0
                ? min(98, max(60, (int) round(($totalApproved / $totalGenerated) * 100)))
                : 0;

            return [
                'labels' => $labels,
                'generated' => $generated,
                'approved' => $approved,
                'pending' => $pending,
                'total' => $totalGenerated,
                'confidence' => $confidence,
            ];
        }

        $generated = array_fill(0, 6, 0);
        $approved = array_fill(0, 6, 0);
        $pending = array_fill(0, 6, 0);

        return [
            'labels' => $labels,
            'generated' => $generated,
            'approved' => $approved,
            'pending' => $pending,
            'total' => 0,
            'confidence' => 0,
        ];
    }

    /**
     * @param  array<int, int|float>  $sparkline
     * @return array<string, mixed>
     */
    private function buildStat(
        string $key,
        string $label,
        int $value,
        ?int $previous,
        string $icon,
        array $sparkline,
        int $pending = 0
    ): array {
        $delta = null;
        $deltaDirection = 'up';

        if ($previous !== null && $previous > 0) {
            $delta = (int) round((($value - $previous) / $previous) * 100);
            $deltaDirection = $delta >= 0 ? 'up' : 'down';
        } elseif ($previous === 0 && $value > 0) {
            $delta = 100;
        }

        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'icon' => $icon,
            'sparkline' => $sparkline,
            'delta' => $delta,
            'deltaDirection' => $deltaDirection,
            'pending' => $pending,
        ];
    }

    /**
     * @return array<int, int>
     */
    private function sparklineForRole(string $role): array
    {
        return collect(range(6, 0))->map(function (int $daysAgo) use ($role) {
            $date = Carbon::today()->subDays($daysAgo);

            return User::where('role', $role)->whereDate('created_at', $date)->count();
        })->values()->all();
    }

    /**
     * @return array<int, int>
     */
    private function sparklineForAllUsers(): array
    {
        return collect(range(6, 0))->map(function (int $daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);

            return User::whereDate('created_at', $date)->count();
        })->values()->all();
    }

    /**
     * @return array<int, int>
     */
    private function sparklineForCourses(): array
    {
        return collect(range(6, 0))->map(function (int $daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);

            return Course::whereDate('created_at', $date)->count();
        })->values()->all();
    }

    /**
     * @return array<int, int>
     */
    private function sparklineForTestimonials(): array
    {
        return collect(range(6, 0))->map(function (int $daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);

            return Testimonial::whereDate('created_at', $date)->count();
        })->values()->all();
    }

    /**
     * @return array<int, int>
     */
    private function sparklineForViews(): array
    {
        return collect(range(6, 0))->map(function (int $daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);

            return Course::whereDate('updated_at', $date)->sum('views') ?: Course::whereDate('created_at', $date)->count() * 5;
        })->values()->all();
    }

    /**
     * @return array<string, array<int, string|int>>
     */
    private function dailyActivitySeries(int $days, string $granularity): array
    {
        if ($granularity === 'hour') {
            $labels = collect(range(0, 23))->map(fn (int $h) => sprintf('%02dh', $h))->all();
            $data = collect(range(0, 23))->map(function (int $hour) {
                return User::whereDate('created_at', Carbon::today())
                    ->whereTime('created_at', '>=', sprintf('%02d:00:00', $hour))
                    ->whereTime('created_at', '<', sprintf('%02d:59:59', $hour))
                    ->count()
                    + Testimonial::whereDate('created_at', Carbon::today())
                        ->whereTime('created_at', '>=', sprintf('%02d:00:00', $hour))
                        ->whereTime('created_at', '<', sprintf('%02d:59:59', $hour))
                        ->count();
            })->all();

            return compact('labels', 'data');
        }

        $labels = [];
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $days <= 7 ? $date->translatedFormat('D') : $date->format('d/m');
            $data[] = User::whereDate('created_at', $date)->count()
                + Course::whereDate('created_at', $date)->count() * 2
                + Testimonial::whereDate('created_at', $date)->count();
        }

        return compact('labels', 'data');
    }

    /**
     * @return array<string, array<int, string|int>>
     */
    private function monthlyActivitySeries(int $months): array
    {
        $labels = [];
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->translatedFormat('M Y');
            $data[] = User::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count()
                + Course::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count() * 3;
        }

        return compact('labels', 'data');
    }
}
