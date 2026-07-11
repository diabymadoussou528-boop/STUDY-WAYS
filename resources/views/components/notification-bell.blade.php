@props(['notificationsRoute' => null])

<div class="topbar-notif-wrap">
    <button class="topbar-icon-btn" id="notifToggle" title="Notifications" type="button">
        <i class="fas fa-bell"></i>
        @if(($unreadNotifications ?? 0) > 0)
            <span class="topbar-icon-badge">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
        @endif
    </button>
    <div class="notif-panel" id="notifPanel">
        <div class="notif-panel-header" style="display:flex;justify-content:space-between;align-items:center;">
            <span>Notifications</span>
            @if(($unreadNotifications ?? 0) > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-sm" style="font-size:.7rem;padding:2px 8px;">Tout lire</button>
                </form>
            @endif
        </div>
        @forelse($recentNotifications ?? [] as $notification)
            <div class="notif-item {{ $notification->read_at ? '' : 'notif-item--unread' }}">
                <span class="notif-item-icon"><i class="fas {{ app(\App\Services\NotificationDispatchService::class)->iconForType($notification->type) }}"></i></span>
                <div>
                    <strong>{{ $notification->title }}</strong>
                    <p>{{ $notification->body }}</p>
                    <small style="color:#9ca3af;">{{ $notification->created_at->diffForHumans() }}</small>
                </div>
            </div>
        @empty
            <div class="notif-item">
                <span class="notif-item-icon"><i class="fas fa-bell-slash"></i></span>
                <div><strong>Aucune notification</strong><p>Vous êtes à jour.</p></div>
            </div>
        @endforelse
        @if($notificationsRoute)
            <div style="padding:10px 16px;border-top:1px solid rgba(0,0,0,.06);">
                <a href="{{ $notificationsRoute }}" class="btn btn-outline btn-sm" style="width:100%;">Voir tout</a>
            </div>
        @endif
    </div>
</div>
