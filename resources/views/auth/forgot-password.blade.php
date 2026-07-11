<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié — StudyWays</title>
    <meta name="description" content="Réinitialisez votre mot de passe StudyWays">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-page">

    @if (session('status'))
        <div class="auth-toast" id="authToast" role="status">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('status') }}</span>
        </div>
    @elseif ($errors->any())
        <div class="auth-toast is-error" id="authToast" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <div class="auth-overlay" id="authOverlay"></div>

    <div class="auth-modal auth-modal--single" role="dialog" aria-modal="true" aria-label="Mot de passe oublié">
        <a href="{{ route('login') }}" class="auth-modal-close js-auth-switch" id="authClose" aria-label="Fermer">
            <i class="fas fa-times"></i>
        </a>

        <div class="auth-single">
            <x-sw-brand :href="route('home')" variant="card" size="sm" centered class="auth-card-logo" />

            <div class="auth-single-icon"><i class="fas fa-key"></i></div>

            <h2 class="auth-card-title">Mot de passe oublié ?</h2>
            <p class="auth-card-subtitle">
                Saisissez votre adresse e-mail et nous vous enverrons un lien sécurisé
                pour réinitialiser votre mot de passe.
            </p>

            <form method="POST" action="{{ route('password.email') }}" id="forgotForm">
                @csrf

                <div class="form-group has-icon {{ $errors->has('email') ? 'has-error' : '' }}">
                    <i class="input-icon fas fa-envelope"></i>
                    <label class="form-label" for="email">Adresse e-mail</label>
                    <input
                        id="email" type="email" name="email" class="form-input"
                        placeholder=" " value="{{ old('email') }}"
                        required autocomplete="email" autofocus
                    >
                    @error('email')
                        <span class="field-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn-auth" id="forgotSubmit">
                    <i class="fas fa-paper-plane"></i> <span>Envoyer le lien</span>
                </button>
            </form>

            <p class="auth-alt">
                <a href="{{ route('login') }}" class="js-auth-switch"><i class="fas fa-arrow-left"></i> Retour à la connexion</a>
            </p>
        </div>
    </div>

    <script>
        localStorage.removeItem('sw_theme');
        document.documentElement.removeAttribute('data-theme');

        // Toast auto show / hide
        const toast = document.getElementById('authToast');
        if (toast) {
            requestAnimationFrame(() => toast.classList.add('show'));
            setTimeout(() => toast.classList.remove('show'), 5000);
            toast.addEventListener('click', () => toast.classList.remove('show'));
        }

        // Smooth exit transition when navigating to login
        function navigateWithExit(url) {
            document.body.classList.add('auth-closing');
            setTimeout(() => { window.location.href = url; }, 220);
        }
        const authOverlay = document.getElementById('authOverlay');
        authOverlay.addEventListener('click', () => navigateWithExit("{{ route('login') }}"));
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') navigateWithExit("{{ route('login') }}"); });
        document.querySelectorAll('.js-auth-switch').forEach((el) => {
            el.addEventListener('click', (e) => { e.preventDefault(); navigateWithExit(el.getAttribute('href')); });
        });

        // Button ripple
        const submitBtn = document.getElementById('forgotSubmit');
        submitBtn.addEventListener('click', function (e) {
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

        // Loading state
        document.getElementById('forgotForm').addEventListener('submit', function () {
            submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Envoi...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>
