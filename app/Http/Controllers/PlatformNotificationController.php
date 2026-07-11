<?php

namespace App\Http\Controllers;

use App\Models\PlatformNotification;
use App\Services\NotificationDispatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlatformNotificationController extends Controller
{
    public function markRead(PlatformNotification $notification, NotificationDispatchService $service): RedirectResponse
    {
        abort_unless((int) $notification->user_id === (int) auth()->id(), 403);

        $service->markAsRead($notification);

        return back();
    }

    public function markAllRead(NotificationDispatchService $service): RedirectResponse
    {
        $service->markAllAsRead(auth()->user());

        return back()->with('success', 'Notifications marquées comme lues.');
    }

    public function index(NotificationDispatchService $service): View
    {
        $notifications = $service->feed(auth()->user(), 30);

        return view('notifications.index', compact('notifications'));
    }
}
