@props([
    'rating' => 0,
    'reviews' => 0,
    'size' => 'md',
    'showCount' => true,
])

@php
    $value = round((float) $rating, 1);
    $starClass = match ($size) {
        'sm' => 'sw-course-rating--sm',
        'lg' => 'sw-course-rating--lg',
        default => '',
    };
@endphp

<div {{ $attributes->class(['sw-course-rating', $starClass]) }}>
    <span class="sw-course-rating__value">{{ number_format($value, 1) }}</span>
    <span class="sw-course-rating__stars" aria-hidden="true">
        @for($i = 1; $i <= 5; $i++)
            <i class="fas fa-star{{ $i <= round($value) ? '' : ' sw-course-rating__star--empty' }}"></i>
        @endfor
    </span>
    @if($showCount)
        <span class="sw-course-rating__count">({{ number_format((int) $reviews) }})</span>
    @endif
</div>
