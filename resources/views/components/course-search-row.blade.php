{{-- Udemy-style horizontal course search result row --}}
@props(['course'])

<a href="{{ $course['url'] }}" class="course-search-row">
    <div class="course-search-row__thumb">
        <img src="{{ $course['thumbnail'] }}" alt="{{ $course['title'] }}" loading="lazy">
    </div>
    <div class="course-search-row__body">
        <h3 class="course-search-row__title">{{ $course['title'] }}</h3>
        <p class="course-search-row__instructors">{{ $course['instructors'] }}</p>
        @if(!empty($course['technologies']))
            <p class="course-search-row__tags">
                @foreach($course['technologies'] as $tag)
                    <span>{{ $tag }}</span>
                @endforeach
            </p>
        @endif
        @if($course['description'])
            <p class="course-search-row__desc">{{ $course['description'] }}</p>
        @endif
        <div class="course-search-row__meta">
            @if($course['rating'] > 0)
                <span class="course-search-row__rating">
                    <strong>{{ number_format($course['rating'], 1) }}</strong>
                    <i class="fas fa-star"></i>
                    <span class="course-search-row__reviews">({{ number_format($course['reviews_count']) }})</span>
                </span>
            @endif
            <span class="course-search-row__price">{{ $course['price_label'] }}</span>
        </div>
        @if(!empty($course['badges']))
            <div class="course-search-row__badges">
                @foreach($course['badges'] as $badge)
                    <span @class([
                        'course-search-badge',
                        'course-search-badge--premium' => $badge === 'Premium',
                        'course-search-badge--bestseller' => $badge === 'Best-seller',
                        'course-search-badge--new' => $badge === 'Nouveau',
                    ])>{{ $badge }}</span>
                @endforeach
            </div>
        @endif
    </div>
</a>
