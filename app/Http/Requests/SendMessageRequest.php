<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
