@extends('layouts.admin')

@section('title', 'Rapports')

@section('content')
<x-admin-page-header kicker="Analytics · Export" title="Rapports" subtitle="Générez et exportez les données de la plateforme." />

<div class="kpi-grid kpi-grid--hero reveal-stagger" style="margin-bottom:22px;">
    @foreach([['Étudiants',$stats['students'],'fa-user-graduate'],['Professeurs',$stats['teachers'],'fa-chalkboard-user'],['Cours',$stats['courses'],'fa-book'],['Inscriptions',$stats['enrollments'],'fa-users']] as [$label,$value,$icon])
        <article class="kpi-card kpi-card--modern glass-card"><div class="kpi-icon"><i class="fas {{ $icon }}"></i></div><div class="kpi-value">{{ $value }}</div><div class="kpi-label">{{ $label }}</div></article>
    @endforeach
</div>

<section class="widget-card glass-card reveal-up">
    <div class="widget-header"><h3 class="widget-title">Exporter les données</h3></div>
    <div class="widget-body">
        <div class="export-grid">
            @foreach(['students' => 'Étudiants', 'teachers' => 'Professeurs', 'courses' => 'Cours'] as $type => $label)
                <div class="export-card glass-card">
                    <h4>{{ $label }}</h4>
                    <div class="export-actions">
                        <a href="{{ route('admin.reports.export', [$type, 'csv']) }}" class="btn btn-outline btn-sm"><i class="fas fa-file-csv"></i> CSV</a>
                        <a href="{{ route('admin.reports.export', [$type, 'excel']) }}" class="btn btn-outline btn-sm"><i class="fas fa-file-excel"></i> Excel</a>
                        <a href="{{ route('admin.reports.export', [$type, 'pdf']) }}" class="btn btn-outline btn-sm"><i class="fas fa-file-pdf"></i> PDF</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection

@section('styles')
<style>.export-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.export-card{padding:20px;border-radius:16px}.export-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}@media(max-width:768px){.export-grid{grid-template-columns:1fr}}</style>
@endsection
