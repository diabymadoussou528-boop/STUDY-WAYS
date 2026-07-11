@extends('layouts.admin')

@section('title', 'Étudiants')

@section('content')
<x-admin-page-header kicker="Gestion · Utilisateurs" title="Étudiants" subtitle="Gérez les comptes étudiants, leur progression et leur statut." />

<section class="widget-card glass-card reveal-up">
    <div class="widget-body widget-body--flush">
        <div class="table-scroll">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Contact</th>
                        <th>Inscriptions</th>
                        <th>Inscrit le</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($students as $student)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <img src="{{ $student->avatarUrl() }}" alt="" class="user-cell-avatar">
                                <a href="{{ route('admin.students.show', $student) }}" class="user-cell-name">{{ $student->name }}</a>
                            </div>
                        </td>
                        <td>
                            <div class="user-cell-name">{{ $student->email }}</div>
                            @if($student->phone)<div class="user-cell-sub">{{ $student->phone }}</div>@endif
                        </td>
                        <td>{{ $student->enrollments_count }}</td>
                        <td><span class="table-date">{{ $student->created_at->format('d/m/Y') }}</span></td>
                        <td>
                            @if($student->is_active)
                                <span class="badge badge-success">Actif</span>
                            @else
                                <span class="badge badge-warning">Suspendu</span>
                            @endif
                        </td>
                        <td>
                            <div class="row-actions">
                                <a href="{{ route('admin.students.show', $student) }}" class="btn btn-outline btn-sm">Voir</a>
                                <form method="POST" action="{{ route('admin.students.toggle-status', $student) }}">@csrf<button type="submit" class="btn btn-outline btn-sm">{{ $student->is_active ? 'Suspendre' : 'Activer' }}</button></form>
                                <form method="POST" action="{{ route('admin.students.destroy', $student) }}" onsubmit="return confirm('Supprimer cet étudiant ?')">@csrf @method('DELETE')<button type="submit" class="btn btn-outline btn-sm">Supprimer</button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state premium-empty"><i class="fas fa-user-graduate"></i><p>Aucun étudiant.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:16px 24px;">{{ $students->links() }}</div>
    </div>
</section>
@endsection
