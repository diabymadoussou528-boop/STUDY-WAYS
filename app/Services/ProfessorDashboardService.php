<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Appointment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Message;
use App\Models\Review;
use App\Models\User;

class ProfessorDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboardPayload(User $professor): array
    {
        $courses = Course::query()
            ->where('user_id', $professor->id)
            ->withCount(['lessons', 'reviews', 'enrollments'])
            ->withAvg('reviews', 'rating')
            ->latest()
            ->get();

        $courseIds = $courses->pluck('id');

        $totalStudents = $courseIds->isEmpty()
            ? 0
            : Enrollment::query()
                ->whereIn('course_id', $courseIds)
                ->where('status', EnrollmentStatus::Active)
                ->pluck('user_id')
                ->unique()
                ->count();
        $totalReviews = $courses->sum('reviews_count');
        $totalViews = (int) $courses->sum('views');
        $globalAverageRating = $courses->avg('reviews_avg_rating');

        $pendingAppointmentsCount = Appointment::query()
            ->where('professor_id', $professor->id)
            ->where('status', AppointmentStatus::Pending)
            ->count();

        $pendingAppointments = Appointment::query()
            ->where('professor_id', $professor->id)
            ->where('status', AppointmentStatus::Pending)
            ->with(['student:id,name,avatar', 'course:id,title'])
            ->latest()
            ->limit(5)
            ->get();

        $recentMessages = Message::query()
            ->where(function ($query) use ($professor) {
                $query->where('from_user_id', $professor->id)
                    ->orWhere('to_user_id', $professor->id);
            })
            ->with(['sender:id,name,avatar', 'recipient:id,name,avatar', 'course:id,title'])
            ->latest()
            ->limit(6)
            ->get();

        $recentReviews = Review::query()
            ->whereIn('course_id', $courses->pluck('id'))
            ->with(['user:id,name,avatar', 'course:id,title'])
            ->latest()
            ->limit(6)
            ->get();

        return [
            'heroStats' => [
                ['key' => 'courses', 'label' => 'Mes cours', 'value' => $courses->count(), 'icon' => 'fa-book', 'delta' => null, 'deltaDirection' => 'up', 'sparkline' => []],
                ['key' => 'students', 'label' => 'Étudiants', 'value' => $totalStudents, 'icon' => 'fa-user-graduate', 'delta' => null, 'deltaDirection' => 'up', 'sparkline' => []],
                ['key' => 'rating', 'label' => 'Note moyenne', 'value' => number_format((float) ($globalAverageRating ?? 0), 1), 'icon' => 'fa-star', 'delta' => null, 'deltaDirection' => 'up', 'sparkline' => []],
                ['key' => 'views', 'label' => 'Vues totales', 'value' => $totalViews, 'icon' => 'fa-eye', 'delta' => null, 'deltaDirection' => 'up', 'sparkline' => []],
                ['key' => 'appointments', 'label' => 'RDV en attente', 'value' => $pendingAppointmentsCount, 'icon' => 'fa-calendar', 'delta' => null, 'deltaDirection' => 'up', 'sparkline' => []],
            ],
            'courses' => $courses,
            'pendingAppointments' => $pendingAppointments,
            'recentMessages' => $recentMessages,
            'recentReviews' => $recentReviews,
            'totalLessons' => $courses->sum('lessons_count'),
            'totalReviews' => $totalReviews,
        ];
    }
}
