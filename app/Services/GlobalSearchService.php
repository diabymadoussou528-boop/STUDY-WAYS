<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Message;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GlobalSearchService
{
    /**
     * @return array<string, mixed>
     */
    public function search(string $query, ?User $user = null, string $scope = 'all', int $limit = 8): array
    {
        $query = trim($query);

        if (strlen($query) < 2) {
            return ['query' => $query, 'results' => [], 'total' => 0, 'suggestions' => $this->suggestions()];
        }

        $results = collect();

        if ($scope === 'all' || $scope === 'courses') {
            $results = $results->merge($this->searchCourses($query, $limit));
        }

        if ($scope === 'all' || $scope === 'teachers') {
            $results = $results->merge($this->searchTeachers($query, $limit));
        }

        if ($scope === 'all' || $scope === 'categories') {
            $results = $results->merge($this->searchCategories($query, $limit));
        }

        if ($scope === 'all' || $scope === 'testimonials') {
            $results = $results->merge($this->searchTestimonials($query, $limit));
        }

        if ($user && ($scope === 'all' || $scope === 'messages')) {
            $results = $results->merge($this->searchMessages($query, $user, $limit));
        }

        if ($user?->isStudent() && ($scope === 'all' || $scope === 'lessons')) {
            $results = $results->merge($this->searchLessons($query, $user, $limit));
        }

        if (Schema::hasTable('search_histories') && $user) {
            DB::table('search_histories')->insert([
                'user_id' => $user->id,
                'query' => $query,
                'scope' => $scope,
                'results_count' => $results->count(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [
            'query' => $query,
            'results' => $results->take($limit * 2)->values()->all(),
            'total' => $results->count(),
            'suggestions' => $this->suggestions($query),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function recentSearches(?User $user, int $limit = 5): array
    {
        if (! $user || ! Schema::hasTable('search_histories')) {
            return [];
        }

        return DB::table('search_histories')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->pluck('query')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function suggestions(?string $query = null): array
    {
        $base = Course::query()->published()->orderByDesc('views')->limit(5)->pluck('title')->all();

        if (! $query) {
            return $base;
        }

        return Course::query()
            ->published()
            ->where('title', 'like', '%'.$query.'%')
            ->limit(5)
            ->pluck('title')
            ->merge($base)
            ->unique()
            ->take(5)
            ->values()
            ->all();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function searchCourses(string $query, int $limit): Collection
    {
        return Course::query()
            ->published()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', '%'.$query.'%')
                    ->orWhere('description', 'like', '%'.$query.'%');
            })
            ->with('user:id,name')
            ->limit($limit)
            ->get()
            ->map(fn (Course $course) => [
                'type' => 'course',
                'title' => $course->title,
                'subtitle' => $course->user?->name ?? 'Professeur',
                'url' => route('courses.show', $course),
                'icon' => 'fa-book',
            ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function searchTeachers(string $query, int $limit): Collection
    {
        return User::query()
            ->where('role', 'professor')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%'.$query.'%')
                    ->orWhere('email', 'like', '%'.$query.'%')
                    ->orWhere('specialization', 'like', '%'.$query.'%');
            })
            ->limit($limit)
            ->get()
            ->map(fn (User $teacher) => [
                'type' => 'teacher',
                'title' => $teacher->name,
                'subtitle' => $teacher->specialization ?: 'Professeur',
                'url' => route('catalog.index').'?q='.urlencode($teacher->name),
                'icon' => 'fa-chalkboard-user',
            ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function searchCategories(string $query, int $limit): Collection
    {
        return Category::query()
            ->where('name', 'like', '%'.$query.'%')
            ->limit($limit)
            ->get()
            ->map(fn (Category $cat) => [
                'type' => 'category',
                'title' => $cat->name,
                'subtitle' => 'Catégorie',
                'url' => route('catalog.index').'?category='.$cat->slug,
                'icon' => 'fa-folder',
            ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function searchTestimonials(string $query, int $limit): Collection
    {
        return Testimonial::query()
            ->where('message', 'like', '%'.$query.'%')
            ->with('user:id,name')
            ->limit($limit)
            ->get()
            ->map(fn (Testimonial $t) => [
                'type' => 'testimonial',
                'title' => Str::limit($t->message, 60),
                'subtitle' => $t->user?->name ?? 'Étudiant',
                'url' => route('testimonials.index'),
                'icon' => 'fa-quote-left',
            ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function searchMessages(string $query, User $user, int $limit): Collection
    {
        if (! Schema::hasTable('messages')) {
            return collect();
        }

        return Message::query()
            ->where(fn ($q) => $q->where('from_user_id', $user->id)->orWhere('to_user_id', $user->id))
            ->where('body', 'like', '%'.$query.'%')
            ->with('course:id,title')
            ->limit($limit)
            ->get()
            ->map(fn (Message $m) => [
                'type' => 'message',
                'title' => Str::limit($m->body, 60),
                'subtitle' => $m->course?->title ?? 'Message',
                'url' => route('student.messages'),
                'icon' => 'fa-envelope',
            ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function searchLessons(string $query, User $user, int $limit): Collection
    {
        $courseIds = Enrollment::query()->where('user_id', $user->id)->pluck('course_id');

        return Lesson::query()
            ->whereIn('course_id', $courseIds)
            ->where('title', 'like', '%'.$query.'%')
            ->with('course:id,title')
            ->limit($limit)
            ->get()
            ->map(fn ($lesson) => [
                'type' => 'lesson',
                'title' => $lesson->title,
                'subtitle' => $lesson->course?->title ?? 'Leçon',
                'url' => route('courses.show', $lesson->course_id),
                'icon' => 'fa-play-circle',
            ]);
    }
}
