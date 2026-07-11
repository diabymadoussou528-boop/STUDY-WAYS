<?php

namespace App\Models;

use App\Enums\LessonType;
use App\Enums\MediaCategory;
use App\Services\MediaStorageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends Model
{
    protected $fillable = [
        'title',
        'content',
        'lesson_type',
        'duration_seconds',
        'sort_order',
        'video_url',
        'resource_url',
        'resource_path',
        'is_preview',
        'course_id',
        'module_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lesson_type' => LessonType::class,
            'is_preview' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Lesson $lesson) {
            if (blank($lesson->resource_path)) {
                return;
            }

            $media = app(MediaStorageService::class);
            $category = $lesson->lesson_type === LessonType::Video
                ? MediaCategory::LessonVideo
                : MediaCategory::LessonResource;

            $media->delete($lesson->resource_path, $category);
        });
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function formattedDuration(): string
    {
        if (! $this->duration_seconds) {
            return '—';
        }

        $hours = intdiv($this->duration_seconds, 3600);
        $minutes = intdiv($this->duration_seconds % 3600, 60);
        $seconds = $this->duration_seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    public function isAccessibleBy(?User $user, bool $isEnrolled): bool
    {
        if ($this->is_preview) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->isAdmin() || (int) $this->course?->user_id === (int) $user->id) {
            return true;
        }

        return $isEnrolled && $user->isStudent();
    }

    public function resourceUrl(): ?string
    {
        if (filled($this->resource_url)) {
            return $this->resource_url;
        }

        if (blank($this->resource_path)) {
            return null;
        }

        $category = $this->lesson_type === LessonType::Video
            ? MediaCategory::LessonVideo
            : MediaCategory::LessonResource;

        return app(MediaStorageService::class)->url($this->resource_path, $category);
    }

    public function storedVideoUrl(): ?string
    {
        if (filled($this->video_url)) {
            return $this->video_url;
        }

        if ($this->lesson_type === LessonType::Video && filled($this->resource_path)) {
            return app(MediaStorageService::class)->url($this->resource_path, MediaCategory::LessonVideo);
        }

        return null;
    }
}
