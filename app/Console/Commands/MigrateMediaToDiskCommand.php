<?php

namespace App\Console\Commands;

use App\Enums\MediaCategory;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Services\MediaStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class MigrateMediaToDiskCommand extends Command
{
    protected $signature = 'media:migrate-to-disk
                            {--dry-run : Preview what would be migrated without making changes}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Pull Google Drive media onto the local public disk and clear Drive playback links';

    public function handle(MediaStorageService $media): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! $this->option('force') && ! $this->confirm('Migrate Google Drive media to the local public disk?', true)) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('[DRY RUN] No files will be moved.');
        }

        $migrated = 0;
        $cleared = 0;
        $skipped = 0;
        $errors = 0;

        User::query()
            ->whereNotNull('avatar')
            ->where(function ($q) {
                $q->where('avatar', 'like', 'google://%')
                    ->orWhere(function ($inner) {
                        $inner->where('avatar', 'not like', 'http%')
                            ->where('avatar', 'not like', 'google://%');
                    });
            })
            ->chunkById(50, function ($users) use ($dryRun, $media, &$migrated, &$skipped, &$errors) {
                foreach ($users as $user) {
                    $result = $this->migrateStoredPath(
                        dryRun: $dryRun,
                        stored: $user->avatar,
                        category: MediaCategory::Avatar,
                        label: "avatar user #{$user->id}",
                        media: $media,
                        onSuccess: function (string $localPath) use ($user) {
                            $user->updateQuietly(['avatar' => $localPath]);
                        },
                    );

                    $this->tally($result, $migrated, $skipped, $errors);
                }
            });

        Course::query()->chunkById(20, function ($courses) use ($dryRun, $media, &$migrated, &$cleared, &$skipped, &$errors) {
            foreach ($courses as $course) {
                foreach ([
                    'thumbnail' => MediaCategory::CourseThumbnail,
                    'video_path' => MediaCategory::CourseVideo,
                ] as $field => $category) {
                    if (blank($course->{$field})) {
                        continue;
                    }

                    $result = $this->migrateStoredPath(
                        dryRun: $dryRun,
                        stored: $course->{$field},
                        category: $category,
                        label: "course #{$course->id} {$field}",
                        media: $media,
                        onSuccess: function (string $localPath) use ($course, $field) {
                            $course->updateQuietly([$field => $localPath]);
                        },
                        driveId: $field === 'video_path'
                            ? ($course->video_drive_id ?: $course->google_drive_video_id)
                            : ($course->thumbnail_drive_id ?: $course->google_drive_thumbnail_id),
                    );

                    $this->tally($result, $migrated, $skipped, $errors);
                }

                if ($this->courseHasDriveMetadata($course)) {
                    $this->line("  CLEAR Drive metadata: course #{$course->id}");

                    if (! $dryRun) {
                        $course->updateQuietly([
                            'video_drive_id' => null,
                            'thumbnail_drive_id' => null,
                            'google_drive_video_id' => null,
                            'google_drive_thumbnail_id' => null,
                            'google_drive_video_url' => null,
                            'google_drive_thumbnail_url' => null,
                            'thumbnail_url' => null,
                            'upload_status' => filled($course->video_path) || filled($course->thumbnail) ? 'completed' : $course->upload_status,
                        ]);
                    }

                    $cleared++;
                }
            }
        });

        Lesson::query()
            ->whereNotNull('resource_path')
            ->chunkById(50, function ($lessons) use ($dryRun, $media, &$migrated, &$skipped, &$errors) {
                foreach ($lessons as $lesson) {
                    $category = str_contains((string) $lesson->resource_path, 'lesson-resource')
                        || str_ends_with(Str::lower((string) $lesson->resource_path), '.pdf')
                        ? MediaCategory::LessonResource
                        : MediaCategory::LessonVideo;

                    $result = $this->migrateStoredPath(
                        dryRun: $dryRun,
                        stored: $lesson->resource_path,
                        category: $category,
                        label: "lesson #{$lesson->id} resource_path",
                        media: $media,
                        onSuccess: function (string $localPath) use ($lesson) {
                            $lesson->updateQuietly(['resource_path' => $localPath]);
                        },
                    );

                    $this->tally($result, $migrated, $skipped, $errors);
                }
            });

        $this->newLine();
        $this->table(
            ['Migrated', 'Drive metadata cleared', 'Skipped', 'Errors'],
            [[$migrated, $cleared, $skipped, $errors]]
        );

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  callable(string): void  $onSuccess
     * @return 'migrated'|'skipped'|'error'|'noop'
     */
    private function migrateStoredPath(
        bool $dryRun,
        ?string $stored,
        MediaCategory $category,
        string $label,
        MediaStorageService $media,
        callable $onSuccess,
        ?string $driveId = null,
    ): string {
        if (blank($stored) && blank($driveId)) {
            return 'noop';
        }

        if (filled($stored) && $media->isExternalUrl($stored)) {
            $this->line("  SKIP (external URL) {$label}: {$stored}");

            return 'skipped';
        }

        $localPath = filled($stored) && ! str_starts_with($stored, 'google://')
            ? $stored
            : null;

        if ($localPath !== null && Storage::disk('public')->exists($localPath)) {
            return 'noop';
        }

        $sourceDisk = null;
        $sourcePath = null;

        if (filled($stored) && str_starts_with($stored, 'google://')) {
            $sourceDisk = Storage::disk('google');
            $sourcePath = Str::after($stored, 'google://');
            $localPath = $category->folder().'/'.basename($sourcePath);
        } elseif (filled($driveId)) {
            $sourceDisk = Storage::disk('google');
            $sourcePath = $driveId;
            $localPath = $category->folder().'/'.$driveId.(str_contains($driveId, '.') ? '' : '.mp4');
        } elseif ($localPath !== null) {
            $this->line("  SKIP (local file missing) {$label}: {$localPath}");

            return 'skipped';
        }

        if ($sourceDisk === null || $sourcePath === null || $localPath === null) {
            return 'skipped';
        }

        $this->line("  MIGRATE {$label}: {$sourcePath} → {$localPath}");

        if ($dryRun) {
            return 'migrated';
        }

        try {
            if (! $sourceDisk->exists($sourcePath)) {
                $this->error("  ERROR source missing for {$label}: {$sourcePath}");

                return 'error';
            }

            Storage::disk('public')->makeDirectory(dirname($localPath));
            $stream = $sourceDisk->readStream($sourcePath);

            if ($stream === false) {
                throw new \RuntimeException('Unable to open source stream.');
            }

            Storage::disk('public')->writeStream($localPath, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            $onSuccess($localPath);

            return 'migrated';
        } catch (Throwable $e) {
            $this->error("  ERROR {$label}: {$e->getMessage()}");

            return 'error';
        }
    }

    private function courseHasDriveMetadata(Course $course): bool
    {
        return filled($course->video_drive_id)
            || filled($course->thumbnail_drive_id)
            || filled($course->google_drive_video_id)
            || filled($course->google_drive_thumbnail_id)
            || filled($course->google_drive_video_url)
            || filled($course->google_drive_thumbnail_url)
            || (filled($course->thumbnail_url) && str_contains((string) $course->thumbnail_url, 'drive.google'));
    }

    private function tally(string $result, int &$migrated, int &$skipped, int &$errors): void
    {
        match ($result) {
            'migrated' => $migrated++,
            'skipped' => $skipped++,
            'error' => $errors++,
            default => null,
        };
    }
}
