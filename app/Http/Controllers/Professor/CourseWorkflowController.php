<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\CoursePublishingService;
use Illuminate\Http\RedirectResponse;

class CourseWorkflowController extends Controller
{
    public function submitForReview(Course $course, CoursePublishingService $service): RedirectResponse
    {
        abort_unless((int) $course->user_id === (int) auth()->id(), 403);

        $service->submitForReview($course, auth()->user());

        return back()->with('success', 'Cours soumis pour revue.');
    }
}
