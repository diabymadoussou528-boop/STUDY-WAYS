<?php

namespace App\Services;

use App\Models\AiChatMessage;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\QuizAttempt;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class RecommendationService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function forStudent(User $student, int $limit = 6): array
    {
        $enrolledIds = Enrollment::query()->where('user_id', $student->id)->pluck('course_id');
        $completedIds = Enrollment::query()
            ->where('user_id', $student->id)
            ->where(function ($q) {
                $q->where('progress', '>=', 100)->orWhereNotNull('completed_at');
            })
            ->pluck('course_id');

        $categoryIds = Course::query()->whereIn('id', $enrolledIds)->pluck('category_id')->filter()->unique();
        $avgRatingByCourse = Review::query()
            ->whereIn('course_id', $enrolledIds)
            ->selectRaw('course_id, avg(rating) as avg_rating')
            ->groupBy('course_id')
            ->pluck('avg_rating', 'course_id');

        $aiTopics = Schema::hasTable('ai_chat_messages')
            ? AiChatMessage::query()
                ->where('user_id', $student->id)
                ->where('role', 'user')
                ->latest()
                ->limit(10)
                ->pluck('topic')
                ->filter()
                ->unique()
            : collect();

        $quizScores = Schema::hasTable('quiz_attempts')
            ? QuizAttempt::query()
                ->where('user_id', $student->id)
                ->where('status', 'submitted')
                ->avg('percentage')
            : null;

        $candidates = Course::query()
            ->published()
            ->whereNotIn('id', $enrolledIds)
            ->with(['user:id,name,avatar', 'category:id,name'])
            ->withCount('enrollments')
            ->withAvg('reviews', 'rating')
            ->get();

        return $candidates
            ->map(function (Course $course) use ($categoryIds, $completedIds, $aiTopics, $quizScores) {
                $score = 50;

                if ($categoryIds->contains($course->category_id)) {
                    $score += 25;
                }

                if ($course->reviews_avg_rating >= 4) {
                    $score += 15;
                }

                if ($course->enrollments_count > 5) {
                    $score += 10;
                }

                if ($aiTopics->isNotEmpty() && $aiTopics->contains(fn ($t) => str_contains(strtolower($course->title), strtolower((string) $t)))) {
                    $score += 20;
                }

                if ($quizScores !== null && $quizScores >= 80 && $course->difficulty === 'advanced') {
                    $score += 10;
                }

                if ($completedIds->isNotEmpty()) {
                    $score += 5;
                }

                return [
                    'course' => $course,
                    'confidence' => min(98, $score),
                    'reason' => $this->reasonFor($course, $categoryIds),
                ];
            })
            ->sortByDesc('confidence')
            ->take($limit)
            ->values()
            ->all();
    }

    private function reasonFor(Course $course, Collection $categoryIds): string
    {
        if ($categoryIds->contains($course->category_id)) {
            return 'Basé sur vos catégories suivies';
        }

        if (($course->reviews_avg_rating ?? 0) >= 4.5) {
            return 'Très bien noté par la communauté';
        }

        return 'Populaire sur StudyWays';
    }
}
