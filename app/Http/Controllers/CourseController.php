<?php

namespace App\Http\Controllers;

use App\Enums\CourseStatus;
use App\Enums\MediaCategory;
use App\Exceptions\MediaUploadException;
use App\Http\Requests\StoreCourseRequest;
use App\Models\Category;
use App\Models\Course;
use App\Services\CourseShowService;
use App\Services\MediaStorageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function create()
    {
        $categories = Category::query()->orderBy('name')->get();

        return view('courses.create', compact('categories'));
    }

    public function store(StoreCourseRequest $request, MediaStorageService $mediaStorage)
    {
        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'video_url' => $request->video_url,
            'user_id' => Auth::id(),
            'status' => CourseStatus::Draft,
            'price' => $request->input('price', 0),
            'difficulty' => $request->difficulty,
        ];

        try {
            if ($request->hasFile('thumbnail')) {
                $data['thumbnail'] = $mediaStorage->upload(
                    $request->file('thumbnail'),
                    MediaCategory::CourseThumbnail,
                );
            }

            if ($request->hasFile('video') && $request->file('video')->isValid()) {
                $data['video_path'] = $mediaStorage->upload(
                    $request->file('video'),
                    MediaCategory::CourseVideo,
                );
                $data['video_url'] = null;
            }
        } catch (MediaUploadException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        Course::create($data);

        return redirect()->route('professor.dashboard')->with('success', 'Cours créé en brouillon.');
    }

    public function destroy($id, MediaStorageService $mediaStorage)
    {
        $course = Course::findOrFail($id);

        if ($course->user_id !== Auth::id()) {
            abort(403);
        }

        $mediaStorage->delete($course->thumbnail, MediaCategory::CourseThumbnail);
        $mediaStorage->delete($course->video_path, MediaCategory::CourseVideo);

        $course->delete();

        return back()->with('success', 'Course deleted');
    }

    public function edit(Course $course): View
    {
        if (! auth()->user()?->isAdmin() && (int) $course->user_id !== (int) auth()->id()) {
            abort(403);
        }
        $categories = Category::query()->orderBy('name')->get();

        return view('courses.edit', compact('course', 'categories'));
    }

    public function update(StoreCourseRequest $request, Course $course, MediaStorageService $mediaStorage)
    {
        if (! auth()->user()?->isAdmin() && (int) $course->user_id !== (int) auth()->id()) {
            abort(403);
        }

        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'video_url' => $request->video_url,
            'price' => $request->input('price', 0),
            'difficulty' => $request->difficulty,
        ];

        try {
            if ($request->hasFile('thumbnail')) {
                if ($course->thumbnail) {
                    $mediaStorage->delete($course->thumbnail, MediaCategory::CourseThumbnail);
                }
                $data['thumbnail'] = $mediaStorage->upload(
                    $request->file('thumbnail'),
                    MediaCategory::CourseThumbnail,
                );
            }

            if ($request->hasFile('video') && $request->file('video')->isValid()) {
                if ($course->video_path) {
                    $mediaStorage->delete($course->video_path, MediaCategory::CourseVideo);
                }
                $data['video_path'] = $mediaStorage->upload(
                    $request->file('video'),
                    MediaCategory::CourseVideo,
                );
                $data['video_url'] = null;
            }
        } catch (\Exception $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $course->update($data);

        $route = auth()->user()->isTeacher() ? 'professor.courses.index' : 'admin.courses';

        return redirect()->route($route)->with('success', 'Cours mis à jour avec succès.');
    }

    public function show(Course $course, CourseShowService $courseShowService): View
    {
        if (! $course->isPublished() && ! auth()->user()?->isAdmin() && (int) $course->user_id !== (int) auth()->id()) {
            abort(404);
        }

        $course->increment('views');

        $payload = $courseShowService->build($course, auth()->user());

        return view('courses.show', $payload);
    }
}
