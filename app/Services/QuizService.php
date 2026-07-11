<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\QuizQuestionType;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QuizService
{
    public function __construct(
        private NotificationDispatchService $notifications,
        private EnrollmentService $enrollments,
        private CertificateService $certificates,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createQuiz(User $teacher, Course $course, array $data): Quiz
    {
        abort_unless((int) $course->user_id === (int) $teacher->id, 403);

        return Quiz::query()->create([
            'course_id' => $course->id,
            'user_id' => $teacher->id,
            ...$data,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     */
    public function syncQuestions(Quiz $quiz, array $questions): void
    {
        $quiz->questions()->delete();

        foreach ($questions as $index => $question) {
            QuizQuestion::query()->create([
                'quiz_id' => $quiz->id,
                'type' => $question['type'],
                'question' => $question['question'],
                'options' => $question['options'] ?? null,
                'correct_answer' => $question['correct_answer'] ?? null,
                'points' => (int) ($question['points'] ?? 1),
                'sort_order' => $index,
            ]);
        }
    }

    public function startAttempt(User $student, Quiz $quiz): QuizAttempt
    {
        abort_unless($quiz->isAvailable(), 403, 'Ce quiz n\'est pas disponible.');

        $course = $quiz->course;
        if (! $this->enrollments->isEnrolled($student, $course)) {
            throw new RuntimeException('Inscrivez-vous au cours pour accéder au quiz.');
        }

        $attemptCount = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $student->id)
            ->where('status', '!=', 'in_progress')
            ->count();

        if ($attemptCount >= $quiz->max_attempts) {
            throw new RuntimeException('Nombre maximum de tentatives atteint.');
        }

        $inProgress = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $student->id)
            ->where('status', 'in_progress')
            ->first();

        if ($inProgress) {
            return $inProgress;
        }

        return QuizAttempt::query()->create([
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    /**
     * @param  array<int, string>  $answers  keyed by question_id
     */
    public function submitAttempt(QuizAttempt $attempt, array $answers): QuizAttempt
    {
        abort_unless($attempt->status === 'in_progress', 403);

        $quiz = $attempt->quiz()->with('questions')->firstOrFail();
        $totalPoints = max(1, (int) $quiz->questions->sum('points'));
        $earned = 0;

        DB::transaction(function () use ($attempt, $quiz, $answers, &$earned, $totalPoints) {
            foreach ($quiz->questions as $question) {
                $answer = $answers[$question->id] ?? '';
                $result = $this->gradeAnswer($question, $answer);
                $earned += $result['points'];

                QuizAttemptAnswer::query()->create([
                    'quiz_attempt_id' => $attempt->id,
                    'quiz_question_id' => $question->id,
                    'answer' => $answer,
                    'is_correct' => $result['is_correct'],
                    'points_awarded' => $result['points'],
                    'feedback' => $result['feedback'],
                    'graded_at' => $question->type->isAutoGraded() ? now() : null,
                ]);
            }

            $needsGrading = $quiz->questions->contains(
                fn (QuizQuestion $question) => ! $question->type->isAutoGraded(),
            );

            $percentage = (int) round(($earned / $totalPoints) * 100);
            $passed = ! $needsGrading && $percentage >= $quiz->passing_score;
            $timeSpent = $attempt->started_at ? now()->diffInSeconds($attempt->started_at) : 0;

            $attempt->update([
                'score' => $earned,
                'percentage' => $percentage,
                'passed' => $passed,
                'status' => $needsGrading ? 'pending_grading' : 'submitted',
                'submitted_at' => now(),
                'time_spent_seconds' => $timeSpent,
            ]);

            $student = $attempt->user ?? User::query()->findOrFail($attempt->user_id);

            if ($needsGrading && $quiz->user_id) {
                $this->notifications->notify(
                    User::query()->find($quiz->user_id),
                    'quiz_pending_grading',
                    'Correction requise — '.$quiz->title,
                    $student->name.' a soumis un quiz contenant des questions à corriger.',
                    ['quiz_id' => $quiz->id, 'attempt_id' => $attempt->id],
                );
            }

            if (! $needsGrading) {
                if ($passed) {
                    $this->unlockCertificateIfEligible($student, $quiz->course);
                }

                $this->notifications->notify(
                    $student,
                    'quiz_completed',
                    'Quiz terminé — '.$quiz->title,
                    'Score : '.$percentage.'%'.($passed ? ' — Réussi !' : ' — À retravailler.'),
                    ['quiz_id' => $quiz->id, 'attempt_id' => $attempt->id],
                );
            }
        });

        return $attempt->fresh(['answers.question']);
    }

    /**
     * @return array{is_correct: ?bool, points: int, feedback: ?string}
     */
    private function gradeAnswer(QuizQuestion $question, string $answer): array
    {
        if ($question->type === QuizQuestionType::Essay) {
            return ['is_correct' => null, 'points' => 0, 'feedback' => 'En attente de correction manuelle.'];
        }

        $correct = trim(strtolower((string) $question->correct_answer));
        $given = trim(strtolower($answer));
        $isCorrect = $correct === $given;

        if ($question->type === QuizQuestionType::MultipleChoice && ! $isCorrect) {
            $isCorrect = $correct === $given || str_contains($given, $correct);
        }

        return [
            'is_correct' => $isCorrect,
            'points' => $isCorrect ? (int) $question->points : 0,
            'feedback' => $isCorrect ? 'Bonne réponse.' : 'Réponse incorrecte.',
        ];
    }

    private function unlockCertificateIfEligible(User $student, Course $course): void
    {
        $enrollment = Enrollment::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->first();

        if (! $enrollment) {
            return;
        }

        $allQuizzesPassed = Quiz::query()
            ->where('course_id', $course->id)
            ->where('is_published', true)
            ->get()
            ->every(function (Quiz $quiz) use ($student) {
                return QuizAttempt::query()
                    ->where('quiz_id', $quiz->id)
                    ->where('user_id', $student->id)
                    ->where('passed', true)
                    ->exists();
            });

        if ($allQuizzesPassed || ! Quiz::query()->where('course_id', $course->id)->where('is_published', true)->exists()) {
            $enrollment->update([
                'progress' => 100,
                'status' => EnrollmentStatus::Completed,
                'completed_at' => now(),
                'certificate_eligible' => true,
            ]);
        }
    }

    /** @return Collection<int, QuizAttempt> */
    public function attemptsForStudent(User $student, Quiz $quiz): Collection
    {
        return QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $student->id)
            ->latest()
            ->get();
    }

    /** @return Collection<int, QuizAttempt> */
    public function attemptsForQuiz(Quiz $quiz): Collection
    {
        return QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('status', '!=', 'in_progress')
            ->with('student:id,name,email')
            ->latest('submitted_at')
            ->get();
    }

    public function pendingGradingCount(User $professor): int
    {
        return QuizAttempt::query()
            ->where('status', 'pending_grading')
            ->whereHas('quiz', fn ($q) => $q->where('user_id', $professor->id))
            ->count();
    }

    public function gradeManualAnswer(
        User $professor,
        QuizAttemptAnswer $answer,
        int $points,
        ?string $feedback = null,
    ): QuizAttempt {
        $attempt = $answer->attempt()->with(['quiz.course', 'answers.question'])->firstOrFail();
        $quiz = $attempt->quiz;

        abort_unless($quiz && (int) $quiz->user_id === (int) $professor->id, 403);
        abort_unless($answer->question && ! $answer->question->type->isAutoGraded(), 422);

        $maxPoints = (int) $answer->question->points;
        $points = max(0, min($points, $maxPoints));

        $answer->update([
            'points_awarded' => $points,
            'is_correct' => $points >= $maxPoints,
            'feedback' => $feedback ?? 'Réponse corrigée par le professeur.',
            'graded_at' => now(),
            'graded_by' => $professor->id,
        ]);

        return $this->recalculateAttemptScore($attempt->fresh(['answers.question']));
    }

    public function recalculateAttemptScore(QuizAttempt $attempt): QuizAttempt
    {
        $attempt->load(['answers.question', 'quiz', 'user']);
        $quiz = $attempt->quiz;
        $totalPoints = max(1, (int) $quiz->questions()->sum('points'));
        $earned = (int) $attempt->answers->sum('points_awarded');
        $hasUngraded = $attempt->answers->contains(fn ($answer) => $answer->graded_at === null);

        $percentage = (int) round(($earned / $totalPoints) * 100);
        $passed = ! $hasUngraded && $percentage >= $quiz->passing_score;

        $attempt->update([
            'score' => $earned,
            'percentage' => $percentage,
            'passed' => $passed,
            'status' => $hasUngraded ? 'pending_grading' : 'submitted',
        ]);

        if (! $hasUngraded) {
            $student = $attempt->user;

            if ($student && $passed) {
                $this->unlockCertificateIfEligible($student, $quiz->course);
            }

            if ($student) {
                $this->notifications->notify(
                    $student,
                    'quiz_graded',
                    'Quiz corrigé — '.$quiz->title,
                    'Votre score final est de '.$percentage.'%'.($passed ? ' — Réussi !' : ' — À retravailler.'),
                    ['quiz_id' => $quiz->id, 'attempt_id' => $attempt->id],
                );
            }
        }

        return $attempt->fresh(['answers.question', 'student']);
    }

    public function assertProfessorOwnsQuiz(User $professor, Quiz $quiz): void
    {
        abort_unless((int) $quiz->user_id === (int) $professor->id, 403);
    }
}
