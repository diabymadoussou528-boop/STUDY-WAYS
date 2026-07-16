<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue — StudyWays</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/course-card.css') }}">
    <link rel="stylesheet" href="{{ asset('css/course-experience.css') }}">
</head>
<body>
<header class="navbar" id="navbar">
    <x-sw-brand :href="route('home')" variant="default" size="lg" />
    <a href="{{ route('home') }}" class="btn btn-outline btn-sm">Accueil</a>
</header>

<main class="sw-catalog-page">
    <section class="sw-section-shell">
        <div class="sw-catalog-hero sw-surface">
            <div class="sw-catalog-hero__copy">
                <h1>Catalogue premium StudyWays</h1>
                <p>Explorez des parcours modernes avec recherche rapide, filtres élégants, cartes cohérentes et vraies données issues de votre plateforme existante.</p>
            </div>
            <x-course-search-bar
                :action="route('catalog.index')"
                :value="$query"
                placeholder="Rechercher Laravel, Vue.js, design, data..."
            />
            <div class="sw-catalog-hero__meta">
                <span><i class="fas fa-book-open"></i> {{ $courses->total() }} cours disponibles</span>
                @if($categorySlug)
                    <span><i class="fas fa-filter"></i> Filtre catégorie actif</span>
                @endif
                @if($difficulty)
                    <span><i class="fas fa-signal"></i> Niveau {{ $difficulty }}</span>
                @endif
            </div>
        </div>

        <div class="sw-catalog-layout">
            <x-course-filters
                class="sw-surface"
                :categories="$categories"
                :selected-category="$categorySlug"
                :selected-difficulty="$difficulty"
                :selected-sort="$sort"
                :query="$query"
            />

            <div class="sw-catalog-results">
                <div class="sw-catalog-results__head sw-surface">
                    <div>
                        <h2>Résultats du catalogue</h2>
                        <p>Cartes cohérentes sur desktop, tablette et mobile.</p>
                    </div>
                    <a href="{{ route('home') }}#catalogue" class="btn btn-outline btn-sm">Retour à l'accueil</a>
                </div>

                <div class="sw-courses-grid reveal-stagger">
                    @forelse($courses as $course)
                        <x-course-card :course="$course" cta-label="Voir les détails" />
                    @empty
                        <p class="sw-empty-state" style="grid-column:1/-1;">Aucun cours publié pour le moment.</p>
                    @endforelse
                </div>

                <x-course-pagination :paginator="$courses" />
            </div>
        </div>
    </section>
</main>
</body>
</html>
