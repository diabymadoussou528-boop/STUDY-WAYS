<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request, GlobalSearchService $search): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
            'scope' => ['nullable', 'in:all,courses,teachers,categories,lessons,messages,testimonials'],
        ]);

        $payload = $search->search(
            $validated['q'],
            auth()->user(),
            $validated['scope'] ?? 'all',
        );

        $payload['recent'] = $search->recentSearches(auth()->user());

        return response()->json($payload);
    }
}
