<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesProtectedAdminActions;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Review;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeacherManagementController extends Controller
{
    use HandlesProtectedAdminActions;

    public function index(): View
    {
        $teachers = User::query()
            ->where('role', 'professor')
            ->withCount('taughtCourses')
            ->latest()
            ->paginate(15);

        return view('admin.teachers.index', compact('teachers'));
    }

    public function show(User $teacher): View
    {
        abort_unless($teacher->role === 'professor', 404);

        $courses = Course::query()
            ->where('user_id', $teacher->id)
            ->withCount(['enrollments', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->latest()
            ->get();

        $reviews = Review::query()
            ->whereIn('course_id', $courses->pluck('id'))
            ->with(['user:id,name,avatar', 'course:id,title'])
            ->latest()
            ->limit(10)
            ->get();

        $testimonials = Testimonial::query()
            ->where('user_id', $teacher->id)
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.teachers.show', compact('teacher', 'courses', 'reviews', 'testimonials'));
    }

    public function toggleStatus(User $teacher): RedirectResponse
    {
        abort_unless($teacher->role === 'professor', 404);

        if (! $this->canManageUser($teacher)) {
            return back()->with('error', 'Action non autorisée.');
        }

        return $this->protectedAction(
            'toggle_user_status',
            'Suspendre / activer '.$teacher->name,
            'Changement de statut pour le professeur '.$teacher->email,
            $teacher,
            ['is_active' => ! $teacher->is_active],
            fn () => $teacher->update(['is_active' => ! $teacher->is_active]),
        );
    }

    public function destroy(User $teacher): RedirectResponse
    {
        abort_unless($teacher->role === 'professor', 404);

        if (! $this->canManageUser($teacher)) {
            return back()->with('error', 'Action non autorisée.');
        }

        return $this->protectedAction(
            'delete_user',
            'Supprimer le professeur '.$teacher->name,
            'Suppression définitive du compte '.$teacher->email,
            $teacher,
            null,
            fn () => $teacher->delete(),
        );
    }
}
