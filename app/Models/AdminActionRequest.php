<?php

namespace App\Models;

use App\Enums\AdminActionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActionRequest extends Model
{
    protected $fillable = [
        'requested_by',
        'action',
        'target_type',
        'target_id',
        'payload',
        'title',
        'description',
        'status',
        'reviewed_by',
        'review_note',
        'reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => AdminActionStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === AdminActionStatus::Pending;
    }

    public function resolveTarget(): ?Model
    {
        if (! $this->target_type || ! $this->target_id) {
            return null;
        }

        if (! class_exists($this->target_type)) {
            return null;
        }

        return $this->target_type::query()->find($this->target_id);
    }
}
