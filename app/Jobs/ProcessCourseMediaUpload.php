<?php

namespace App\Jobs;

use App\Enums\MediaCategory;
use App\Models\Course;
use App\Services\MediaStorageService;
use App\Services\NotificationDispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessCourseMediaUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array{path: string, name: string, mime: string}|null  $thumbnail
     * @param  array{path: string, name: string, mime: string}|null  $video
     */
    public function __construct(
        public int $courseId,
        public ?array $thumbnail = null,
        public ?array $video = null,
    ) {}

    public function handle(MediaStorageService $media, NotificationDispatchService $notifications): void
    {
        $course = Course::find($this->courseId);

        if (! $course) {
            $this->cleanupTemp();

            return;
        }

        $course->update(['upload_status' => 'processing']);

        /** @var array<int, array{0: MediaCategory, 1: string}> $uploaded */
        $uploaded = [];

        try {
            $data = [];

            if ($this->thumbnail) {
                $file = $this->toUploadedFile($this->thumbnail);
                $stored = $media->upload($file, MediaCategory::CourseThumbnail);
                $data['thumbnail'] = $stored;
                $this->captureDriveMeta($data, 'thumbnail', $stored, $media);
                $uploaded[] = [MediaCategory::CourseThumbnail, $stored];
            }

            if ($this->video) {
                $file = $this->toUploadedFile($this->video);
                $stored = $media->upload($file, MediaCategory::CourseVideo);
                $data['video_path'] = $stored;
                $data['video_url'] = null;
                $this->captureDriveMeta($data, 'video', $stored, $media);
                $uploaded[] = [MediaCategory::CourseVideo, $stored];
            }

            $data['upload_status'] = 'completed';
            $course->update($data);
        } catch (Throwable $e) {
            fwrite(STDERR, 'Exception in Job: '.$e->getMessage()."\n".$e->getTraceAsString()."\n");
            foreach ($uploaded as [$category, $stored]) {
                $media->delete($stored, $category);
            }

            $course->update(['upload_status' => 'failed']);

            logger()->error('Course media upload failed', [
                'course_id' => $this->courseId,
                'error' => $e->getMessage(),
            ]);

            $notifications->notify(
                $course->creator ?? $course->user,
                'course_upload_failed',
                'Échec de l’importation',
                'L’importation des médias du cours « '.$course->title.' » a échoué. Veuillez réessayer.',
                ['course_id' => $course->id],
                false,
            );
        } finally {
            $this->cleanupTemp();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function captureDriveMeta(array &$data, string $type, string $stored, MediaStorageService $media): void
    {
        if (! $id = $media->driveFileId($stored)) {
            return;
        }

        $category = $type === 'thumbnail' ? MediaCategory::CourseThumbnail : MediaCategory::CourseVideo;
        $url = $media->url($stored, $category);

        $data['google_drive_'.$type.'_id'] = $id;
        $data['google_drive_'.$type.'_url'] = $url;

        if ($type === 'thumbnail') {
            $data['thumbnail_drive_id'] = $id;
            $data['thumbnail_url'] = $url;
        } else {
            $data['video_drive_id'] = $id;
            // Keep video_url empty — Drive share links are not playable in <video>.
            // Playback goes through courses.video.stream using video_drive_id / video_path.
            $data['video_url'] = null;
        }
    }

    /**
     * @return array{path: string, name: string, mime: string}
     */
    private function toUploadedFile(array $meta): UploadedFile
    {
        $absolute = Storage::disk('local')->path($meta['path']);

        return new UploadedFile($absolute, $meta['name'], $meta['mime'], null, true);
    }

    private function cleanupTemp(): void
    {
        foreach ([$this->thumbnail, $this->video] as $meta) {
            if ($meta && Storage::disk('local')->exists($meta['path'])) {
                Storage::disk('local')->delete($meta['path']);
            }
        }
    }
}
