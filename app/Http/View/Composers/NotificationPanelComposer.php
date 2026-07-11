<?php

namespace App\Http\View\Composers;

use App\Services\MessagingService;
use App\Services\NotificationDispatchService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class NotificationPanelComposer
{
    public function __construct(
        private NotificationDispatchService $notifications,
        private MessagingService $messaging,
    ) {}

    public function compose(View $view): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $recentNotifications = Schema::hasTable('platform_notifications')
            ? $this->notifications->feed($user, 8)
            : collect();

        $view->with([
            'unreadNotifications' => $this->notifications->unreadCount($user),
            'recentNotifications' => $recentNotifications,
            'unreadMessages' => Schema::hasTable('messages')
                ? $this->messaging->unreadCount($user)
                : ($view->getData()['unreadMessages'] ?? 0),
        ]);
    }
}
