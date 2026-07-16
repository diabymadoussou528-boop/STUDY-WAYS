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
    <link rel="stylesheet" href="{{ asset('css/brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/course-detail.css') }}">
    <link rel="stylesheet" href="{{ asset('css/course-card.css') }}">
    <link rel="stylesheet" href="{{ asset('css/course-experience.css') }}">
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

@php
    $videoUrl = $course->videoUrl();
    $thumbUrl = $course->thumbnailUrl();
    $isYoutube = $videoUrl && (str_contains($videoUrl, 'youtube.com') || str_contains($videoUrl, 'youtu.be') || str_contains($videoUrl, 'vimeo.com'));
@endphp

<main class="sw-course-detail">
    <section class="sw-section-shell">
        <div class="sw-course-detail__hero">
            <div style="display:grid; gap:24px;">
                <x-course-header class="sw-surface" :course="$course" :hero-stats="$heroStats" :tags="$tags" />
                <x-course-player
                    :title="$course->title"
                    :thumbnail="$thumbUrl"
                    :video-url="$videoUrl"
                    :is-embed="$isYoutube"
                    player-id="cswPlayer"
                />
            </div>

            <aside class="sw-course-aside">
                <div class="sw-course-price-card sw-surface">
                    <img src="{{ $thumbUrl }}" alt="{{ $course->title }}" class="sw-course-price-card__thumb">
                    <div class="sw-course-price-card__price">
                        @if($course->isFree()) Gratuit @else {{ number_format($course->price, 0, ',', ' ') }} XOF @endif
                    </div>

                    @if($course->is_premium_only)
                        <span class="course-badge-premium"><i class="fas fa-crown"></i> Premium</span>
                    @endif

                    @auth
                        @if(auth()->user()->isStudent())
                            @if($isEnrolled)
                                <a href="{{ route('courses.learn', [$course, $continueLesson?->id]) }}" class="btn btn-primary course-cta">
                                    <i class="fas fa-play"></i> Continuer l'apprentissage
                                </a>
                                @if($progressPercent > 0)
                                    <div class="course-progress-mini">
                                        <div class="course-progress-mini__bar" style="width: {{ $progressPercent }}%"></div>
                                    </div>
                                    <span class="course-progress-mini__label">{{ $progressPercent }}% complété</span>
                                @endif
                                <div class="course-lesson-nav">
                                    @if($previousLesson)
                                        <a href="{{ route('courses.learn', [$course, $previousLesson]) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Précédent</a>
                                    @endif
                                    @if($nextLesson)
                                        <a href="{{ route('courses.learn', [$course, $nextLesson]) }}" class="btn btn-outline btn-sm">Suivant <i class="fas fa-arrow-right"></i></a>
                                    @endif
                                </div>
                            @else
                                <a href="{{ $course->isFree() ? route('student.enrollment.confirm', $course) : route('student.checkout.course', $course) }}" class="btn btn-primary course-cta btn-glow">
                                    <i class="fas fa-{{ $course->isFree() ? 'user-plus' : 'lock' }}"></i>
                                    {{ $course->isFree() ? 'S\'inscrire gratuitement' : 'Acheter et s\'inscrire' }}
                                </a>
                            @endif

                            @if($isFavorited)
                                <form method="POST" action="{{ route('courses.unfavorite', $course) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline course-cta-secondary"><i class="fas fa-heart"></i> Retirer des favoris</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('courses.favorite', $course) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline course-cta-secondary"><i class="far fa-heart"></i> Ajouter aux favoris</button>
                                </form>
                            @endif
                        @elseif(auth()->user()->isAdmin() || (int) $course->user_id === (int) auth()->id())
                            <a href="{{ route('courses.learn', $course) }}" class="btn btn-primary course-cta"><i class="fas fa-eye"></i> Prévisualiser</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary course-cta">Se connecter pour s'inscrire</a>
                    @endauth

                    <div class="sw-course-mini-meta">
                        <span><i class="fas fa-users"></i> {{ number_format($heroStats['enrollments']) }} étudiants</span>
                        <span><i class="fas fa-eye"></i> {{ number_format($heroStats['views']) }} vues</span>
                        <span><i class="fas fa-clock"></i> {{ $heroStats['durationLabel'] }}</span>
                        <span><i class="fas fa-calendar"></i> {{ $heroStats['publishedAt'] }}</span>
                        <span><i class="fas fa-signal"></i> {{ collect($specifications)->firstWhere('label', 'Niveau')['value'] ?? 'Tous niveaux' }}</span>
                    </div>

                    <div class="sw-course-share">
                        <button type="button" data-copy-share aria-label="Copier le lien"><i class="fas fa-share-nodes"></i></button>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($course->title) }}" target="_blank" rel="noopener" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>

                <div class="sw-course-panel sw-surface">
                    <div class="sw-course-panel__head">
                        <h2>Ce cours comprend</h2>
                    </div>
                    <div class="sw-course-mini-meta">
                        @foreach($specifications as $spec)
                            <span><i class="fas {{ $spec['icon'] }}"></i> {{ $spec['label'] }} : {{ $spec['value'] }}</span>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>

        <div class="sw-course-content">
            <div class="sw-course-panels">
                <section class="sw-course-main-panel sw-surface">
                    <div class="sw-course-panel__head">
                        <h2>À propos de ce cours</h2>
                        <p>Une présentation claire, premium et orientée conversion.</p>
                    </div>
                    <div class="sw-course-prose">{!! nl2br(e($course->description)) !!}</div>
                </section>

                @if(!empty($course->objectives))
                    <section class="sw-course-main-panel sw-surface">
                        <div class="sw-course-panel__head">
                            <h2>Qu'allez-vous apprendre ?</h2>
                            <p>Les objectifs clés de ce parcours.</p>
                        </div>
                        <div class="sw-course-objectives">
                            @foreach($course->objectives as $objective)
                                <div class="sw-course-objective">
                                    <i class="fas fa-check-circle"></i>
                                    <span>{{ $objective }}</span>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if(!empty($course->requirements))
                    <section class="sw-course-main-panel sw-surface">
                        <div class="sw-course-panel__head">
                            <h2>Prérequis</h2>
                            <p>Ce qu'il faut connaître avant de commencer.</p>
                        </div>
                        <div class="sw-course-objectives">
                            @foreach($course->requirements as $requirement)
                                <div class="sw-course-objective">
                                    <i class="fas fa-circle-dot"></i>
                                    <span>{{ $requirement }}</span>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="sw-course-main-panel sw-surface">
                    <div class="sw-course-panel__head">
                        <h2>Programme du cours</h2>
                        <p><strong>{{ $totalLessons }}</strong> leçons · <strong>{{ $heroStats['durationLabel'] }}</strong> au total</p>
                    </div>
                    <div class="sw-course-program">
                        @forelse($modules as $module)
                            <div class="sw-course-panel__head" style="margin:10px 0 2px;">
                                <h2 style="font-size:1.05rem;">{{ $module['title'] }}</h2>
                                @if(!empty($module['description']))
                                    <p>{{ $module['description'] }}</p>
                                @endif
                            </div>
                            @foreach($module['lessons'] as $lesson)
                                <div class="sw-course-program__item">
                                    <i class="fas {{ $lesson['typeIcon'] }}"></i>
                                    <div>
                                        <strong>{{ $lesson['title'] }}</strong>
                                        @if($lesson['isPreview'])
                                            <small> · Aperçu disponible</small>
                                        @endif
                                    </div>
                                    <small>{{ $lesson['duration'] }}</small>
                                    @if($lesson['isAccessible'])
                                        <a href="{{ route('courses.learn', [$course, $lesson['id']]) }}" class="btn btn-outline btn-sm">Ouvrir</a>
                                    @else
                                        <span title="Inscription requise"><i class="fas fa-lock"></i></span>
                                    @endif
                                </div>
                            @endforeach
                        @empty
                            <p class="sw-empty-state">Le programme sera bientôt disponible.</p>
                        @endforelse
                    </div>
                </section>

                <section class="sw-course-main-panel sw-surface">
                    <div class="sw-course-panel__head">
                        <h2>Statistiques du cours</h2>
                        <p>Indicateurs mis à jour depuis vos données réelles.</p>
                    </div>
                    <div class="sw-course-stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
                        <div class="sw-course-stat-card"><strong>{{ number_format($heroStats['enrollments']) }}</strong><span>Étudiants</span></div>
                        <div class="sw-course-stat-card"><strong>{{ number_format($heroStats['views']) }}</strong><span>Vues</span></div>
                        <div class="sw-course-stat-card"><strong>{{ $heroStats['durationLabel'] }}</strong><span>Durée</span></div>
                        <div class="sw-course-stat-card"><strong>{{ number_format($heroStats['rating'], 1) }}</strong><span>Note moyenne</span></div>
                    </div>
                </section>

                <section class="sw-course-main-panel sw-surface">
                    <div class="sw-course-panel__head">
                        <h2>Avis des étudiants</h2>
                        <p>Retours authentiques de la communauté StudyWays.</p>
                    </div>

                    @auth
                        @if(auth()->user()->isStudent() && $isEnrolled)
                            <form method="POST" action="{{ route('courses.rate', $course) }}" class="course-review-form">
                                @csrf
                                <label>Votre note
                                    <select name="rating" class="form-input" required>
                                        @for($i = 5; $i >= 1; $i--)
                                            <option value="{{ $i }}" @selected(old('rating', $userReview?->rating) == $i)>{{ $i }} étoile{{ $i > 1 ? 's' : '' }}</option>
                                        @endfor
                                    </select>
                                </label>
                                <label>Commentaire
                                    <textarea name="comment" class="form-input" rows="3" placeholder="Partagez votre expérience...">{{ old('comment', $userReview?->comment) }}</textarea>
                                </label>
                                <button type="submit" class="btn btn-primary btn-sm">Publier mon avis</button>
                            </form>
                        @endif
                    @endauth

                    <div class="sw-review-list">
                        @forelse($course->reviews as $review)
                            <article class="sw-review-card">
                                <div class="sw-review-card__head">
                                    <strong>{{ $review->user?->name }}</strong>
                                    <span>{{ $review->created_at?->translatedFormat('j F Y') }}</span>
                                </div>
                                <x-course-rating :rating="$review->rating" :reviews="0" size="sm" :show-count="false" />
                                <p style="margin:10px 0 0; color:#4b5563; line-height:1.7;">{{ $review->comment }}</p>
                            </article>
                        @empty
                            <p class="sw-empty-state">Aucun avis pour le moment. Soyez le premier à commenter.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="sw-course-aside">
                <section class="sw-course-panel sw-surface">
                    <div class="sw-course-panel__head">
                        <h2>Formateur</h2>
                        <p>Présentation de l'instructeur du cours.</p>
                    </div>
                    <x-teacher-card :instructor="$instructorStats" />
                </section>

                @if(!empty($tags))
                    <section class="sw-course-panel sw-surface">
                        <div class="sw-course-panel__head">
                            <h2>Tags</h2>
                            <p>Technologies et thèmes associés.</p>
                        </div>
                        <div class="sw-course-header__tags">
                            @foreach($tags as $tag)
                                <span>{{ $tag }}</span>
                            @endforeach
                        </div>
                    </section>
                @endif
            </aside>
        </div>

        @if($relatedCourses->isNotEmpty())
            <section class="sw-related-section">
                <div class="sw-home-catalog-head">
                    <div class="sw-home-catalog-copy">
                        <h2 style="font-size:2rem;">Cours similaires</h2>
                        <p>Suggestions basées sur la catégorie, les tags et les mots-clés.</p>
                    </div>
                </div>
                <div class="sw-courses-grid">
                    @foreach($relatedCourses as $related)
                        <x-course-card :course="$related" cta-label="Voir les détails" />
                    @endforeach
                </div>
            </section>
        @endif
    </section>
