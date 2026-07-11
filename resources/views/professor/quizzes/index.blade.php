@extends('layouts.professor')

@section('title', 'Mes quiz')

@section('content')
<x-admin-page-header kicker="Évaluation" title="Quiz & évaluations" subtitle="Créez et gérez les quiz de vos cours." />

<section class="widget-card glass-card">
    <div class="widget-body widget-body--flush">
        <table class="premium-table">
            <thead><tr><th>Titre</th><th>Cours</th><th>Questions</th><th>Tentatives</th><th>Statut</th><th></th></tr></thead>
            <tbody>
                @forelse($quizzes as $quiz)
                    <tr>
                        <td>{{ $quiz->title }}</td>
                        <td>{{ $quiz->course?->title }}</td>
                        <td>{{ $quiz->questions_count }}</td>
                        <td>{{ $quiz->attempts_count }}</td>
                        <td>{{ $quiz->is_published ? 'Publié' : 'Brouillon' }}</td>
                        <td>
                            <a href="{{ route('professor.quizzes.attempts', $quiz) }}" class="btn btn-outline btn-sm">Tentatives</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state premium-empty"><p>Aucun quiz créé.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
{{ $quizzes->links() }}
@endsection
