@extends('layouts.admin')

@section('title', 'Cours')

@section('content')
<x-admin-page-header kicker="Contenu · Cours" title="Cours" subtitle="Gérez le catalogue, les publications et les statistiques.">
    <a href="{{ route('admin.courses.create') }}" class="btn btn-primary btn-glow"><i class="fas fa-plus"></i> Nouveau cours</a>
</x-admin-page-header>

<section class="widget-card glass-card reveal-up">
    <div class="widget-body widget-body--flush">
        <div class="table-scroll">
            <table class="premium-table">
                <thead><tr><th>Cours</th><th>Professeur</th><th>Étudiants</th><th>Note</th><th>Vues</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                @forelse($courses as $course)
                    <tr>
                        <td><strong>{{ $course->title }}</strong></td>
                        <td><div class="user-cell">@if($course->user)<img src="{{ $course->user->avatarUrl() }}" alt="" class="user-cell-avatar">@endif<span>{{ $course->user?->name ?? '—' }}</span></div></td>
                        <td>{{ $course->enrollments_count }}</td>
                        <td>{{ number_format((float) ($course->reviews_avg_rating ?? 0), 1) }}</td>
                        <td>{{ number_format($course->views ?? 0) }}</td>
                        <td>
                            @php $status = $course->status instanceof \App\Enums\CourseStatus ? $course->status : \App\Enums\CourseStatus::tryFrom($course->status ?? 'draft'); @endphp
                            <span class="badge {{ $status?->badgeClass() ?? 'badge-admin' }}">{{ $status?->label() ?? '—' }}</span>
                        </td>
                        <td>
                            <div class="row-actions">
                                <a href="{{ route('courses.show', $course) }}" class="btn btn-outline btn-sm">Voir</a>
                                @if($status !== \App\Enums\CourseStatus::Published)
                                    <form method="POST" action="{{ route('admin.courses.publish', $course) }}">@csrf<button class="btn btn-primary btn-sm">Publier</button></form>
                                @endif
                                @if($status !== \App\Enums\CourseStatus::Archived)
                                    <form method="POST" action="{{ route('admin.courses.status', [$course, 'archived']) }}">@csrf<button class="btn btn-outline btn-sm">Archiver</button></form>
                                @endif
                                @if(auth()->user()->isSuperAdmin())
                                    <form method="POST" action="{{ route('admin.courses.duplicate', $course) }}">@csrf<button class="btn btn-outline btn-sm">Dupliquer</button></form>
                                @endif
                                <form method="POST" action="{{ route('admin.courses.manage.destroy', $course) }}" onsubmit="return confirm('Supprimer ce cours ?')">@csrf @method('DELETE')<button class="btn btn-outline btn-sm">Supprimer</button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state premium-empty"><p>Aucun cours.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:16px 24px;">{{ $courses->links() }}</div>
    </div>
</section>
@endsection
