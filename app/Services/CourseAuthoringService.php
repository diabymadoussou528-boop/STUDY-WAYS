<?php

namespace App\Services;

use App\Enums\CourseStatus;
use App\Enums\MediaCategory;
use App\Exceptions\MediaUploadException;
use App\Jobs\ProcessCourseMediaUpload;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CourseAuthoringService
{
    public function __construct(
        private CategoryService $categoryService,
        private MediaStorageService $mediaStorage,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     *
     * @throws MediaUploadException
     */
    public function create(User $author, array $validated, ?UploadedFile $thumbnail = null, ?UploadedFile $video = null): Course
    {
        $data = $this->mapValidated($validated);
        $data['user_id'] = $author->id;
        $data['creator_id'] = $author->id;
        $data['teacher_id'] = $author->id;
        $data['status'] = CourseStatus::Draft;
        $data['category_id'] = $this->resolveCategoryId($validated);
        $data['upload_status'] = 'pending';

        $course = Course::query()->create($data);

        $thumbnailMeta = $thumbnail ? $this->stashTemp($thumbnail) : null;
        $videoMeta = $video ? $this->stashTemp($video) : null;

        if ($thumbnailMeta !== null || $videoMeta !== null) {
            ProcessCourseMediaUpload::dispatch($course->id, $thumbnailMeta, $videoMeta);
        }

        return $course;
    }

    /**
     * Persist the uploaded file to a private temporary disk so it can be processed
     * asynchronously by the upload job without keeping it in memory.
     *
     * @return array{path: string, name: string, mime: string}
     */
    private function stashTemp(UploadedFile $file): array
    {
        $path = $file->store('uploads', 'local');

        return [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
        ];
    }

    /**
     * Capture the Google Drive file id and public url when media is stored on Google Drive.
     *
     * @param  array<string, mixed>  $data
     */
    private function captureDriveMeta(array &$data, string $type, string $stored): void
    {
        if (! $id = $this->mediaStorage->driveFileId($stored)) {
            return;
        }

        $category = $type === 'thumbnail' ? MediaCategory::CourseThumbnail : MediaCategory::CourseVideo;
        $url = $this->mediaStorage->url($stored, $category);
        $data['google_drive_'.$type.'_id'] = $id;
        $data['google_drive_'.$type.'_url'] = $url;

        if ($type === 'thumbnail') {
            $data['thumbnail_drive_id'] = $id;
            $data['thumbnail_url'] = $url;
        } else {
            $data['video_drive_id'] = $id;
            // Never persist raw Drive URLs as video_url — they break HTML5 playback.
            $data['video_url'] = null;
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     *
     * @throws MediaUploadException
     */
    public function update(
        Course $course,
        array $validated,
        ?UploadedFile $thumbnail = null,
        ?UploadedFile $video = null,
        bool $removeVideo = false,
    ): Course {
        $data = $this->mapValidated($validated);
        $data['category_id'] = $this->resolveCategoryId($validated);

        if ($thumbnail) {
            if ($course->thumbnail) {
                $this->mediaStorage->delete($course->thumbnail, MediaCategory::CourseThumbnail);
            }
            $stored = $this->mediaStorage->upload($thumbnail, MediaCategory::CourseThumbnail);
            $data['thumbnail'] = $stored;
            $this->captureDriveMeta($data, 'thumbnail', $stored);
        }

        if ($removeVideo && ! $video) {
            if ($course->video_path) {
                $this->mediaStorage->delete($course->video_path, MediaCategory::CourseVideo);
            }
            $data['video_path'] = null;
            $data['google_drive_video_id'] = null;
            $data['google_drive_video_url'] = null;
            $data['video_drive_id'] = null;
            $data['video_url'] = null;
            $data['upload_status'] = 'pending';
        }

        if ($video) {
            if ($course->video_path) {
                $this->mediaStorage->delete($course->video_path, MediaCategory::CourseVideo);
            }
            $stored = $this->mediaStorage->upload($video, MediaCategory::CourseVideo);
            $data['video_path'] = $stored;
            $data['video_url'] = null;
            $data['upload_status'] = 'pending';
            $this->captureDriveMeta($data, 'video', $stored);
        }

        $course->update($data);

        return $course->fresh();
    }

    public function delete(Course $course): void
    {
        $this->mediaStorage->delete($course->thumbnail, MediaCategory::CourseThumbnail);
        $this->mediaStorage->delete($course->video_path, MediaCategory::CourseVideo);
        $course->delete();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function mapValidated(array $validated): array
    {
        $objectives = $this->linesToArray($validated['objectives'] ?? null);
        $requirements = $this->linesToArray($validated['requirements'] ?? null);
        $tags = $this->normalizeTags($validated['tags'] ?? null);

        $durationMinutes = isset($validated['duration_hours'])
            ? (int) round((float) $validated['duration_hours'] * 60)
            : (int) Arr::get($validated, 'duration_minutes', 0);

        return [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'short_description' => Arr::get($validated, 'short_description'),
            'duration_minutes' => $durationMinutes,
            'language' => Arr::get($validated, 'language', 'Français'),
            'objectives' => $objectives,
            'requirements' => $requirements,
            'tags' => $tags,
            'meta_keywords' => $tags,
            'video_url' => null,
            'price' => 0,
            'difficulty' => Arr::get($validated, 'difficulty'),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveCategoryId(array $validated): int
    {
        if (! empty($validated['category'])) {
            return $this->categoryService->findOrCreate((string) $validated['category'])->id;
        }

        return (int) $validated['category_id'];
    }

    /**
     * @return list<string>|null
     */
    private function linesToArray(null|string|array $value): ?array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(
                fn ($line) => trim((string) $line),
                $value,
            )));
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return array_values(array_filter(array_map(
            'trim',
            preg_split("/\r\n|\n|\r/", $value) ?: [],
        )));
    }

    private function normalizeTags(null|string|array $tags): ?string
    {
        if (is_array($tags)) {
            $parts = $tags;
        } elseif (is_string($tags)) {
            $parts = preg_split('/[,;]+/', $tags) ?: [];
        } else {
            return null;
        }

        $normalized = collect($parts)
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->unique(fn (string $tag) => Str::lower($tag))
            ->values()
            ->implode(', ');

        return $normalized !== '' ? $normalized : null;
    }
}
