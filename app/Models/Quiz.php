<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    protected $fillable = [
        'course_id',
        'user_id',
        'title',
        'description',
        'time_limit_minutes',
        'max_attempts',
        'passing_score',
        'randomize_questions',
        'show_feedback',
        'is_published',
        'available_at',
        'due_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'randomize_questions' => 'boolean',
            'show_feedback' => 'boolean',
            'is_published' => 'boolean',
            'available_at' => 'datetime',
            'due_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function isAvailable(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        if ($this->available_at && $this->available_at->isFuture()) {
            return false;
        }

        if ($this->due_at && $this->due_at->isPast()) {
            return false;
        }

        return true;
    }
}