</main>

<script src="{{ asset('js/course-detail.js') }}"></script>
<script>
document.querySelector('[data-copy-share]')?.addEventListener('click', async () => {
    try {
        await navigator.clipboard.writeText(@json($shareUrl));
        alert('Lien copié !');
    } catch (e) {
        prompt('Copiez ce lien :', @json($shareUrl));
    }
});

/* ── Premium CSW Player ─────────────────────────────────────── */
(function () {
    const player  = document.getElementById('cswPlayer');
    if (!player) {
        return;
    }

    const cover = player.querySelector('[data-player-cover]');
    const media = player.querySelector('[data-player-media]');
    const loader = player.querySelector('[data-player-loader]');
    const errBox = player.querySelector('[data-player-error]');
    const playBtn = player.querySelector('[data-player-trigger]');
    const retryBtn = player.querySelector('[data-player-retry]');

    const videoUrl = player.dataset.videoUrl || '';
    const isEmbed  = player.dataset.isEmbed === '1';
    let launching = false;

    function showLoader() {
        loader.hidden = false;
        errBox.hidden = true;
    }

    function showError() {
        launching = false;
        loader.hidden = true;
        errBox.hidden = false;
        media.hidden = true;
    }

    function showMedia() {
        launching = false;
        loader.hidden = true;
        errBox.hidden = true;
        media.hidden = false;
    }

    function hideCover() {
        cover.classList.add('is-fading');
        cover.addEventListener('transitionend', () => {
            cover.style.display = 'none';
        }, { once: true });
        // Fallback if transitionend never fires (display already none, etc.)
        setTimeout(() => {
            if (cover.classList.contains('is-fading')) {
                cover.style.display = 'none';
            }
        }, 500);
    }

    function launchPlayer() {
        if (!videoUrl || launching) {
            return;
        }

        launching = true;
        media.innerHTML = '';
        hideCover();
        showLoader();

        if (isEmbed) {
            const sep = videoUrl.includes('?') ? '&' : '?';
            const src = videoUrl + sep + 'autoplay=1&rel=0';
            const iframe = document.createElement('iframe');
            iframe.className = 'sw-video-player__iframe';
            iframe.src = src;
            iframe.title = 'Lecteur vidéo';
            iframe.allowFullscreen = true;
            iframe.allow = 'autoplay; encrypted-media; fullscreen; picture-in-picture';
            iframe.onload = showMedia;
            media.appendChild(iframe);
            showMedia();
            return;
        }

        const vid = document.createElement('video');
        vid.className = 'sw-video-player__video';
        vid.controls = true;
        vid.autoplay = true;
        vid.playsInline = true;
        vid.preload = 'auto';
        vid.src = videoUrl;

        vid.addEventListener('loadeddata', () => {
            showMedia();
            vid.play().catch(() => {});
        }, { once: true });

        vid.addEventListener('error', showError, { once: true });

        media.hidden = false;
        media.appendChild(vid);
    }

    if (playBtn) {
        playBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            launchPlayer();
        });
    }

    if (cover && videoUrl) {
        cover.style.cursor = 'pointer';
        cover.addEventListener('click', (e) => {
            if (e.target.closest('[data-player-trigger]')) {
                return;
            }
            launchPlayer();
        });
    }

    if (retryBtn) {
        retryBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            launching = false;
            media.innerHTML = '';
            errBox.hidden = true;
            launchPlayer();
        });
    }
})();
</script>
</body>
</html>
