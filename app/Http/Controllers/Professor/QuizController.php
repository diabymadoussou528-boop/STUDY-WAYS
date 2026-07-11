<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Services\QuizService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(): View
    {
        $quizzes = Quiz::query()
            ->where('user_id', auth()->id())
            ->with('course:id,title')
            ->withCount('questions', 'attempts')
            ->latest()
            ->paginate(12);

        return view('professor.quizzes.index', compact('quizzes'));
    }

    public function create(Course $course): View
    {
        abort_unless((int) $course->user_id === (int) auth()->id(), 403);

        return view('professor.quizzes.create', compact('course'));
    }

    public function store(Request $request, Course $course, QuizService $service): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
            'randomize_questions' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.type' => ['required', 'in:multiple_choice,true_false,short_answer,essay'],
            'questions.*.question' => ['required', 'string'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.correct_answer' => ['nullable', 'string'],
            'questions.*.points' => ['nullable', 'integer', 'min:1'],
        ]);

        $quiz = $service->createQuiz(auth()->user(), $course, [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
            'max_attempts' => $validated['max_attempts'],
            'passing_score' => $validated['passing_score'],
            'randomize_questions' => (bool) ($validated['randomize_questions'] ?? false),
            'is_published' => (bool) ($validated['is_published'] ?? false),
        ]);

        $service->syncQuestions($quiz, $validated['questions']);

        return redirect()->route('professor.quizzes.index')->with('success', 'Quiz créé avec succès.');
    }

    public function attempts(Quiz $quiz, QuizService $service): View
    {
        $service->assertProfessorOwnsQuiz(auth()->user(), $quiz);

        $quiz->load('course:id,title');
        $attempts = $service->attemptsForQuiz($quiz);

        return view('professor.quizzes.attempts', compact('quiz', 'attempts'));
    }

    public function showAttempt(QuizAttempt $attempt, QuizService $service): View
    {
        $attempt->load(['quiz.course', 'student', 'answers.question']);

        $service->assertProfessorOwnsQuiz(auth()->user(), $attempt->quiz);

        return view('professor.quizzes.grade', compact('attempt'));
    }

    public function gradeAnswer(Request $request, QuizAttemptAnswer $answer, QuizService $service): RedirectResponse
    {
        $validated = $request->validate([
            'points' => ['required', 'integer', 'min:0'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->gradeManualAnswer(
            auth()->user(),
            $answer,
            (int) $validated['points'],
            $validated['feedback'] ?? null,
        );

        return back()->with('success', 'Réponse corrigée avec succès.');
    }
}
