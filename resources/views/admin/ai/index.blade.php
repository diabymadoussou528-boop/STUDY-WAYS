@extends('layouts.admin')

@section('title', 'IA Recommandations')

@section('content')
<x-admin-page-header kicker="Intelligence artificielle" title="Recommandations IA" subtitle="Suggestions personnalisées pour les étudiants et les cours." />

<div class="kpi-grid kpi-grid--hero reveal-stagger" style="margin-bottom:22px;">
    <article class="kpi-card kpi-card--modern glass-card"><div class="kpi-icon"><i class="fas fa-brain"></i></div><div class="kpi-value">{{ count($recommendations) }}</div><div class="kpi-label">Recommandations actives</div></article>
    <article class="kpi-card kpi-card--modern glass-card"><div class="kpi-icon"><i class="fas fa-percent"></i></div><div class="kpi-value">{{ round(collect($recommendations)->avg('confidence') ?: 87) }}%</div><div class="kpi-label">Confiance moyenne</div></article>
</div>

<div class="dash-grid dash-grid--2 reveal-up" style="margin-bottom:22px;">
    <section class="widget-card glass-card">
        <div class="widget-header"><h3 class="widget-title">Recommandations générées</h3></div>
        <div class="widget-body"><div id="chartAiGenerated" class="chart-canvas"></div></div>
    </section>
    <section class="widget-card glass-card">
        <div class="widget-header"><h3 class="widget-title">Taux d'acceptation</h3></div>
        <div class="widget-body"><div id="chartAiAccepted" class="chart-canvas"></div></div>
    </section>
</div>

<section class="widget-card glass-card reveal-up">
    <div class="widget-header"><h3 class="widget-title">Suggestions IA</h3></div>
    <div class="widget-body widget-body--flush">
        <div class="table-scroll">
            <table class="premium-table">
                <thead><tr><th>Étudiant</th><th>Cours suggéré</th><th>Confiance</th><th>Action</th></tr></thead>
                <tbody>
                @foreach($recommendations as $rec)
                    <tr>
                        <td><div class="user-cell"><img src="{{ $rec['student']->avatarUrl() }}" alt="" class="user-cell-avatar"><span>{{ $rec['student']->name }}</span></div></td>
                        <td>{{ $rec['course']->title }}</td>
                        <td><span class="badge badge-success">{{ $rec['confidence'] }}%</span></td>
                        <td>{{ $rec['action'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>window.__AI_CHARTS__ = @json($charts);</script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.1/dist/apexcharts.min.js"></script>
<script src="{{ asset('js/admin-dashboard.js') }}" defer></script>
@endsection
