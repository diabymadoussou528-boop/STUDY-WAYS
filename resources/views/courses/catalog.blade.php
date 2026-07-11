<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $course['title'] }} — StudyWays</title>
    <script>
        (function () {
            const theme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    
    <style>
        .catalog-container {
            max-width: 1200px;
            margin: 120px auto 60px;
            padding: 0 24px;
        }
        .catalog-header {
            margin-bottom: 40px;
            position: relative;
        }
        .catalog-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 12px;
            line-height: 1.2;
        }
        .catalog-subtitle {
            font-size: 1.1rem;
            color: var(--text-muted);
            max-width: 700px;
            line-height: 1.6;
        }
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
            gap: 32px;
            margin-top: 40px;
        }
        .video-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .video-card:hover {
            border-color: var(--border-hover);
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(139,32,50,0.15);
        }
        .video-card iframe {
            width: 100%;
            height: 220px;
            border: 0;
            display: block;
        }
        .video-card-body {
            padding: 20px;
        }
        .video-card-title {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        .video-card-title i {
            color: var(--primary);
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            margin-bottom: 24px;
            font-weight: 500;
            transition: color 0.2s;
        }
        .btn-back:hover {
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .catalog-title {
                font-size: 2rem;
            }
            .video-grid {
                grid-template-columns: 1fr;
            }
            .catalog-container {
                margin-top: 100px;
            }
        }
    </style>
</head>
<body>

    <!-- ─── NAVBAR ─── -->
    <header class="navbar scrolled" id="navbar">
        <x-sw-brand :href="route('home')" variant="default" size="lg" />

        <div class="nav-tools">
            <nav id="mainNav">
                <a href="{{ route('home') }}">Accueil</a>
                <a href="{{ route('home') }}#catalogue">Cours</a>
                <a href="{{ route('home') }}#tarifs">Tarifs</a>
                <a href="{{ route('home') }}#temoignages">Témoignages</a>
            </nav>
            <button id="themeToggleBtn" class="theme-toggle-btn" aria-label="Toggle theme">
                <i class="fas fa-moon"></i>
            </button>
        </div>
    </header>

    <main class="catalog-container">
        <a href="{{ route('home') }}" class="btn-back">
            <i class="fas fa-arrow-left"></i> Retour à l'accueil
        </a>

        <header class="catalog-header">
            <h1 class="catalog-title">{{ $course['title'] }}</h1>
            <p class="catalog-subtitle">
                Voici les liens attribués à ce module. Vous pouvez lancer chaque cours vidéo directement depuis cette interface.
            </p>
        </header>

        <div class="video-grid">
            @foreach($course['videos'] as $video)
                <article class="video-card">
                    <iframe src="{{ $video }}" allowfullscreen></iframe>
                    <div class="video-card-body">
                        <h3 class="video-card-title">
                            <i class="fas fa-play-circle"></i> Lecture du contenu YouTube
                        </h3>
                    </div>
                </article>
            @endforeach
        </div>
    </main>

    <!-- ─── SCRIPTS ─── -->
    <script>
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
