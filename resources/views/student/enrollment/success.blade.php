@extends('layouts.student')

@section('title', 'Inscription confirmée')

@section('content')
<x-admin-page-header kicker="Succès" title="Inscription confirmée" subtitle="Vous pouvez commencer à apprendre dès maintenant." />

<section class="widget-card glass-card reveal-up">
    <div class="widget-body" style="text-align:center;padding:40px 24px;">
        <div class="sidebar-promo-icon" style="margin:0 auto 16px;"><i class="fas fa-check-circle"></i></div>
        <h3 class="widget-title">{{ $enrollment->course->title }}</h3>
        <p class="widget-subtitle">Inscrit le {{ $enrollment->enrolled_at?->format('d/m/Y à H:i') }}</p>
        <div style="margin-top:24px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <a href="{{ route('student.courses') }}" class="btn btn-primary btn-glow"><i class="fas fa-play"></i> Mes cours</a>
            <a href="{{ route('courses.show', $enrollment->course) }}" class="btn btn-outline">Voir le cours</a>
        </div>
    </div>
</section>
@endsection
