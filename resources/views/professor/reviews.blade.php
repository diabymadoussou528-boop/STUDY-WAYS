@extends('layouts.professor')

@section('title', 'Avis des étudiants')

@section('content')
@php
    $professor = auth()->user();
    $coursesList = \App\Models\Course::where('user_id', $professor->id)->get();
    
    $selectedCourseId = request('course_id');
    
    $query = \App\Models\Review::whereIn('course_id', $coursesList->pluck('id'))
        ->with(['user', 'course']);
        
    if ($selectedCourseId) {
        $query->where('course_id', $selectedCourseId);
    }
    
    $reviews = $query->latest()->paginate(10);
    
    // Aggregated stats
    $allReviewsQuery = \App\Models\Review::whereIn('course_id', $coursesList->pluck('id'));
    if ($selectedCourseId) {
        $allReviewsQuery->where('course_id', $selectedCourseId);
    }
    $allReviews = $allReviewsQuery->get();
    
    $totalReviewsCount = $allReviews->count();
    $avgRating = $allReviews->avg('rating') ?: 0;
    
    $ratingsDistribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    foreach ($allReviews as $r) {
        $ratingVal = (int) $r->rating;
        if (isset($ratingsDistribution[$ratingVal])) {
            $ratingsDistribution[$ratingVal]++;
        }
    }
@endphp

<x-admin-page-header kicker="Retours & Modération" title="Avis des étudiants" subtitle="Suivez les avis et commentaires laissés par vos apprenants." />

<div class="dash-grid dash-grid--2 reveal-up" style="margin-bottom: 24px;">
    <!-- Average Rating Card -->
    <article class="widget-card glass-card" style="display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center; padding:32px;">
        <span style="font-size: 0.9rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px;">Note globale</span>
        <div style="font-size: 4rem; font-weight:900; color:var(--primary); line-height:1;">
            {{ number_format($avgRating, 1) }}
        </div>
        <div style="color:#d97706; font-size:1.5rem; margin: 12px 0 8px;">
            @php $rounded = round($avgRating); @endphp
            {{ str_repeat('★', $rounded) }}{{ str_repeat('☆', 5 - $rounded) }}
        </div>
        <span style="font-size: 0.85rem; color:var(--text-muted);">Basé sur {{ $totalReviewsCount }} évaluation(s)</span>
    </article>

    <!-- Rating Distribution Card -->
    <article class="widget-card glass-card">
        <div class="widget-header">
            <h3 class="widget-title">Répartition des notes</h3>
        </div>
        <div class="widget-body" style="display:flex; flex-direction:column; gap:8px; padding-top:8px;">
            @foreach([5, 4, 3, 2, 1] as $stars)
                @php
                    $count = $ratingsDistribution[$stars];
                    $pct = $totalReviewsCount > 0 ? ($count / $totalReviewsCount) * 100 : 0;
                @endphp
                <div style="display:flex; align-items:center; gap:12px; font-size:0.85rem;">
                    <span style="width:50px; font-weight:600; color:var(--text-muted);">{{ $stars }} étoiles</span>
                    <div class="progress-bar-track" style="flex:1; height:8px; background:var(--bg-elevated); border-radius:var(--radius-pill); overflow:hidden;">
                        <div class="progress-bar-fill" style="width: {{ $pct }}%; height:100%; background:var(--primary); border-radius:var(--radius-pill);"></div>
                    </div>
                    <span style="width:40px; text-align:right; font-weight:700; color:var(--text);">{{ $count }}</span>
                </div>
            @endforeach
        </div>
    </article>
</div>

<div class="filters-bar glass-card reveal-up" style="display:flex; align-items:center; justify-content:space-between; gap:16px; padding:16px 24px; margin-bottom:20px; border-radius:var(--radius-lg); border:1px solid var(--border);">
    <form method="GET" action="{{ url()->current() }}" style="display:flex; align-items:center; gap:12px;">
        <select name="course_id" class="form-input" style="max-width:280px; padding:8px 12px; margin-bottom:0;" onchange="this.form.submit()">
            <option value="">Tous les cours</option>
            @foreach($coursesList as $c)
                <option value="{{ $c->id }}" @selected($selectedCourseId == $c->id)>{{ $c->title }}</option>
            @endforeach
        </select>
    </form>
</div>

<section class="widget-card glass-card reveal-up">
    <div class="widget-header">
        <h3 class="widget-title">Commentaires récents</h3>
    </div>
    <div class="widget-body widget-body--flush">
        <div style="display:flex; flex-direction:column; gap:1px; background:var(--border);">
            @forelse($reviews as $review)
                <div class="testimonial-mini" style="background:var(--bg-card); padding:24px; display:flex; flex-direction:column; gap:12px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:16px;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <img src="{{ $review->user?->avatarUrl() }}" alt="" class="user-cell-avatar" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                            <div>
                                <strong class="user-cell-name">{{ $review->user?->name ?? 'Anonyme' }}</strong>
                                <div style="color:#d97706; font-size:0.8rem; margin-top:2px;">
                                    {{ str_repeat('★', min(5, (int) ($review->rating ?? 5))) }}{{ str_repeat('☆', 5 - min(5, (int) ($review->rating ?? 5))) }}
                                </div>
                            </div>
                        </div>
                        <span class="user-cell-email" style="font-size:0.8rem; color:var(--text-muted);">{{ $review->created_at->diffForHumans() }}</span>
                    </div>
                    <p style="font-size:0.9rem; color:var(--text-muted); line-height:1.6; margin:0;">
                        "{{ $review->comment }}"
                    </p>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="badge badge-primary"><i class="fas fa-book"></i> {{ $review->course?->title }}</span>
                    </div>
                </div>
            @empty
                <div class="empty-state premium-empty" style="background:var(--bg-card); padding:48px 24px;">
                    <i class="fas fa-star" style="font-size:2rem; color:var(--text-dim); margin-bottom:12px;"></i>
                    <p>Aucun avis pour le moment.</p>
                </div>
            @endforelse
        </div>
        <div style="padding:16px 24px;">
            {{ $reviews->appends(request()->query())->links() }}
        </div>
    </div>
</section>
@endsection
