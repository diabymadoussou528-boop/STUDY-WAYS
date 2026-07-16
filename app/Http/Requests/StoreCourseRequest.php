<?php

namespace App\Http\Requests;

use App\Enums\MediaCategory;
use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || (! $user->isTeacher() && ! $user->isAdmin())) {
            return false;
        }

        $course = $this->route('course');

        if ($course) {
            return $user->can('update', $course);
        }

        return $user->can('create', Course::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $thumbnailLimits = MediaCategory::CourseThumbnail->limits();
        $videoLimits = MediaCategory::CourseVideo->limits();
        $isCreate = $this->isMethod('POST');

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'category' => ['required', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'video' => [
                Rule::requiredIf($isCreate),
                'nullable',
                'file',
                ...$this->fileRules($videoLimits),
            ],
            'thumbnail' => [
                Rule::requiredIf($isCreate && ! $this->hasFile('video')),
                'nullable',
                'file',
                ...$this->fileRules($thumbnailLimits),
            ],
            'duration_hours' => ['nullable', 'numeric', 'min:0.1', 'max:1000'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'language' => ['nullable', 'string', 'max:50'],
            'difficulty' => ['nullable', 'string', 'max:50'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'objectives' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category.required' => 'Indiquez une catégorie pour le cours.',
            'thumbnail.required' => 'Ajoutez une miniature pour le cours.',
            'video.required' => 'Téléversez une vidéo ou fournissez un lien vidéo.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('category')) {
            $this->merge([
                'category' => trim(preg_replace('/\s+/u', ' ', (string) $this->input('category')) ?? ''),
            ]);
        }

        if ($this->filled('duration_hours')) {
            $this->merge([
                'duration_minutes' => (int) round((float) $this->input('duration_hours') * 60),
            ]);
        }

        $this->merge([
            'is_premium_only' => $this->boolean('is_premium_only'),
            'remove_video' => $this->boolean('remove_video'),
        ]);
    }

    /**
     * @param  array{max_kb: int, mimes: array<int, string>}  $limits
     * @return array<int, mixed>
     */
    private function fileRules(array $limits): array
    {
        return [
            'mimes:'.implode(',', $limits['mimes']),
            'max:'.$limits['max_kb'],
        ];
    }
}
