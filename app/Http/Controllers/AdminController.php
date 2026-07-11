<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesProtectedAdminActions;
use App\Models\Course;
use App\Models\Review;
use App\Models\Testimonial;
use App\Models\User;
use App\Services\AdminAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    use HandlesProtectedAdminActions;

    public function index(AdminAnalyticsService $analytics)
    {
        $analyticsData = $analytics->dashboardPayload();
        $stats = $analyticsData['stats'];
        $heroStats = $analyticsData['heroStats'];
        $platformMetrics = $analyticsData['platformMetrics'];
        $charts = $analyticsData['charts'];

        $students = User::where('role', 'student')->count();
        $professors = User::where('role', 'professor')->count();
        $courses = Course::count();
        $views = Course::sum('views');
        $admins = User::where('role', 'admin')->count();
        $testimonials = Testimonial::count();
        $approvedTestimonials = Testimonial::where('is_approved', true)->count();
        $pendingTestimonials = Testimonial::where('is_approved', false)->count();

        $latestCourses = Course::query()
            ->when(Schema::hasColumn('courses', 'user_id'), fn ($q) => $q->with('user:id,name,avatar'))
            ->withCount(['reviews', 'lessons'])
            ->withAvg('reviews', 'rating')
            ->latest()
            ->limit(8)
            ->get();

        $latestUsers = User::query()
            ->latest()
            ->limit(8)
            ->get();

        $latestTestimonials = Testimonial::with('user')
            ->latest()
            ->limit(6)
            ->get();

        $topCourses = Course::query()
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderByDesc('views')
            ->limit(5)
            ->get();

        $recentActivity = $this->buildRecentActivity($latestUsers, $latestCourses, $latestTestimonials);

        $latestStudents = User::where('role', 'student')->latest()->limit(5)->get();
        $latestTeachers = User::where('role', 'professor')->latest()->limit(5)->get();

        return view('admin.dashboard', compact(
            'stats',
            'heroStats',
            'platformMetrics',
            'charts',
            'students',
            'professors',
            'courses',
            'views',
            'admins',
            'testimonials',
            'approvedTestimonials',
            'pendingTestimonials',
            'latestCourses',
            'latestUsers',
            'latestTestimonials',
            'topCourses',
            'recentActivity',
            'latestStudents',
            'latestTeachers'
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildRecentActivity($users, $courses, $testimonials): array
    {
        $items = collect();

        foreach ($users->take(4) as $user) {
            $items->push([
                'type' => 'user',
                'title' => $user->name,
                'desc' => 'Nouvelle inscription · '.ucfirst($user->role),
                'time' => $user->created_at ?? now(),
                'avatar' => $user->avatarUrl(),
            ]);
        }

        foreach ($courses->take(3) as $course) {
            $items->push([
                'type' => 'course',
                'title' => $course->title,
                'desc' => 'Nouveau cours publié',
                'time' => $course->created_at ?? now(),
                'avatar' => $course->user?->avatarUrl() ?? '',
            ]);
        }

        foreach ($testimonials->take(3) as $testimonial) {
            $items->push([
                'type' => 'testimonial',
                'title' => $testimonial->user?->name ?? 'Anonyme',
                'desc' => Str::limit($testimonial->message, 48),
                'time' => $testimonial->created_at ?? now(),
                'avatar' => $testimonial->user?->avatarUrl() ?? '',
            ]);
        }

        return $items->sortByDesc('time')->take(8)->values()->all();
    }

    public function deleteCourse(int $id): RedirectResponse
    {
        $course = Course::findOrFail($id);

        return $this->protectedAction(
            'delete_course',
            'Supprimer le cours « '.$course->title.' »',
            'Suppression définitive du cours.',
            $course,
            null,
            fn () => $course->delete(),
        );
    }

    public function deleteUser(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! $this->canManageUser($user)) {
            return back()->with('error', 'Impossible de supprimer cet utilisateur.');
        }

        return $this->protectedAction(
            'delete_user',
            'Supprimer '.$user->name,
            'Suppression du compte '.$user->email,
            $user,
            null,
            fn () => $user->delete(),
        );
    }

    /**
     * Manage testimonials — admin only.
     */
    public function testimonials()
    {
        $testimonials = Testimonial::with('user')->latest()->get();

        $reviewsByCourse = Review::query()
            ->with(['user:id,name,email,avatar', 'course:id,title'])
            ->latest()
            ->get()
            ->groupBy('course_id');

        $courseReviewStats = Review::query()
            ->selectRaw('course_id, COUNT(*) as review_count, AVG(rating) as avg_rating')
            ->groupBy('course_id')
            ->orderByDesc('review_count')
            ->limit(8)
            ->get()
            ->map(function ($row) {
                $course = Course::query()->find($row->course_id);

                return [
                    'course' => $course?->title ?? 'Cours supprimé',
                    'review_count' => (int) $row->review_count,
                    'avg_rating' => round((float) $row->avg_rating, 1),
                ];
            });

        $avgRating = round((float) (Review::avg('rating') ?? Testimonial::avg('rating') ?? 0), 1);

        $testimonialCharts = [
            'ratings' => $courseReviewStats->pluck('avg_rating')->values()->all(),
            'labels' => $courseReviewStats->pluck('course')->map(fn ($t) => Str::limit($t, 18))->values()->all(),
            'counts' => $courseReviewStats->pluck('review_count')->values()->all(),
        ];

        return view('admin.testimonials', compact(
            'testimonials',
            'reviewsByCourse',
            'courseReviewStats',
            'avgRating',
            'testimonialCharts',
        ));
    }

    /**
     * Delete testimonial.
     */
    public function deleteTestimonial(int $id): RedirectResponse
    {
        $testimonial = Testimonial::findOrFail($id);

        return $this->protectedAction(
            'delete_testimonial',
            'Supprimer un témoignage',
            'Suppression du témoignage de '.($testimonial->user?->name ?? 'utilisateur'),
            $testimonial,
            null,
            fn () => $testimonial->delete(),
        );
    }
}
