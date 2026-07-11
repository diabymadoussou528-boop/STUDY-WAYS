<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class MessagingController extends Controller
{
    public function index(MessagingService $service): View
    {
        $student = auth()->user();

        return view('student.messages', [
            'conversations' => $service->conversationsFor($student),
            'contacts' => $service->contactableTeachersForStudent($student),
            'unreadCount' => $service->unreadCount($student),
            'isPremium' => (bool) $student->is_premium,
        ]);
    }

    public function thread(Request $request, MessagingService $service): JsonResponse
    {
        $request->validate([
            'other_user_id' => ['required', 'integer', 'exists:users,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
        ]);

        try {
            $messages = $service->threadMessages(
                $request->user(),
                $request->integer('other_user_id'),
                $request->integer('course_id'),
            );

            $service->markThreadAsRead(
                $request->user(),
                $request->integer('other_user_id'),
                $request->integer('course_id'),
            );
        } catch (RuntimeException $exception) {
            return response()->json(['error' => $exception->getMessage()], 403);
        }

        return response()->json([
            'messages' => $messages->map(fn ($m) => [
                'id' => $m->id,
                'body' => $m->body,
                'from_user_id' => $m->from_user_id,
                'to_user_id' => $m->to_user_id,
                'read_at' => $m->read_at?->toIso8601String(),
                'created_at' => $m->created_at->toIso8601String(),
                'sender' => [
                    'id' => $m->sender?->id,
                    'name' => $m->sender?->name,
                    'avatar' => $m->sender?->avatarUrl(),
                ],
            ]),
        ]);
    }

    public function send(SendMessageRequest $request, MessagingService $service): JsonResponse
    {
        try {
            $message = $service->send(
                $request->user(),
                $request->integer('recipient_id'),
                $request->integer('course_id'),
                $request->validated('body'),
            );
        } catch (RuntimeException $exception) {
            return response()->json(['error' => $exception->getMessage()], 403);
        }

        return response()->json([
            'message' => [
                'id' => $message->id,
                'body' => $message->body,
                'from_user_id' => $message->from_user_id,
                'to_user_id' => $message->to_user_id,
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ]);
    }

    public function unreadCount(MessagingService $service): JsonResponse
    {
        return response()->json([
            'count' => $service->unreadCount(auth()->user()),
        ]);
    }
}
