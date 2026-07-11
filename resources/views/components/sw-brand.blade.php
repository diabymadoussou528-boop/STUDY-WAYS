@props([
    'href' => null,
    'variant' => 'default',
    'size' => 'md',
    'centered' => false,
    'animate' => true,
])

@php
    $tag = $href ? 'a' : 'span';
    $classes = collect([
        'sw-brand',
        'sw-brand--' . $variant,
        'sw-brand--' . $size,
        $centered ? 'sw-brand--center' : null,
        $animate ? 'sw-brand-fade-in' : null,
    ])->filter()->implode(' ');
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
    @if($href) aria-label="StudyWays — Accueil" @else aria-hidden="true" @endif
>
    <span class="brand-mark">
        <span class="brand-study">Study<i class="fas fa-graduation-cap brand-cap"></i></span>
        <span class="brand-ways">Ways</span>
    </span>
</{{ $tag }}>
