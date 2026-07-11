@extends('layouts.student')

@section('title', 'Ma progression')

@section('content')
<x-admin-page-header kicker="Analytics" title="Progression d'apprentissage" subtitle="Statistiques, séries et réalisations." />

<div class="kpi-grid kpi-grid--4 reveal-stagger" style="margin-bottom:22px;">
    <article class="kpi-card glass-card"><div class="kpi-value">{{ $currentStreak }}</div><div class="kpi-label">Série actuelle (jours)</div></article>
    <article class="kpi-card glass-card"><div class="kpi-value">{{ $longestStreak }}</div><div class="kpi-label">Meilleure série</div></article>
    <article class="kpi-card glass-card"><div class="kpi-value">{{ $totalStudyMinutes }}m</div><div class="kpi-label">Temps total</div></article>
    <article class="kpi-card glass-card"><div class="kpi-value">{{ $avgQuizScore }}%</div><div class="kpi-label">Moyenne quiz</div></article>
</div>

<div class="dash-grid dash-grid--2">
    <section class="widget-card glass-card">
        <div class="widget-header"><h3 class="widget-title">Activité hebdomadaire</h3></div>
        <div class="widget-body">
            @foreach($weeklyActivity as $day)
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                    <span style="width:36px;">{{ $day['label'] }}</span>
                    <div style="flex:1;background:#eee;border-radius:999px;height:8px;overflow:hidden;"><div style="width:{{ min(100, $day['minutes']) }}%;background:#8B2032;height:100%;"></div></div>
                    <span>{{ $day['minutes'] }}m</span>
                </div>
            @endforeach
        </div>
    </section>
    <section class="widget-card glass-card">
        <div class="widget-header"><h3 class="widget-title">Réalisations</h3></div>
        <div class="widget-body">
            @forelse($achievements as $badge)
                <div style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">
                    <i class="fas {{ $badge['icon'] }}" style="color:#8B2032;width:24px;text-align:center;"></i>
                    <div><strong>{{ $badge['title'] }}</strong><div style="font-size:.88rem;color:#666;">{{ $badge['desc'] }}</div></div>
                </div>
            @empty
                <p style="color:#888;">Continuez à apprendre pour débloquer des badges.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
