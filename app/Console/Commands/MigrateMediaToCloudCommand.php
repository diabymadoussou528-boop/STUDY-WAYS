<?php

namespace App\Console\Commands;

use App\Enums\MediaCategory;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Services\MediaStorageService;
use Illuminate\Console\Command;

class MigrateMediaToCloudCommand extends Command
{
    protected $signature = 'media:migrate-to-cloud {--dry-run : Preview changes without uploading}';

    protected $description = 'Migrate locally stored media files to Cloudinary';

    public function handle(MediaStorageService $mediaStorage): int
    {
        if (! $mediaStorage->usesCloudinary()) {
            $this->error('Cloudinary is not configured. Set MEDIA_DISK=cloudinary and CLOUDINARY_* credentials in .env.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $migrated = 0;

        User::query()
            ->whereNotNull('avatar')
            ->each(function (User $user) use ($mediaStorage, $dryRun, &$migrated) {
                if ($this->shouldSkip($user->avatar, $mediaStorage)) {
                    return;
                }

                if ($dryRun) {
                    $this->line("Would migrate avatar for user #{$user->id}: {$user->avatar}");
                    $migrated++;

                    return;
                }

                $cloudPath = $mediaStorage->migrateLocalToCloud($user->avatar, MediaCategory::Avatar);

                if ($cloudPath !== null && $cloudPath !== $user->avatar) {
                    $user->update(['avatar' => $cloudPath]);
                    $this->info("Migrated avatar for user #{$user->id}");
                    $migrated++;
                }
            });

        Course::query()
            ->where(function ($query) {
                $query->whereNotNull('thumbnail')->orWhereNotNull('video_path');
            })
            ->each(function (Course $course) use ($mediaStorage, $dryRun, &$migrated) {
                foreach ([
                    'thumbnail' => MediaCategory::CourseThumbnail,
                    'video_path' => MediaCategory::CourseVideo,
                ] as $column => $category) {
                    $path = $course->{$column};

                    if ($this->shouldSkip($path, $mediaStorage)) {
                        continue;
                    }

                    if ($dryRun) {
                        $this->line("Would migrate course #{$course->id} {$column}: {$path}");
                        $migrated++;

                        continue;
                    }

                    $cloudPath = $mediaStorage->migrateLocalToCloud($path, $category);

                    if ($cloudPath !== null && $cloudPath !== $path) {
                        $course->update([$column => $cloudPath]);
                        $this->info("Migrated course #{$course->id} {$column}");
                        $migrated++;
                    }
                }
            });

        Lesson::query()
            ->whereNotNull('resource_path')
            ->each(function (Lesson $lesson) use ($mediaStorage, $dryRun, &$migrated) {
                if ($this->shouldSkip($lesson->resource_path, $mediaStorage)) {
                    return;
                }

                $category = $lesson->lesson_type?->value === 'video'
                    ? MediaCategory::LessonVideo
                    : MediaCategory::LessonResource;

                if ($dryRun) {
                    $this->line("Would migrate lesson #{$lesson->id}: {$lesson->resource_path}");
                    $migrated++;

                    return;
                }

                $cloudPath = $mediaStorage->migrateLocalToCloud($lesson->resource_path, $category);

                if ($cloudPath !== null && $cloudPath !== $lesson->resource_path) {
                    $lesson->update(['resource_path' => $cloudPath]);
                    $this->info("Migrated lesson #{$lesson->id}");
                    $migrated++;
                }
            });

        $this->newLine();
        $this->info($dryRun
            ? "Dry run complete. {$migrated} file(s) would be migrated."
            : "Migration complete. {$migrated} file(s) migrated.");

        return self::SUCCESS;
    }

    private function shouldSkip(?string $path, MediaStorageService $mediaStorage): bool
    {
        if (blank($path)) {
            return true;
        }

        return $mediaStorage->isExternalUrl($path) || $mediaStorage->isCloudStored($path);
    }
}
