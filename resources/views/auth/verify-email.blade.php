<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification e-mail — StudyWays</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-page">

    <div class="auth-overlay" id="authOverlay"></div>

    <div class="auth-modal auth-modal--single" role="dialog" aria-modal="true" aria-label="Vérification e-mail">
        <div class="auth-single">
                <x-sw-brand :href="route('home')" variant="card" size="sm" centered class="auth-card-logo" />

            <div class="auth-single-icon"><i class="fas fa-envelope-circle-check"></i></div>

            <h2 class="auth-card-title">Vérifiez votre e-mail</h2>
            <p class="auth-card-subtitle">
                Merci pour votre inscription ! Avant de commencer, veuillez confirmer votre adresse e-mail
                en cliquant sur le lien que nous venons de vous envoyer.
            </p>

            @if (session('status') === 'verification-link-sent')
                <div class="auth-success">
                    <i class="fas fa-check-circle"></i>
                    Un nouveau lien de vérification a été envoyé à votre adresse e-mail.
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}" style="margin-top:20px;">
                @csrf
                <button type="submit" class="btn btn-auth-primary btn-block">
                    <i class="fas fa-paper-plane"></i> Renvoyer l'e-mail de vérification
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" style="margin-top:16px;text-align:center;">
                @csrf
                <button type="submit" class="auth-link-btn">Se déconnecter</button>
            </form>
        </div>
    </div>

    <script src="{{ asset('js/auth.js') }}" defer></script>
</body>
</html>
