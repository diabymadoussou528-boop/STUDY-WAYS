<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — StudyWays</title>
    <meta name="description" content="Connectez-vous à votre espace StudyWays">
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

    <div class="auth-modal" role="dialog" aria-modal="true" aria-label="Connexion">
        <a href="{{ route('home') }}" class="auth-modal-close" id="authClose" aria-label="Fermer">
            <i class="fas fa-times"></i>
        </a>
        <div class="auth-split">

        <!-- LEFT — Branded Panel -->
        <aside class="auth-panel-left">
            <div class="auth-float-shapes">
                <div class="shape"></div>
                <div class="shape"></div>
                <div class="shape"></div>
            </div>
            <div class="auth-brand-content">
                <x-sw-brand :href="route('home')" variant="on-dark" size="xl" class="auth-brand-logo" />
                <h1 class="auth-brand-title">Bonjour,<br>bienvenue&nbsp;!</h1>
                <p class="auth-brand-desc">
                    Pas encore de compte ? Rejoignez StudyWays et commencez
                    votre parcours d'apprentissage dès aujourd'hui.
                </p>
                <a href="{{ route('register') }}" class="auth-switch-btn js-auth-switch">
                    S'inscrire
                </a>
                <a href="{{ route('home') }}" class="auth-brand-link" style="margin-top:26px;">
                    <i class="fas fa-arrow-left"></i> Retour à l'accueil
                </a>
            </div>
        </aside>

        <!-- RIGHT — Form Panel -->
        <section class="auth-panel-right">
            <div class="auth-card">
                <h2 class="auth-card-title">Se connecter</h2>
                <p class="auth-card-subtitle">Accédez à votre espace selon votre profil</p>

                @if($errors->any())
                    <div class="auth-error">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf

                    <!-- Email -->
                    <div class="form-group has-icon">
                        <i class="input-icon fas fa-envelope"></i>
                        <label class="form-label" for="email">Adresse e-mail</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            class="form-input"
                            placeholder=" "
                            value="{{ old('email') }}"
                            required
                            autocomplete="email"
                            autofocus
                        >
                    </div>

                    <!-- Password -->
                    <div class="form-group has-icon">
                        <i class="input-icon fas fa-lock"></i>
                        <label class="form-label" for="password">Mot de passe</label>
                        <div class="input-password-wrap">
                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="form-input"
                                placeholder=" "
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" class="password-toggle" id="togglePass" aria-label="Afficher le mot de passe">
                                Voir
                            </button>
                        </div>
                    </div>

                    <!-- Remember / Forgot -->
                    <div class="form-row">
                        <label class="form-checkbox">
                            <input type="checkbox" name="remember" id="remember"> Se souvenir de moi
                        </label>
                        <a href="{{ route('password.request') }}" class="form-forgot js-auth-switch">Mot de passe oublié ?</a>
                    </div>

                    <button type="submit" class="btn-auth" id="loginSubmit">
                        <i class="fas fa-sign-in-alt"></i> <span>Se connecter</span>
                    </button>
                </form>

                <p class="auth-alt">
                    Pas encore de compte ? <a href="{{ route('register') }}">Créer un compte</a>
                </p>
            </div>
        </section>

        </div>
    </div>

    <script>
        // Force light mode (dark mode removed)
        localStorage.removeItem('sw_theme');
        document.documentElement.removeAttribute('data-theme');

        // Modal close behaviour (close button + overlay click + Escape)
        const authClose = document.getElementById('authClose');
        const authOverlay = document.getElementById('authOverlay');
        const homeUrl = authClose.getAttribute('href');

        function navigateWithExit(url) {
            document.body.classList.add('auth-closing');
            setTimeout(() => { window.location.href = url; }, 220);
        }

        authClose.addEventListener('click', (e) => { e.preventDefault(); navigateWithExit(homeUrl); });
        authOverlay.addEventListener('click', () => navigateWithExit(homeUrl));
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') navigateWithExit(homeUrl); });

        // Smooth transition when switching to the other auth page
        document.querySelectorAll('.js-auth-switch').forEach((el) => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                navigateWithExit(el.getAttribute('href'));
            });
        });

        // Password Toggle
        const passField = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePass');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                const isText = passField.type === 'text';
                passField.type = isText ? 'password' : 'text';
                toggleBtn.textContent = isText ? 'Voir' : 'Masquer';
            });
        }

        // Button ripple
        const loginBtn = document.getElementById('loginSubmit');
        loginBtn.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
            ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });

        // Submit loading state
        document.getElementById('loginForm').addEventListener('submit', function() {
            loginBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Connexion...';
            loginBtn.disabled = true;
        });
    </script>
</body>
</html>
