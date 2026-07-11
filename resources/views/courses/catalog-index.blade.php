<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue — StudyWays</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
</head>
<body>
<header class="navbar" id="navbar">
    <x-sw-brand :href="route('home')" variant="default" size="lg" />
    <a href="{{ route('home') }}" class="btn btn-outline btn-sm">Accueil</a>
</header>

<section class="courses-catalog reveal" style="padding-top:120px;">
    <h2 class="courses-heading section-title-center">Catalogue des cours</h2>
    <div class="courses-heading-line"></div>
    <p class="section-lead">{{ $courses->total() }} cours disponibles</p>

    <div class="courses-grid reveal-stagger">
        @forelse($courses as $course)
            <article class="course-card">
                <div class="course-thumb" style="background-image:url('{{ $course->thumbnailUrl() }}');background-size:cover;">
                    @if($course->is_premium_only)
                        <span class="course-badge">Premium</span>
                    @endif
                </div>
                <div class="course-body">
                    <h3>{{ $course->title }}</h3>
                    <p class="course-stars">★ {{ number_format($course->reviews_avg_rating ?? 0, 1) }} <span>({{ $course->reviews_count }})</span></p>
                    <p class="course-desc">{{ Str::limit($course->short_description ?? $course->description, 120) }}</p>
                    <p style="font-size:.85rem;color:#666;margin:8px 0;">
                        {{ $course->user?->name }} · {{ $course->enrollments_count }} inscrits
                        @if($course->isFree()) · Gratuit @else · {{ number_format($course->price, 0, ',', ' ') }} XOF @endif
                    </p>
                    <a href="{{ route('courses.show', $course) }}" class="btn btn-details">Détails du cours</a>
                </div>
            </article>
        @empty
            <p style="text-align:center;grid-column:1/-1;">Aucun cours publié pour le moment.</p>
        @endforelse
    </div>

    <div style="text-align:center;margin:40px 0;">{{ $courses->links() }}</div>
</section>
</body>
</html>
