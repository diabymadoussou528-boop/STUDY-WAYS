<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CourseSearchService
{
    /**
     * @return array<string, mixed>
     */
    public function search(string $query, ?User $user = null, int $perGroup = 20): array
    {
        $query = trim($query);

        if ($query === '') {
            return $this->emptyPayload('');
        }

        if (mb_strlen($query) < 2) {
            return $this->emptyPayload($query, 'Entrez au moins 2 caractères pour rechercher.');
        }

        $terms = $this->extractTerms($query);
        $courses = $this->matchingCourses($terms, $query);

        if ($courses->isEmpty()) {
            $this->recordHistory($user, $query, 0);

            return $this->emptyPayload($query);
        }

        $groups = $this->groupCourses($courses, $query, $terms, $perGroup);
        $this->recordHistory($user, $query, $courses->count());

        return [
            'query' => $query,
            'total' => $courses->count(),
            'groups' => $groups,
            'matched_categories' => $courses->pluck('category.name')->filter()->unique()->values()->all(),
            'suggestions' => $this->suggestions($query),
        ];
    }

    /**
     * Lightweight payload for autocomplete dropdowns.
     *
     * @return array<string, mixed>
     */
    public function preview(string $query, int $limit = 6): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return ['query' => $query, 'results' => [], 'total' => 0];
        }

        $terms = $this->extractTerms($query);
        $courses = $this->matchingCourses($terms, $query, $limit);

        return [
            'query' => $query,
            'total' => $courses->count(),
            'results' => $courses->map(fn (Course $course) => $this->formatCourse($course))->values()->all(),
            'results_url' => route('courses.search', ['q' => $query]),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function extractTerms(string $query): array
    {
        return collect(preg_split('/[\s,;]+/', mb_strtolower($query), -1, PREG_SPLIT_NO_EMPTY))
            ->filter(fn (string $term) => mb_strlen($term) >= 2)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $terms
     * @return Collection<int, Course>
     */
    private function matchingCourses(array $terms, string $rawQuery, ?int $limit = null): Collection
    {
        $needle = mb_strtolower($rawQuery);

        $builder = Course::query()
            ->published()
            ->with(['user:id,name,avatar,specialization', 'category:id,name,slug'])
            ->withCount(['reviews', 'enrollments'])
            ->withAvg('reviews', 'rating')
            ->where(function (Builder $outer) use ($terms, $needle) {
                foreach ($terms as $term) {
                    $like = '%'.$term.'%';
                    $outer->orWhere(function (Builder $q) use ($like) {
                        $q->whereRaw('LOWER(title) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(COALESCE(short_description, "")) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(description) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(COALESCE(meta_keywords, "")) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(COALESCE(meta_title, "")) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(COALESCE(difficulty, "")) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(COALESCE(objectives, "")) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(COALESCE(requirements, "")) LIKE ?', [$like])
                            ->orWhereHas('category', fn (Builder $cat) => $cat->whereRaw('LOWER(name) LIKE ?', [$like]))
                            ->orWhereHas('user', fn (Builder $user) => $user
                                ->where('role', 'professor')
                                ->where(function (Builder $prof) use ($like) {
                                    $prof->whereRaw('LOWER(name) LIKE ?', [$like])
                                        ->orWhereRaw('LOWER(COALESCE(specialization, "")) LIKE ?', [$like]);
                                }));
                    });
                }

                if (mb_strlen($needle) >= 2) {
                    $like = '%'.$needle.'%';
                    $outer->orWhereRaw('LOWER(title) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(COALESCE(meta_keywords, "")) LIKE ?', [$like]);
                }
            });

        $courses = $builder->get();

        return $courses
            ->map(function (Course $course) use ($terms, $needle) {
                $course->search_score = $this->relevanceScore($course, $terms, $needle);

                return $course;
            })
            ->sortByDesc('search_score')
            ->values()
            ->when($limit, fn (Collection $c) => $c->take($limit));
    }

    /**
     * @param  array<int, string>  $terms
     */
    private function relevanceScore(Course $course, array $terms, string $needle): int
    {
        $score = 0;
        $title = mb_strtolower($course->title);
        $keywords = mb_strtolower((string) ($course->meta_keywords ?? ''));
        $category = mb_strtolower((string) ($course->category?->name ?? ''));

        if ($title === $needle) {
            $score += 120;
        } elseif (str_starts_with($title, $needle)) {
            $score += 90;
        } elseif (str_contains($title, $needle)) {
            $score += 70;
        }

        foreach ($terms as $term) {
            if (str_contains($title, $term)) {
                $score += 35;
            }
            if (str_contains($keywords, $term)) {
                $score += 30;
            }
            if (str_contains($category, $term)) {
                $score += 25;
            }
            if (str_contains(mb_strtolower((string) ($course->user?->specialization ?? '')), $term)) {
                $score += 20;
            }
            if (str_contains(mb_strtolower((string) $course->description), $term)) {
                $score += 10;
            }
        }

        $score += min(20, (int) ($course->enrollments_count ?? 0));
        $score += (int) round(((float) ($course->reviews_avg_rating ?? 0)) * 4);

        return $score;
    }

    /**
     * @param  Collection<int, Course>  $courses
     * @param  array<int, string>  $terms
     * @return array<int, array<string, mixed>>
     */
    private function groupCourses(Collection $courses, string $query, array $terms, int $perGroup): array
    {
        $grouped = $courses->groupBy(fn (Course $course) => $course->category_id ?? 0);

        return $grouped
            ->map(function (Collection $items, int|string $categoryId) use ($query, $terms, $perGroup) {
                /** @var Course $first */
                $first = $items->first();
                $categoryName = $first->category?->name ?? 'Autres';

                return [
                    'category_id' => (int) $categoryId ?: null,
                    'category_name' => $categoryName,
                    'category_slug' => $first->category?->slug,
                    'heading' => $this->groupHeading($categoryName, $query, $terms),
                    'courses' => $items
                        ->sortByDesc('search_score')
                        ->take($perGroup)
                        ->map(fn (Course $course) => $this->formatCourse($course))
                        ->values()
                        ->all(),
                    'count' => $items->count(),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $terms
     */
    private function groupHeading(string $categoryName, string $query, array $terms): string
    {
        $related = Category::query()
            ->where(function (Builder $q) use ($terms, $query) {
                foreach ($terms as $term) {
                    $q->orWhereRaw('LOWER(name) LIKE ?', ['%'.$term.'%']);
                }
                $q->orWhereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($query).'%']);
            })
            ->orderBy('name')
            ->limit(3)
            ->pluck('name')
            ->unique()
            ->values();

        if ($related->count() >= 2) {
            return 'Les meilleurs cours dans les catégories '.$related->implode(' et ');
        }

        if ($related->count() === 1 && $related->first() !== $categoryName) {
            return 'Les meilleurs cours dans les catégories '.$related->first().' et '.$categoryName;
        }

        return 'Les meilleurs cours dans la catégorie '.$categoryName;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCourse(Course $course): array
    {
        $rating = round((float) ($course->reviews_avg_rating ?? 0), 1);
        $reviewsCount = (int) ($course->reviews_count ?? 0);
        $enrollments = (int) ($course->enrollments_count ?? 0);
        $isNew = $course->published_at && $course->published_at->gte(now()->subDays(30));

        return [
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'url' => route('courses.show', $course),
            'thumbnail' => $course->thumbnailUrl(),
            'instructors' => $course->user?->name ?? 'StudyWays',
            'category' => $course->category?->name,
            'description' => Str::limit(strip_tags($course->short_description ?? $course->description ?? ''), 160),
            'rating' => $rating,
            'reviews_count' => $reviewsCount,
            'enrollments_count' => $enrollments,
            'price' => (float) $course->price,
            'price_label' => $course->isFree()
                ? 'Gratuit'
                : number_format((float) $course->price, 0, ',', ' ').' XOF',
            'is_free' => $course->isFree(),
            'is_premium' => (bool) $course->is_premium_only,
            'is_bestseller' => $enrollments >= 10,
            'is_new' => (bool) $isNew,
            'badges' => array_values(array_filter([
                $course->is_premium_only ? 'Premium' : null,
                $enrollments >= 10 ? 'Best-seller' : null,
                $isNew ? 'Nouveau' : null,
            ])),
            'technologies' => $this->technologyTags($course),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function technologyTags(Course $course): array
    {
        $tags = collect();

        if ($course->meta_keywords) {
            $tags = $tags->merge(preg_split('/[,;|]+/', $course->meta_keywords) ?: []);
        }

        if ($course->category?->name) {
            $tags->push($course->category->name);
        }

        if ($course->difficulty) {
            $tags->push(ucfirst($course->difficulty));
        }

        return $tags
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->unique()
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(string $query, ?string $message = null): array
    {
        return [
            'query' => $query,
            'total' => 0,
            'groups' => [],
            'matched_categories' => [],
            'suggestions' => $this->suggestions($query),
            'message' => $message ?? 'Aucun cours trouvé pour « '.$query.' ».',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function suggestions(?string $query = null): array
    {
        return Course::query()
            ->published()
            ->when($query, fn (Builder $q) => $q->whereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($query).'%']))
            ->orderByDesc('views')
            ->limit(6)
            ->pluck('title')
            ->all();
    }

    private function recordHistory(?User $user, string $query, int $count): void
    {
        if (! $user || ! Schema::hasTable('search_histories')) {
            return;
        }

        DB::table('search_histories')->insert([
            'user_id' => $user->id,
            'query' => $query,
            'scope' => 'courses',
            'results_count' => $count,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
