<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer votre mot de passe — StudyWays Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-page">

    @if ($errors->any())
        <div class="auth-toast is-error" id="authToast" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <div class="auth-overlay" id="authOverlay"></div>

    <div class="auth-modal auth-modal--single" role="dialog" aria-modal="true" aria-label="Créer votre mot de passe">
        <div class="auth-single">
            <x-sw-brand variant="card" size="sm" centered class="auth-card-logo" />

            <div class="auth-single-icon"><i class="fas fa-shield-halved"></i></div>

            <h2 class="auth-card-title">Créez votre mot de passe</h2>
            <p class="auth-card-subtitle">
                Bienvenue sur StudyWays ! Pour des raisons de sécurité, remplacez votre mot de passe temporaire avant d'accéder au tableau de bord.
            </p>

            <form method="POST" action="{{ route('password.force.update') }}" id="forcePasswordForm">
                @csrf

                <div class="form-group has-icon">
                    <i class="input-icon fas fa-key"></i>
                    <label class="form-label" for="current_password">Mot de passe temporaire</label>
                    <input id="current_password" type="password" name="current_password" class="form-input" placeholder=" " required autocomplete="current-password">
                </div>

                <div class="form-group has-icon">
                    <i class="input-icon fas fa-lock"></i>
                    <label class="form-label" for="password">Nouveau mot de passe</label>
                    <input id="password" type="password" name="password" class="form-input" placeholder=" " required autocomplete="new-password">
                </div>

                <div class="form-group has-icon">
                    <i class="input-icon fas fa-lock"></i>
                    <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-input" placeholder=" " required autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-auth-primary btn-block">
                    <i class="fas fa-check"></i> Enregistrer et accéder au dashboard
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
