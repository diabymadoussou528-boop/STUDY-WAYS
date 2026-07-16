<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(): View
    {
        $query = request('q');
        $categorySlug = request('category');
        $difficulty = request('difficulty');
        $sort = request('sort', 'recent');

        $courses = Course::query()
            ->published()
            ->with(['user:id,name,avatar', 'category:id,name,slug'])
            ->withCount(['enrollments', 'reviews', 'lessons'])
            ->withAvg('reviews', 'rating')
            ->when($categorySlug, function ($q) use ($categorySlug) {
                $q->whereHas('category', fn ($cat) => $cat->where('slug', $categorySlug));
            })
            ->when($query, function ($q) use ($query) {
                $like = '%'.mb_strtolower($query).'%';
                $q->where(function ($inner) use ($like) {
                    $inner->whereRaw('LOWER(title) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(COALESCE(meta_keywords, "")) LIKE ?', [$like])
                        ->orWhereHas('category', fn ($cat) => $cat->whereRaw('LOWER(name) LIKE ?', [$like]));
                });
            })
            ->when($difficulty, fn ($q) => $q->where('difficulty', $difficulty))
            ->when($sort === 'popular', fn ($q) => $q->orderByDesc('enrollments_count')->orderByDesc('published_at'))
            ->when($sort === 'rating', fn ($q) => $q->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count'))
            ->when($sort === 'title', fn ($q) => $q->orderBy('title'))
            ->when(! in_array($sort, ['popular', 'rating', 'title'], true), fn ($q) => $q->latest('published_at'))
            ->paginate(12)
            ->withQueryString();

        $categories = Category::query()->withCount(['courses' => fn ($q) => $q->published()])->get();

        return view('courses.catalog-index', compact('courses', 'categories', 'query', 'sort', 'difficulty', 'categorySlug'));
    }
}
