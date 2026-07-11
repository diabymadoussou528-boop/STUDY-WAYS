@extends('layouts.student')

@section('title', 'Confirmer l\'inscription')

@section('content')
<x-admin-page-header kicker="Inscription" :title="$course->title" subtitle="Vérifiez les détails avant de confirmer." />

<section class="widget-card glass-card reveal-up">
    <div class="widget-body">
        @if($enrolled)
            <div class="empty-state premium-empty">
                <i class="fas fa-check-circle"></i>
                <p>Vous êtes déjà inscrit à ce cours.</p>
                <a href="{{ route('student.courses') }}" class="btn btn-primary">Mes cours</a>
            </div>
        @else
            <div class="user-cell" style="margin-bottom:20px;">
                <img src="{{ $course->thumbnailUrl() }}" alt="" class="user-cell-avatar" style="width:80px;height:80px;border-radius:12px;">
                <div>
                    <strong>{{ $course->title }}</strong>
                    <p class="widget-subtitle">{{ $course->user?->name }} · {{ $course->lessons_count ?? $course->lessons->count() }} leçons</p>
                </div>
            </div>

            <p style="margin-bottom:20px;">
                @if($course->isFree())
                    Ce cours est <strong>gratuit</strong>.
                @else
                    Prix : <strong>{{ number_format($course->price, 0, ',', ' ') }} XOF</strong>
                @endif
            </p>

            @if($course->is_premium_only && !auth()->user()->hasActivePremium())
                <div class="alert" style="background:#fff3cd;padding:12px;border-radius:8px;margin-bottom:16px;">
                    Ce cours nécessite un abonnement Premium.
                    <a href="{{ route('student.premium') }}">Passer en Premium</a>
                </div>
            @elseif(!$course->isFree())
                <a href="{{ route('student.checkout.course', $course) }}" class="btn btn-primary btn-glow">
                    <i class="fas fa-lock"></i> Procéder au paiement
                </a>
                <a href="{{ route('courses.show', $course) }}" class="btn btn-outline">Retour</a>
            @else
                <form method="POST" action="{{ route('student.enrollment.store', $course) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-glow">
                        <i class="fas fa-check"></i> Confirmer l'inscription
                    </button>
                    <a href="{{ route('courses.show', $course) }}" class="btn btn-outline">Retour</a>
                </form>
            @endif
        @endif
    </div>
</section>
@endsection
