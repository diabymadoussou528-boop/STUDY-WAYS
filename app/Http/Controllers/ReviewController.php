<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Review;
use App\Services\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Course $course, EnrollmentService $enrollmentService): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user?->isStudent(), 403);
        abort_unless($course->isPublished(), 404);
        abort_unless($enrollmentService->isEnrolled($user, $course), 403);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        Review::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $course->id,
            ],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        return back()->with('success', 'Merci pour votre avis !');
    }
}
