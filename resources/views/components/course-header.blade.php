@props([
    'course',
    'heroStats',
    'tags' => [],
])

<div {{ $attributes->class(['sw-course-header']) }}>
    @if(!empty($tags))
        <div class="sw-course-header__tags">
            @foreach(array_slice($tags, 0, 4) as $tag)
                <span>{{ $tag }}</span>
            @endforeach
        </div>
    @endif

    <div class="sw-course-header__meta">
        <span><i class="fas fa-folder-open"></i> {{ $course->category?->name ?? 'Cours' }}</span>
        <span><i class="fas fa-chalkboard-user"></i> {{ $course->user?->name ?? 'Instructeur' }}</span>
    </div>

    <h1 class="sw-course-header__title">{{ $course->title }}</h1>
    <p class="sw-course-header__desc">{{ $course->short_description ?? \Illuminate\Support\Str::limit($course->description, 220) }}</p>

    <div class="sw-course-header__stats">
        <x-course-rating :rating="$heroStats['rating']" :reviews="$heroStats['reviewsCount']" />
        <span><i class="fas fa-users"></i> {{ number_format($heroStats['enrollments']) }} étudiants</span>
        <span><i class="fas fa-clock"></i> {{ $heroStats['durationLabel'] }}</span>
        <span><i class="fas fa-eye"></i> {{ number_format($heroStats['views']) }} vues</span>
    </div>
</div>
