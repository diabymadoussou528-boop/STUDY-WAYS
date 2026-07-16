<?php

namespace App\Enums;

enum MediaCategory: string
{
    case Avatar = 'avatar';
    case CourseThumbnail = 'course_thumbnail';
    case CourseVideo = 'course_video';
    case LessonVideo = 'lesson_video';
    case LessonResource = 'lesson_resource';

    public function folder(): string
    {
        return config('media.folders.'.$this->value, $this->value);
    }

    /**
     * @return array{max_kb: int, mimes: array<int, string>, mime_types: array<int, string>}
     */
    public function limits(): array
    {
        return config('media.limits.'.$this->value, [
            'max_kb' => 5120,
            'mimes' => [],
            'mime_types' => [],
        ]);
    }

    public function resourceType(): string
    {
        return match ($this) {
            self::Avatar, self::CourseThumbnail => 'image',
            self::CourseVideo, self::LessonVideo => 'video',
            self::LessonResource => 'auto',
        };
    }

    public function isImage(): bool
    {
        return in_array($this, [self::Avatar, self::CourseThumbnail], true);
    }
}
