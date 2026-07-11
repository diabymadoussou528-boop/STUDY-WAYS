<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe — StudyWays</title>
    <meta name="description" content="Choisissez un nouveau mot de passe StudyWays">
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

    <div class="auth-modal auth-modal--single" role="dialog" aria-modal="true" aria-label="Réinitialiser le mot de passe">
        <a href="{{ route('login') }}" class="auth-modal-close js-auth-switch" id="authClose" aria-label="Fermer">
            <i class="fas fa-times"></i>
        </a>

        <div class="auth-single">
            <x-sw-brand :href="route('home')" variant="card" size="sm" centered class="auth-card-logo" />

            <div class="auth-single-icon"><i class="fas fa-lock-open"></i></div>

            <h2 class="auth-card-title">Nouveau mot de passe</h2>
            <p class="auth-card-subtitle">
                Choisissez un mot de passe sécurisé pour protéger votre compte StudyWays.
            </p>

            <form method="POST" action="{{ route('password.store') }}" id="resetForm">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">
                <input type="hidden" name="email" value="{{ old('email', $request->email) }}">

                <!-- New Password -->
                <div class="form-group has-icon {{ $errors->has('password') ? 'has-error' : '' }}">
                    <i class="input-icon fas fa-lock"></i>
                    <label class="form-label" for="password">Nouveau mot de passe</label>
                    <div class="input-password-wrap">
                        <input
                            id="password" type="password" name="password" class="form-input"
                            placeholder=" " required autocomplete="new-password" autofocus
                        >
                        <button type="button" class="password-toggle" id="togglePass">Voir</button>
                    </div>
                    <div class="password-meter"><div class="password-meter-bar" id="strengthBar"></div></div>
                    <span class="form-hint" id="strengthLabel"></span>
                    @error('password')
                        <span class="field-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div class="form-group has-icon">
                    <i class="input-icon fas fa-shield-halved"></i>
                    <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
                    <div class="input-password-wrap">
                        <input
                            id="password_confirmation" type="password" name="password_confirmation"
                            class="form-input" placeholder=" " required autocomplete="new-password"
                        >
                        <button type="button" class="password-toggle" id="toggleConfirm">Voir</button>
                    </div>
                    <span class="field-error" id="matchError" style="display:none;">
                        <i class="fas fa-circle-exclamation"></i> Les mots de passe ne correspondent pas.
                    </span>
                </div>

                <button type="submit" class="btn-auth" id="resetSubmit">
                    <i class="fas fa-rotate-right"></i> <span>Réinitialiser le mot de passe</span>
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

        const toast = document.getElementById('authToast');
        if (toast) {
            requestAnimationFrame(() => toast.classList.add('show'));
            setTimeout(() => toast.classList.remove('show'), 5000);
            toast.addEventListener('click', () => toast.classList.remove('show'));
        }

        function navigateWithExit(url) {
            document.body.classList.add('auth-closing');
            setTimeout(() => { window.location.href = url; }, 220);
        }
        document.getElementById('authOverlay').addEventListener('click', () => navigateWithExit("{{ route('login') }}"));
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') navigateWithExit("{{ route('login') }}"); });
        document.querySelectorAll('.js-auth-switch').forEach((el) => {
            el.addEventListener('click', (e) => { e.preventDefault(); navigateWithExit(el.getAttribute('href')); });
        });

        // Show / hide password
        function makeToggle(fieldId, btnId) {
            const field = document.getElementById(fieldId);
            const btn = document.getElementById(btnId);
            if (btn) btn.addEventListener('click', () => {
                const isText = field.type === 'text';
                field.type = isText ? 'password' : 'text';
                btn.textContent = isText ? 'Voir' : 'Masquer';
            });
        }
        makeToggle('password', 'togglePass');
        makeToggle('password_confirmation', 'toggleConfirm');

        // Strength meter
        const pwField = document.getElementById('password');
        const bar = document.getElementById('strengthBar');
        const label = document.getElementById('strengthLabel');
        pwField.addEventListener('input', () => {
            const val = pwField.value;
            let strength = 0;
            if (val.length >= 8) strength++;
            if (/[A-Z]/.test(val)) strength++;
            if (/[0-9]/.test(val)) strength++;
            if (/[^A-Za-z0-9]/.test(val)) strength++;
            bar.className = 'password-meter-bar';
            if (val.length === 0) { label.textContent = ''; return; }
            if (strength <= 1) { bar.classList.add('weak'); label.textContent = 'Faible'; }
            else if (strength <= 2) { bar.classList.add('medium'); label.textContent = 'Moyen'; }
            else { bar.classList.add('strong'); label.textContent = 'Fort'; }
        });

        // Live match validation
        const confirmField = document.getElementById('password_confirmation');
        const matchError = document.getElementById('matchError');
        const confirmGroup = confirmField.closest('.form-group');
        function checkMatch() {
            if (confirmField.value.length === 0) { matchError.style.display = 'none'; confirmGroup.classList.remove('has-error'); return; }
            const ok = confirmField.value === pwField.value;
            matchError.style.display = ok ? 'none' : 'flex';
            confirmGroup.classList.toggle('has-error', !ok);
        }
        confirmField.addEventListener('input', checkMatch);
        pwField.addEventListener('input', checkMatch);

        // Button ripple
        const submitBtn = document.getElementById('resetSubmit');
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
        document.getElementById('resetForm').addEventListener('submit', function () {
            submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Mise à jour...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>
