<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class EnrollmentController extends Controller
{
    public function confirm(Course $course, EnrollmentService $service): View|RedirectResponse
    {
        $student = auth()->user();
        $enrolled = $service->isEnrolled($student, $course);

        $course->load(['user:id,name,avatar', 'category:id,name', 'lessons'])->loadCount('enrollments');

        return view('student.enrollment.confirm', compact('course', 'enrolled'));
    }

    public function store(Course $course, EnrollmentService $service): RedirectResponse
    {
        $this->authorize('create', Enrollment::class);

        if (! $course->isFree()) {
            return redirect()->route('student.checkout.course', $course);
        }

        try {
            $enrollment = $service->enroll(auth()->user(), $course);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('student.enrollment.success', $enrollment)
            ->with('success', 'Inscription confirmée !');
    }

    public function success(Enrollment $enrollment): View
    {
        $this->authorize('view', $enrollment);

        $enrollment->load('course.user:id,name');

        return view('student.enrollment.success', compact('enrollment'));
    }

    public function destroy(Enrollment $enrollment, EnrollmentService $service): RedirectResponse
    {
        $this->authorize('delete', $enrollment);

        try {
            $service->cancel(auth()->user(), $enrollment);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Inscription annulée.');
    }
}
