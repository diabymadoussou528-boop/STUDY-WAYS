<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiTutorChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStudent() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4000'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],
            'topic' => ['nullable', 'string', 'max:200'],
            'mode' => ['nullable', 'string', 'in:chat,evaluation,explain,examples,quiz,recommend'],
            'conversation_id' => ['nullable', 'integer', 'exists:ai_conversations,id'],
        ];
    }
}
