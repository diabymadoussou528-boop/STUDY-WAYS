<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiTutorChatRequest;
use App\Services\AiTutorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class AiTutorController extends Controller
{
    public function index(AiTutorService $service): View
    {
        $student = auth()->user();
        $courses = $service->enrolledCoursesFor($student)->map(function ($course) {
            $course->setAttribute('lessons_json', $course->lessons->map(fn ($l) => [
                'id' => $l->id,
                'title' => $l->title,
            ])->values());

            return $course;
        });
        $history = $service->chatHistory($student);

        $suggestedQuestions = [
            'Explique-moi ce concept avec un exemple simple.',
            'Peux-tu me résumer la leçon ?',
            'Donne-moi un exercice pratique.',
            'Corrige ma réponse et explique pourquoi.',
            'Quelles sont les étapes pour progresser ?',
        ];

        return view('student.ai-tutor', [
            'courses' => $courses,
            'history' => $history,
            'suggestedQuestions' => $suggestedQuestions,
            'isPremium' => (bool) $student->is_premium,
        ]);
    }

    public function chat(AiTutorChatRequest $request, AiTutorService $service): JsonResponse
    {
        try {
            $result = $service->chat(
                $request->user(),
                $request->validated('message'),
                $request->validated('course_id'),
                $request->validated('lesson_id'),
                $request->validated('topic'),
                $request->validated('mode') ?? 'chat',
            );
        } catch (RuntimeException $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        return response()->json([
            'reply' => $result['reply'],
            'message_id' => $result['message_id'],
        ]);
    }

    public function clear(Request $request, AiTutorService $service): JsonResponse
    {
        $service->clearHistory(
            $request->user(),
            $request->integer('course_id') ?: null,
        );

        return response()->json(['success' => true]);
    }

    public function history(Request $request, AiTutorService $service): JsonResponse
    {
        $history = $service->chatHistory(
            $request->user(),
            $request->integer('course_id') ?: null,
        );

        return response()->json(['messages' => $history]);
    }
}
