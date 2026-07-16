<?php

namespace App\Services;

use App\Models\AiChatMessage;
use App\Models\AiConversation;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AiTutorService
{
    /**
     * @return Collection<int, Course>
     */
    public function enrolledCoursesFor(User $student): Collection
    {
        if (! Schema::hasTable('enrollments')) {
            return collect();
        }

        $courseIds = Enrollment::query()
            ->where('user_id', $student->id)
            ->pluck('course_id');

        return Course::query()
            ->whereIn('id', $courseIds)
            ->with(['lessons:id,title,course_id', 'user:id,name'])
            ->orderBy('title')
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function chatHistory(User $student, ?int $courseId = null, ?int $conversationId = null): array
    {
        if (! Schema::hasTable('ai_chat_messages')) {
            return [];
        }

        return AiChatMessage::query()
            ->where('user_id', $student->id)
            ->when($conversationId, fn ($q) => $q->where('conversation_id', $conversationId))
            ->when(! $conversationId && $courseId, fn ($q) => $q->where('course_id', $courseId))
            ->orderBy('created_at')
            ->limit(config('ai.max_history'))
            ->get()
            ->map(fn (AiChatMessage $m) => [
                'id' => $m->id,
                'role' => $m->role,
                'content' => $m->content,
                'created_at' => $m->created_at->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return array{reply: string, message_id: int, conversation_id: int, conversation_title: string}
     */
    public function chat(
        User $student,
        string $message,
        ?int $courseId = null,
        ?int $lessonId = null,
        ?string $topic = null,
        string $mode = 'chat',
        ?int $conversationId = null,
    ): array {
        $course = $courseId ? Course::query()->with('lessons')->find($courseId) : null;
        $lesson = $lessonId ? Lesson::query()->find($lessonId) : null;

        $this->assertCourseAccess($student, $course);

        // If no conversation_id is provided, create one
        if (! $conversationId) {
            $title = $topic ?: (mb_strlen($message) > 30 ? mb_substr($message, 0, 27).'...' : $message);
            $conversation = AiConversation::create([
                'user_id' => $student->id,
                'course_id' => $course?->id,
                'title' => $title,
            ]);
            $conversationId = $conversation->id;
        } else {
            $conversation = AiConversation::where('user_id', $student->id)->findOrFail($conversationId);
            // Auto rename conversation if it was a default title
            if ($conversation->title === 'Nouvelle discussion' || $conversation->title === 'Discussion') {
                $title = $topic ?: (mb_strlen($message) > 30 ? mb_substr($message, 0, 27).'...' : $message);
                $conversation->update(['title' => $title]);
            }
        }

        AiChatMessage::query()->create([
            'user_id' => $student->id,
            'conversation_id' => $conversationId,
            'course_id' => $course?->id,
            'lesson_id' => $lesson?->id,
            'topic' => $topic,
            'role' => 'user',
            'content' => $message,
            'mode' => $mode,
        ]);

        $history = $this->buildApiMessages($student, $course, $lesson, $topic, $mode, $conversationId);
        $reply = $this->callProvider($history);

        $assistant = AiChatMessage::query()->create([
            'user_id' => $student->id,
            'conversation_id' => $conversationId,
            'course_id' => $course?->id,
            'lesson_id' => $lesson?->id,
            'topic' => $topic,
            'role' => 'assistant',
            'content' => $reply,
            'mode' => $mode,
        ]);

        return [
            'reply' => $reply,
            'message_id' => $assistant->id,
            'conversation_id' => $conversationId,
            'conversation_title' => $conversation->title,
        ];
    }

    public function clearHistory(User $student, ?int $courseId = null): void
    {
        AiChatMessage::query()
            ->where('user_id', $student->id)
            ->when($courseId, fn ($q) => $q->where('course_id', $courseId))
            ->delete();
    }

    public function getConversations(User $student, ?string $search = null)
    {
        return AiConversation::query()
            ->where('user_id', $student->id)
            ->when($search, fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->orderByDesc('updated_at')
            ->get();
    }

    public function renameConversation(User $student, int $id, string $title): void
    {
        AiConversation::query()
            ->where('user_id', $student->id)
            ->where('id', $id)
            ->firstOrFail()
            ->update(['title' => $title]);
    }

    public function deleteConversation(User $student, int $id): void
    {
        AiConversation::query()
            ->where('user_id', $student->id)
            ->where('id', $id)
            ->firstOrFail()
            ->delete();
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    private function buildApiMessages(
        User $student,
        ?Course $course,
        ?Lesson $lesson,
        ?string $topic,
        string $mode,
        ?int $conversationId = null,
    ): array {
        $system = $this->systemPrompt($student, $course, $lesson, $topic, $mode);

        $messages = [
            ['role' => 'system', 'content' => $system],
        ];

        $recent = AiChatMessage::query()
            ->where('user_id', $student->id)
            ->when($conversationId, fn ($q) => $q->where('conversation_id', $conversationId))
            ->when(! $conversationId && $course, fn ($q) => $q->where('course_id', $course->id))
            ->where('mode', $mode)
            ->orderByDesc('created_at')
            ->limit(config('ai.max_history'))
            ->get()
            ->sortBy('created_at');

        foreach ($recent as $msg) {
            if ($msg->role === 'user' || $msg->role === 'assistant') {
                $messages[] = [
                    'role' => $msg->role,
                    'content' => $msg->content,
                ];
            }
        }

        return $messages;
    }

    private function systemPrompt(
        User $student,
        ?Course $course,
        ?Lesson $lesson,
        ?string $topic,
        string $mode,
    ): string {
        $parts = [
            'Tu es le tuteur IA de StudyWays, une plateforme e-learning premium.',
            'Réponds en français, de manière claire, pédagogique et encourageante.',
            'Utilise des exemples concrets et structure tes réponses avec des listes si utile.',
            'Étudiant : '.$student->name.'.',
        ];

        if ($course) {
            $parts[] = 'Cours sélectionné : « '.$course->title.' ».';
            if ($course->description) {
                $parts[] = 'Description du cours : '.mb_substr(strip_tags($course->description), 0, 500);
            }
            if ($course->user) {
                $parts[] = 'Professeur : '.$course->user->name.'.';
            }
        }

        if ($lesson) {
            $parts[] = 'Leçon en focus : « '.$lesson->title.' ».';
        }

        if ($topic) {
            $parts[] = 'Sujet / thème : '.$topic.'.';
        }

        if ($mode === 'evaluation') {
            $parts[] = 'Mode : ÉVALUATION DU NIVEAU. Pose des questions progressives, analyse les réponses de l\'étudiant, estime son niveau (débutant, intermédiaire, avancé), identifie les lacunes et recommande des leçons ou exercices précis.';
        } elseif ($mode === 'explain') {
            $parts[] = 'Mode : EXPLICATION. Explique le concept demandé de façon progressive, avec analogie simple puis détail technique. Utilise du Markdown.';
        } elseif ($mode === 'examples') {
            $parts[] = 'Mode : EXEMPLES. Fournis 2 à 4 exemples concrets, progressifs, avec code ou cas pratiques si pertinent. Utilise du Markdown.';
        } elseif ($mode === 'quiz') {
            $parts[] = 'Mode : QUIZ. Génère un mini-quiz de 5 questions (QCM ou réponses courtes) sur le sujet/cours, puis propose un barème. N\'affiche pas encore les réponses détaillées sauf si l\'étudiant le demande. Utilise du Markdown.';
        } elseif ($mode === 'recommend') {
            $parts[] = 'Mode : RECOMMANDATIONS. Propose un plan d\'étude personnalisé, les prochaines leçons à suivre et des ressources liées au cours. Utilise du Markdown.';
        } else {
            $parts[] = 'Tu peux expliquer des concepts, résumer, proposer des exercices, générer des quiz, corriger des réponses et recommander des ressources liées au cours. Réponds en Markdown clair.';
        }

        return implode("\n", $parts);
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    private function callProvider(array $messages): string
    {
        $provider = config('ai.provider', 'gemini');

        return match ($provider) {
            'gemini' => $this->callGemini($messages),
            default => $this->callGroq($messages),
        };
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    private function callGemini(array $messages): string
    {
        $apiKey = config('ai.gemini.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException(
                'Clé API Gemini manquante. Ajoutez GEMINI_API_KEY dans votre fichier .env.'
            );
        }

        // Convert OpenAI/Groq style message structure to Gemini contents style
        $contents = [];
        $systemInstruction = '';

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemInstruction = $msg['content'];

                continue;
            }

            // Gemini expects 'user' or 'model' roles
            $role = $msg['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => $msg['content']],
                ],
            ];
        }

        $body = [
            'contents' => $contents,
            'generationConfig' => [
                'maxOutputTokens' => config('ai.gemini.max_tokens'),
                'temperature' => config('ai.gemini.temperature'),
            ],
        ];

        if (! empty($systemInstruction)) {
            $body['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ];
        }

        try {
            $model = config('ai.gemini.model', 'gemini-1.5-flash');
            $url = config('ai.gemini.base_url').'/models/'.$model.':generateContent?key='.$apiKey;

            $response = Http::timeout(60)
                ->post($url, $body)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $errorMsg = $exception->response?->json('error.message') ?? $exception->getMessage();
            throw new RuntimeException('Erreur du service Gemini : '.$errorMsg, previous: $exception);
        }

        $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (blank($content)) {
            throw new RuntimeException('Réponse Gemini vide. Réessayez.');
        }

        return trim($content);
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    private function callGroq(array $messages): string
    {
        $apiKey = config('ai.groq.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException(
                'Clé API Groq manquante. Ajoutez GROQ_API_KEY dans votre fichier .env (gratuit sur https://console.groq.com/).'
            );
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->post(config('ai.groq.base_url').'/chat/completions', [
                    'model' => config('ai.groq.model'),
                    'messages' => $messages,
                    'max_tokens' => config('ai.groq.max_tokens'),
                    'temperature' => config('ai.groq.temperature'),
                ])
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $body = $exception->response?->json('error.message') ?? $exception->getMessage();

            throw new RuntimeException('Erreur du service IA : '.$body, previous: $exception);
        }

        $content = $response['choices'][0]['message']['content'] ?? null;

        if (blank($content)) {
            throw new RuntimeException('Réponse IA vide. Réessayez.');
        }

        return trim($content);
    }

    private function assertCourseAccess(User $student, ?Course $course): void
    {
        if (! $course) {
            return;
        }

        if (! Schema::hasTable('enrollments')) {
            return;
        }

        $enrolled = Enrollment::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->exists();

        if (! $enrolled) {
            throw new RuntimeException('Vous devez être inscrit à ce cours pour utiliser le tuteur IA dans ce contexte.');
        }
    }
}
