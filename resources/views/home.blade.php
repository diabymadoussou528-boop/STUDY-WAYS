<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>StudyWays — Plateforme E-learning d'élite</title>
    <meta name="description" content="Propulsez votre avenir avec la plateforme e-learning d'élite. Apprenez avec les meilleurs experts, sur une plateforme d'exception.">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/course-search.css') }}">
</head>
<body>

    <!-- ─── PAGE LOADER ─── -->
    <div id="pageLoader" class="page-loader">
        <div class="loader-ring"></div>
        <p>StudyWays...</p>
    </div>

    <!-- ─── NAVBAR ─── -->
    <header class="navbar" id="navbar">
        <x-sw-brand :href="route('home')" variant="default" size="lg" />
        <div class="nav-tools">
        <div class="nav-search-wrap">
          <label class="visually-hidden" for="siteSearch" data-i18n-key="search.label">Rechercher sur le site</label>
          <input
            type="search"
            id="siteSearch"
            class="nav-search-input"
            placeholder="Rechercher cours, sections..."
            autocomplete="off"
            data-i18n-key="search.placeholder"
            data-i18n-attr="placeholder"
          />
          <button type="button" class="nav-search-submit" id="siteSearchBtn" aria-label="Lancer la recherche">
            <i class="fas fa-search"></i>
          </button>
          <div id="searchResults" class="search-dropdown" hidden></div>
        </div>
        <nav id="mainNav">
            <a href="#hero">Accueil</a>
            <a href="#catalogue">Cours</a>
            @can('view-homepage-upgrade')
                <a href="#tarifs">Tarifs</a>
            @endcan
            <a href="#temoignages">Témoignages</a>
            @auth
                <a href="{{ auth()->user()->role === 'admin' ? route('admin.dashboard') : (auth()->user()->role === 'professor' ? route('professor.dashboard') : route('student.dashboard')) }}" class="btn btn-outline-nav" style="margin-left:12px;">
                    Mon Espace
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-nav" style="margin-left:12px;">Connexion</a>
                <a href="{{ route('register') }}" class="btn btn-primary-nav">Inscription</a>
            @endauth
        </nav>
      </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <button id="mobileMenuBtn" class="mobile-menu-btn" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </header>

    <main>
        <!-- ─── HERO ─── -->
        <section class="hero" id="hero">
            <div class="hero-bg-shapes" aria-hidden="true">
                <div class="hero-shape hero-shape--1"></div>
                <div class="hero-shape hero-shape--2"></div>
                <div class="hero-shape hero-shape--3"></div>
            </div>
            <div class="hero-inner">

                <!-- Text -->
                <div class="hero-text">
                    <div class="hero-badge animate-up">
                        <i class="fas fa-graduation-cap"></i>
                        Plateforme E-learning d'élite
                    </div>
                    <h1 class="hero-title animate-up delay-1">
                        Propulsez votre avenir avec l'E-learning d'élite.
                    </h1>
                    <p class="hero-desc animate-up delay-2">
                        Apprenez avec les meilleurs experts, sur une plateforme d'exception.
                    </p>
                    <div class="hero-actions animate-up delay-3">
                        <a href="{{ route('register') }}" class="btn btn-cta">Lancez-vous</a>
                        <a href="#catalogue" class="btn btn-outline">Apprendre plus</a>
                    </div>
                    <div class="hero-kpis animate-up delay-3">
                        <div class="kpi">
                            <strong data-counter="1200">0</strong>
                            <span>Étudiants actifs</span>
                        </div>
                        <div class="kpi">
                            <strong data-counter="95">0</strong>
                            <span>Professeurs vérifiés</span>
                        </div>
                        <div class="kpi">
                            <strong data-counter="{{ $courseCount }}">0</strong>
                            <span>Cours disponibles</span>
                        </div>
                    </div>
                </div>

                <!-- Image -->
                <div class="hero-image animate-up delay-2">
                    <div class="hero-deco-top"></div>
                    <div class="hero-deco-bottom"></div>
                    <div class="hero-img-wrap">
                        <img src="{{ asset('images/hero.jpg') }}" alt="Étudiants StudyWays" class="hero-img" fetchpriority="high">
                    </div>
                </div>

            </div>
            <div class="hero-scroll-indicator" aria-hidden="true">
                <div class="scroll-mouse"><div class="scroll-wheel"></div></div>
                <span>Défiler</span>
            </div>
        </section>

        <!-- ─── ICON FEATURES ─── -->
        <section class="icon-features reveal" id="features">
            <div style="max-width:1200px;margin:0 auto;text-align:center;margin-bottom:48px;">
                <h2 class="section-title-center">Pourquoi choisir StudyWays ?</h2>
                <div class="courses-heading-line"></div>
                <p class="section-lead">Une expérience d'apprentissage pensée pour votre réussite.</p>
            </div>
            <div class="icon-features-grid reveal-stagger">
                <div class="icon-card icon-card--active">
                    <div class="icon-card__shine"></div>
                    <div class="icon-circle"><i class="fas fa-chalkboard-teacher" style="color:var(--primary)"></i></div>
                    <h3>Experts certifiés</h3>
                    <p>Apprenez auprès de professionnels avec des années d'expérience terrain.</p>
                </div>
                <div class="icon-card">
                    <div class="icon-card__shine"></div>
                    <div class="icon-circle"><i class="fas fa-play-circle" style="color:var(--primary)"></i></div>
                    <h3>Vidéos HD interactives</h3>
                    <p>Des contenus structurés, accessibles 24h/24, depuis n'importe quel appareil.</p>
                </div>
                <div class="icon-card">
                    <div class="icon-card__shine"></div>
                    <div class="icon-circle"><i class="fas fa-certificate" style="color:var(--primary)"></i></div>
                    <h3>Certifications reconnues</h3>
                    <p>Obtenez des certificats valorisés par les entreprises partenaires.</p>
                </div>
                <div class="icon-card">
                    <div class="icon-card__shine"></div>
                    <div class="icon-circle"><i class="fas fa-headset" style="color:var(--primary)"></i></div>
                    <h3>Support 24/7</h3>
                    <p>Une équipe dédiée pour répondre à toutes vos questions en temps réel.</p>
                </div>
            </div>
        </section>

        <!-- ─── ABOUT SPLIT ─── -->
        <section class="reveal" id="about" style="background:var(--bg);">
            <div class="split-about">
                <div class="split-about__media">
                    <span class="split-about__frame" aria-hidden="true"></span>
                    <div class="split-about__img">
                        <img src="{{ asset('images/slide1.jpeg') }}" alt="Apprentissage en ligne" loading="lazy">
                    </div>
                    <div class="split-about__badge" aria-hidden="true">
                        <span class="split-badge-icon"><i class="fas fa-users"></i></span>
                        <span class="split-badge-text">
                            <strong data-counter="1200">0</strong>
                            <small>Étudiants actifs</small>
                        </span>
                    </div>
                </div>
                <div class="split-about__text">
                    <div class="split-kicker">
                        <span class="split-kicker-line"></span>
                        Notre Mission
                    </div>
                    <h2>Une plateforme construite pour transformer des vies</h2>
                    <p class="split-lead">
                        Nous croyons que l'éducation de qualité doit être accessible à tous.
                        StudyWays réunit les meilleurs formateurs africains et internationaux
                        pour vous offrir une expérience d'apprentissage unique.
                    </p>
                    <ul class="split-features reveal-stagger">
                        <li class="split-feature">
                            <span class="check-icon">✓</span>
                            <span>Cours disponibles en français</span>
                        </li>
                        <li class="split-feature">
                            <span class="check-icon">✓</span>
                            <span>Paiement mobile money accepté</span>
                        </li>
                        <li class="split-feature">
                            <span class="check-icon">✓</span>
                            <span>Accès hors ligne sur mobile</span>
                        </li>
                    </ul>
                    <a href="{{ route('register') }}" class="btn btn-cta" style="width:fit-content;">Rejoindre StudyWays</a>
                </div>
            </div>
        </section>

        <!-- ─── BENTO DARK BAND ─── -->
        <section class="bento-dark reveal">
            <div class="bento-dark__text">
                <div class="split-kicker" style="color:rgba(255,255,255,0.6);">
                    <span class="split-kicker-line" style="background:rgba(255,255,255,0.4);"></span>
                    Intelligence Artificielle
                </div>
                <h2>Une IA qui adapte votre parcours à vos objectifs</h2>
                <p class="bento-lead">
                    Notre algorithme analyse votre progression en temps réel et recommande
                    les contenus les plus pertinents pour vous faire avancer plus vite.
                </p>
            </div>
            <div class="bento-visual reveal-stagger">
                <div class="bento-card bento-card--a">
                    <span class="bento-card__icon"><i class="fas fa-brain"></i></span>
                    <strong>Recommandations</strong>
                    <small>Des contenus ciblés selon vos objectifs.</small>
                </div>
                <div class="bento-card bento-card--b">
                    <div class="bento-ring">
                        <span class="bento-ring__core"><i class="fas fa-graduation-cap"></i></span>
                    </div>
                    <strong>Apprentissage adaptatif</strong>
                </div>
                <div class="bento-card bento-card--c">
                    <span class="bento-card__icon"><i class="fas fa-chart-line"></i></span>
                    <div class="bento-card__body">
                        <strong>Progression en temps réel</strong>
                        <div class="bento-bars" aria-hidden="true">
                            <span style="--w:88%"></span>
                            <span style="--w:64%"></span>
                            <span style="--w:95%"></span>
                        </div>
                    </div>
                </div>
                 <a href="{{ route('register') }}" class="btn btn-cta bento-cta">Commencer gratuitement</a>
                <div class="bento-badge" aria-hidden="true">
                    <span class="bento-badge-icon"><i class="fas fa-robot"></i></span>
                    Propulsé par l'IA
                </div>
            </div>
        </section>

        <!-- ─── COURSES CATALOG ─── -->
        <section class="courses-catalog reveal" id="catalogue">
            <h2 class="courses-heading section-title-center">Nos cours les plus populaires</h2>
            <div class="courses-heading-line"></div>
            <p class="section-lead">Des formations pratiques et reconnues, animées par des experts du terrain.</p>
            <div class="courses-grid reveal-stagger">
                @forelse($featuredCourses as $course)
                <article class="course-card">
                    <div class="course-thumb" style="background-image:url('{{ $course->thumbnailUrl() }}');background-size:cover;background-position:center;">
                        @if($course->is_premium_only)
                            <span class="course-badge">Premium</span>
                        @elseif($course->enrollments_count > 10)
                            <span class="course-badge">Populaire</span>
                        @endif
                        <div class="course-thumb-inner">
                            <span class="thumb-tag">{{ $course->category?->name ?? 'Cours' }}</span>
                            <small>{{ $course->user?->name }}</small>
                        </div>
                    </div>
                    <div class="course-body">
                        <h3>{{ $course->title }}</h3>
                        <p class="course-stars">★★★★★ <span>({{ number_format($course->reviews_avg_rating ?? 4.5, 1) }})</span></p>
                        <p class="course-desc">{{ Str::limit($course->short_description ?? $course->description, 120) }}</p>
                        <a href="{{ route('courses.show', $course) }}" class="btn btn-details">Détails du cours</a>
                    </div>
                </article>
                @empty
                <p style="text-align:center;grid-column:1/-1;color:#666;">Aucun cours publié pour le moment.</p>
                @endforelse
            </div>
            <div style="text-align:center;margin-top:40px;">
                <a href="{{ route('catalog.index') }}" class="btn btn-outline" style="padding:12px 36px;">Voir tout le catalogue</a>
            </div>
        </section>

        <!-- ─── PRICING ─── -->
        @can('view-homepage-upgrade')
        <section id="tarifs" class="pricing-wrap reveal">
            <h2 class="section-title-center">Choisissez votre formule StudyWays</h2>
            <div class="courses-heading-line"></div>
            <p class="section-lead">Des parcours adaptés à votre rythme et à vos objectifs professionnels.</p>
            <div class="pricing-grid reveal-stagger">
                <article class="price-card">
                    <h3>Apprentissage unitaire</h3>
                    <p class="price-sub">Idéal pour une compétence précise.</p>
                    <p class="price-amount">10 000 FCFA <span>/ Cours</span></p>
                    <p class="price-note">Paiement unique à vie.</p>
                    <a href="{{ route('register') }}" class="btn btn-blue-outline btn-block">Acheter un cours</a>
                    <ul class="price-list">
                        <li>Accès illimité au cours choisi</li>
                        <li>Support de l'instructeur</li>
                        <li>Certificat d'achèvement</li>
                    </ul>
                </article>

                <article class="price-card price-card--popular">
                    <div class="price-ribbon">Le plus populaire</div>
                    <h3>Premium Mensuel</h3>
                    <p class="price-sub">L'accès total pour les apprenants sérieux.</p>
                    <p class="price-amount">15 000 FCFA <span>/ Mois</span></p>
                    <p class="price-note">Annulez à tout moment.</p>
                    <a href="{{ route('register') }}" class="btn btn-blue btn-block">Commencer l'essai gratuit</a>
                    <ul class="price-list">
                        <li>Accès illimité à tous les cours</li>
                        <li>Projets pratiques guidés</li>
                        <li>Support prioritaire 24/7</li>
                        <li>Certifications incluses</li>
                    </ul>
                </article>

                <article class="price-card">
                    <h3>Premium Annuel</h3>
                    <p class="price-sub">Économisez sur le long terme.</p>
                    <p class="price-amount-strike">180 000 FCFA</p>
                    <p class="price-amount">120 000 FCFA <span>/ An</span></p>
                    <p class="price-note">Garantie satisfait ou remboursé 14 jours.</p>
                    <a href="{{ route('register') }}" class="btn btn-blue-outline btn-block">Économiser 33%</a>
                    <ul class="price-list">
                        <li>Tout le plan mensuel</li>
                        <li>Accès anticipé aux nouveautés</li>
                        <li>2 heures de coaching privé / an</li>
                    </ul>
                </article>
            </div>
        </section>
        @endcan

        <!-- ─── TESTIMONIALS ─── -->
        <section id="temoignages" class="testimonials-section reveal">
            <div class="testi-bg" aria-hidden="true">
                <span class="testi-bg-orb testi-bg-orb--1"></span>
                <span class="testi-bg-orb testi-bg-orb--2"></span>
            </div>

            <div class="testi-container">
                <header class="testi-header">
                    <div class="testi-title-block">
                        <span class="testi-title-icon" aria-hidden="true"><i class="fas fa-quote-left"></i></span>
                        <h2 class="testi-heading">
                            <span class="testi-heading-highlight">Ils nous</span> font confiance
                        </h2>
                    </div>
                    <p class="testi-lead">Des parcours réels, des résultats concrets — découvrez ce que nos apprenants en pensent.</p>
                </header>

                <div class="testi-carousel" id="testiCarousel">
                    <div class="testi-stage" id="testiStage">
                        @php
                            $roleLabels = [
                                'student' => 'Étudiant(e)',
                                'professor' => 'Professeur(e)',
                                'admin' => 'Administrateur',
                            ];
                        @endphp

                        @if(isset($testimonials) && $testimonials->count() > 0)
                            @foreach($testimonials as $testi)
                                @php
                                    $role = $testi->user
                                        ? ($roleLabels[$testi->user->role] ?? ucfirst($testi->user->role))
                                        : 'Membre StudyWays';
                                @endphp
                                @include('partials.home-testimonial-card', [
                                    'name' => $testi->user?->name ?? 'Anonyme',
                                    'role' => $role,
                                    'message' => $testi->message,
                                    'rating' => $testi->rating ?? 5,
                                    'avatarUrl' => $testi->user?->avatarUrl(),
                                    'index' => $loop->index,
                                ])
                            @endforeach
                        @else
                            @foreach([
                                ['name' => 'Thomas L.', 'role' => 'Développeur Full Stack', 'message' => 'StudyWays m\'a permis d\'apprendre à mon rythme avec des cours clairs et des formateurs à l\'écoute. Une plateforme vraiment professionnelle.'],
                                ['name' => 'Alexandre M.', 'role' => 'UX/UI Designer', 'message' => 'Publier et suivre des parcours est fluide. L\'interface est intuitive, élégante, et les retours de mes apprenants sont excellents.'],
                                ['name' => 'Camille D.', 'role' => 'Étudiante en marketing', 'message' => 'J\'ai adoré la qualité des contenus et la progression guidée. Je recommande StudyWays sans hésiter.'],
                            ] as $i => $demo)
                                @include('partials.home-testimonial-card', [
                                    'name' => $demo['name'],
                                    'role' => $demo['role'],
                                    'message' => $demo['message'],
                                    'rating' => 5,
                                    'avatarUrl' => null,
                                    'index' => $i,
                                ])
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="testi-nav" id="testiNav">
                    <button type="button" class="testi-nav-btn" id="testiPrev" aria-label="Témoignage précédent">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button type="button" class="testi-nav-btn" id="testiNext" aria-label="Témoignage suivant">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>

                <div class="testi-dots" id="testiDots" role="tablist" aria-label="Pagination des témoignages"></div>

                @auth
                    <div class="testi-cta reveal">
                        <a href="{{ route('testimonials.create') }}" class="btn btn-cta testi-cta-btn">
                            <i class="fas fa-pen"></i> Rédiger un avis
                        </a>
                    </div>
                @else
                    <div class="testi-cta reveal">
                        <a href="{{ route('login') }}" class="btn btn-outline testi-cta-btn">
                            <i class="fas fa-sign-in-alt"></i> Connectez-vous pour témoigner
                        </a>
                    </div>
                @endauth
            </div>
        </section>

        <!-- ─── CTA STRIP ─── -->
        <section class="contact-strip reveal">
            <h2>Prêt à lancer votre carrière ?</h2>
            <p>Rejoignez des milliers d'apprenants et atteignez vos objectifs avec StudyWays.</p>
            <div class="hero-actions">
                <a href="{{ route('register') }}" class="btn btn-cta">Créer un compte gratuit</a>
                <a href="{{ route('login') }}" class="btn btn-outline">Connexion</a>
            </div>
        </section>
    </main>

    <!-- ─── FOOTER ─── -->
    <footer class="site-footer reveal">
        <div class="footer-grid">
            <div>
                <x-sw-brand :href="route('home')" variant="footer" size="md" style="margin-bottom:16px;" />
                <p class="footer-about">Nous aidons étudiants et professionnels à monter en compétences grâce à des parcours clairs, interactifs et reconnus.</p>
                <div class="footer-social">
                    <a href="#" class="social-round" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-round" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-round" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-round" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div>
                <h4>Plateforme</h4>
                <a href="#catalogue">Tous les cours</a>
                @can('view-homepage-upgrade')
                    <a href="#tarifs">Abonnements</a>
                @endcan
                <a href="#temoignages">Témoignages</a>
                <a href="{{ route('register') }}?role=professor">Devenir Professeur</a>
            </div>
            <div>
                <h4>Informations</h4>
                <a href="#">À propos de nous</a>
                <a href="#">Conditions d'utilisation</a>
                <a href="#">Politique de confidentialité</a>
                <a href="#">Centre d'aide</a>
            </div>
            <div>
                <h4>Contact</h4>
                <p class="footer-contact-row"><i class="fas fa-phone-alt" style="color:var(--primary)"></i> (+223) 78 33 33 33</p>
                <p class="footer-contact-row"><i class="fas fa-envelope" style="color:var(--primary)"></i> info@studyways.com</p>
                <p class="footer-contact-row"><i class="fas fa-map-marker-alt" style="color:var(--primary)"></i> Bamako, Mali</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p class="footer-copy">© {{ date('Y') }} StudyWays. Tous droits réservés.</p>
        </div>
    </footer>

    <button type="button" id="backToTop" class="fab-top" aria-label="Retour en haut">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- ─── SCRIPTS ─── -->
    <script src="{{ asset('js/home.js') }}" defer></script>
    <script src="{{ asset('js/course-search.js') }}" defer></script>
</body>
</html>
