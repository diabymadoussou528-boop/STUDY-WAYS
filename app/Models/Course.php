<?php

namespace App\Models;

use App\Enums\CourseStatus;
use App\Enums\MediaCategory;
use App\Services\MediaStorageService;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'short_description',
        'slug',
        'thumbnail',
        'video_url',
        'video_path',
        'category_id',
        'user_id',
        'status',
        'price',
        'is_premium_only',
        'difficulty',
        'duration_minutes',
        'requirements',
        'objectives',
        'faq',
        'views',
        'submitted_at',
        'published_at',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
        'canonical_url',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CourseStatus::class,
            'price' => 'decimal:2',
            'is_premium_only' => 'boolean',
            'requirements' => 'array',
            'objectives' => 'array',
            'faq' => 'array',
            'submitted_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Course $course) {
            if (blank($course->slug)) {
                $course->slug = Str::slug($course->title).'-'.Str::random(4);
            }
            if (! $course->status) {
                $course->status = CourseStatus::Draft;
            }
        });

        static::deleting(function (Course $course) {
            $media = app(MediaStorageService::class);
            $media->delete($course->thumbnail, MediaCategory::CourseThumbnail);
            $media->delete($course->video_path, MediaCategory::CourseVideo);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('sort_order')->orderBy('id');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('sort_order')->orderBy('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CourseStatus::Published);
    }

    public function isPublished(): bool
    {
        return $this->status === CourseStatus::Published;
    }

    public function isFree(): bool
    {
        return (float) $this->price <= 0;
    }

    public function averageRating(): ?float
    {
        return $this->reviews()->avg('rating');
    }

    public function thumbnailUrl(): string
    {
        if ($this->thumbnail) {
            return app(MediaStorageService::class)->url($this->thumbnail, MediaCategory::CourseThumbnail)
                ?? asset('storage/'.$this->thumbnail);
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($this->title).'&background=8b2032&color=fff&size=256';
    }

    public function videoUrl(): ?string
    {
        if (filled($this->video_url)) {
            return $this->video_url;
        }

        if (filled($this->video_path)) {
            return app(MediaStorageService::class)->url($this->video_path, MediaCategory::CourseVideo);
        }

        return null;
    }
}
