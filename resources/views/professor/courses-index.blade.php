@extends('layouts.professor')

@section('title', 'Mes cours')

@section('content')
<x-admin-page-header kicker="Contenu pédagogique" title="Mes cours" subtitle="Gérez et publiez votre contenu.">
    <a href="{{ route('courses.create') }}" class="btn btn-primary btn-glow"><i class="fas fa-plus"></i> Nouveau cours</a>
</x-admin-page-header>

<section class="widget-card glass-card reveal-up">
    <div class="widget-body widget-body--flush">
        <div class="table-scroll">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Cours</th>
                        <th>Statut</th>
                        <th>Étudiants</th>
                        <th>Leçons</th>
                        <th>Note</th>
                        <th>Vues</th>
                        <th style="width:240px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($courses as $course)
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:12px;">
                                    @if($course->thumbnail)
                                        <img src="{{ $course->thumbnailUrl() }}" alt="" style="width: 48px; height: 32px; border-radius: var(--radius-sm); object-fit: cover;">
                                    @else
                                        <div style="width: 48px; height: 32px; background: var(--primary-glow); color: var(--primary); display:flex; align-items:center; justify-content:center; border-radius: var(--radius-sm);">
                                            <i class="fas fa-book"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="user-cell-name">{{ $course->title }}</div>
                                        <div class="user-cell-sub">{{ $course->category?->name ?? 'Sans catégorie' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @php $status = $course->status instanceof \App\Enums\CourseStatus ? $course->status : \App\Enums\CourseStatus::tryFrom($course->status ?? 'draft'); @endphp
                                <span class="badge {{ $status?->badgeClass() ?? 'badge-admin' }}">{{ $status?->label() ?? '—' }}</span>
                            </td>
                            <td>{{ number_format($course->enrollments_count ?? 0) }}</td>
                            <td>{{ $course->lessons_count ?? 0 }}</td>
                            <td><span class="rating-pill">★ {{ number_format((float) ($course->reviews_avg_rating ?? 0), 1) }}</span></td>
                            <td>{{ number_format($course->views ?? 0) }}</td>
                            <td>
                                <div class="row-actions" style="justify-content: flex-end; gap: 8px;">
                                    <a href="{{ route('courses.show', $course) }}" class="btn btn-outline btn-sm" title="Aperçu"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('courses.edit', $course) }}" class="btn btn-outline btn-sm" title="Modifier"><i class="fas fa-edit"></i></a>
                                    
                                    @if(($status ?? null) === \App\Enums\CourseStatus::Draft)
                                        <form method="POST" action="{{ route('professor.courses.submit-review', $course) }}" style="display:inline;">
                                            @csrf
                                            <button class="btn btn-primary btn-sm" title="Soumettre pour revue"><i class="fas fa-paper-plane"></i></button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('courses.delete', $course->id) }}" onsubmit="return confirm('Supprimer définitivement ce cours ?')" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline btn-sm btn-sm-danger" title="Supprimer" style="color:var(--clr-danger); border-color:rgba(220,38,38,0.15);"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state premium-empty">
                                    <i class="fas fa-book-open"></i>
                                    <p>Aucun cours. Créez votre premier contenu.</p>
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
