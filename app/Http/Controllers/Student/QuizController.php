<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(): View
    {
        $student = auth()->user();
        $courseIds = Enrollment::query()->where('user_id', $student->id)->pluck('course_id');

        $quizzes = Quiz::query()
            ->whereIn('course_id', $courseIds)
            ->where('is_published', true)
            ->with('course:id,title')
            ->latest()
            ->paginate(12);

        return view('student.quizzes.index', compact('quizzes'));
    }

    public function show(Quiz $quiz, QuizService $service): View|RedirectResponse
    {
        try {
            $attempt = $service->startAttempt(auth()->user(), $quiz);
        } catch (\RuntimeException $e) {
            return redirect()->route('student.quizzes.index')->with('error', $e->getMessage());
        }

        $quiz->load(['questions', 'course']);

        return view('student.quizzes.show', compact('quiz', 'attempt'));
    }

    public function submit(Request $request, Quiz $quiz, QuizService $service): RedirectResponse
    {
        $attempt = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', auth()->id())
            ->where('status', 'in_progress')
            ->first();

        if (! $attempt) {
            try {
                $attempt = $service->startAttempt(auth()->user(), $quiz);
            } catch (\RuntimeException $exception) {
                return redirect()->route('student.quizzes.index')->with('error', $exception->getMessage());
            }
        }

        $answers = $request->input('answers', []);
        $service->submitAttempt($attempt, $answers);

        return redirect()->route('student.quizzes.result', $attempt);
    }

    public function result(QuizAttempt $attempt): View
    {
        abort_unless((int) $attempt->user_id === (int) auth()->id(), 403);
        $attempt->load(['quiz.course', 'answers.question']);

        return view('student.quizzes.result', compact('attempt'));
    }
}
