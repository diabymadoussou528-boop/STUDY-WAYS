@extends('layouts.professor')

@section('title', 'Correction — '.$attempt->quiz?->title)

@section('content')
<x-admin-page-header
    kicker="{{ $attempt->quiz?->course?->title }}"
    title="Correction — {{ $attempt->quiz?->title }}"
    subtitle="{{ $attempt->student?->name }} · {{ $attempt->submitted_at?->translatedFormat('j F Y H:i') }}"
/>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('success') }}</div>
@endif

<div class="kpi-grid kpi-grid--3" style="margin-bottom:22px;">
    <article class="kpi-card glass-card"><div class="kpi-value">{{ $attempt->percentage }}%</div><div class="kpi-label">Score actuel</div></article>
    <article class="kpi-card glass-card"><div class="kpi-value">{{ $attempt->score }}</div><div class="kpi-label">Points</div></article>
    <article class="kpi-card glass-card">
        <div class="kpi-value">
            @if($attempt->status === 'pending_grading')
                En attente
            @else
                {{ $attempt->passed ? 'Réussi' : 'Échoué' }}
            @endif
        </div>
        <div class="kpi-label">Statut</div>
    </article>
</div>

<section class="widget-card glass-card">
    <div class="widget-header"><h3 class="widget-title">Réponses de l'étudiant</h3></div>
    <div class="widget-body">
        @foreach($attempt->answers as $answer)
            <div style="margin-bottom:24px;padding:20px;border:1px solid rgba(0,0,0,.08);border-radius:14px;">
                <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
                    <strong>{{ $answer->question?->question }}</strong>
                    <span class="badge">{{ $answer->question?->type?->label() ?? 'Question' }} · {{ $answer->question?->points }} pts</span>
                </div>

                <p style="margin:0 0 16px;color:#555;white-space:pre-wrap;">{{ $answer->answer ?: '—' }}</p>

                @if($answer->question && ! $answer->question->type->isAutoGraded())
                    <form method="POST" action="{{ route('professor.quizzes.answers.grade', $answer) }}" style="display:grid;gap:12px;max-width:480px;">
                        @csrf
                        <label>
                            Points attribués (max {{ $answer->question->points }})
                            <input type="number" name="points" min="0" max="{{ $answer->question->points }}" value="{{ $answer->points_awarded }}" class="form-input" required>
                        </label>
                        <label>
                            Commentaire
                            <textarea name="feedback" rows="3" class="form-input">{{ $answer->feedback }}</textarea>
                        </label>
                        <button type="submit" class="btn btn-primary btn-sm">Enregistrer la correction</button>
                    </form>
                @else
                    <div style="font-size:.9rem;color:#666;">
                        <span>{{ $answer->is_correct ? '✓ Correct' : '✗ Incorrect' }}</span>
                        · {{ $answer->points_awarded }} / {{ $answer->question?->points }} pts
                        @if($answer->feedback)<br><small>{{ $answer->feedback }}</small>@endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</section>

<a href="{{ route('professor.quizzes.attempts', $attempt->quiz) }}" class="btn btn-outline" style="margin-top:16px;">Retour aux tentatives</a>
@endsection
