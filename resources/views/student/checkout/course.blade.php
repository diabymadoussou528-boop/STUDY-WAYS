@extends('layouts.student')

@section('title', 'Paiement — '.$course->title)

@section('content')
<x-admin-page-header kicker="Paiement" :title="$course->title" subtitle="Finalisez votre inscription au cours." />

<section class="widget-card glass-card reveal-up">
    <div class="widget-body">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
            <div>
                <p class="widget-subtitle">{{ $course->user?->name }} · {{ $course->category?->name }}</p>
            </div>
            <div style="font-size:1.75rem;font-weight:700;color:#8B2032;">
                {{ number_format($course->price, 0, ',', ' ') }} XOF
            </div>
        </div>

        <form method="POST" action="{{ route('student.checkout.pay', $course) }}" style="max-width:420px;">
            @csrf
            <label style="display:block;margin-bottom:16px;">
                Mode de paiement
                <select name="provider" class="form-input" required>
                    @foreach($providers as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="btn btn-primary btn-glow">
                <i class="fas fa-lock"></i> Payer et s'inscrire
            </button>
            <a href="{{ route('courses.show', $course) }}" class="btn btn-outline">Annuler</a>
        </form>

        @if(isset($providers['stripe']))
            <p class="widget-subtitle" style="margin-top:16px;"><i class="fab fa-stripe"></i> Paiement sécurisé via Stripe.</p>
        @endif
    </div>
</section>
@endsection
