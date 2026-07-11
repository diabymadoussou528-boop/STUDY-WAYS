<?php

namespace App\Services;

use App\Models\PlatformNotification;
use App\Models\User;
use App\Notifications\LmsEventNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

class NotificationDispatchService
{
    public function notify(?User $user, string $type, string $title, ?string $body = null, ?array $data = null, bool $sendEmail = true): ?PlatformNotification
    {
        if (! $user || ! Schema::hasTable('platform_notifications')) {
            return null;
        }

        $notification = PlatformNotification::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        if ($sendEmail && $this->shouldSendEmail($type)) {
            $this->sendEmail($user, $type, $title, $body, $data);
        }

        return $notification;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function notifyAdmins(string $type, string $title, ?string $body = null, ?array $data = null): void
    {
        User::query()
            ->where('role', 'admin')
            ->each(fn (User $admin) => $this->notify($admin, $type, $title, $body, $data));
    }

    public function unreadCount(User $user): int
    {
        if (! Schema::hasTable('platform_notifications')) {
            return 0;
        }

        return PlatformNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return Collection<int, PlatformNotification>
     */
    public function feed(User $user, int $limit = 15): Collection
    {
        if (! Schema::hasTable('platform_notifications')) {
            return collect();
        }

        return PlatformNotification::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function markAsRead(PlatformNotification $notification): void
    {
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }
    }

    public function markAllAsRead(User $user): void
    {
        PlatformNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function iconForType(string $type): string
    {
        return match ($type) {
            'new_enrollment', 'enrollment_confirmed' => 'fa-user-plus',
            'appointment_request', 'appointment_accepted', 'appointment_rejected', 'appointment_rescheduled', 'appointment_cancelled' => 'fa-calendar-check',
            'course_published', 'course_approved' => 'fa-book',
            'premium_subscription', 'payment_received' => 'fa-crown',
            'approval_request' => 'fa-clipboard-check',
            'quiz_completed', 'quiz_graded', 'quiz_pending_grading' => 'fa-clipboard-question',
            default => 'fa-bell',
        };
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function sendEmail(User $user, string $type, string $title, ?string $body, ?array $data): void
    {
        try {
            Notification::send($user, new LmsEventNotification($type, $title, $body, $data));
        } catch (\Throwable $exception) {
            Log::warning('LMS email notification failed', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function shouldSendEmail(string $type): bool
    {
        if (! config('notifications.email_enabled', true)) {
            return false;
        }

        if (blank(config('mail.from.address'))) {
            return false;
        }

        return in_array($type, config('notifications.email_types', []), true);
    }
}
