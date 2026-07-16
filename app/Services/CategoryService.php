<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Str;

class CategoryService
{
    public function normalize(string $name): string
    {
        return Str::of($name)
            ->lower()
            ->ascii()
            ->replaceMatches('/\s+/u', ' ')
            ->trim()
            ->toString();
    }

    public function displayName(string $name): string
    {
        $collapsed = Str::of($name)
            ->replaceMatches('/\s+/u', ' ')
            ->trim()
            ->toString();

        if ($collapsed === '') {
            return 'Sans catégorie';
        }

        return Str::title($collapsed);
    }

    public function findOrCreate(string $name): Category
    {
        $display = $this->displayName($name);
        $normalized = $this->normalize($display);

        if ($normalized === '') {
            $normalized = 'sans-categorie';
            $display = 'Sans catégorie';
        }

        $existing = Category::query()
            ->where('normalized_name', $normalized)
            ->first();

        if ($existing) {
            return $existing;
        }

        $slugBase = Str::slug($display) ?: 'category';
        $slug = $slugBase;
        $suffix = 1;

        while (Category::query()->where('slug', $slug)->exists()) {
            $slug = $slugBase.'-'.$suffix;
            $suffix++;
        }

        return Category::query()->create([
            'name' => $display,
            'slug' => $slug,
            'normalized_name' => $normalized,
        ]);
    }

    /**
     * @return list<string>
     */
    public function suggestions(?string $term = null, int $limit = 12): array
    {
        return Category::query()
            ->when($term, function ($query) use ($term) {
                $normalized = $this->normalize($term);

                $query->where(function ($inner) use ($term, $normalized) {
                    $inner->where('name', 'like', '%'.$term.'%')
                        ->orWhere('normalized_name', 'like', '%'.$normalized.'%');
                });
            })
            ->orderBy('name')
            ->limit($limit)
            ->pluck('name')
            ->all();
    }
}
