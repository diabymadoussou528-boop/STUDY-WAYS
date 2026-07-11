<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $course->title }} — StudyWays</title>
    <meta name="description" content="{{ $course->meta_description ?? Str::limit($course->short_description ?? $course->description, 160) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/course-detail.css') }}">
</head>
<body class="course-detail-page">

<header class="navbar course-navbar" id="navbar">
    <x-sw-brand :href="route('home')" variant="default" size="lg" />
    <nav class="course-nav-links">
        <a href="{{ route('catalog.index') }}">Catalogue</a>
        @auth
            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">Mon espace</a>
        @else
            <a href="{{ route('login') }}" class="btn btn-outline btn-sm">Connexion</a>
        @endauth
    </nav>
</header>

@if(session('success'))
    <div class="course-flash course-flash--success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="course-flash course-flash--error">{{ session('error') }}</div>
@endif

<section class="course-hero">
    <div class="course-hero__inner">
        <div class="course-hero__content">
            @if(!empty($tags))
                <div class="course-hero__tags">
                    <span class="course-hero__accent"></span>
                    <span>{{ implode(', ', array_slice($tags, 0, 3)) }}</span>
                </div>
            @endif

            <h1 class="course-hero__title">{{ $course->title }}</h1>
            <p class="course-hero__desc">{{ $course->short_description ?? Str::limit($course->description, 220) }}</p>

            <div class="course-hero__stats">
                <div class="course-hero__stat">
                    <i class="fas fa-users"></i>
                    <span>{{ number_format($heroStats['enrollments']) }} étudiants inscrits</span>
                </div>
                <div class="course-hero__stat">
                    <span class="course-stars">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star{{ $i <= round($heroStats['rating']) ? '' : ' course-star--empty' }}"></i>
                        @endfor
                    </span>
                    <span>{{ number_format($heroStats['rating'], 2) }} ({{ $heroStats['reviewsCount'] }} avis)</span>
                </div>
            </div>
        </div>

        <aside class="course-enroll-card">
            <div class="course-enroll-card__media">
                <img src="{{ $course->thumbnailUrl() }}" alt="{{ $course->title }}">
                @if($course->videoUrl())
                    <button type="button" class="course-enroll-card__play" aria-label="Aperçu vidéo">
                        <i class="fas fa-play"></i>
                    </button>
                @endif
            </div>

            <div class="course-enroll-card__body">
                <div class="course-enroll-card__price">
                    @if($course->isFree())
                        Gratuit
                    @else
                        {{ number_format($course->price, 0, ',', ' ') }} XOF
                    @endif
                </div>

                @if($course->is_premium_only)
                    <span class="course-badge-premium"><i class="fas fa-crown"></i> Premium</span>
                @endif

                @auth
                    @if(auth()->user()->isStudent())
                        @if($isEnrolled)
                            <a href="{{ route('courses.learn', $course) }}" class="btn btn-primary course-cta">
                                <i class="fas fa-play"></i> Commencer le cours
                            </a>
                            @if($progressPercent > 0)
                                <div class="course-progress-mini">
                                    <div class="course-progress-mini__bar" style="width: {{ $progressPercent }}%"></div>
                                </div>
                                <span class="course-progress-mini__label">{{ $progressPercent }}% complété</span>
                            @endif
                            <form method="POST" action="{{ route('student.enrollment.cancel', $activeEnrollment) }}" onsubmit="return confirm('Annuler l\'inscription ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline course-cta-secondary">Annuler l'inscription</button>
                            </form>
                        @else
                            <a href="{{ $course->isFree() ? route('student.enrollment.confirm', $course) : route('student.checkout.course', $course) }}" class="btn btn-primary course-cta btn-glow">
                                <i class="fas fa-{{ $course->isFree() ? 'user-plus' : 'lock' }}"></i>
                                {{ $course->isFree() ? 'S\'inscrire gratuitement' : 'Acheter et s\'inscrire' }}
                            </a>
                        @endif
                    @elseif(auth()->user()->isAdmin() || (int) $course->user_id === (int) auth()->id())
                        <a href="{{ route('courses.learn', $course) }}" class="btn btn-primary course-cta">
                            <i class="fas fa-eye"></i> Prévisualiser le contenu
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary course-cta">Se connecter pour s'inscrire</a>
                @endauth

                <div class="course-includes">
                    <h3>Ce cours comprend</h3>
                    <ul>
                        <li><i class="fas fa-book-open"></i> {{ $totalLessons }} leçons</li>
                        <li><i class="fas fa-clock"></i> {{ $heroStats['durationLabel'] }} de contenu</li>
                        <li><i class="fas fa-signal"></i> {{ collect($specifications)->firstWhere('label', 'Niveau')['value'] ?? 'Tous niveaux' }}</li>
                        <li><i class="fas fa-certificate"></i> Certificat de fin</li>
                        <li><i class="fas fa-infinity"></i> Accès illimité</li>
                    </ul>
                </div>
            </div>
        </aside>
    </div>
