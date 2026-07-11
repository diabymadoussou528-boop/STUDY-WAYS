@extends('layouts.admin')

@section('title', $student->name)

@section('content')
<x-admin-page-header kicker="Profil étudiant" :title="$student->name" :subtitle="$student->email">
    <a href="{{ route('admin.students') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Retour</a>
</x-admin-page-header>

<div class="dash-grid dash-grid--profile reveal-up">
    <section class="widget-card glass-card">
        <div class="widget-header"><h3 class="widget-title">Informations personnelles</h3></div>
        <div class="widget-body">
            <div class="profile-summary">
                <img src="{{ $student->avatarUrl() }}" alt="" class="profile-summary-avatar">
                <ul class="profile-summary-list">
                    <li><strong>E-mail</strong> {{ $student->email }}</li>
                    <li><strong>Téléphone</strong> {{ $student->phone ?? '—' }}</li>
                    <li><strong>Inscrit le</strong> {{ $student->created_at->format('d/m/Y') }}</li>
                    <li><strong>Statut</strong> {{ $student->is_active ? 'Actif' : 'Suspendu' }}</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="widget-card glass-card">
        <div class="widget-header"><h3 class="widget-title">Statistiques d'apprentissage</h3></div>
        <div class="widget-body">
            <div class="mini-kpi-grid">
                <div class="mini-kpi"><span class="mini-kpi-value">{{ $enrollments->count() }}</span><span class="mini-kpi-label">Cours inscrits</span></div>
                <div class="mini-kpi"><span class="mini-kpi-value">{{ $inProgress->count() }}</span><span class="mini-kpi-label">En cours</span></div>
                <div class="mini-kpi"><span class="mini-kpi-value">{{ $completed->count() }}</span><span class="mini-kpi-label">Complétés</span></div>
                <div class="mini-kpi"><span class="mini-kpi-value">{{ (int) round($enrollments->avg('progress') ?? 0) }}%</span><span class="mini-kpi-label">Progression moy.</span></div>
            </div>
        </div>
    </section>
</div>

<section class="widget-card glass-card reveal-up" style="margin-top:22px;">
    <div class="widget-header"><h3 class="widget-title">Cours et progression</h3></div>
    <div class="widget-body widget-body--flush">
        <div class="table-scroll">
            <table class="premium-table">
                <thead><tr><th>Cours</th><th>Professeur</th><th>Progression</th><th>Statut</th></tr></thead>
                <tbody>
                @forelse($enrollments as $enrollment)
                    <tr>
                        <td>{{ $enrollment->course?->title ?? '—' }}</td>
                        <td>{{ $enrollment->course?->user?->name ?? '—' }}</td>
                        <td><div class="progress-bar-wrap"><div class="progress-bar" style="width:{{ $enrollment->progress }}%"></div></div> {{ $enrollment->progress }}%</td>
                        <td>@if($enrollment->isCompleted())<span class="badge badge-success">Complété</span>@else<span class="badge badge-admin">En cours</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty-state premium-empty"><p>Aucune inscription.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@section('styles')
<style>
.profile-summary { display:flex; gap:20px; align-items:flex-start; }
.profile-summary-avatar { width:88px; height:88px; border-radius:50%; object-fit:cover; border:3px solid rgba(139,32,50,0.15); }
.profile-summary-list { list-style:none; display:grid; gap:10px; }
.mini-kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
.mini-kpi { background:#faf9fb; border-radius:14px; padding:16px; text-align:center; border:1px solid rgba(0,0,0,0.05); }
.mini-kpi-value { display:block; font-size:1.5rem; font-weight:800; color:#8B2032; }
.mini-kpi-label { font-size:0.8rem; color:#6b7280; }
.progress-bar-wrap { background:#eee; border-radius:999px; height:8px; width:120px; display:inline-block; vertical-align:middle; margin-right:8px; }
.progress-bar { background:linear-gradient(90deg,#8B2032,#a82841); height:100%; border-radius:999px; }
.dash-grid--profile { grid-template-columns:1fr 1fr; gap:22px; }
@media(max-width:900px){ .dash-grid--profile,.mini-kpi-grid{ grid-template-columns:1fr; } .profile-summary{ flex-direction:column; align-items:center; text-align:center; } }
</style>
@endsection
