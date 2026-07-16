@extends('layouts.professor')

@section('title', 'Archives')

@section('content')
@php
    $archivedCourses = $courses->filter(function($course) {
        $status = $course->status instanceof \App\Enums\CourseStatus ? $course->status->value : $course->status;
        return $status === 'archived';
    });
@endphp

<x-admin-page-header kicker="Contenu pédagogique" title="Cours archivés" subtitle="Consultez et restaurez vos cours retirés de la plateforme." />

@if(session('success'))
    <div class="flash-toast flash-toast--success" style="margin-bottom:16px;">
        <i class="fas fa-check-circle"></i>
        {{ session('success') }}
    </div>
@endif

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
                        <th style="width:160px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($archivedCourses as $course)
                        @php $status = $course->status instanceof \App\Enums\CourseStatus ? $course->status : \App\Enums\CourseStatus::tryFrom($course->status ?? 'archived'); @endphp
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
                                <span class="badge badge-inactive">{{ $status?->label() ?? 'Archivé' }}</span>
                            </td>
                            <td>{{ number_format($course->enrollments_count ?? 0) }}</td>
                            <td>{{ $course->lessons_count ?? 0 }}</td>
                            <td><span class="rating-pill">★ {{ number_format((float) ($course->reviews_avg_rating ?? 0), 1) }}</span></td>
                            <td>{{ number_format($course->views ?? 0) }}</td>
                            <td>
                                <div class="row-actions" style="justify-content: flex-end; gap: 8px;">
                                    <form method="POST" action="{{ route('professor.courses.restore', $course) }}" style="display:inline;">
                                        @csrf
                                        <button class="btn btn-outline btn-sm" title="Restaurer le cours"><i class="fas fa-undo"></i> Restaurer</button>
                                    </form>

                                    <form method="POST" action="{{ route('courses.delete', $course->id) }}" onsubmit="return confirm('Supprimer définitivement ce cours ?')" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline btn-sm btn-sm-danger" title="Supprimer définitivement" style="color:var(--clr-danger); border-color:rgba(220,38,38,0.15);"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state premium-empty">
                                    <i class="fas fa-archive"></i>
                                    <p>Aucun cours archivé.</p>
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
