@extends('layouts.admin')

@section('title', 'Professeurs')

@section('content')
<x-admin-page-header kicker="Gestion · Équipe" title="Professeurs" subtitle="Gérez les formateurs, leurs cours et leurs performances." />

<section class="widget-card glass-card reveal-up">
    <div class="widget-body widget-body--flush">
        <div class="table-scroll">
            <table class="premium-table">
                <thead><tr><th>Professeur</th><th>Contact</th><th>Cours publiés</th><th>Inscrit le</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                @forelse($teachers as $teacher)
                    <tr>
                        <td><div class="user-cell"><img src="{{ $teacher->avatarUrl() }}" alt="" class="user-cell-avatar"><a href="{{ route('admin.teachers.show', $teacher) }}" class="user-cell-name">{{ $teacher->name }}</a></div></td>
                        <td><div class="user-cell-name">{{ $teacher->email }}</div>@if($teacher->phone)<div class="user-cell-sub">{{ $teacher->phone }}</div>@endif</td>
                        <td>{{ $teacher->taught_courses_count }}</td>
                        <td><span class="table-date">{{ $teacher->created_at->format('d/m/Y') }}</span></td>
                        <td>@if($teacher->is_active)<span class="badge badge-success">Actif</span>@else<span class="badge badge-warning">Suspendu</span>@endif</td>
                        <td><div class="row-actions"><a href="{{ route('admin.teachers.show', $teacher) }}" class="btn btn-outline btn-sm">Voir</a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state premium-empty"><i class="fas fa-chalkboard-user"></i><p>Aucun professeur.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:16px 24px;">{{ $teachers->links() }}</div>
    </div>
</section>
@endsection
