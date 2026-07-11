<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | STORE OR UPDATE REVIEW
    |--------------------------------------------------------------------------
    */
    public function store(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        Review::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'course_id' => $id,
            ],
            [
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]
        );

        return back()->with('success', 'Review saved');
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW REVIEWS FOR A COURSE (OPTIONNEL)
    |--------------------------------------------------------------------------
    */
    public function show($id)
    {
        $course = Course::findOrFail($id);

        return view('courses.show', compact('course'));
    }
}
