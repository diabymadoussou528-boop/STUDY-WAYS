<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de certificat — StudyWays</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <style>body{font-family:Inter,sans-serif;background:#f5f0f1;margin:0;padding:48px 20px;color:#111}.card{max-width:720px;margin:0 auto;background:#fff;border-radius:16px;padding:32px;box-shadow:0 12px 40px rgba(139,32,50,.1);text-align:center;border:1px solid rgba(139,32,50,.12)}.btn{display:inline-block;margin-top:24px;background:#8B2032;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none}</style>
</head>
<body>
    <div class="card">
        @if($valid)
            <div style="width:72px;height:72px;border-radius:50%;background:rgba(34,197,94,.12);color:#16a34a;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:2rem;"><i class="fas fa-check"></i></div>
            <h1 style="color:#8B2032;margin-bottom:8px;">Certificat authentique</h1>
            <p style="color:#666;margin-bottom:24px;">Ce certificat StudyWays est valide et vérifié.</p>
            <div style="text-align:left;background:#faf9fb;border-radius:12px;padding:20px;">
                <p><strong>Étudiant :</strong> {{ $enrollment->user?->name }}</p>
                <p><strong>Cours :</strong> {{ $enrollment->course?->title }}</p>
                <p><strong>Professeur :</strong> {{ $enrollment->course?->user?->name }}</p>
                <p><strong>N° certificat :</strong> {{ $enrollment->certificate_number }}</p>
                <p><strong>Émis le :</strong> {{ $enrollment->certificate_issued_at?->translatedFormat('d F Y') }}</p>
            </div>
        @else
            <div style="width:72px;height:72px;border-radius:50%;background:rgba(239,68,68,.12);color:#dc2626;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:2rem;"><i class="fas fa-times"></i></div>
            <h1 style="margin-bottom:8px;">Certificat non trouvé</h1>
            <p style="color:#666;">Le code de vérification est invalide ou le certificat n'existe pas.</p>
        @endif
        <a href="{{ route('home') }}" class="btn">Retour à l'accueil</a>
    </div>
</body>
</html>
