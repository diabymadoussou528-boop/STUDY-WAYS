@props([
    'course',
    'showProgress' => false,
    'progress' => null,
    'ctaLabel' => 'Voir les détails',
])

@php
    $rating = round((float) ($course->reviews_avg_rating ?? 0), 1);
    $duration = $course->duration_minutes ? $course->duration_minutes.' min' : 'Durée flexible';
    $description = $course->short_description ?: \Illuminate\Support\Str::limit($course->description, 96);
    $teacher = $course->user?->name ?? 'Instructeur';
    $students = (int) ($course->enrollments_count ?? 0);
    $reviews = (int) ($course->reviews_count ?? 0);
@endphp

<article {{ $attributes->merge(['class' => 'sw-course-card']) }}>
    <a href="{{ route('courses.show', $course) }}" class="sw-course-card__media" aria-label="{{ $course->title }}">
        <span class="sw-course-card__skeleton" aria-hidden="true"></span>
        <img
            src="{{ $course->thumbnailUrl() }}"
            alt="{{ $course->title }}"
            loading="lazy"
            class="sw-course-card__img"
            onload="this.classList.add('is-loaded'); if (this.previousElementSibling) this.previousElementSibling.remove();"
        >
        <span class="sw-course-card__play"><i class="fas fa-play"></i></span>
        @if($course->is_premium_only)
            <span class="sw-course-card__badge sw-course-card__badge--premium"><i class="fas fa-crown"></i> Premium</span>
        @elseif($course->isFree())
            <span class="sw-course-card__badge">Gratuit</span>
        @endif
        <span class="sw-course-card__duration"><i class="fas fa-clock"></i> {{ $duration }}</span>
    </a>

    <div class="sw-course-card__body">
        <div class="sw-course-card__meta">
            <span class="sw-course-card__category">{{ $course->category?->name ?? 'Cours' }}</span>
            @if($course->difficulty)
                <span class="sw-course-card__difficulty">{{ ucfirst($course->difficulty) }}</span>
            @endif
        </div>

        <h3 class="sw-course-card__title">
            <a href="{{ route('courses.show', $course) }}">{{ $course->title }}</a>
        </h3>

        <p class="sw-course-card__teacher">
            <i class="fas fa-chalkboard-user"></i>
            {{ $teacher }}
        </p>

        <p class="sw-course-card__desc">{{ $description }}</p>

        <x-course-rating :rating="$rating" :reviews="$reviews" size="sm" />

        <div class="sw-course-card__stats">
            <span title="Étudiants"><i class="fas fa-users"></i> {{ number_format($students) }} étudiants</span>
            <span title="Vues"><i class="fas fa-eye"></i> {{ number_format($course->views ?? 0) }} vues</span>
        </div>

        @if($showProgress && $progress !== null)
            <div class="sw-course-card__progress">
                <div class="sw-course-card__progress-bar" style="width: {{ min(100, (int) $progress) }}%"></div>
            </div>
            <span class="sw-course-card__progress-label">{{ (int) $progress }}% complété</span>
        @endif

        <a href="{{ route('courses.show', $course) }}" class="btn btn-details sw-course-card__cta">
            {{ $ctaLabel }}
            <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</article>
