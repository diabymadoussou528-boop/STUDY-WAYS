@extends('layouts.professor')

@section('title', 'Dashboard')

@section('content')

<div class="dash-header reveal-up">
    <div class="dash-header-main">
        <span class="dash-kicker"><span class="pulse-dot"></span> Espace professeur · {{ now()->translatedFormat('l d F') }}</span>
        <h1 class="dash-title">Bonjour, <span class="dash-title-accent">{{ explode(' ', auth()->user()->name)[0] }}</span></h1>
        <p class="dash-subtitle">Gérez vos cours, vos étudiants et vos rendez-vous.</p>
        <div class="dash-chips">
            <span class="dash-chip"><i class="fas fa-book"></i> {{ $courses->count() }} cours</span>
            <span class="dash-chip"><i class="fas fa-user-graduate"></i> {{ number_format($heroStats[1]['value'] ?? 0) }} étudiants</span>
            <span class="dash-chip dash-chip--live"><i class="fas fa-signal"></i> Espace actif</span>
        </div>
    </div>
    <div class="dash-header-aside">
        <a href="{{ route('courses.create') }}" class="btn btn-primary btn-glow">
            <i class="fas fa-plus"></i> Créer un cours
        </a>
    </div>
</div>

@if($pendingAppointments->count() > 0)
    <div class="pending-strip reveal-up">
        <div class="pending-strip-icon"><i class="fas fa-calendar"></i></div>
        <div class="pending-strip-text">
            <strong>{{ $pendingAppointments->count() }} rendez-vous en attente</strong>
            <span>Des étudiants attendent votre confirmation.</span>
        </div>
        <a href="{{ route('professor.appointments') }}" class="btn btn-primary btn-sm">Voir</a>
    </div>
@endif

<div class="kpi-grid kpi-grid--hero reveal-stagger">
    @foreach($heroStats as $stat)
        <article class="kpi-card kpi-card--modern glass-card" data-animate data-tilt>
            <div class="kpi-card-top">
                <div class="kpi-icon"><i class="fas {{ $stat['icon'] }}"></i></div>
                @if($stat['delta'] !== null)
                    <span class="kpi-delta kpi-delta--{{ $stat['deltaDirection'] }}">
                        <i class="fas fa-arrow-{{ $stat['deltaDirection'] === 'up' ? 'up' : 'down' }}"></i>
                        {{ abs($stat['delta']) }}%
                    </span>
                @endif
            </div>
            <div class="kpi-value" @if(is_numeric($stat['value'])) data-counter="{{ $stat['value'] }}" @endif>{{ $stat['value'] }}</div>
            <div class="kpi-label">{{ $stat['label'] }}</div>
            <span class="kpi-status"><i class="fas fa-circle" style="font-size:0.4rem"></i> Actif</span>
        </article>
    @endforeach
</div>

<div class="dash-grid reveal-up">
    <section class="widget-card glass-card">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">Mes cours</h3>
                <p class="widget-subtitle">Performance et gestion de votre contenu</p>
            </div>
            <a href="{{ route('professor.courses.index') }}" class="btn btn-outline btn-sm">Tout voir</a>
        </div>
        <div class="widget-body widget-body--flush">
            <div class="table-scroll">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Cours</th>
                            <th>Étudiants</th>
                            <th>Leçons</th>
                            <th>Note</th>
                            <th>Vues</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($courses->take(8) as $course)
                            <tr>
                                <td>
                                    <div class="table-course">
                                        <span class="table-course-icon"><i class="fas fa-book"></i></span>
                                        <span>{{ Str::limit($course->title, 34) }}</span>
                                    </div>
                                </td>
                                <td>{{ number_format($course->enrollments_count ?? 0) }}</td>
                                <td>{{ $course->lessons_count ?? 0 }}</td>
                                <td>
                                    <span class="rating-pill">★ {{ number_format((float) ($course->reviews_avg_rating ?? 0), 1) }}</span>
                                </td>
                                <td>{{ number_format($course->views ?? 0) }}</td>
                                <td>
                                    <a href="{{ route('courses.show', $course->id) }}" class="btn btn-outline btn-sm">Gérer</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state premium-empty">
                                        <i class="fas fa-book-open"></i>
                                        <p>Aucun cours publié. Créez votre premier contenu.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<div class="dash-grid dash-grid--widgets reveal-up">
    <section class="widget-card glass-card">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">Rendez-vous en attente</h3>
                <p class="widget-subtitle">Demandes à confirmer</p>
            </div>
            <a href="{{ route('professor.appointments') }}" class="btn btn-outline btn-sm">Tout voir</a>
        </div>
        <div class="widget-body">
            @forelse($pendingAppointments as $appointment)
                <div class="course-list-item">
                    <div class="course-list-thumb"><i class="fas fa-calendar"></i></div>
                    <div class="course-list-info">
                        <div class="course-list-title">{{ $appointment->student?->name ?? 'Étudiant' }}</div>
                        <div class="course-list-meta">
                            <span>{{ $appointment->course?->title ?? 'Cours' }}</span>
                            <span>{{ $appointment->scheduled_at?->format('d/m/Y H:i') ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state premium-empty"><p>Aucun rendez-vous en attente.</p></div>
            @endforelse
        </div>
    </section>

    <section class="widget-card glass-card">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">Messages récents</h3>
                <p class="widget-subtitle">Derniers échanges</p>
            </div>
            <a href="{{ route('professor.messages') }}" class="btn btn-outline btn-sm">Tout voir</a>
        </div>
        <div class="widget-body">
            <div class="activity-feed">
                @forelse($recentMessages->take(5) as $message)
                    <div class="activity-item">
                        <div class="activity-icon"><i class="fas fa-envelope"></i></div>
                        <div class="activity-body">
                            <strong>{{ $message->from_user_id === auth()->id() ? 'Vous → '.$message->recipient?->name : $message->sender?->name }}</strong>
                            <p>{{ Str::limit($message->body, 60) }}</p>
                            <time>{{ $message->created_at->diffForHumans() }}</time>
                        </div>
                    </div>
                @empty
                    <div class="empty-state premium-empty"><p>Aucun message récent.</p></div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="widget-card glass-card">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">Derniers avis</h3>
                <p class="widget-subtitle">Retours de vos étudiants</p>
            </div>
            <a href="{{ route('professor.reviews') }}" class="btn btn-outline btn-sm">Tout voir</a>
        </div>
        <div class="widget-body">
            @forelse($recentReviews->take(5) as $review)
                <div class="testimonial-mini">
                    <div class="testimonial-mini-header">
                        @if($review->user)
                            <img src="{{ $review->user->avatarUrl() }}" alt="" class="testimonial-mini-avatar">
                        @endif
                        <div>
                            <strong style="font-size:0.8125rem">{{ $review->user?->name ?? 'Anonyme' }}</strong>
                            <div class="testimonial-mini-stars">{{ str_repeat('★', min(5, (int) ($review->rating ?? 5))) }}</div>
                        </div>
                    </div>
                    <p class="testimonial-mini-text">{{ Str::limit($review->comment ?? '', 90) }}</p>
                    <span class="course-list-meta">{{ $review->course?->title }}</span>
                </div>
            @empty
                <div class="empty-state premium-empty"><p>Aucun avis pour le moment.</p></div>
            @endforelse
        </div>
    </section>
</div>

@endsection
