<?php

namespace App\Http\Requests;

use App\Enums\MediaCategory;
use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;

class StoreLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $course = Course::query()->find($this->integer('course_id'));

        return $course !== null && (int) $course->user_id === (int) $user->id;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $resourceLimits = MediaCategory::LessonResource->limits();
        $videoLimits = MediaCategory::LessonVideo->limits();

        return [
            'course_id' => ['required', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'video_url' => ['nullable', 'url'],
            'file' => [
                'nullable',
                'file',
                'mimes:'.implode(',', array_unique(array_merge($resourceLimits['mimes'], $videoLimits['mimes']))),
                'max:'.max($resourceLimits['max_kb'], $videoLimits['max_kb']),
            ],
        ];
    }
}
