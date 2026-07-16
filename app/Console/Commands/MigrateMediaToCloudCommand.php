<?php

namespace App\Console\Commands;

use App\Enums\MediaCategory;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Services\MediaStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateMediaToCloudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:migrate-to-cloud
                            {--dry-run : Preview what would be migrated without making changes}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate locally stored media files to cloud (Google Drive) storage';

    public function handle(MediaStorageService $media): int
    {
        if (config('media.disk') !== 'google') {
            $this->info('Cloud storage is not configured. Set MEDIA_DISK=google and configure GOOGLE_DRIVE_* credentials to enable migration.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[DRY RUN] No files will be moved.');
        }

        $migrated = 0;
        $skipped = 0;
        $errors = 0;

        // --- User avatars ---
        User::query()
            ->whereNotNull('avatar')
            ->where('avatar', 'not like', 'google://%')
            ->where('avatar', 'not like', 'http%')
            ->chunkById(50, function ($users) use ($dryRun, &$migrated, &$skipped, &$errors) {
                foreach ($users as $user) {
                    if (! Storage::disk('public')->exists($user->avatar)) {
                        $this->line("  SKIP (file missing) avatar for user #{$user->id}");
                        $skipped++;

                        continue;
                    }

                    $this->line("  MIGRATE avatar: {$user->avatar}");

                    if (! $dryRun) {
                        try {
                            $stream = Storage::disk('public')->readStream($user->avatar);
                            $newPath = 'google://'.$user->avatar;
                            Storage::disk('google')->writeStream($user->avatar, $stream);
                            Storage::disk('public')->delete($user->avatar);
                            $user->updateQuietly(['avatar' => $newPath]);
                            $migrated++;
                        } catch (\Throwable $e) {
                            $this->error("  ERROR: {$e->getMessage()}");
                            $errors++;
                        }
                    } else {
                        $migrated++;
                    }
                }
            });

        // --- Course thumbnails & videos ---
        Course::query()
            ->where(function ($q) {
                $q->whereNotNull('thumbnail')
                    ->where('thumbnail', 'not like', 'google://%')
                    ->where('thumbnail', 'not like', 'http%');
            })
            ->orWhere(function ($q) {
                $q->whereNotNull('video_path')
                    ->where('video_path', 'not like', 'google://%')
                    ->where('video_path', 'not like', 'http%');
            })
            ->chunkById(20, function ($courses) use ($dryRun, &$migrated, &$skipped, &$errors) {
                foreach ($courses as $course) {
                    foreach (['thumbnail' => MediaCategory::CourseThumbnail, 'video_path' => MediaCategory::CourseVideo] as $field => $category) {
                        if (blank($course->{$field})) {
                            continue;
                        }

                        if (! Storage::disk('public')->exists($course->{$field})) {
                            $skipped++;

                            continue;
                        }

                        $this->line("  MIGRATE course #{$course->id} {$field}: {$course->{$field}}");

                        if (! $dryRun) {
                            try {
                                $stream = Storage::disk('public')->readStream($course->{$field});
                                Storage::disk('google')->writeStream($course->{$field}, $stream);
                                Storage::disk('public')->delete($course->{$field});
                                $course->updateQuietly([$field => 'google://'.$course->{$field}]);
                                $migrated++;
                            } catch (\Throwable $e) {
                                $this->error("  ERROR: {$e->getMessage()}");
                                $errors++;
                            }
                        } else {
                            $migrated++;
                        }
                    }
                }
            });

        // --- Lesson resources ---
        Lesson::query()
            ->whereNotNull('resource_path')
            ->where('resource_path', 'not like', 'google://%')
            ->where('resource_path', 'not like', 'http%')
            ->chunkById(50, function ($lessons) use ($dryRun, &$migrated, &$skipped, &$errors) {
                foreach ($lessons as $lesson) {
                    if (! Storage::disk('public')->exists($lesson->resource_path)) {
                        $skipped++;

                        continue;
                    }

                    $this->line("  MIGRATE lesson #{$lesson->id} resource_path: {$lesson->resource_path}");

                    if (! $dryRun) {
                        try {
                            $stream = Storage::disk('public')->readStream($lesson->resource_path);
                            Storage::disk('google')->writeStream($lesson->resource_path, $stream);
                            Storage::disk('public')->delete($lesson->resource_path);
                            $lesson->updateQuietly(['resource_path' => 'google://'.$lesson->resource_path]);
                            $migrated++;
                        } catch (\Throwable $e) {
                            $this->error("  ERROR: {$e->getMessage()}");
                            $errors++;
                        }
                    } else {
                        $migrated++;
                    }
                }
            });

        $this->newLine();
        $this->table(
            ['Migrated', 'Skipped', 'Errors'],
            [[$migrated, $skipped, $errors]]
        );

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
