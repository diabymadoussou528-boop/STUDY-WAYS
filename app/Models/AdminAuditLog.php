<?php

namespace App\Models;

use App\Support\UserAgentParser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'role',
        'action',
        'module',
        'description',
        'old_values',
        'new_values',
        'target_user_id',
        'metadata',
        'ip_address',
        'user_agent',
        'browser',
        'operating_system',
        'device',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public static function record(string $action, ?User $target = null, ?array $metadata = null): self
    {
        return self::recordDetailed($action, null, null, null, null, $target, $metadata);
    }

    public static function recordDetailed(
        string $action,
        ?string $module = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $target = null,
        ?array $metadata = null,
    ): self {
        $user = auth()->user();
        $parsed = UserAgentParser::parse(request()->userAgent());

        return self::query()->create([
            'user_id' => $user?->id,
            'role' => $user?->role,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'target_user_id' => $target?->id,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'browser' => $parsed['browser'],
            'operating_system' => $parsed['operating_system'],
            'device' => $parsed['device'],
        ]);
    }
}
