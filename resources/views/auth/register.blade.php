<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — StudyWays</title>
    <meta name="description" content="Créez votre compte StudyWays et commencez à apprendre dès aujourd'hui">
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

    <div class="auth-modal auth-modal--reverse" role="dialog" aria-modal="true" aria-label="Inscription">
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
                <h1 class="auth-brand-title">Content de<br>vous revoir&nbsp;!</h1>
                <p class="auth-brand-desc">
                    Vous avez déjà un compte ? Connectez-vous pour retrouver
                    votre parcours et vos cours en cours.
                </p>
                <a href="{{ route('login') }}" class="auth-switch-btn js-auth-switch">
                    Se connecter
                </a>
                <a href="{{ route('home') }}" class="auth-brand-link" style="margin-top:26px;">
                    <i class="fas fa-arrow-left"></i> Retour à l'accueil
                </a>
            </div>
        </aside>

        <!-- RIGHT — Form Panel -->
        <section class="auth-panel-right" style="align-items:flex-start;padding-top:40px;overflow-y:auto;">
            <div class="auth-card" style="margin:auto 0;">
                <h2 class="auth-card-title">Créer un compte</h2>
                <p class="auth-card-subtitle">Choisissez votre rôle et commencez votre parcours</p>

                @if($errors->any())
                    <div class="auth-error">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" id="registerForm" enctype="multipart/form-data">
                    @csrf

                    <!-- Avatar Upload -->
                    <div class="avatar-upload">
                        <label class="avatar-preview" for="avatarInput" id="avatarPreview">
                            <span class="avatar-upload-placeholder" id="avatarPlaceholder">
                                <i class="fas fa-user" style="font-size:1.5rem;color:var(--clr-dim)"></i>
                            </span>
                            <img id="avatarImg" src="" alt="" style="display:none;width:100%;height:100%;object-fit:cover;">
                        </label>
                        <div class="avatar-upload-info">
                            <label for="avatarInput" class="avatar-upload-btn">
                                <i class="fas fa-camera"></i> Photo de profil
                            </label>
                            <span class="avatar-upload-hint">JPG, PNG · Max 2 Mo</span>
                        </div>
                        <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display:none">
                    </div>

                    <!-- Name -->
                    <div class="form-group has-icon">
                        <i class="input-icon fas fa-user"></i>
                        <label class="form-label" for="name">Nom complet</label>
                        <input
                            id="name" type="text" name="name" class="form-input"
                            placeholder=" " value="{{ old('name') }}"
                            required autocomplete="name" autofocus
                        >
                    </div>

                    <!-- Email -->
                    <div class="form-group has-icon">
                        <i class="input-icon fas fa-envelope"></i>
                        <label class="form-label" for="email">Adresse e-mail</label>
                        <input
                            id="email" type="email" name="email" class="form-input"
                            placeholder=" " value="{{ old('email') }}"
                            required autocomplete="email"
                        >
                    </div>

                    <!-- Role -->
                    <div class="form-group has-icon">
                        <i class="input-icon fas fa-user-tag"></i>
                        <label class="form-label" for="role">Je suis</label>
                        <select id="role" name="role" class="form-select" required>
                            <option value="" disabled {{ old('role') ? '' : 'selected' }}>Sélectionnez votre rôle</option>
                            <option value="student"   {{ old('role') == 'student'   ? 'selected' : '' }}>Étudiant(e)</option>
                            <option value="professor" {{ old('role') == 'professor' ? 'selected' : '' }}>Professeur / Formateur</option>
                        </select>
                    </div>

                    <!-- Specialization (professors only) -->
                    <div class="form-group has-icon" id="specializationGroup" style="display:none;">
                        <i class="input-icon fas fa-graduation-cap"></i>
                        <label class="form-label" for="specialization">Spécialisation</label>
                        <input
                            id="specialization" type="text" name="specialization" class="form-input"
                            placeholder=" " value="{{ old('specialization') }}"
                            autocomplete="organization-title"
                        >
                        <span class="form-hint">Ex. : Développement Web, UI/UX, Data Science...</span>
                    </div>

                    <!-- Password -->
                    <div class="form-group has-icon">
                        <i class="input-icon fas fa-lock"></i>
                        <label class="form-label" for="password">Mot de passe</label>
                        <div class="input-password-wrap">
                            <input
                                id="password" type="password" name="password" class="form-input"
                                placeholder=" " required autocomplete="new-password"
                            >
                            <button type="button" class="password-toggle" id="togglePass">Voir</button>
                        </div>
                        <div class="password-meter"><div class="password-meter-bar" id="strengthBar"></div></div>
                        <span class="form-hint" id="strengthLabel"></span>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group has-icon">
                        <i class="input-icon fas fa-shield-halved"></i>
                        <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
                        <div class="input-password-wrap">
                            <input
                                id="password_confirmation" type="password" name="password_confirmation"
                                class="form-input" placeholder=" " required
                            >
                            <button type="button" class="password-toggle" id="toggleConfirm">Voir</button>
                        </div>
                    </div>

                    <button type="submit" class="btn-auth" id="registerSubmit">
                        <i class="fas fa-user-plus"></i> <span>Créer mon compte</span>
                    </button>
                </form>

                <p class="auth-alt">
                    Déjà un compte ? <a href="{{ route('login') }}">Se connecter</a>
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

        // Role-based specialization field
        const roleSelect = document.getElementById('role');
        const specializationGroup = document.getElementById('specializationGroup');
        const specializationInput = document.getElementById('specialization');

        function toggleSpecializationField() {
            const isProfessor = roleSelect.value === 'professor';
            specializationGroup.style.display = isProfessor ? 'block' : 'none';
            specializationInput.required = isProfessor;
            if (!isProfessor) {
                specializationInput.value = '';
            }
        }

        if (roleSelect) {
            const params = new URLSearchParams(window.location.search);
            if (params.get('role') === 'professor') {
                roleSelect.value = 'professor';
            }
            roleSelect.addEventListener('change', toggleSpecializationField);
            toggleSpecializationField();
        }

        // Password Toggle
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

        // Password Strength
        const pwField = document.getElementById('password');
        const bar = document.getElementById('strengthBar');
        const label = document.getElementById('strengthLabel');
        if (pwField) {
            pwField.addEventListener('input', () => {
                const val = pwField.value;
                let strength = 0;
                if (val.length >= 8) strength++;
                if (/[A-Z]/.test(val)) strength++;
                if (/[0-9]/.test(val)) strength++;
                if (/[^A-Za-z0-9]/.test(val)) strength++;
                bar.className = 'password-meter-bar';
                if (val.length === 0) { label.textContent = ''; return; }
                if (strength <= 1) { bar.classList.add('weak'); label.textContent = 'Faible'; label.style.color = 'var(--clr-danger)'; }
                else if (strength <= 2) { bar.classList.add('medium'); label.textContent = 'Moyen'; label.style.color = 'var(--clr-warning)'; }
                else { bar.classList.add('strong'); label.textContent = 'Fort'; label.style.color = 'var(--clr-success)'; }
            });
        }

        // Avatar Preview
        const avatarInput = document.getElementById('avatarInput');
        const avatarImg = document.getElementById('avatarImg');
        const avatarPlaceholder = document.getElementById('avatarPlaceholder');
        if (avatarInput) {
            avatarInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = (ev) => {
                    avatarImg.src = ev.target.result;
                    avatarImg.style.display = 'block';
                    avatarPlaceholder.style.display = 'none';
                };
                reader.readAsDataURL(file);
            });
        }

        // Button ripple
        const submitBtn = document.getElementById('registerSubmit');
        submitBtn.addEventListener('click', function(e) {
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

        // Submit loading state — delay disable so the browser can send the POST.
        document.getElementById('registerForm').addEventListener('submit', function() {
            submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Création...';
            submitBtn.setAttribute('aria-busy', 'true');
        });
    </script>
</body>
</html>
