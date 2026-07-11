<?php

namespace App\Http\Requests;

use App\Enums\MediaCategory;
use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isTeacher() || $user->isAdmin());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $thumbnailLimits = MediaCategory::CourseThumbnail->limits();
        $videoLimits = MediaCategory::CourseVideo->limits();

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'video_url' => ['nullable', 'url'],
            'video' => ['nullable', 'file', ...$this->fileRules($videoLimits)],
            'thumbnail' => ['nullable', 'file', ...$this->fileRules($thumbnailLimits)],
            'price' => ['nullable', 'numeric', 'min:0'],
            'difficulty' => ['nullable', 'string', 'max:50'],
        ];
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
