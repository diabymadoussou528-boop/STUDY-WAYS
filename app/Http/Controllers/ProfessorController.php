<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Support\Facades\Auth;

class ProfessorController extends Controller
{
    /**
     * Dashboard professeur
     */
    public function index()
    {
        $userId = Auth::id();

        // Récupérer les cours du professeur connecté avec statistiques utiles.
        $courses = Course::where('user_id', $userId)
            ->withCount('lessons')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->latest()
            ->get();

        $totalCourses = $courses->count();
        $totalLessons = $courses->sum('lessons_count');
        $totalReviews = $courses->sum('reviews_count');
        $totalViews = $courses->sum('views');
        $globalAverageRating = $courses->avg('reviews_avg_rating');

        return view('professor.dashboard', compact(
            'courses',
            'totalCourses',
            'totalLessons',
            'totalReviews',
            'totalViews',
            'globalAverageRating'
        ));
    }
}
