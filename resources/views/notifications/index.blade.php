@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : (auth()->user()->isTeacher() ? 'layouts.professor' : 'layouts.student'))

@section('title', 'Notifications')

@section('content')
<x-admin-page-header kicker="Alertes" title="Notifications" subtitle="Votre fil d'activité personnel.">
    @if(($unreadNotifications ?? 0) > 0)
        <form method="POST" action="{{ route('notifications.read-all') }}">@csrf
            <button class="btn btn-outline btn-sm">Tout marquer comme lu</button>
        </form>
    @endif
</x-admin-page-header>

<section class="widget-card glass-card reveal-up">
    <div class="widget-body">
        <div class="activity-timeline">
            @forelse($notifications as $notification)
                <div class="activity-item {{ $notification->read_at ? '' : 'activity-item--unread' }}">
                    <span class="activity-icon"><i class="fas {{ app(\App\Services\NotificationDispatchService::class)->iconForType($notification->type) }}"></i></span>
                    <div class="activity-content">
                        <strong>{{ $notification->title }}</strong>
                        <p>{{ $notification->body }}</p>
                        <span class="activity-time">{{ $notification->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            @empty
                <div class="empty-state premium-empty"><p>Aucune notification.</p></div>
            @endforelse
        </div>
    </div>
</section>
@endsection

@section('styles')
<style>
.activity-timeline{display:flex;flex-direction:column;gap:14px}
.activity-item{display:flex;gap:14px;padding:16px;border-radius:14px;background:#faf9fb;border:1px solid rgba(0,0,0,0.05)}
.activity-item--unread{border-color:rgba(139,32,50,.25);background:#fff8f9}
.activity-icon{width:42px;height:42px;border-radius:12px;background:rgba(139,32,50,0.1);color:#8B2032;display:flex;align-items:center;justify-content:center}
.activity-time{font-size:0.8rem;color:#9ca3af}
.notif-item--unread{background:rgba(139,32,50,.04)}
</style>
@endsection
