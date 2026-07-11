@extends('layouts.student')

@section('title', 'Historique Premium')

@section('content')
<x-admin-page-header kicker="Abonnement" title="Historique des paiements" subtitle="Vos transactions Premium." />

<section class="widget-card glass-card reveal-up">
    <div class="widget-body widget-body--flush">
        <div class="table-scroll">
            <table class="premium-table">
                <thead><tr><th>Date</th><th>Montant</th><th>Fournisseur</th><th>Statut</th></tr></thead>
                <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ number_format($payment->amount, 0, ',', ' ') }} {{ $payment->currency }}</td>
                        <td>{{ ucfirst($payment->provider) }}</td>
                        <td><span class="badge badge-success">{{ ucfirst($payment->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty-state premium-empty"><p>Aucun paiement enregistré.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:16px 24px;">{{ $payments->links() }}</div>
    </div>
</section>
@endsection
