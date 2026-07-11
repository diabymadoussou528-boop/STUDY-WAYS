<?php

namespace App\Http\Controllers\Student;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Course;
use App\Services\AppointmentService;
use App\Services\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class AppointmentController extends Controller
{
    public function index(EnrollmentService $enrollmentService): View
    {
        $student = auth()->user();

        $appointments = Appointment::query()
            ->where('student_id', $student->id)
            ->with(['professor:id,name,avatar', 'course:id,title'])
            ->latest('scheduled_at')
            ->get();

        $enrolledCourses = $student->enrollments()
            ->with(['course.user:id,name', 'course:id,title,user_id'])
            ->where('status', EnrollmentStatus::Active)
            ->get()
            ->pluck('course')
            ->filter();

        return view('student.appointments', compact('appointments', 'enrolledCourses'));
    }

    public function store(Request $request, AppointmentService $service): RedirectResponse
    {
        $this->authorize('create', Appointment::class);

        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $course = Course::query()->findOrFail($validated['course_id']);

        try {
            $service->request(auth()->user(), $course, $validated);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Demande de rendez-vous envoyée.');
    }

    public function cancel(Appointment $appointment, AppointmentService $service): RedirectResponse
    {
        $this->authorize('cancel', $appointment);

        try {
            $service->cancel($appointment, auth()->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Rendez-vous annulé.');
    }
}
