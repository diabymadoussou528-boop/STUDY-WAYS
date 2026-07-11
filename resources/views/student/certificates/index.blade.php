@extends('layouts.student')

@section('title', 'Mes certificats')

@section('content')
<x-admin-page-header kicker="Réussite" title="Mes certificats" subtitle="Téléchargez vos certificats officiels pour les cours complétés." />

@if(!($isPremium ?? false))
    <div class="widget-card glass-card reveal-up" style="margin-bottom:18px;border-left:4px solid #8B2032;">
        <div class="widget-body" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
            <div style="width:48px;height:48px;border-radius:14px;background:rgba(139,32,50,.12);display:flex;align-items:center;justify-content:center;color:#8B2032;font-size:1.25rem;">
                <i class="fas fa-crown"></i>
            </div>
            <div style="flex:1;min-width:220px;">
                <strong style="display:block;margin-bottom:4px;">Premium requis</strong>
                <span style="color:#666;font-size:.92rem;">Les certificats officiels sont disponibles avec l'abonnement Premium StudyWays.</span>
            </div>
            <a href="{{ route('student.premium') }}" class="btn btn-primary btn-sm"><i class="fas fa-crown"></i> Passer Premium</a>
        </div>
    </div>
@endif

<section class="widget-card glass-card reveal-up">
    <div class="widget-header">
        <div>
            <h3 class="widget-title">Certificats disponibles</h3>
            <p class="widget-subtitle">{{ $enrollments->count() }} cours complété(s)</p>
        </div>
    </div>
    <div class="widget-body widget-body--flush">
        <div class="table-scroll">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Cours</th>
                        <th>Professeur</th>
                        <th>Complété le</th>
                        <th>N° certificat</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enrollments as $enrollment)
                        <tr>
                            <td>
                                <div class="table-course">
                                    <span class="table-course-icon"><i class="fas fa-certificate"></i></span>
                                    <span>{{ $enrollment->course?->title ?? '—' }}</span>
                                </div>
                            </td>
                            <td>{{ $enrollment->course?->user?->name ?? '—' }}</td>
                            <td>{{ $enrollment->completed_at?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td>
                                @if($enrollment->certificate_number)
                                    <code style="font-size:.82rem;">{{ $enrollment->certificate_number }}</code>
                                @else
                                    <span class="text-muted">À générer</span>
                                @endif
                            </td>
                            <td>
                                @if($isPremium)
                                    <a href="{{ route('student.certificates.show', $enrollment) }}" class="btn btn-primary btn-sm" target="_blank">
                                        <i class="fas fa-download"></i> Télécharger
                                    </a>
                                @else
                                    <a href="{{ route('student.premium') }}" class="btn btn-outline btn-sm">Premium</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state premium-empty">
                                    <i class="fas fa-certificate"></i>
                                    <p>Aucun certificat disponible pour le moment.</p>
                                    <p style="font-size:.9rem;color:#888;margin-top:8px;">Terminez un cours à 100 % pour obtenir votre certificat.</p>
                                    <a href="{{ route('student.courses') }}" class="btn btn-primary btn-sm" style="margin-top:12px;">Mes cours</a>
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
