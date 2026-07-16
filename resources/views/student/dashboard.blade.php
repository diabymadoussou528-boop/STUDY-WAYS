@extends('layouts.student')

@section('title', 'Dashboard')

@section('content')

<div class="dash-header reveal-up">
    <div class="dash-header-main">
        <span class="dash-kicker"><span class="pulse-dot"></span> Espace étudiant · {{ now()->translatedFormat('l d F') }}</span>
        <h1 class="dash-title">Bonjour, <span class="dash-title-accent">{{ explode(' ', auth()->user()->name)[0] }}</span></h1>
        <p class="dash-subtitle">Suivez votre progression et continuez votre apprentissage.</p>
        <div class="dash-chips">
            <span class="dash-chip"><i class="fas fa-book-open"></i> {{ $inProgress->count() }} cours en cours</span>
            <span class="dash-chip"><i class="fas fa-circle-check"></i> {{ is_numeric($completed) ? $completed : $completed->count() }} complétés</span>
            @if($isPremium)
                <span class="dash-chip dash-chip--live"><i class="fas fa-crown"></i> Membre Premium</span>
            @else
                <span class="dash-chip"><i class="fas fa-chart-line"></i> Continuez à progresser</span>
            @endif
        </div>
    </div>
    <div class="dash-header-aside">
        <a href="{{ route('student.ai-tutor') }}" class="btn btn-outline">
            <i class="fas fa-robot"></i> Tuteur IA
        </a>
        <a href="{{ route('student.messages') }}" class="btn btn-outline">
            <i class="fas fa-envelope"></i> Messages
        </a>
        <a href="{{ route('home') }}#catalogue" class="btn btn-outline">
            <i class="fas fa-search"></i> Explorer
        </a>
        @unless($isPremium)
            <a href="{{ route('student.premium') }}" class="btn btn-primary btn-glow">
                <i class="fas fa-crown"></i> Passer Premium
            </a>
        @endunless
    </div>
</div>

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

<div class="dash-grid dash-grid--2 reveal-up">
    <section class="widget-card glass-card">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">Cours en cours</h3>
                <p class="widget-subtitle">Reprenez là où vous vous êtes arrêté</p>
            </div>
            <a href="{{ route('student.courses') }}" class="btn btn-outline btn-sm">Tout voir</a>
        </div>
        <div class="widget-body widget-body--flush">
            <div class="table-scroll">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Cours</th>
                            <th>Professeur</th>
                            <th>Progression</th>
                            <th>Dernier accès</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inProgress as $enrollment)
                            <tr>
                                <td>
                                    <div class="table-course">
                                        <span class="table-course-icon"><i class="fas fa-play"></i></span>
                                        <span>{{ Str::limit($enrollment->course?->title ?? '—', 34) }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($enrollment->course?->user)
                                        <div class="user-cell">
                                            <img src="{{ $enrollment->course->user->avatarUrl() }}" alt="" class="user-cell-avatar">
                                            <span class="user-cell-name">{{ $enrollment->course->user->name }}</span>
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <div class="progress-inline">
                                        <div class="progress-bar-wrap">
                                            <div class="progress-bar" style="width:{{ $enrollment->progress }}%"></div>
                                        </div>
                                        <span>{{ $enrollment->progress }}%</span>
                                    </div>
                                </td>
                                <td>{{ ($enrollment->last_accessed_at ?? $enrollment->enrolled_at)?->diffForHumans() ?? '—' }}</td>
                                <td>
                                    @if($enrollment->course)
                                        <a href="{{ route('courses.show', $enrollment->course->id) }}" class="btn btn-primary btn-sm">Continuer</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state premium-empty">
                                        <i class="fas fa-book-open"></i>
                                        <p>Aucun cours en cours. Explorez le catalogue pour commencer.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="widget-card glass-card">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">Activité récente</h3>
                <p class="widget-subtitle">Vos dernières actions</p>
            </div>
        </div>
        <div class="widget-body">
            <div class="activity-feed">
                @forelse($recentActivity as $item)
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas {{ $item['icon'] ?? 'fa-circle' }}"></i>
                        </div>
                        <div class="activity-body">
                            <strong>{{ $item['title'] }}</strong>
                            <p>{{ $item['desc'] }}</p>
                            @if(isset($item['time']))
                                <time>{{ $item['time']->diffForHumans() }}</time>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty-state premium-empty"><p>Aucune activité récente.</p></div>
                @endforelse
            </div>
        </div>
    </section>
</div>

<section class="widget-card glass-card reveal-up">
    <div class="widget-header">
        <div>
            <h3 class="widget-title">Cours recommandés</h3>
            <p class="widget-subtitle">Suggestions pour enrichir votre parcours</p>
        </div>
        <a href="{{ route('home') }}#catalogue" class="btn btn-outline btn-sm">Catalogue</a>
    </div>
    <div class="widget-body">
        <div class="course-card-grid">
            @forelse($recommended as $course)
                <x-course-card :course="$course" cta-label="Voir les détails" />
            @empty
                <div class="empty-state premium-empty"><p>Aucune recommandation pour le moment.</p></div>
            @endforelse
        </div>
    </div>
</section>

@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('css/course-card.css') }}">
<link rel="stylesheet" href="{{ asset('css/course-experience.css') }}">
<style>
.progress-inline { display:flex; align-items:center; gap:8px; }
.progress-bar-wrap { background:#eee; border-radius:999px; height:8px; width:100px; overflow:hidden; }
.progress-bar { background:linear-gradient(90deg,#8B2032,#a82841); height:100%; border-radius:999px; transition: width 0.6s ease; }
.course-card-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:16px; }
</style>
@endsection

@section('scripts')
<script src="{{ asset('js/admin-dashboard.js') }}" defer></script>
@endsection
