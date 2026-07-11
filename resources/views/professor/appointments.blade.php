@extends('layouts.professor')

@section('title', 'Rendez-vous')

@section('content')
<x-admin-page-header kicker="Planning" title="Rendez-vous" subtitle="Demandes et sessions planifiées." />

<section class="widget-card glass-card reveal-up">
    <div class="widget-body widget-body--flush">
        <div class="table-scroll">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Cours</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appointments as $appointment)
                        <tr>
                            <td>
                                @if($appointment->student)
                                    <div class="user-cell">
                                        <img src="{{ $appointment->student->avatarUrl() }}" alt="" class="user-cell-avatar">
                                        <span>{{ $appointment->student->name }}</span>
                                    </div>
                                @else — @endif
                            </td>
                            <td>{{ $appointment->course?->title ?? '—' }}</td>
                            <td>{{ $appointment->scheduled_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td><span class="badge badge-admin">{{ ucfirst($appointment->status->value) }}</span></td>
                            <td>
                                @if($appointment->status->value === 'pending')
                                    <details class="row-actions">
                                        <summary class="btn btn-primary btn-sm">Répondre</summary>
                                        <div style="padding:12px;min-width:280px;">
                                            <form method="POST" action="{{ route('professor.appointments.accept', $appointment) }}" style="margin-bottom:8px;">
                                                @csrf
                                                <input type="url" name="meeting_link" placeholder="Lien de réunion" class="form-input" style="margin-bottom:6px;">
                                                <textarea name="response_note" placeholder="Note" class="form-input" rows="2" style="margin-bottom:6px;"></textarea>
                                                <button class="btn btn-primary btn-sm">Accepter</button>
                                            </form>
                                            <form method="POST" action="{{ route('professor.appointments.reject', $appointment) }}">
                                                @csrf
                                                <textarea name="response_note" placeholder="Motif du refus" class="form-input" rows="2" style="margin-bottom:6px;"></textarea>
                                                <button class="btn btn-outline btn-sm">Refuser</button>
                                            </form>
                                            <form method="POST" action="{{ route('professor.appointments.reschedule', $appointment) }}" style="margin-top:8px;">
                                                @csrf
                                                <input type="datetime-local" name="scheduled_at" class="form-input" required style="margin-bottom:6px;">
                                                <button class="btn btn-outline btn-sm">Proposer un autre créneau</button>
                                            </form>
                                        </div>
                                    </details>
                                @elseif($appointment->meeting_link)
                                    <a href="{{ $appointment->meeting_link }}" target="_blank" class="btn btn-primary btn-sm">Rejoindre</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state premium-empty">
                                    <i class="fas fa-calendar-check"></i>
                                    <p>Aucun rendez-vous.</p>
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
