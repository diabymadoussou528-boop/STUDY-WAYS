@extends('layouts.admin')

@section('title', 'Demande #'.$approval->id)

@section('content')
<x-admin-page-header kicker="Approbation" :title="$approval->title" :subtitle="'Demandé par '.$approval->requester?->name">
    <a href="{{ route('admin.approvals') }}" class="btn btn-outline">Retour</a>
</x-admin-page-header>

<section class="widget-card glass-card reveal-up">
    <div class="widget-body">
        <p><strong>Action :</strong> {{ $approval->action }}</p>
        <p><strong>Description :</strong> {{ $approval->description ?? '—' }}</p>
        <p><strong>Statut :</strong> {{ ucfirst($approval->status->value) }}</p>
        <p><strong>Date :</strong> {{ $approval->created_at->format('d/m/Y H:i') }}</p>
        @if($approval->isPending())
            <div style="margin-top:20px;display:flex;gap:10px;">
                <form method="POST" action="{{ route('admin.approvals.approve', $approval) }}">@csrf<button class="btn btn-primary">Approuver</button></form>
                <form method="POST" action="{{ route('admin.approvals.reject', $approval) }}">@csrf<button class="btn btn-outline">Rejeter</button></form>
            </div>
        @endif
    </div>
</section>
@endsection
