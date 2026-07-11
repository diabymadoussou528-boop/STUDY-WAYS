@extends('layouts.student')

@section('title', 'Premium')

@section('content')
<x-admin-page-header kicker="Abonnement" title="StudyWays Premium" subtitle="Débloquez tout le potentiel de votre apprentissage." />

@if(session('success'))
    <div class="widget-card glass-card" style="margin-bottom:16px;padding:12px 20px;color:#155724;background:#d4edda;">{{ session('success') }}</div>
@endif

@if($isPremium)
    <section class="widget-card glass-card reveal-up">
        <div class="widget-body" style="text-align:center;padding:40px 24px;">
            <div class="sidebar-promo-icon" style="margin:0 auto 16px;"><i class="fas fa-crown"></i></div>
            <h3 class="widget-title">Vous êtes membre Premium</h3>
            <p class="widget-subtitle">Profitez de toutes les fonctionnalités exclusives.</p>
            <form method="POST" action="{{ route('student.premium.cancel') }}" style="margin-top:16px;" onsubmit="return confirm('Annuler l\'abonnement ?')">
                @csrf
                <button type="submit" class="btn btn-outline">Annuler l'abonnement</button>
            </form>
            <a href="{{ route('student.premium.history') }}" class="btn btn-outline" style="margin-top:8px;">Historique des paiements</a>
        </div>
    </section>
@else
    <div class="kpi-grid kpi-grid--hero reveal-stagger" style="margin-bottom:22px;">
        @foreach([
            ['Tuteur IA illimité', 'fa-robot'],
            ['Certificats officiels', 'fa-certificate'],
            ['Contenu exclusif', 'fa-lock-open'],
            ['Support prioritaire', 'fa-headset'],
        ] as [$label, $icon])
            <article class="kpi-card kpi-card--modern glass-card">
                <div class="kpi-icon"><i class="fas {{ $icon }}"></i></div>
                <div class="kpi-label">{{ $label }}</div>
            </article>
        @endforeach
    </div>

    <div class="dash-grid dash-grid--2 reveal-stagger">
        @foreach($plans as $key => $plan)
            <section class="widget-card glass-card">
                <div class="widget-body" style="padding:32px 24px;text-align:center;">
                    <h3 class="widget-title">{{ $plan['label'] }}</h3>
                    <p style="font-size:2rem;font-weight:700;color:#8B2032;margin:16px 0;">
                        {{ number_format($plan['amount'], 0, ',', ' ') }} <small style="font-size:.875rem;">{{ $plan['currency'] }}</small>
                    </p>
                    <form method="POST" action="{{ route('student.premium.subscribe') }}">
                        @csrf
                        <input type="hidden" name="plan" value="{{ $key }}">
                        <select name="provider" class="form-input" style="margin-bottom:12px;" required>
                            @foreach($providers ?? ['manual' => 'Manual'] as $pKey => $pLabel)
                                <option value="{{ $pKey }}">{{ $pLabel }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary btn-glow sidebar-promo-btn">
                            <i class="fas fa-crown"></i> S'abonner
                        </button>
                    </form>
                </div>
            </section>
        @endforeach
    </div>
@endif
@endsection
