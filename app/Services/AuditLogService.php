<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Support\UserAgentParser;

class AuditLogService
{
    public function log(
        string $action,
        ?string $module = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $target = null,
        ?array $metadata = null,
        ?User $actor = null,
    ): AdminAuditLog {
        $user = $actor ?? auth()->user();
        $parsed = UserAgentParser::parse(request()->userAgent());

        return AdminAuditLog::query()->create([
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
