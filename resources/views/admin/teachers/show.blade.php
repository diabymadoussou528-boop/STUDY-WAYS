@extends('layouts.admin')

@section('title', $teacher->name)

@section('content')
<x-admin-page-header kicker="Profil professeur" :title="$teacher->name" :subtitle="$teacher->email">
    <a href="{{ route('admin.teachers') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Retour</a>
</x-admin-page-header>

<section class="widget-card glass-card reveal-up">
    <div class="widget-header"><h3 class="widget-title">Cours publiés</h3></div>
    <div class="widget-body widget-body--flush">
        <div class="table-scroll">
            <table class="premium-table">
                <thead><tr><th>Cours</th><th>Étudiants</th><th>Avis</th><th>Note</th><th>Vues</th></tr></thead>
                <tbody>
                @forelse($courses as $course)
                    <tr>
                        <td>{{ $course->title }}</td>
                        <td>{{ $course->enrollments_count }}</td>
                        <td>{{ $course->reviews_count }}</td>
                        <td>{{ number_format((float) ($course->reviews_avg_rating ?? 0), 1) }}/5</td>
                        <td>{{ number_format($course->views ?? 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state premium-empty"><p>Aucun cours publié.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
