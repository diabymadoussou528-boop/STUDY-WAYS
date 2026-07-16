@props([
    'categories' => collect(),
    'selectedCategory' => null,
    'selectedDifficulty' => null,
    'selectedSort' => 'recent',
    'query' => '',
])

<form method="GET" action="{{ route('catalog.index') }}" {{ $attributes->class(['sw-catalog-filters']) }}>
    <input type="hidden" name="q" value="{{ $query }}">

    <div class="sw-catalog-filters__section">
        <h3>Tri</h3>
        <label class="sw-filter-field">
            <span class="visually-hidden">Trier les cours</span>
            <select name="sort" class="sw-filter-select">
                <option value="recent" @selected($selectedSort === 'recent')>Plus récents</option>
                <option value="popular" @selected($selectedSort === 'popular')>Plus populaires</option>
                <option value="rating" @selected($selectedSort === 'rating')>Mieux notés</option>
                <option value="title" @selected($selectedSort === 'title')>Titre A-Z</option>
            </select>
        </label>
    </div>

    <div class="sw-catalog-filters__section">
        <h3>Catégories</h3>
        <label class="sw-filter-check">
            <input type="radio" name="category" value="" @checked(blank($selectedCategory))>
            <span>Toutes les catégories</span>
        </label>
        @foreach($categories as $category)
            <label class="sw-filter-check">
                <input type="radio" name="category" value="{{ $category->slug }}" @checked($selectedCategory === $category->slug)>
                <span>{{ $category->name }}</span>
                <small>{{ number_format($category->courses_count ?? 0) }}</small>
            </label>
        @endforeach
    </div>

    <div class="sw-catalog-filters__section">
        <h3>Niveau</h3>
        <label class="sw-filter-check">
            <input type="radio" name="difficulty" value="" @checked(blank($selectedDifficulty))>
            <span>Tous niveaux</span>
        </label>
        @foreach(['beginner' => 'Débutant', 'intermediate' => 'Intermédiaire', 'advanced' => 'Avancé'] as $value => $label)
            <label class="sw-filter-check">
                <input type="radio" name="difficulty" value="{{ $value }}" @checked($selectedDifficulty === $value)>
                <span>{{ $label }}</span>
            </label>
        @endforeach
    </div>

    <div class="sw-catalog-filters__actions">
        <button type="submit" class="btn btn-primary btn-sm">Appliquer</button>
        <a href="{{ route('catalog.index') }}" class="btn btn-outline btn-sm">Réinitialiser</a>
    </div>
</form>
