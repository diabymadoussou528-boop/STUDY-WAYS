@extends('layouts.student')

@section('title', 'Résultat du quiz')

@section('content')
<x-admin-page-header kicker="{{ $attempt->quiz?->course?->title }}" title="Résultat — {{ $attempt->quiz?->title }}" />

<div class="kpi-grid kpi-grid--3" style="margin-bottom:22px;">
    <article class="kpi-card glass-card"><div class="kpi-value">{{ $attempt->percentage }}%</div><div class="kpi-label">Score</div></article>
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

@if($attempt->status === 'pending_grading')
    <div class="alert alert-info" style="margin-bottom:20px;">
        Certaines réponses sont en attente de correction par votre professeur.
    </div>
@endif

<section class="widget-card glass-card">
    <div class="widget-header"><h3 class="widget-title">Analyse des réponses</h3></div>
    <div class="widget-body">
        @foreach($attempt->answers as $answer)
            @php
                $pending = $answer->graded_at === null && $answer->question && ! $answer->question->type->isAutoGraded();
                $bg = $pending ? 'rgba(251,191,36,.12)' : ($answer->is_correct ? 'rgba(34,197,94,.08)' : 'rgba(239,68,68,.08)');
            @endphp
            <div style="margin-bottom:16px;padding:14px;border-radius:12px;background:{{ $bg }};">
                <strong>{{ $answer->question?->question }}</strong>
                <p style="margin:8px 0 0;">Votre réponse : {{ $answer->answer ?: '—' }}</p>
                @if($attempt->quiz?->show_feedback)<small>{{ $answer->feedback }}</small>@endif
            </div>
        @endforeach
    </div>
</section>
@endsection
