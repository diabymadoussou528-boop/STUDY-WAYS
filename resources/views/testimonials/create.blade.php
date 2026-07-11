<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un témoignage — StudyWays</title>
    <script>
        (function () {
            const theme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="page-testimonial">
    <button id="themeToggleBtn" class="theme-toggle-floating" aria-label="Toggle theme">
        <i class="fas fa-moon"></i>
    </button>
    <div class="auth-container">
        <div class="auth-card" style="max-width: 580px;">
            <a href="{{ route('home') }}" class="auth-back-link">
                <i class="fas fa-arrow-left"></i> Retour à l'accueil
            </a>

            <div class="auth-card-header" style="text-align:center; margin-bottom:32px;">
                <x-sw-brand :href="route('home')" variant="card" size="sm" centered style="margin:0 auto 16px;" />
                <h1 style="font-size:1.5rem; font-weight:700; margin-bottom:6px;">Partagez votre expérience</h1>
                <p style="color:var(--clr-muted); font-size:0.9rem;">Votre témoignage aide d'autres apprenants à choisir StudyWays.</p>
            </div>

            @if($errors->any())
                <div class="auth-error">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('testimonials.store') }}">
                @csrf

                <div class="form-group">
                    <label for="rating" class="form-label">Note (optionnel)</label>
                    <div style="display:flex; gap:8px; margin-top:4px;">
                        @for($i = 1; $i <= 5; $i++)
                            <label style="cursor:pointer; font-size:1.5rem; color: {{ old('rating', 0) >= $i ? '#d29922' : '#333' }}; transition: color 0.2s;">
                                <input type="radio" name="rating" value="{{ $i }}" {{ old('rating') == $i ? 'checked' : '' }}
                                       style="display:none" onchange="updateStars(this)">
                                ★
                            </label>
                        @endfor
                    </div>
                </div>

                <div class="form-group">
                    <label for="message" class="form-label">Votre témoignage</label>
                    <textarea id="message" name="message" class="form-input" rows="5"
                              placeholder="Décrivez votre expérience avec StudyWays (minimum 20 caractères)..."
                              required minlength="20" maxlength="1000"
                              style="resize:vertical; min-height:120px;">{{ old('message') }}</textarea>
                    <span class="form-hint" id="charCount">0 / 1000 caractères</span>
                </div>

                <button type="submit" class="btn-auth">
                    <i class="fas fa-paper-plane"></i> Envoyer mon témoignage
                </button>
            </form>
        </div>
    </div>

    <script>
        // Star rating interaction
        function updateStars(radio) {
            const val = parseInt(radio.value);
            const labels = radio.closest('div').querySelectorAll('label');
            labels.forEach((l, i) => {
                l.style.color = i < val ? '#d29922' : '#333';
            });
        }

        // Character counter
        const textarea = document.getElementById('message');
        const counter = document.getElementById('charCount');
        if (textarea && counter) {
            const update = () => counter.textContent = textarea.value.length + ' / 1000 caractères';
            textarea.addEventListener('input', update);
            update();
        }

        // Theme toggle logic
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        if (themeToggleBtn) {
            const icon = themeToggleBtn.querySelector('i');
            const updateThemeIcon = (theme) => {
                if (theme === 'light') {
                    icon.className = 'fas fa-sun';
                } else {
                    icon.className = 'fas fa-moon';
                }
            };

            const currentTheme = localStorage.getItem('theme') || 'dark';
            updateThemeIcon(currentTheme);

            themeToggleBtn.addEventListener('click', () => {
                const activeTheme = document.documentElement.getAttribute('data-theme') || 'dark';
                const newTheme = activeTheme === 'dark' ? 'light' : 'dark';
                
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateThemeIcon(newTheme);
            });
        }
    </script>
</body>
</html>
