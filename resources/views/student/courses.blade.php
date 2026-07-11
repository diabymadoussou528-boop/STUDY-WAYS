@extends('layouts.student')

@section('title', 'Mes cours')

@section('content')
<x-admin-page-header kicker="Apprentissage" title="Mes cours" subtitle="Tous vos cours inscrits et votre progression." />

<div class="kpi-grid kpi-grid--3 reveal-stagger" style="margin-bottom:22px;">
    @foreach(array_slice($heroStats, 0, 3) as $stat)
        <article class="kpi-card kpi-card--modern glass-card" data-animate>
            <div class="kpi-icon"><i class="fas {{ $stat['icon'] }}"></i></div>
            <div class="kpi-value">{{ $stat['value'] }}</div>
            <div class="kpi-label">{{ $stat['label'] }}</div>
        </article>
    @endforeach
</div>

@if($inProgress->isNotEmpty())
<section class="widget-card glass-card reveal-up" style="margin-bottom:18px;">
    <div class="widget-header">
        <h3 class="widget-title">En cours</h3>
    </div>
    <div class="widget-body course-card-grid">
        @foreach($inProgress as $enrollment)
            <div class="sw-course-card">
                <div class="sw-progress-ring-wrap">
                    <svg viewBox="0 0 36 36" width="56" height="56">
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#eee" stroke-width="3"/>
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#8B2032" stroke-width="3" stroke-dasharray="{{ $enrollment->progress }}, 100"/>
                    </svg>
                    <span class="sw-progress-ring-label">{{ $enrollment->progress }}%</span>
                </div>
                <div style="flex:1;">
                    <div class="course-list-title">{{ $enrollment->course?->title }}</div>
                    <div class="course-list-meta">{{ $enrollment->course?->user?->name ?? 'Professeur' }}</div>
                    @if($enrollment->course)
                        <a href="{{ route('courses.show', $enrollment->course->id) }}" class="btn btn-primary btn-sm" style="margin-top:10px;">Continuer</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>
@endif

<section class="widget-card glass-card reveal-up">
    <div class="widget-header">
        <div>
            <h3 class="widget-title">Tous les cours</h3>
            <p class="widget-subtitle">{{ ($enrollments ?? collect())->count() }} inscription(s)</p>
        </div>
        <a href="{{ route('home') }}#catalogue" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Explorer</a>
    </div>
    <div class="widget-body widget-body--flush">
        <div class="table-scroll">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Cours</th>
                        <th>Professeur</th>
                        <th>Progression</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($enrollments ?? collect()) as $enrollment)
                        <tr>
                            <td>
                                <div class="table-course">
                                    <span class="table-course-icon"><i class="fas fa-book"></i></span>
                                    <span>{{ $enrollment->course?->title ?? '—' }}</span>
                                </div>
                            </td>
                            <td>{{ $enrollment->course?->user?->name ?? '—' }}</td>
                            <td>
                                <div class="progress-inline">
                                    <div class="progress-bar-wrap"><div class="progress-bar" style="width:{{ $enrollment->progress }}%"></div></div>
                                    <span>{{ $enrollment->progress }}%</span>
                                </div>
                            </td>
                            <td>
                                @if($enrollment->isCompleted())
                                    <span class="badge badge-success">Complété</span>
                                @else
                                    <span class="badge badge-admin">En cours</span>
                                @endif
                            </td>
                            <td>
                                @if($enrollment->course)
                                    <a href="{{ route('courses.show', $enrollment->course->id) }}" class="btn btn-outline btn-sm">Ouvrir</a>
                                    <a href="{{ route('student.messages') }}?course={{ $enrollment->course_id }}&teacher={{ $enrollment->course->user_id }}" class="btn btn-outline btn-sm" title="Message au professeur"><i class="fas fa-envelope"></i></a>
                                    @if($enrollment->certificate_eligible && $enrollment->isCompleted())
                                        <a href="{{ route('student.certificates.show', $enrollment) }}" class="btn btn-outline btn-sm" title="Certificat" target="_blank"><i class="fas fa-certificate"></i></a>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state premium-empty">
                                    <i class="fas fa-book-open"></i>
                                    <p>Vous n'êtes inscrit à aucun cours pour le moment.</p>
                                    <a href="{{ route('home') }}#catalogue" class="btn btn-primary btn-sm" style="margin-top:12px;">Découvrir les cours</a>
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

@section('styles')
<link rel="stylesheet" href="{{ asset('css/student-chat.css') }}">
<style>.progress-inline{display:flex;align-items:center;gap:8px}.progress-bar-wrap{background:#eee;border-radius:999px;height:8px;width:100px;overflow:hidden}.progress-bar{background:linear-gradient(90deg,#8B2032,#a82841);height:100%;border-radius:999px}</style>
@endsection
