<?php

namespace App\Http\Controllers;

use App\Services\CourseSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseSearchController extends Controller
{
    public function index(Request $request, CourseSearchService $search): View
    {
        $query = (string) $request->query('q', '');
        $payload = $search->search($query, auth()->user());

        return view('courses.search', $payload);
    }

    public function preview(Request $request, CourseSearchService $search): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        return response()->json($search->preview($validated['q']));
    }
}
