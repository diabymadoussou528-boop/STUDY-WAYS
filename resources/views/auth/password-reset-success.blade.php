<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe réinitialisé — StudyWays</title>
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

    <div class="auth-modal auth-modal--single" role="dialog" aria-modal="true" aria-label="Mot de passe réinitialisé">
        <div class="auth-single">
            <x-sw-brand :href="route('home')" variant="card" size="sm" centered class="auth-card-logo" />

            <div class="auth-single-icon auth-success-icon"><i class="fas fa-check"></i></div>

            <h2 class="auth-card-title">Mot de passe mis à jour&nbsp;!</h2>
            <p class="auth-card-subtitle">
                Votre mot de passe a été réinitialisé avec succès.
                Vous pouvez désormais vous connecter avec votre nouveau mot de passe.
            </p>

            <a href="{{ route('login') }}" class="btn-auth js-auth-switch" id="successBtn" style="text-decoration:none;">
                <i class="fas fa-arrow-right-to-bracket"></i> <span>Retour à la connexion</span>
            </a>
        </div>
    </div>

    <script>
        localStorage.removeItem('sw_theme');
        document.documentElement.removeAttribute('data-theme');

        function navigateWithExit(url) {
            document.body.classList.add('auth-closing');
            setTimeout(() => { window.location.href = url; }, 220);
        }
        document.querySelectorAll('.js-auth-switch').forEach((el) => {
            el.addEventListener('click', (e) => { e.preventDefault(); navigateWithExit(el.getAttribute('href')); });
        });
    </script>
</body>
</html>
