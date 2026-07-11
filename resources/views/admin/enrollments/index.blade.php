@extends('layouts.admin')

@section('title', 'Inscriptions')

@section('content')
<x-admin-page-header kicker="Contenu" title="Inscriptions" subtitle="Toutes les inscriptions aux cours de la plateforme." />

<section class="widget-card glass-card reveal-up">
    <div class="widget-body widget-body--flush">
        <div class="table-scroll">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Cours</th>
                        <th>Statut</th>
                        <th>Progression</th>
                        <th>Inscrit le</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($enrollments as $enrollment)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <img src="{{ $enrollment->user?->avatarUrl() }}" alt="" class="user-cell-avatar">
                                <span>{{ $enrollment->user?->name ?? '—' }}</span>
                            </div>
                        </td>
                        <td>{{ $enrollment->course?->title ?? '—' }}</td>
                        <td><span class="badge badge-admin">{{ ucfirst($enrollment->status->value) }}</span></td>
                        <td>{{ $enrollment->progress }}%</td>
                        <td>{{ $enrollment->enrolled_at?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state premium-empty"><p>Aucune inscription.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:16px 24px;">{{ $enrollments->links() }}</div>
    </div>
</section>
@endsection
