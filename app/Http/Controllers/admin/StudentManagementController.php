<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesProtectedAdminActions;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StudentManagementController extends Controller
{
    use HandlesProtectedAdminActions;

    public function index(): View
    {
        $query = User::query()->where('role', 'student');

        if ($search = request('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (request()->has('status') && request('status') !== '') {
            $query->where('is_active', request('status') === 'active');
        }

        $students = $query->withCount('enrollments')
            ->latest()
            ->paginate(15);

        return view('admin.students.index', compact('students'));
    }

    public function show(User $student): View
    {
        abort_unless($student->role === 'student', 404);

        $enrollments = Schema::hasTable('enrollments')
            ? Enrollment::query()
                ->where('user_id', $student->id)
                ->with('course.user:id,name')
                ->latest('enrolled_at')
                ->get()
            : collect();

        $inProgress = $enrollments->filter(fn ($e) => ! $e->isCompleted());
        $completed = $enrollments->filter(fn ($e) => $e->isCompleted());

        return view('admin.students.show', compact('student', 'enrollments', 'inProgress', 'completed'));
    }

    public function toggleStatus(User $student): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);

        if (! $this->canManageUser($student)) {
            return back()->with('error', 'Action non autorisée.');
        }

        return $this->protectedAction(
            'toggle_user_status',
            'Suspendre / activer '.$student->name,
            'Changement de statut pour l\'étudiant '.$student->email,
            $student,
            ['is_active' => ! $student->is_active],
            fn () => $student->update(['is_active' => ! $student->is_active]),
        );
    }

    public function destroy(User $student): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);

        if (! $this->canManageUser($student)) {
            return back()->with('error', 'Action non autorisée.');
        }

        return $this->protectedAction(
            'delete_user',
            'Supprimer l\'étudiant '.$student->name,
            'Suppression définitive du compte '.$student->email,
            $student,
            null,
            fn () => $student->delete(),
        );
    }
}
