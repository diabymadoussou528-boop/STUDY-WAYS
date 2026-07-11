@extends('layouts.professor')

@section('title', 'Tentatives — '.$quiz->title)

@section('content')
<x-admin-page-header
    kicker="{{ $quiz->course?->title }}"
    title="Tentatives — {{ $quiz->title }}"
    subtitle="Consultez et corrigez les réponses des étudiants."
/>

<section class="widget-card glass-card">
    <div class="widget-body widget-body--flush">
        <table class="premium-table">
            <thead>
                <tr>
                    <th>Étudiant</th>
                    <th>Date</th>
                    <th>Score</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($attempts as $attempt)
                    <tr>
                        <td>{{ $attempt->student?->name }}</td>
                        <td>{{ $attempt->submitted_at?->translatedFormat('j M Y H:i') ?? '—' }}</td>
                        <td>{{ $attempt->percentage }}%</td>
                        <td>
                            @if($attempt->status === 'pending_grading')
                                <span class="badge badge-warning">À corriger</span>
                            @elseif($attempt->passed)
                                <span class="badge badge-success">Réussi</span>
                            @else
                                <span class="badge badge-muted">Soumis</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('professor.quizzes.attempts.show', $attempt) }}" class="btn btn-outline btn-sm">
                                {{ $attempt->status === 'pending_grading' ? 'Corriger' : 'Voir' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state premium-empty"><p>Aucune tentative pour ce quiz.</p></div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<a href="{{ route('professor.quizzes.index') }}" class="btn btn-outline" style="margin-top:16px;">Retour aux quiz</a>
@endsection
