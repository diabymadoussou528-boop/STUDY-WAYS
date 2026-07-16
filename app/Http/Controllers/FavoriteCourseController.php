<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FavoriteCourseController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        abort_unless($request->user()?->isStudent(), 403);
        abort_unless(Schema::hasTable('course_favorites'), 503);

        $favorites = $request->user()
            ->favoriteCourses()
            ->published()
            ->with(['user:id,name,avatar', 'category:id,name'])
            ->withCount(['enrollments', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->orderByPivot('created_at', 'desc')
            ->paginate(12);

        return view('student.favorites', compact('favorites'));
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        abort_unless($request->user()?->isStudent(), 403);
        abort_unless($course->isPublished(), 404);
        abort_unless(Schema::hasTable('course_favorites'), 503);

        $request->user()->favoriteCourses()->syncWithoutDetaching([$course->id]);

        return back()->with('success', 'Cours ajouté à vos favoris.');
    }

    public function destroy(Request $request, Course $course): RedirectResponse
    {
        abort_unless($request->user()?->isStudent(), 403);
        abort_unless(Schema::hasTable('course_favorites'), 503);

        $request->user()->favoriteCourses()->detach($course->id);

        return back()->with('success', 'Cours retiré de vos favoris.');
    }
}
