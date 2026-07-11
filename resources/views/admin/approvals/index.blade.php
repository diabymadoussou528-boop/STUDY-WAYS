@extends('layouts.admin')

@section('title', 'Demandes d\'approbation')

@section('content')
<x-admin-page-header kicker="Super Admin · Modération" title="Demandes d'approbation" :subtitle="$pendingCount.' demande(s) en attente.'" />

<section class="widget-card glass-card reveal-up">
    <div class="widget-body widget-body--flush">
        <div class="table-scroll">
            <table class="premium-table">
                <thead><tr><th>Type</th><th>Demandé par</th><th>Description</th><th>Date</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                @forelse($requests as $request)
                    <tr>
                        <td><strong>{{ $request->title }}</strong></td>
                        <td><div class="user-cell"><img src="{{ $request->requester?->avatarUrl() }}" alt="" class="user-cell-avatar"><span>{{ $request->requester?->name }}</span></div></td>
                        <td>{{ Str::limit($request->description ?? '—', 60) }}</td>
                        <td><span class="table-date">{{ $request->created_at->format('d/m/Y H:i') }}</span></td>
                        <td>
                            @if($request->status->value === 'pending')<span class="badge badge-warning">En attente</span>
                            @elseif($request->status->value === 'approved')<span class="badge badge-success">Approuvé</span>
                            @else<span class="badge badge-warning">Rejeté</span>@endif
                        </td>
                        <td>
                            <a href="{{ route('admin.approvals.show', $request) }}" class="btn btn-outline btn-sm">Détails</a>
                            @if($request->isPending())
                                <form method="POST" action="{{ route('admin.approvals.approve', $request) }}" style="display:inline">@csrf<button class="btn btn-primary btn-sm">Approuver</button></form>
                                <form method="POST" action="{{ route('admin.approvals.reject', $request) }}" style="display:inline">@csrf<button class="btn btn-outline btn-sm">Rejeter</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state premium-empty"><p>Aucune demande.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:16px 24px;">{{ $requests->links() }}</div>
    </div>
</section>
@endsection
