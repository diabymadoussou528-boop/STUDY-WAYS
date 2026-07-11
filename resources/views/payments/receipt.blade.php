<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu — {{ $payment->receipt_number }}</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <style>
        body{font-family:Inter,sans-serif;background:#f5f0f1;padding:24px;color:#111}
        .receipt{max-width:760px;margin:0 auto;background:#fff;border:2px solid #8B2032;border-radius:16px;padding:32px}
        .brand{color:#8B2032;font-weight:800;letter-spacing:.2em;text-transform:uppercase;font-size:.8rem}
        h1{margin:12px 0 24px}
        table{width:100%;border-collapse:collapse}
        td{padding:10px 0;border-bottom:1px solid #eee}
        .toolbar{text-align:center;margin-top:20px}
        @media print{.toolbar{display:none}body{background:#fff;padding:0}}
    </style>
</head>
<body>
    <div class="receipt">
        <div class="brand">StudyWays</div>
        <h1>Reçu de paiement</h1>
        <table>
            <tr><td>Reçu N°</td><td><strong>{{ $payment->receipt_number }}</strong></td></tr>
            <tr><td>Transaction</td><td>{{ $payment->transaction_id }}</td></tr>
            <tr><td>Facture</td><td>{{ $invoice->number ?? '—' }}</td></tr>
            <tr><td>Client</td><td>{{ $user->name }} ({{ $user->email }})</td></tr>
            <tr><td>Montant</td><td><strong>{{ number_format((float) $payment->amount, 0, ',', ' ') }} {{ $payment->currency }}</strong></td></tr>
            <tr><td>Statut</td><td>{{ ucfirst($payment->status) }}</td></tr>
            <tr><td>Provider</td><td>{{ ucfirst($payment->provider) }}</td></tr>
            <tr><td>Date</td><td>{{ $payment->created_at->translatedFormat('d F Y H:i') }}</td></tr>
        </table>
    </div>
    <div class="toolbar">
        <button onclick="window.print()" style="background:#8B2032;color:#fff;border:none;padding:12px 24px;border-radius:10px;cursor:pointer;">Imprimer / PDF</button>
    </div>
</body>
</html>
