<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $courses = Course::query()
            ->with('user:id,name')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->with([
                'reviews' => function ($query) use ($userId) {
                    $query->where('user_id', $userId)->select('id', 'course_id', 'rating', 'comment');
                },
            ])
            ->latest()
            ->get();

        return view('student.dashboard', compact('courses'));
    }
}
