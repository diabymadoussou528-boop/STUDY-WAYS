<?php

namespace App\Models;

use App\Enums\MediaCategory;
use App\Notifications\ResetPasswordNotification;
use App\Services\MediaStorageService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'avatar',
        'bio',
        'specialization',
        'email_verified_at',
        'is_active',
        'is_super_admin',
        'first_login',
        'is_premium',
        'premium_plan',
        'current_streak',
        'longest_streak',
        'last_study_date',
        'total_study_minutes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
            'first_login' => 'boolean',
            'is_premium' => 'boolean',
            'last_study_date' => 'date',
        ];
    }

    protected $hidden = ['password', 'remember_token'];

    /**
     * Send the branded password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function isSimpleAdmin(): bool
    {
        return $this->isAdmin() && ! $this->isSuperAdmin();
    }

    public function mustChangePassword(): bool
    {
        return $this->isSimpleAdmin() && (bool) $this->first_login;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function nameParts(): array
    {
        $parts = preg_split('/\s+/', trim($this->name), 2) ?: [];

        return [
            $parts[0] ?? $this->name,
            $parts[1] ?? '',
        ];
    }

    public function isTeacher(): bool
    {
        return in_array($this->role, ['teacher', 'professor'], true);
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function canViewHomepageUpgradeSection(): bool
    {
        return $this->isStudent();
    }

    public function hasUploadedAvatar(): bool
    {
        return filled($this->avatar);
    }

    /**
     * Get the URL for the user's avatar.
     */
    public function avatarUrl(): string
    {
        if ($this->hasUploadedAvatar()) {
            return app(MediaStorageService::class)->url($this->avatar, MediaCategory::Avatar)
                ?? asset('storage/'.$this->avatar);
        }

        $initials = collect(explode(' ', $this->name))
            ->map(fn ($word) => strtoupper($word[0]))
            ->take(2)
            ->join('');

        return 'https://ui-avatars.com/api/?name='.urlencode($this->name)
            .'&background=8b2032&color=fff&bold=true&size=128';
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function favoriteCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_favorites')->withTimestamps();
    }

    public function taughtCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'user_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function platformNotifications(): HasMany
    {
        return $this->hasMany(PlatformNotification::class);
    }

    public function hasActivePremium(): bool
    {
        if (! $this->is_premium) {
            return false;
        }

        $active = $this->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->exists();

        return $active || ! Schema::hasTable('subscriptions');
    }
}
