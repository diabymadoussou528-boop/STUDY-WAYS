<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiTutorChatRequest;
use App\Models\AiConversation;
use App\Services\AiTutorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class AiTutorController extends Controller
{
    public function index(Request $request, AiTutorService $service): View
    {
        $student = auth()->user();
        $courses = $service->enrolledCoursesFor($student)->map(function ($course) {
            $course->setAttribute('lessons_json', $course->lessons->map(fn ($l) => [
                'id' => $l->id,
                'title' => $l->title,
            ])->values());

            return $course;
        });

        $conversations = $service->getConversations($student, $request->string('search') ?: null);
        $activeConversationId = $request->integer('conversation_id') ?: ($conversations->first()?->id ?? null);

        $history = $activeConversationId
            ? $service->chatHistory($student, null, $activeConversationId)
            : [];

        $suggestedQuestions = [
            'Explique-moi ce concept avec un exemple simple.',
            'Peux-tu me résumer la leçon ?',
            'Donne-moi un exercice pratique.',
            'Corrige ma réponse et explique pourquoi.',
            'Quelles sont les étapes pour progresser ?',
        ];

        return view('student.ai-tutor', [
            'courses' => $courses,
            'conversations' => $conversations,
            'activeConversationId' => $activeConversationId,
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
                $request->validated('conversation_id'),
            );
        } catch (RuntimeException $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        return response()->json([
            'reply' => $result['reply'],
            'message_id' => $result['message_id'],
            'conversation_id' => $result['conversation_id'],
            'conversation_title' => $result['conversation_title'],
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
            $request->integer('conversation_id') ?: null
        );

        return response()->json(['messages' => $history]);
    }

    public function createConversation(Request $request, AiTutorService $service): JsonResponse
    {
        $student = $request->user();
        $courseId = $request->integer('course_id') ?: null;
        $title = $request->string('title', 'Nouvelle discussion');

        $conversation = AiConversation::create([
            'user_id' => $student->id,
            'course_id' => $courseId,
            'title' => $title,
        ]);

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
        ]);
    }

    public function renameConversation(Request $request, $id, AiTutorService $service): JsonResponse
    {
        $request->validate(['title' => 'required|string|max:255']);
        $service->renameConversation($request->user(), (int) $id, $request->title);

        return response()->json(['success' => true]);
    }

    public function deleteConversation(Request $request, $id, AiTutorService $service): JsonResponse
    {
        $service->deleteConversation($request->user(), (int) $id);

        return response()->json(['success' => true]);
    }
}
