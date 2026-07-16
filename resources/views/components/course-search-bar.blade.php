@props([
    'action',
    'value' => '',
    'placeholder' => 'Rechercher un cours',
])

<form action="{{ $action }}" method="GET" {{ $attributes->class(['sw-search-bar']) }}>
    <i class="fas fa-search sw-search-bar__icon" aria-hidden="true"></i>
    <input
        type="search"
        name="q"
        value="{{ $value }}"
        class="sw-search-bar__input"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
    >
    <button type="submit" class="sw-search-bar__submit">Rechercher</button>
</form>
