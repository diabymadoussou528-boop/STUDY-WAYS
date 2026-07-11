@extends('layouts.student')

@section('title', 'Rendez-vous')

@section('content')
<x-admin-page-header kicker="Planning" title="Rendez-vous" subtitle="Sessions planifiées avec vos professeurs." />

<section class="widget-card glass-card reveal-up" style="margin-bottom:24px;">
    <div class="widget-header">
        <h3 class="widget-title">Demander un rendez-vous</h3>
    </div>
    <div class="widget-body">
        @if($enrolledCourses->isEmpty())
            <p class="widget-subtitle">Inscrivez-vous à un cours pour demander un rendez-vous avec le professeur.</p>
        @else
            <form method="POST" action="{{ route('student.appointments.store') }}" class="form-grid" style="display:grid;gap:12px;max-width:480px;">
                @csrf
                <label>Cours
                    <select name="course_id" class="form-input" required>
                        @foreach($enrolledCourses as $c)
                            <option value="{{ $c->id }}">{{ $c->title }} — {{ $c->user?->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Date et heure
                    <input type="datetime-local" name="scheduled_at" class="form-input" required min="{{ now()->addHour()->format('Y-m-d\TH:i') }}">
                </label>
                <label>Motif
                    <textarea name="message" class="form-input" rows="3" placeholder="Expliquez votre demande..."></textarea>
                </label>
                <button type="submit" class="btn btn-primary btn-glow"><i class="fas fa-paper-plane"></i> Envoyer la demande</button>
            </form>
        @endif
    </div>
</section>

<section class="widget-card glass-card reveal-up">
    <div class="widget-header"><h3 class="widget-title">Mes rendez-vous</h3></div>
    <div class="widget-body widget-body--flush">
        <div class="table-scroll">
            <table class="premium-table">
                <thead><tr><th>Professeur</th><th>Cours</th><th>Date</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                @forelse($appointments as $appointment)
                    <tr>
                        <td>
                            @if($appointment->professor)
                                <div class="user-cell">
                                    <img src="{{ $appointment->professor->avatarUrl() }}" alt="" class="user-cell-avatar">
                                    <span>{{ $appointment->professor->name }}</span>
                                </div>
                            @else — @endif
                        </td>
                        <td>{{ $appointment->course?->title ?? '—' }}</td>
                        <td>{{ $appointment->scheduled_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td><span class="badge badge-admin">{{ ucfirst($appointment->status->value) }}</span></td>
                        <td>
                            @if(in_array($appointment->status->value, ['pending', 'accepted', 'rescheduled'], true))
                                <form method="POST" action="{{ route('student.appointments.cancel', $appointment) }}">@csrf
                                    <button class="btn btn-outline btn-sm">Annuler</button>
                                </form>
                            @endif
                            @if($appointment->meeting_link)
                                <a href="{{ $appointment->meeting_link }}" target="_blank" class="btn btn-primary btn-sm">Rejoindre</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state premium-empty"><i class="fas fa-calendar-check"></i><p>Aucun rendez-vous planifié.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
