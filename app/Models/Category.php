<?php

namespace App\Models;

use App\Services\CategoryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'normalized_name'];

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if (filled($category->name) && (blank($category->normalized_name) || $category->isDirty('name'))) {
                $service = app(CategoryService::class);
                $base = $service->normalize($category->name) ?: 'category';
                $candidate = $base;
                $suffix = 1;

                while (
                    static::query()
                        ->where('normalized_name', $candidate)
                        ->when($category->exists, fn ($q) => $q->whereKeyNot($category->getKey()))
                        ->exists()
                ) {
                    $candidate = $base.'-'.$suffix;
                    $suffix++;
                }

                $category->normalized_name = $candidate;
            }

            if (blank($category->slug) && filled($category->name)) {
                $category->slug = Str::slug($category->name) ?: 'category-'.Str::random(4);
            }
        });
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
