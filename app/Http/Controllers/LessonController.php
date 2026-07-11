<?php

namespace App\Http\Controllers;

use App\Enums\LessonType;
use App\Enums\MediaCategory;
use App\Exceptions\MediaUploadException;
use App\Http\Requests\StoreLessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\MediaStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function create(int $course): View
    {
        $courseModel = Course::query()->findOrFail($course);

        if (! auth()->user()?->isAdmin() && (int) $courseModel->user_id !== (int) auth()->id()) {
            abort(403);
        }

        return view('lessons.create', ['course_id' => $courseModel->id]);
    }

    public function store(StoreLessonRequest $request, MediaStorageService $mediaStorage): RedirectResponse
    {
        $data = [
            'title' => $request->title,
            'video_url' => $request->video_url,
            'course_id' => $request->course_id,
        ];

        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $mime = (string) $file->getMimeType();
                $isVideo = str_starts_with($mime, 'video/');

                if ($isVideo) {
                    $data['resource_path'] = $mediaStorage->upload($file, MediaCategory::LessonVideo);
                    $data['lesson_type'] = LessonType::Video;
                } else {
                    $data['resource_path'] = $mediaStorage->upload($file, MediaCategory::LessonResource);
                }
            }
        } catch (MediaUploadException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        Lesson::create($data);

        return back()->with('success', 'Leçon ajoutée.');
    }
}
