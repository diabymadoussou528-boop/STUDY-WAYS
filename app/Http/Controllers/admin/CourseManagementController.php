<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourseStatus;
use App\Http\Controllers\Concerns\HandlesProtectedAdminActions;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\CoursePublishingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourseManagementController extends Controller
{
    use HandlesProtectedAdminActions;

    public function __construct(
        private CoursePublishingService $publishing,
    ) {}

    public function index(): View
    {
        $courses = Course::query()
            ->with('user:id,name,avatar')
            ->withCount(['enrollments', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->latest()
            ->paginate(12);

        return view('admin.courses.index', compact('courses'));
    }

    public function destroy(Course $course): RedirectResponse
    {
        return $this->protectedAction(
            'delete_course',
            'Supprimer le cours « '.$course->title.' »',
            'Suppression définitive du cours et de son contenu associé.',
            $course,
            null,
            fn () => $course->delete(),
        );
    }

    public function updateStatus(Course $course, string $status): RedirectResponse
    {
        $allowed = array_map(fn (CourseStatus $s) => $s->value, CourseStatus::cases());

        if (! in_array($status, $allowed, true)) {
            return back()->with('error', 'Statut invalide.');
        }

        return $this->protectedAction(
            'update_course_status',
            'Changer le statut de « '.$course->title.' »',
            'Nouveau statut : '.$status,
            $course,
            ['status' => $status],
            function () use ($course, $status) {
                if ($status === CourseStatus::Published->value) {
                    $this->publishing->publish($course, auth()->user());
                } elseif ($status === CourseStatus::Archived->value) {
                    $this->publishing->archive($course);
                } else {
                    $course->update(['status' => $status]);
                }
            },
        );
    }

    public function publish(Course $course): RedirectResponse
    {
        return $this->protectedAction(
            'publish_course',
            'Publier « '.$course->title.' »',
            'Le cours sera visible dans le catalogue.',
            $course,
            null,
            fn () => $this->publishing->publish($course, auth()->user()),
        );
    }

    public function duplicate(Course $course): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $copy = $this->publishing->duplicate($course);

        return redirect()
            ->route('admin.courses')
            ->with('success', 'Cours dupliqué : '.$copy->title);
    }
}