</section>

<main class="course-main">
    <div class="course-main__grid">
        <div class="course-content">
            <nav class="course-tabs" role="tablist">
                <button type="button" class="course-tab is-active" data-tab="overview">Aperçu</button>
                <button type="button" class="course-tab" data-tab="topics">Programme</button>
                <button type="button" class="course-tab" data-tab="instructor">Instructeur</button>
                <button type="button" class="course-tab" data-tab="feedback">Avis</button>
            </nav>

            <div class="course-tab-panel is-active" id="tab-overview">
                <div class="course-section">
                    <h2>À propos de ce cours</h2>
                    <div class="course-section__line"></div>
                    <div class="course-prose">{!! nl2br(e($course->description)) !!}</div>
                </div>

                @if(!empty($course->objectives))
                    <div class="course-section">
                        <h2>Qu'allez-vous apprendre ?</h2>
                        <div class="course-section__line"></div>
                        <div class="course-objectives">
                            @foreach($course->objectives as $objective)
                                <div class="course-objective">
                                    <i class="fas fa-check"></i>
                                    <span>{{ $objective }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(!empty($course->requirements))
                    <div class="course-section">
                        <h2>Prérequis</h2>
                        <div class="course-section__line"></div>
                        <ul class="course-list">
                            @foreach($course->requirements as $requirement)
                                <li>{{ $requirement }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="course-tab-panel" id="tab-topics">
                <div class="course-topics-header">
                    <h2>Programme du cours</h2>
                    <div class="course-topics-meta">
                        <span><strong>{{ $totalLessons }}</strong> leçons</span>
                        <span><strong>{{ $heroStats['durationLabel'] }}</strong> au total</span>
                    </div>
                </div>
                <div class="course-section__line"></div>

                @forelse($modules as $index => $module)
                    <div class="course-accordion {{ $index === 0 ? 'is-open' : '' }}" data-accordion>
                        <button type="button" class="course-accordion__trigger">
                            <span>{{ $module['title'] }}</span>
                            <span class="course-accordion__meta">{{ $module['lessonCount'] }} leçons</span>
                            <i class="fas fa-plus course-accordion__icon"></i>
                        </button>
                        <div class="course-accordion__body">
                            @if($module['description'])
                                <p class="course-accordion__desc">{{ $module['description'] }}</p>
                            @endif
                            <ul class="course-lesson-list">
                                @foreach($module['lessons'] as $lesson)
                                    <li class="course-lesson-item {{ $lesson['isCompleted'] ? 'is-done' : '' }}">
                                        <i class="fas {{ $lesson['typeIcon'] }} course-lesson-item__icon"></i>
                                        <span class="course-lesson-item__title">{{ $lesson['title'] }}</span>
                                        @if($lesson['isPreview'])
                                            <span class="course-lesson-item__badge">Aperçu</span>
                                        @endif
                                        @if($lesson['isCompleted'])
                                            <i class="fas fa-check-circle course-lesson-item__done"></i>
                                        @endif
                                        <span class="course-lesson-item__duration">{{ $lesson['duration'] }}</span>
                                        @if($lesson['isAccessible'])
                                            <a href="{{ route('courses.learn', [$course, $lesson['id']]) }}" class="course-lesson-item__link">Ouvrir</a>
                                        @else
                                            <span class="course-lesson-item__locked" title="Inscription requise"><i class="fas fa-lock"></i></span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @empty
                    <p class="course-empty">Le programme sera bientôt disponible.</p>
                @endforelse
            </div>

            <div class="course-tab-panel" id="tab-instructor">
                <div class="course-section">
                    <h2>À propos de l'instructeur</h2>
                    <div class="course-section__line"></div>
                    <div class="course-instructor-card">
                        <img src="{{ $instructorStats['avatar'] }}" alt="{{ $instructorStats['name'] }}" class="course-instructor-card__avatar">
                        <div>
                            <h3>{{ $instructorStats['name'] }}</h3>
                            @if(!empty($instructorStats['specialization']))
                                <p class="course-instructor-card__specialization">{{ $instructorStats['specialization'] }}</p>
                            @endif
                            <div class="course-instructor-card__rating">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star{{ $i <= round($instructorStats['rating']) ? '' : ' course-star--empty' }}"></i>
                                @endfor
                                <span>{{ number_format($instructorStats['rating'], 2) }} ({{ $instructorStats['reviewsCount'] }} avis)</span>
                            </div>
                            <p>{{ $instructorStats['bio'] ?? 'Instructeur passionné par le partage de connaissances et l\'accompagnement des étudiants.' }}</p>
                            <div class="course-instructor-card__stats">
                                <span><i class="fas fa-book"></i> {{ $instructorStats['coursesCount'] }} cours</span>
                                <span><i class="fas fa-users"></i> {{ number_format($instructorStats['studentsCount']) }} étudiants</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="course-tab-panel" id="tab-feedback">
                <div class="course-section">
                    <h2>Avis des étudiants</h2>
                    <div class="course-section__line"></div>
                    @forelse($course->reviews as $review)
                        <article class="course-review">
                            <div class="course-review__head">
                                <strong>{{ $review->user?->name }}</strong>
                                <span>{{ $review->created_at?->translatedFormat('j F Y') }}</span>
                            </div>
                            <div class="course-review__stars">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star{{ $i <= $review->rating ? '' : ' course-star--empty' }}"></i>
                                @endfor
                            </div>
                            <p>{{ $review->comment }}</p>
                        </article>
                    @empty
                        <p class="course-empty">Aucun avis pour le moment.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <aside class="course-sidebar">
            <div class="course-sidebar-card">
                <h3>Ce cours comprend</h3>
                <div class="course-section__line"></div>
                <ul class="course-spec-list">
                    @foreach($specifications as $spec)
                        <li>
                            <i class="fas {{ $spec['icon'] }}"></i>
                            <span class="course-spec-list__label">{{ $spec['label'] }}</span>
                            <span class="course-spec-list__value">{{ $spec['value'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="course-sidebar-card">
                <h4>Partager</h4>
                <div class="course-share">
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" class="course-share__btn course-share__btn--fb" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($course->title) }}" target="_blank" rel="noopener" class="course-share__btn course-share__btn--tw" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" class="course-share__btn course-share__btn--in" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            @if(!empty($tags))
                <div class="course-sidebar-card">
                    <h4>Tags</h4>
                    <div class="course-section__line"></div>
                    <div class="course-tags">
                        @foreach($tags as $tag)
                            <span class="course-tag">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </aside>
    </div>
</main>

<script src="{{ asset('js/course-detail.js') }}"></script>
</body>
</html>
