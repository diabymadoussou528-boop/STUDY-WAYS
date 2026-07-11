<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Services\CourseShowService;
use App\Services\EnrollmentService;
use App\Services\LearningProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseLearnController extends Controller
{
    public function show(
        Course $course,
        CourseShowService $courseShowService,
        ?Lesson $lesson = null,
    ): View|RedirectResponse {
        if (! $course->isPublished() && ! auth()->user()?->isAdmin() && (int) $course->user_id !== (int) auth()->id()) {
            abort(404);
        }

        $user = auth()->user();
        $payload = $courseShowService->build($course, $user);

        if (! $payload['canAccessFullContent'] && ! $lesson?->is_preview) {
            return redirect()
                ->route('courses.show', $course)
                ->with('error', 'Inscrivez-vous au cours pour accéder au contenu complet.');
        }

        $course->load(['modules.lessons', 'lessons']);

        $allLessons = $course->modules->isNotEmpty()
            ? $course->modules->flatMap->lessons
            : $course->lessons;

        if ($allLessons->isEmpty()) {
            return redirect()
                ->route('courses.show', $course)
                ->with('error', 'Ce cours ne contient pas encore de leçons.');
        }

        $currentLesson = $lesson ?? $allLessons->first();

        if (! $currentLesson || (int) $currentLesson->course_id !== (int) $course->id) {
            abort(404);
        }

        if (! $currentLesson->isAccessibleBy($user, $payload['isEnrolled'])) {
            return redirect()
                ->route('courses.show', $course)
                ->with('error', 'Cette leçon nécessite une inscription active.');
        }

        if ($payload['activeEnrollment']) {
            $payload['activeEnrollment']->update(['last_accessed_at' => now()]);
        }

        return view('courses.learn', array_merge($payload, [
            'currentLesson' => $currentLesson,
            'allLessons' => $allLessons,
        ]));
    }

    public function complete(
        Request $request,
        Course $course,
        Lesson $lesson,
        LearningProgressService $progressService,
        EnrollmentService $enrollmentService,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user || ! $user->isStudent()) {
            abort(403);
        }

        if ((int) $lesson->course_id !== (int) $course->id) {
            abort(404);
        }

        if (! $enrollmentService->isEnrolled($user, $course) && ! $lesson->is_preview) {
            abort(403);
        }

        $progressService->recordLessonComplete($user, $lesson, (int) $request->input('seconds', 0));

        return back()->with('success', 'Leçon marquée comme terminée.');
    }
}
