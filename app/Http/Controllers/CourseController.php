<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Exceptions\MediaUploadException;
use App\Http\Requests\StoreCourseRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Services\AdminActionRequestService;
use App\Services\CategoryService;
use App\Services\CourseAuthoringService;
use App\Services\CoursePublishingService;
use App\Services\CourseShowService;
use App\Services\MediaStorageService;
use App\Services\NotificationDispatchService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseController extends Controller
{
    use AuthorizesRequests;

    public function create(CategoryService $categoryService): View
    {
        $this->authorize('create', Course::class);

        return view('courses.create', [
            'categorySuggestions' => $categoryService->suggestions(),
            'storeRoute' => auth()->user()?->isAdmin() && ! auth()->user()?->isTeacher()
                ? route('admin.courses.store')
                : route('courses.store'),
        ]);
    }

    public function store(StoreCourseRequest $request, CourseAuthoringService $authoring, CoursePublishingService $publishing, AdminActionRequestService $approvalService): RedirectResponse
    {
        $this->authorize('create', Course::class);

        try {
            $course = $authoring->create(
                $request->user(),
                $request->validated(),
                $request->file('thumbnail'),
                $request->file('video'),
            );

            $user = $request->user();

            if ($user->isSuperAdmin()) {
                $publishing->publish($course, $user);
                $course->update([
                    'approval_status' => ApprovalStatus::Approved,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);
                $message = 'Cours « '.$course->title.' » créé et publié avec succès.';
            } elseif ($user->isTeacher()) {
                $publishing->publish($course, $user);
                $course->update([
                    'approval_status' => ApprovalStatus::Approved,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);
                $this->notifySuperAdminCoursePublished($course, $user);
                $message = 'Cours « '.$course->title.' » créé et publié avec succès.';
            } elseif ($user->isAdmin()) {
                $course->update(['approval_status' => ApprovalStatus::Pending]);
                $approvalService->submit(
                    $user,
                    'create_course',
                    'Création d’un cours',
                    'Un nouveau cours attend l’approbation du Super Admin.',
                    $course,
                    ['course_id' => $course->id],
                );
                $this->notifySuperAdminApprovalRequest($course, $user);
                $message = 'Cours « '.$course->title.' » créé avec succès. Une demande d’approbation a été envoyée.';
            } else {
                $message = 'Cours « '.$course->title.' » créé avec succès.';
            }
        } catch (MediaUploadException $exception) {
            Log::error('Échec du téléversement média lors de la création du cours : '.$exception->getMessage(), [
                'exception' => $exception,
                'user_id' => auth()->id(),
            ]);

            return back()->withInput()->with('error', 'Erreur de téléversement : '.$exception->getMessage());
        } catch (\Throwable $exception) {
            Log::error('Erreur inattendue lors de la création du cours : '.$exception->getMessage(), [
                'exception' => $exception,
                'user_id' => auth()->id(),
            ]);

            return back()->withInput()->with('error', 'Erreur lors de la création du cours : '.$exception->getMessage());
        }

        $route = $request->user()->isTeacher()
            ? 'professor.courses.index'
            : 'admin.courses';

        return redirect()
            ->route($route)
            ->with('success', $message);
    }

    public function destroy(Course $course, CourseAuthoringService $authoring): RedirectResponse
    {
        $this->authorize('delete', $course);

        $authoring->delete($course);

        return back()->with('success', 'Cours supprimé avec succès.');
    }

    public function edit(Course $course, CategoryService $categoryService): View
    {
        $this->authorize('update', $course);

        return view('courses.edit', [
            'course' => $course->load('category'),
            'categorySuggestions' => $categoryService->suggestions(),
        ]);
    }

    public function update(StoreCourseRequest $request, Course $course, CourseAuthoringService $authoring): RedirectResponse
    {
        $this->authorize('update', $course);

        try {
            $authoring->update(
                $course,
                $request->validated(),
                $request->file('thumbnail'),
                $request->file('video'),
                $request->boolean('remove_video'),
            );
        } catch (MediaUploadException $exception) {
            Log::error('Échec du téléversement média lors de la mise à jour du cours : '.$exception->getMessage(), [
                'course_id' => $course->id,
                'exception' => $exception,
            ]);

            return back()->withInput()->with('error', 'Erreur de téléversement : '.$exception->getMessage());
        } catch (\Throwable $exception) {
            Log::error('Erreur inattendue lors de la mise à jour du cours : '.$exception->getMessage(), [
                'course_id' => $course->id,
                'exception' => $exception,
            ]);

            return back()->withInput()->with('error', 'Erreur lors de la modification du cours : '.$exception->getMessage());
        }

        $route = $request->user()->isTeacher()
            ? 'professor.courses.index'
            : 'admin.courses';

        return redirect()->route($route)->with('success', 'Cours mis à jour avec succès.');
    }

    public function show(Course $course, CourseShowService $courseShowService): View
    {
        if (! $course->isPublished() && ! auth()->user()?->can('update', $course)) {
            abort(404);
        }

        $course->increment('views');

        $payload = $courseShowService->build($course, auth()->user());

        return view('courses.show', $payload);
    }

    public function categorySuggestions(Request $request, CategoryService $categoryService): JsonResponse
    {
        $this->authorize('create', Course::class);

        return response()->json([
            'suggestions' => $categoryService->suggestions($request->string('q')->toString()),
        ]);
    }

    /**
     * Notify all Super Admins that a teacher just published a course.
     */
    private function notifySuperAdminCoursePublished(Course $course, User $teacher): void
    {
        app(NotificationDispatchService::class)->notifyAdmins(
            'teacher_course_published',
            'Nouveau cours publié',
            $teacher->name.' a publié le cours « '.$course->title.' ».',
            [
                'course_id' => $course->id,
                'teacher_id' => $teacher->id,
                'link' => route('courses.show', $course),
            ],
        );
    }

    /**
     * Notify all Super Admins that a simple admin requested course approval.
     */
    private function notifySuperAdminApprovalRequest(Course $course, User $admin): void
    {
        app(NotificationDispatchService::class)->notifyAdmins(
            'approval_request',
            'Demande d’approbation',
            $admin->name.' a soumis une demande d’approbation pour le cours « '.$course->title.' ».',
            [
                'course_id' => $course->id,
                'admin_id' => $admin->id,
                'action' => 'create_course',
            ],
        );
    }

    /**
     * Pollable endpoint so the course create form can show real upload progress
     * (pending → processing → completed/failed) after the media is processed in a queue.
     */
    public function uploadStatus(Course $course): JsonResponse
    {
        $this->authorize('view', $course);

        return response()->json([
            'upload_status' => $course->upload_status,
            'thumbnail_url' => $course->thumbnailUrl(),
            'video_url' => $course->videoUrl(),
            'completed' => $course->upload_status === 'completed',
            'failed' => $course->upload_status === 'failed',
        ]);
    }

    /**
     * Stream a course video directly from storage (Google Drive or local) so it
     * remains playable in the browser regardless of the file's sharing visibility.
     *
     * Implements HTTP 206 Partial Content so HTML5 video players can seek and
     * buffer correctly — browsers refuse to play video without byte-range support.
     */
    public function streamVideo(Course $course, MediaStorageService $media): StreamedResponse
    {
        [$disk, $path] = $this->resolveCourseVideoSource($course);

        if ($path === null || ! $disk->exists($path)) {
            abort(404);
        }

        $size = $disk->size($path);
        $mime = $disk->mimeType($path) ?: 'video/mp4';

        $start = 0;
        $end = $size - 1;
        $statusCode = 200;

        $rangeHeader = request()->header('Range');

        if ($rangeHeader && preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $matches)) {
            $start = $matches[1] !== '' ? (int) $matches[1] : 0;
            $end = $matches[2] !== '' ? (int) $matches[2] : $size - 1;

            // Clamp end to the last byte
            $end = min($end, $size - 1);

            if ($start > $end || $start >= $size) {
                return response('Range Not Satisfiable', 416, [
                    'Content-Range' => "bytes */{$size}",
                ]);
            }

            $statusCode = 206;
        }

        $length = $end - $start + 1;

        $headers = [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline',
            'Accept-Ranges' => 'bytes',
            'Content-Length' => $length,
            'Cache-Control' => 'private, max-age=3600',
        ];

        if ($statusCode === 206) {
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
        }

        $stream = $disk->readStream($path);

        if ($start > 0) {
            fseek($stream, $start);
        }

        return response()->stream(function () use ($stream, $length) {
            $remaining = $length;
            $chunk = 64 * 1024; // 64 KB chunks

            while ($remaining > 0 && ! feof($stream)) {
                $read = fread($stream, min($chunk, $remaining));
                if ($read === false) {
                    break;
                }
                echo $read;
                $remaining -= strlen($read);
                flush();
            }

            fclose($stream);
        }, $statusCode, $headers);
    }

    /**
     * Prefer local public-disk files whenever they exist so playback does not
     * depend on Google Drive sharing or credentials.
     *
     * @return array{0: Filesystem, 1: string|null}
     */
    private function resolveCourseVideoSource(Course $course): array
    {
        $stored = $course->video_path;

        if (filled($stored) && ! str_starts_with($stored, 'google://') && ! str_starts_with($stored, 'http')) {
            if (Storage::disk('public')->exists($stored)) {
                return [Storage::disk('public'), $stored];
            }
        }

        if (filled($stored) && str_starts_with($stored, 'google://')) {
            return [Storage::disk('google'), Str::after($stored, 'google://')];
        }

        $driveId = $course->video_drive_id ?: $course->google_drive_video_id;

        if (filled($driveId)) {
            return [Storage::disk('google'), $driveId];
        }

        if (filled($stored) && ! str_starts_with($stored, 'http')) {
            return [Storage::disk('public'), $stored];
        }

        return [Storage::disk('public'), null];
    }

    /**
     * Stream a lesson video with HTTP 206 Range support.
     */
    public function streamLesson(Lesson $lesson): StreamedResponse
    {
        $path = $lesson->resource_path;

        if (blank($path)) {
            abort(404);
        }

        if (str_starts_with($path, 'google://')) {
            $cleanPath = Str::after($path, 'google://');
            $disk = Storage::disk('google');
        } else {
            $cleanPath = $path;
            $disk = Storage::disk('public');
        }

        if (! $disk->exists($cleanPath)) {
            abort(404);
        }

        $size = $disk->size($cleanPath);
        $mime = $disk->mimeType($cleanPath) ?: 'video/mp4';
        $start = 0;
        $end = $size - 1;
        $statusCode = 200;

        $rangeHeader = request()->header('Range');

        if ($rangeHeader && preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $matches)) {
            $start = $matches[1] !== '' ? (int) $matches[1] : 0;
            $end = $matches[2] !== '' ? (int) $matches[2] : $size - 1;
            $end = min($end, $size - 1);

            if ($start > $end || $start >= $size) {
                return response('Range Not Satisfiable', 416, [
                    'Content-Range' => "bytes */{$size}",
                ]);
            }

            $statusCode = 206;
        }

        $length = $end - $start + 1;
        $stream = $disk->readStream($cleanPath);

        if ($start > 0) {
            fseek($stream, $start);
        }

        $headers = [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline',
            'Accept-Ranges' => 'bytes',
            'Content-Length' => $length,
            'Cache-Control' => 'private, max-age=3600',
        ];

        if ($statusCode === 206) {
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
        }

        return response()->stream(function () use ($stream, $length) {
            $remaining = $length;
            $chunk = 64 * 1024;

            while ($remaining > 0 && ! feof($stream)) {
                $read = fread($stream, min($chunk, $remaining));
                if ($read === false) {
                    break;
                }
                echo $read;
                $remaining -= strlen($read);
                flush();
            }

            fclose($stream);
        }, $statusCode, $headers);
    }
}
