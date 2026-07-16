<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats de recherche{{ $query ? ' — '.$query : '' }} — StudyWays</title>
    <meta name="description" content="Recherchez des cours StudyWays par technologie, catégorie ou mot-clé.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/course-card.css') }}">
    <link rel="stylesheet" href="{{ asset('css/course-experience.css') }}">
    <link rel="stylesheet" href="{{ asset('css/course-search.css') }}">
</head>
<body>
<header class="navbar scrolled" id="navbar">
    <x-sw-brand :href="route('home')" variant="default" size="lg" />
    <div class="nav-tools" style="flex:1;max-width:720px;margin:0 24px;">
        <form action="{{ route('courses.search') }}" method="GET" class="nav-search-wrap" style="width:100%;">
            <label class="visually-hidden" for="siteSearch">Rechercher des cours</label>
            <input
                type="search"
                id="siteSearch"
                name="q"
                class="nav-search-input"
                value="{{ $query }}"
                placeholder="Rechercher ReactJS, Laravel, Python..."
                autocomplete="off"
            />
            <button type="submit" class="nav-search-submit" aria-label="Rechercher">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
    <a href="{{ route('catalog.index') }}" class="btn btn-outline-nav">Catalogue</a>
</header>

<main class="course-search-page">
    <div class="sw-section-shell">
        @if($query)
            <header class="course-search-header">
                <h1>
                    Résultats les plus pertinents pour
                    <span class="course-search-highlight">« {{ $query }} »</span>
                </h1>
                @if($total > 0)
                    <p>{{ $total }} cours trouvé(s)</p>
                @endif
            </header>
        @else
            <header class="course-search-header">
                <h1>Rechercher un cours</h1>
                <p>Tapez une technologie, une catégorie ou un mot-clé (ex. ReactJS, Laravel).</p>
            </header>
        @endif

        @if($query && $total === 0)
            <div class="course-search-empty">
                <i class="fas fa-search"></i>
                <h2>Aucun cours trouvé</h2>
                <p>{{ $message ?? 'Essayez un autre mot-clé ou parcourez le catalogue.' }}</p>
                <a href="{{ route('catalog.index') }}" class="btn btn-primary-nav">Voir le catalogue</a>
                @if(!empty($suggestions))
                    <div class="course-search-suggestions">
                        <span>Suggestions :</span>
                        @foreach($suggestions as $suggestion)
                            <a href="{{ route('courses.search', ['q' => $suggestion]) }}">{{ $suggestion }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        @foreach($groups as $group)
            <section class="course-search-group">
                <h2 class="course-search-group__title">
                    {{ $group['heading'] }}
                    @if($group['category_slug'])
                        <a href="{{ route('catalog.index') }}?category={{ $group['category_slug'] }}" class="course-search-group__link">
                            Voir la catégorie
                        </a>
                    @endif
                </h2>
                <div class="sw-courses-grid">
                    @foreach($group['courses'] as $course)
                        <x-course-card :course="$course" cta-label="Voir les détails" />
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</main>

<script src="{{ asset('js/course-search.js') }}" defer></script>
</body>
</html>
