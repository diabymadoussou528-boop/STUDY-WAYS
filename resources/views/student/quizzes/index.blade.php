@extends('layouts.student')

@section('title', 'Mes quiz')

@section('content')
<x-admin-page-header kicker="Évaluation" title="Mes quiz" subtitle="Quiz disponibles pour vos cours inscrits." />

<section class="widget-card glass-card">
    <div class="widget-body widget-body--flush">
        <div class="table-scroll">
            <table class="premium-table">
                <thead><tr><th>Quiz</th><th>Cours</th><th>Score requis</th><th></th></tr></thead>
                <tbody>
                    @forelse($quizzes as $quiz)
                        <tr>
                            <td>{{ $quiz->title }}</td>
                            <td>{{ $quiz->course?->title }}</td>
                            <td>{{ $quiz->passing_score }}%</td>
                            <td><a href="{{ route('student.quizzes.show', $quiz) }}" class="btn btn-primary btn-sm">Commencer</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="empty-state premium-empty"><p>Aucun quiz disponible.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
{{ $quizzes->links() }}
@endsection
