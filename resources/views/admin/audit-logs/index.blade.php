@extends('layouts.admin')

@section('title', 'Journal d\'audit')

@section('content')
<x-admin-page-header kicker="Sécurité" title="Journal d'audit" subtitle="Historique des actions administratives.">
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('admin.audit-logs.export', 'csv').'?'.http_build_query(request()->query()) }}" class="btn btn-outline"><i class="fas fa-download"></i> CSV</a>
        <a href="{{ route('admin.audit-logs.export', 'excel').'?'.http_build_query(request()->query()) }}" class="btn btn-outline"><i class="fas fa-file-excel"></i> Excel</a>
        <a href="{{ route('admin.audit-logs.export', 'pdf').'?'.http_build_query(request()->query()) }}" class="btn btn-outline"><i class="fas fa-file-pdf"></i> PDF</a>
    </div>
</x-admin-page-header>

<section class="widget-card glass-card reveal-up">
    <div class="widget-body">
        <form method="GET" class="filter-bar" style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
            <input type="text" name="action" value="{{ request('action') }}" placeholder="Action" class="form-input">
            <input type="text" name="module" value="{{ request('module') }}" placeholder="Module" class="form-input">
            <input type="text" name="role" value="{{ request('role') }}" placeholder="Rôle" class="form-input">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input">
            <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
        </form>

        <div class="table-scroll">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Utilisateur</th>
                        <th>Rôle</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Description</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $log->actor?->name ?? '—' }}</td>
                        <td>{{ $log->role ?? '—' }}</td>
                        <td><code>{{ $log->action }}</code></td>
                        <td>{{ $log->module ?? '—' }}</td>
                        <td>{{ Str::limit($log->description, 60) }}</td>
                        <td>{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state premium-empty"><p>Aucun enregistrement.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:16px 0;">{{ $logs->links() }}</div>
    </div>
</section>
@endsection
