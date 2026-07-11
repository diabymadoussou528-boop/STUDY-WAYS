<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Journal d'audit — StudyWays</title>
    <style>
        body { font-family: Inter, Arial, sans-serif; margin: 24px; color: #111; }
        h1 { color: #8B2032; font-size: 1.25rem; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #8B2032; color: #fff; }
        tr:nth-child(even) { background: #faf9fb; }
        .meta { color: #666; font-size: 11px; margin-bottom: 12px; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body>
    <h1>Journal d'audit StudyWays</h1>
    <p class="meta">Exporté le {{ now()->format('d/m/Y H:i') }} · {{ $logs->count() }} entrées</p>
    <table>
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
            @foreach($logs as $log)
                <tr>
                    <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $log->actor?->name ?? '—' }}</td>
                    <td>{{ $log->role ?? '—' }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->module ?? '—' }}</td>
                    <td>{{ $log->description }}</td>
                    <td>{{ $log->ip_address ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <script>window.onload = () => window.print();</script>
</body>
</html>
