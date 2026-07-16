<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RelatedCourseService
{
    public function __construct(private CategoryService $categoryService) {}

    /**
     * @return Collection<int, Course>
     */
    public function for(Course $course, int $limit = 6): Collection
    {
        $tags = $this->courseKeywords($course);

        $candidates = Course::query()
            ->published()
            ->where('id', '!=', $course->id)
            ->with(['user:id,name', 'category:id,name'])
            ->withCount(['enrollments', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->latest('published_at')
            ->limit(40)
            ->get();

        return $candidates
            ->map(function (Course $candidate) use ($course, $tags) {
                return [
                    'course' => $candidate,
                    'score' => $this->score($course, $candidate, $tags),
                ];
            })
            ->filter(fn (array $row) => $row['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->pluck('course')
            ->values();
    }

    /**
     * @param  list<string>  $sourceTags
     */
    private function score(Course $source, Course $candidate, array $sourceTags): int
    {
        $score = 0;

        if ($source->category_id && $source->category_id === $candidate->category_id) {
            $score += 50;
        }

        $sourceCategory = $this->categoryService->normalize((string) ($source->category?->name ?? ''));
        $candidateCategory = $this->categoryService->normalize((string) ($candidate->category?->name ?? ''));

        if ($sourceCategory !== '' && $candidateCategory !== '') {
            if ($sourceCategory === $candidateCategory) {
                $score += 20;
            } elseif (Str::contains($candidateCategory, $sourceCategory) || Str::contains($sourceCategory, $candidateCategory)) {
                $score += 15;
            } elseif (similar_text($sourceCategory, $candidateCategory) / max(strlen($sourceCategory), 1) > 0.6) {
                $score += 10;
            }
        }

        $candidateTags = $this->courseKeywords($candidate);
        $overlap = count(array_intersect($sourceTags, $candidateTags));
        $score += $overlap * 8;

        if ($source->difficulty && $candidate->difficulty && strcasecmp($source->difficulty, $candidate->difficulty) === 0) {
            $score += 5;
        }

        return $score;
    }

    /**
     * @return list<string>
     */
    private function courseKeywords(Course $course): array
    {
        $parts = [
            $course->title,
            $course->short_description,
            $course->meta_keywords,
            $course->category?->name,
        ];

        if (is_array($course->objectives)) {
            $parts = array_merge($parts, $course->objectives);
        }

        $raw = implode(' ', array_filter($parts));

        return collect(preg_split('/[\s,;|]+/u', Str::lower($raw)) ?: [])
            ->map(fn (string $word) => trim($word))
            ->filter(fn (string $word) => mb_strlen($word) >= 3)
            ->unique()
            ->values()
            ->all();
    }
}
