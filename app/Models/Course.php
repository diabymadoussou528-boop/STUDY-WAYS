<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\CourseStatus;
use App\Enums\MediaCategory;
use App\Services\MediaStorageService;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'creator_id',
        'teacher_id',
        'status',
        'approval_status',
        'google_drive_thumbnail_id',
        'google_drive_video_id',
        'google_drive_thumbnail_url',
        'google_drive_video_url',
        'thumbnail_url',
        'thumbnail_drive_id',
        'video_drive_id',
        'upload_status',
        'price',
        'is_premium_only',
        'difficulty',
        'language',
        'tags',
        'duration_minutes',
        'requirements',
        'objectives',
        'faq',
        'views',
        'submitted_at',
        'published_at',
        'approved_by',
        'approved_at',
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
            'approval_status' => ApprovalStatus::class,
            'price' => 'decimal:2',
            'is_premium_only' => 'boolean',
            'requirements' => 'array',
            'objectives' => 'array',
            'faq' => 'array',
            'submitted_at' => 'datetime',
            'published_at' => 'datetime',
            'approved_at' => 'datetime',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_favorites')->withTimestamps();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CourseStatus::Published);
    }

    public function isPublished(): bool
    {
        return $this->status === CourseStatus::Published;
    }

    public function isPendingApproval(): bool
    {
        return $this->approval_status === ApprovalStatus::Pending;
    }

    public function requiresApproval(): bool
    {
        return $this->approval_status === ApprovalStatus::Pending
            || $this->approval_status === ApprovalStatus::Rejected;
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
            $localUrl = app(MediaStorageService::class)->url($this->thumbnail, MediaCategory::CourseThumbnail);

            if (filled($localUrl) && ! str_starts_with((string) $this->thumbnail, 'google://')) {
                return $localUrl;
            }

            if (filled($localUrl)) {
                return $localUrl;
            }

            if (! str_starts_with((string) $this->thumbnail, 'google://')) {
                return asset('storage/'.$this->thumbnail);
            }
        }

        if (filled($this->thumbnail_url) && ! str_contains((string) $this->thumbnail_url, 'drive.google')) {
            return $this->thumbnail_url;
        }

        if (filled($this->google_drive_thumbnail_url)) {
            return $this->google_drive_thumbnail_url;
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($this->title).'&background=8b2032&color=fff&size=256';
    }

    public function videoUrl(): ?string
    {
        // Prefer app streaming for any stored file (local disk or Drive reference).
        // Never return raw Google Drive share URLs — browsers cannot play them in <video>.
        if ($this->hasStreamableVideo()) {
            return route('courses.video.stream', $this);
        }

        if (filled($this->video_url) && ! $this->isGoogleDriveUrl($this->video_url)) {
            return $this->video_url;
        }

        return null;
    }

    public function hasStreamableVideo(): bool
    {
        return filled($this->video_path)
            || filled($this->video_drive_id)
            || filled($this->google_drive_video_id);
    }

    public function hasProcessedMedia(): bool
    {
        return $this->upload_status === 'completed'
            || (filled($this->thumbnail) && filled($this->video_path))
            || filled($this->video_drive_id)
            || filled($this->google_drive_video_id);
    }

    private function isGoogleDriveUrl(string $url): bool
    {
        return str_contains($url, 'drive.google.com')
            || str_contains($url, 'docs.google.com')
            || str_contains($url, 'googleusercontent.com');
    }
}
