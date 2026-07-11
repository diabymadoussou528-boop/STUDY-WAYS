@extends('layouts.admin')

@section('title', 'Témoignages')

@section('content')

<div class="dash-header reveal-up">
    <div class="dash-header-main">
        <span class="dash-kicker"><span class="pulse-dot"></span> Modération · Témoignages</span>
        <h1 class="dash-title">Gestion des <span class="dash-title-accent">témoignages</span></h1>
        <p class="dash-subtitle">Approuvez, modérez et gérez les avis de votre communauté.</p>
    </div>
    <div class="dash-header-aside">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
    </div>
</div>

@php
    $approvedCount = $testimonials->where('is_approved', true)->count();
    $pendingCount = $testimonials->where('is_approved', false)->count();
@endphp

<div class="kpi-grid kpi-grid--3 reveal-stagger">
    <article class="kpi-card kpi-card--modern glass-card" data-animate>
        <div class="kpi-card-top">
            <div class="kpi-icon"><i class="fas fa-quote-left"></i></div>
        </div>
        <div class="kpi-value">{{ $testimonials->count() }}</div>
        <div class="kpi-label">Témoignages plateforme</div>
    </article>
    <article class="kpi-card kpi-card--modern glass-card" data-animate>
        <div class="kpi-card-top">
            <div class="kpi-icon"><i class="fas fa-star"></i></div>
        </div>
        <div class="kpi-value">{{ $avgRating ?: '—' }}</div>
        <div class="kpi-label">Note moyenne (cours)</div>
    </article>
    <article class="kpi-card kpi-card--modern glass-card" data-animate>
        <div class="kpi-card-top">
            <div class="kpi-icon"><i class="fas fa-clock"></i></div>
            @if($pendingCount > 0)
                <span class="kpi-delta kpi-delta--up">{{ $pendingCount }} en attente</span>
            @endif
        </div>
        <div class="kpi-value">{{ $pendingCount }}</div>
        <div class="kpi-label">À modérer</div>
    </article>
</div>

<div class="dash-grid dash-grid--2 reveal-up" style="margin-bottom:22px;">
    <section class="widget-card glass-card">
        <div class="widget-header">
            <h3 class="widget-title">Note moyenne par cours</h3>
        </div>
        <div class="widget-body">
            <div id="chartTestimonialRatings" class="chart-canvas"></div>
        </div>
    </section>
    <section class="widget-card glass-card">
        <div class="widget-header">
            <h3 class="widget-title">Cours les plus commentés</h3>
        </div>
        <div class="widget-body">
            <div id="chartMostReviewed" class="chart-canvas"></div>
        </div>
    </section>
</div>

@foreach($reviewsByCourse as $courseId => $reviews)
    @php $course = $reviews->first()?->course; @endphp
    <section class="widget-card glass-card reveal-up" style="margin-bottom:18px;">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">{{ $course?->title ?? 'Cours #'.$courseId }}</h3>
                <p class="widget-subtitle">{{ $reviews->count() }} avis · moyenne {{ number_format($reviews->avg('rating'), 1) }}/5</p>
            </div>
        </div>
        <div class="widget-body widget-body--flush">
            <div class="table-scroll">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Note</th>
                            <th>Commentaire</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($reviews as $review)
                        <tr>
                            <td>
                                @if($review->user)
                                    <div class="user-cell">
                                        <img src="{{ $review->user->avatarUrl() }}" alt="" class="user-cell-avatar">
                                        <div>
                                            <div class="user-cell-name">{{ $review->user->name }}</div>
                                            <div class="user-cell-sub">{{ $review->user->email }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">Utilisateur supprimé</span>
                                @endif
                            </td>
                            <td><span class="rating-pill">★ {{ $review->rating }}/5</span></td>
                            <td><p class="table-message">{{ Str::limit($review->comment, 100) }}</p></td>
                            <td><span class="table-date">{{ $review->created_at->format('d/m/Y H:i') }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endforeach

<section class="widget-card glass-card reveal-up">
    <div class="widget-header">
        <div>
            <h3 class="widget-title">Tous les témoignages</h3>
            <p class="widget-subtitle">{{ $testimonials->count() }} avis enregistrés</p>
        </div>
        <div class="table-tools">
            <div class="table-search">
                <i class="fas fa-search"></i>
                <input type="search" placeholder="Rechercher..." data-table-search="testimonialsTable">
            </div>
        </div>
    </div>
    <div class="widget-body widget-body--flush">
        <div class="table-scroll">
            <table class="premium-table" id="testimonialsTable">
                <thead>
                    <tr>
                        <th data-sort="text">Utilisateur</th>
                        <th data-sort="text">Message</th>
                        <th data-sort="number">Note</th>
                        <th>Statut</th>
                        <th data-sort="text">Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($testimonials as $testi)
                    <tr>
                        <td data-value="{{ $testi->user?->name ?? '' }}">
                            @if($testi->user)
                                <div class="user-cell">
                                    <img src="{{ $testi->user->avatarUrl() }}" alt="" class="user-cell-avatar">
                                    <div>
                                        <div class="user-cell-name">{{ $testi->user->name }}</div>
                                        <div class="user-cell-sub">{{ $testi->user->email }}</div>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted">Utilisateur supprimé</span>
                            @endif
                        </td>
                        <td data-value="{{ $testi->message }}">
                            <p class="table-message">{{ Str::limit($testi->message, 100) }}</p>
                        </td>
                        <td data-value="{{ $testi->rating ?? 0 }}">
                            @if($testi->rating)
                                <span class="rating-pill">★ {{ $testi->rating }}/5</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($testi->is_approved)
                                <span class="badge badge-success">Approuvé</span>
                            @else
                                <span class="badge badge-warning">En attente</span>
                            @endif
                        </td>
                        <td data-value="{{ $testi->created_at->timestamp }}">
                            <span class="table-date">{{ $testi->created_at->format('d/m/Y H:i') }}</span>
                        </td>
                        <td>
                            <div class="row-actions">
                                <button type="button" class="row-action-btn" title="Actions"><i class="fas fa-ellipsis-vertical"></i></button>
                                <div class="row-action-menu">
                                    <form method="POST" action="{{ route('admin.testimonials.delete', $testi->id) }}" onsubmit="return confirm('Supprimer ce témoignage ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"><i class="fas fa-trash-alt"></i> Supprimer</button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state premium-empty">
                                <i class="fas fa-message"></i>
                                <p>Aucun témoignage pour le moment.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

@endsection

@section('scripts')
<script>
    window.__TESTIMONIAL_CHARTS__ = @json($testimonialCharts);
</script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.1/dist/apexcharts.min.js"></script>
<script src="{{ asset('js/admin-dashboard.js') }}" defer></script>
@endsection
