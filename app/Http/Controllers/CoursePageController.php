<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CoursePageController extends Controller
{
    public function show(string $slug): View|RedirectResponse
    {
        $course = Course::query()->where('slug', $slug)->first();

        if ($course) {
            return redirect()->route('courses.show', $course);
        }

        abort(404);
    }
}
