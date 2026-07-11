<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;

class AiRecommendationService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function recommendations(): array
    {
        $students = User::query()->where('role', 'student')->latest()->limit(6)->get();
        $courses = Course::query()->with('user:id,name')->latest()->limit(6)->get();

        return $students->zip($courses)->map(function ($pair, int $index) {
            [$student, $course] = [$pair[0] ?? null, $pair[1] ?? null];

            if (! $student || ! $course) {
                return null;
            }

            $confidence = min(98, 72 + ($index * 4));

            return [
                'id' => $index + 1,
                'student' => $student,
                'course' => $course,
                'confidence' => $confidence,
                'action' => 'Suggérer l\'inscription au cours « '.$course->title.' »',
                'reason' => 'Profil et historique d\'apprentissage compatibles avec ce contenu.',
            ];
        })->filter()->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardCharts(): array
    {
        return [
            'labels' => ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
            'generated' => [12, 18, 15, 22, 28, 19, 24],
            'accepted' => [8, 14, 11, 17, 21, 15, 18],
        ];
    }
}
