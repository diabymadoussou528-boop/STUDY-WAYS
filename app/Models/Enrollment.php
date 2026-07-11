<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'status',
        'progress',
        'current_lesson_id',
        'enrolled_at',
        'last_accessed_at',
        'completed_at',
        'certificate_eligible',
        'certificate_number',
        'certificate_issued_at',
        'verification_token',
        'cancelled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'enrolled_at' => 'datetime',
            'last_accessed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'certificate_eligible' => 'boolean',
            'certificate_issued_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function currentLesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'current_lesson_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === EnrollmentStatus::Completed
            || $this->progress >= 100
            || $this->completed_at !== null;
    }

    public function isActive(): bool
    {
        return $this->status === EnrollmentStatus::Active;
    }
}
