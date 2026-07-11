<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NotificationDispatchService;
use App\Services\PlatformNotificationService;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(
        NotificationDispatchService $dispatch,
        PlatformNotificationService $activity,
    ): View {
        $user = auth()->user();

        $notifications = $dispatch->feed($user, 30);
        $platformActivity = $activity->feed(10);

        return view('admin.notifications.index', compact('notifications', 'platformActivity'));
    }
}
