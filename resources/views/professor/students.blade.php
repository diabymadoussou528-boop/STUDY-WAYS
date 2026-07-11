@extends('layouts.professor')

@section('title', 'Mes Étudiants')

@section('content')
@php
    $professor = auth()->user();
    $coursesList = \App\Models\Course::where('user_id', $professor->id)->get();
    
    $selectedCourseId = request('course_id');
    
    $query = \App\Models\Enrollment::whereIn('course_id', $coursesList->pluck('id'))
        ->with(['user', 'course']);
        
    if ($selectedCourseId) {
        $query->where('course_id', $selectedCourseId);
    }
    
    $enrollments = $query->latest()->paginate(15);
    
    $totalStudentsCount = \App\Models\Enrollment::whereIn('course_id', $coursesList->pluck('id'))
        ->pluck('user_id')
        ->unique()
        ->count();
        
    $completedCount = \App\Models\Enrollment::whereIn('course_id', $coursesList->pluck('id'))
        ->where('progress', '>=', 100)
        ->count();
@endphp

<x-admin-page-header kicker="Communauté" title="Mes Étudiants" subtitle="Gérez les apprenants inscrits à vos cours." />

<div class="kpi-grid kpi-grid--hero reveal-stagger" style="margin-bottom: 24px;">
    <article class="kpi-card kpi-card--modern glass-card">
        <div class="kpi-icon"><i class="fas fa-users"></i></div>
        <div class="kpi-value" data-counter="{{ $totalStudentsCount }}">{{ $totalStudentsCount }}</div>
        <div class="kpi-label">Étudiants uniques</div>
    </article>
    <article class="kpi-card kpi-card--modern glass-card">
        <div class="kpi-icon"><i class="fas fa-graduation-cap"></i></div>
        <div class="kpi-value" data-counter="{{ $completedCount }}">{{ $completedCount }}</div>
        <div class="kpi-label">Cours complétés</div>
    </article>
    <article class="kpi-card kpi-card--modern glass-card">
        <div class="kpi-icon"><i class="fas fa-chart-line"></i></div>
        <div class="kpi-value">
            {{ $totalStudentsCount > 0 ? round(($completedCount / \App\Models\Enrollment::whereIn('course_id', $coursesList->pluck('id'))->count()) * 100) : 0 }}%
        </div>
        <div class="kpi-label">Taux de complétion moyen</div>
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
    
    <button type="button" class="btn btn-outline btn-sm" onclick="exportStudentsToCSV()">
        <i class="fas fa-file-export"></i> Exporter en CSV
    </button>
</div>

<section class="widget-card glass-card reveal-up">
    <div class="widget-body widget-body--flush">
        <div class="table-scroll">
            <table class="premium-table" id="studentsTable">
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Cours</th>
                        <th>Progression</th>
                        <th>Dernier accès</th>
                        <th>Certificat</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enrollments as $enrollment)
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <img src="{{ $enrollment->user?->avatarUrl() }}" alt="" class="user-cell-avatar">
                                    <div>
                                        <div class="user-cell-name">{{ $enrollment->user?->name }}</div>
                                        <div class="user-cell-sub">{{ $enrollment->user?->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="user-cell-name">{{ $enrollment->course?->title }}</div>
                                <div class="user-cell-sub">{{ $enrollment->course?->category?->name }}</div>
                            </td>
                            <td>
                                <div class="progress-bar-wrap">
                                    <div class="progress-bar-track">
                                        <div class="progress-bar-fill" style="width: {{ $enrollment->progress }}%;"></div>
                                    </div>
                                    <span class="progress-bar-label">{{ $enrollment->progress }}%</span>
                                </div>
                            </td>
                            <td>
                                <span class="table-date">
                                    {{ $enrollment->last_accessed_at ? $enrollment->last_accessed_at->format('d/m/Y H:i') : '—' }}
                                </span>
                            </td>
                            <td>
                                @if($enrollment->progress >= 100)
                                    <span class="badge badge-success"><i class="fas fa-award"></i> Éligible</span>
                                @else
                                    <span class="badge badge-neutral">En cours</span>
                                @endif
                            </td>
                            <td>
                                @if($enrollment->isActive())
                                    <span class="badge badge-success">Actif</span>
                                @else
                                    <span class="badge badge-warning">Suspendu</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state premium-empty">
                                    <i class="fas fa-user-graduate"></i>
                                    <p>Aucun étudiant inscrit pour le moment.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:16px 24px;">
            {{ $enrollments->appends(request()->query())->links() }}
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    function exportStudentsToCSV() {
        let csv = [];
        let rows = document.querySelectorAll("#studentsTable tr");
        
        for (let i = 0; i < rows.length; i++) {
            let row = [], cols = rows[i].querySelectorAll("td, th");
            
            for (let j = 0; j < cols.length - 1; j++) { // exclude action column if any
                let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").trim();
                row.push('"' + text.replace(/"/g, '""') + '"');
            }
            csv.push(row.join(","));
        }
        
        let csvFile = new Blob([csv.join("\n")], {type: "text/csv;charset=utf-8;"});
        let downloadLink = document.createElement("a");
        downloadLink.download = "etudiants-studyways.csv";
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }
</script>
@endsection
