@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')

<div class="dash-header reveal-up">
    <div class="dash-header-main">
        <span class="dash-kicker"><span class="pulse-dot"></span> Tableau de bord · {{ now()->translatedFormat('l d F') }}</span>
        <h1 class="dash-title">Bonjour, <span class="dash-title-accent">{{ explode(' ', auth()->user()->name)[0] }}</span></h1>
        <p class="dash-subtitle">Vue d'ensemble de votre plateforme e-learning en temps réel.</p>
        <div class="dash-chips">
            <span class="dash-chip"><i class="fas fa-user-graduate"></i> {{ number_format($students) }} étudiants</span>
            <span class="dash-chip"><i class="fas fa-book"></i> {{ number_format($courses) }} cours</span>
            <span class="dash-chip dash-chip--live"><i class="fas fa-signal"></i> Plateforme active</span>
        </div>
    </div>
    <div class="dash-header-aside">
        <button type="button" class="dash-date-picker" id="dashDatePicker">
            <i class="fas fa-calendar-days"></i>
            {{ now()->translatedFormat('d M Y') }}
            <i class="fas fa-chevron-down dash-chevron"></i>
        </button>
        <a href="{{ route('admin.courses.create') }}" class="btn btn-primary btn-glow">
            <i class="fas fa-plus"></i> Nouveau cours
        </a>
    </div>
</div>

@if($pendingTestimonials > 0)
    <div class="pending-strip reveal-up">
        <div class="pending-strip-icon"><i class="fas fa-clock"></i></div>
        <div class="pending-strip-text">
            <strong>{{ $pendingTestimonials }} demande{{ $pendingTestimonials > 1 ? 's' : '' }} d'approbation en attente</strong>
            <span>Témoignages et contenus nécessitent votre attention.</span>
        </div>
        <a href="{{ route('admin.testimonials') }}" class="btn btn-primary btn-sm">Voir</a>
    </div>
@endif

<!-- Hero KPI — 5 cards -->
<div class="kpi-grid kpi-grid--hero reveal-stagger">
    @foreach($heroStats as $stat)
        <article class="kpi-card kpi-card--modern glass-card" data-animate data-tilt>
            <div class="kpi-card-top">
                <div class="kpi-icon"><i class="fas {{ $stat['icon'] }}"></i></div>
                @if($stat['delta'] !== null)
                    <span class="kpi-delta kpi-delta--{{ $stat['deltaDirection'] }}">
                        <i class="fas fa-arrow-{{ $stat['deltaDirection'] === 'up' ? 'up' : 'down' }}"></i>
                        {{ abs($stat['delta']) }}%
                    </span>
                @endif
            </div>
            <div class="kpi-value" @if(is_numeric($stat['value'])) data-counter="{{ $stat['value'] }}" @endif>{{ is_numeric($stat['value']) ? 0 : $stat['value'] }}</div>
            <div class="kpi-label">{{ $stat['label'] }}</div>
            <span class="kpi-status"><i class="fas fa-circle" style="font-size:0.4rem"></i> Actif</span>
            <div class="kpi-sparkline" id="spark-{{ $stat['key'] }}" data-series='@json($stat['sparkline'])'></div>
        </article>
    @endforeach
</div>

<!-- Row 2: Website Views + Activity + Growth Card -->
<div class="dash-grid dash-grid--hero-row reveal-up">
    <section class="widget-card glass-card">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">Vues du site</h3>
                <p class="widget-subtitle">Trafic et engagement de la plateforme</p>
            </div>
            <div class="chart-tabs chart-tabs--modern" data-chart-tabs="websiteViews">
                <span class="chart-tabs-indicator" aria-hidden="true"></span>
                <button type="button" class="chart-tab" data-range="today">Aujourd'hui</button>
                <button type="button" class="chart-tab active" data-range="week">7 jours</button>
                <button type="button" class="chart-tab" data-range="month">30 jours</button>
                <button type="button" class="chart-tab" data-range="year">12 mois</button>
            </div>
        </div>
        <div class="widget-body">
            <div id="chartWebsiteViews" class="chart-canvas"></div>
        </div>
    </section>

    <section class="widget-card glass-card">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">Activité en temps réel</h3>
                <p class="widget-subtitle">Derniers événements</p>
            </div>
        </div>
        <div class="widget-body">
            <div class="activity-feed">
                @forelse(collect($recentActivity)->take(6) as $item)
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-{{ $item['type'] === 'course' ? 'book' : ($item['type'] === 'testimonial' ? 'quote-left' : 'user-plus') }}"></i>
                        </div>
                        <div class="activity-body">
                            <strong>{{ $item['title'] }}</strong>
                            <p>{{ $item['desc'] }}</p>
                            <time>{{ optional($item['time'])->diffForHumans() ?? 'à l\'instant' }}</time>
                        </div>
                    </div>
                @empty
                    <div class="empty-state premium-empty"><p>Aucune activité récente.</p></div>
                @endforelse
    </div>
    </div>
    </section>

    <div class="growth-card">
        <div>
            <div class="growth-card-trophy">🏆</div>
            <h3 class="growth-card-title">Plateforme en croissance</h3>
            <p class="growth-card-text">Votre écosystème e-learning progresse. Continuez à publier du contenu de qualité.</p>
    </div>
        <a href="{{ route('admin.reports') }}" class="growth-card-btn">
            <i class="fas fa-chart-line"></i> Voir les rapports
        </a>
    </div>
</div>

<!-- Row 3: Categories + Student Growth + Performance -->
<div class="dash-grid dash-grid--analytics reveal-up">
    <section class="widget-card glass-card">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">Répartition des cours</h3>
                <p class="widget-subtitle">Par catégorie</p>
            </div>
        </div>
        <div class="widget-body widget-body--center">
            <div id="chartCategories" class="chart-canvas chart-canvas--donut"></div>
        </div>
    </section>

    <section class="widget-card glass-card">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">Croissance étudiants</h3>
                <p class="widget-subtitle">Nouveaux, actifs et récurrents</p>
            </div>
        </div>
        <div class="widget-body">
            <div id="chartStudentGrowth" class="chart-canvas"></div>
        </div>
    </section>

    <section class="widget-card glass-card">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">Performance globale</h3>
                <p class="widget-subtitle">Indicateurs clés</p>
            </div>
        </div>
        <div class="widget-body">
            <div class="performance-grid">
                <div class="performance-ring-item">
                    <div class="performance-ring-canvas" id="ringCompletion" data-value="{{ $platformMetrics['completion'] }}"></div>
                    <div class="performance-ring-label">Complétion</div>
                    <div class="performance-ring-value">{{ $platformMetrics['completion'] }}%</div>
                </div>
                <div class="performance-ring-item">
                    <div class="performance-ring-canvas" id="ringRating" data-value="{{ (int) ($platformMetrics['rating'] * 20) }}"></div>
                    <div class="performance-ring-label">Note moyenne</div>
                    <div class="performance-ring-value">{{ $platformMetrics['rating'] }}/5</div>
                </div>
                <div class="performance-ring-item">
                    <div class="performance-ring-canvas" id="ringEngagement" data-value="{{ $platformMetrics['engagement'] }}"></div>
                    <div class="performance-ring-label">Engagement</div>
                    <div class="performance-ring-value">{{ $platformMetrics['engagement'] }}%</div>
                </div>
                <div class="performance-ring-item">
                    <div class="performance-ring-canvas" id="ringTestimonials" data-value="{{ min(100, $platformMetrics['testimonialGrowth'] * 4) }}"></div>
                    <div class="performance-ring-label">Témoignages</div>
                    <div class="performance-ring-value">+{{ $platformMetrics['testimonialGrowth'] }} ce mois</div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Row 4: Teacher Activity + Learning Progress -->
<div class="dash-grid dash-grid--2 reveal-up">
    <section class="widget-card glass-card">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">Activité professeurs</h3>
                <p class="widget-subtitle">Performance par enseignant</p>
            </div>
        </div>
        <div class="widget-body">
            <div id="chartTeacherPerformance" class="chart-canvas"></div>
        </div>
    </section>

    <section class="widget-card glass-card">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">Progression d'apprentissage</h3>
                <p class="widget-subtitle">Vues, notes et complétion</p>
            </div>
        </div>
        <div class="widget-body">
            <div id="chartCourseEngagement" class="chart-canvas"></div>
        </div>
    </section>
</div>

<!-- Row 5: Popular Courses + Testimonials + AI -->
<div class="dash-grid dash-grid--widgets reveal-up">
    <section class="widget-card glass-card">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">Cours populaires</h3>
                <p class="widget-subtitle">Les plus consultés</p>
            </div>
        </div>
        <div class="widget-body">
            @forelse($topCourses as $course)
                <div class="course-list-item">
                    <div class="course-list-thumb"><i class="fas fa-play"></i></div>
                    <div class="course-list-info">
                        <div class="course-list-title">{{ Str::limit($course->title, 36) }}</div>
                        <div class="course-list-meta">
                            <span class="rating-pill">★ {{ number_format((float)($course->reviews_avg_rating ?? 0), 1) }}</span>
                            <span>{{ $course->reviews_count ?? 0 }} avis</span>
    </div>
                    </div>
                    <span class="course-list-views">{{ number_format($course->views) }}</span>
                </div>
        @empty
                <div class="empty-state premium-empty"><p>Aucun cours pour le moment.</p></div>
            @endforelse
                    </div>
</section>

    <section class="widget-card glass-card">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">Derniers témoignages</h3>
                <p class="widget-subtitle">Avis récents des étudiants</p>
    </div>
            <a href="{{ route('admin.testimonials') }}" class="btn btn-outline btn-sm">Tout voir</a>
                    </div>
        <div class="widget-body">
            @forelse($latestTestimonials->take(4) as $testimonial)
                <div class="testimonial-mini">
                    <div class="testimonial-mini-header">
                        @if($testimonial->user)
                            <img src="{{ $testimonial->user->avatarUrl() }}" alt="" class="testimonial-mini-avatar">
                    @endif
                        <div>
                            <strong style="font-size:0.8125rem">{{ $testimonial->user?->name ?? 'Anonyme' }}</strong>
                            <div class="testimonial-mini-stars">{{ str_repeat('★', min(5, $testimonial->rating ?? 5)) }}</div>
                        </div>
                    </div>
                    <p class="testimonial-mini-text">{{ Str::limit($testimonial->message, 90) }}</p>
                </div>
            @empty
                <div class="empty-state premium-empty"><p>Aucun témoignage.</p></div>
        @endforelse
        </div>
</section>

    <section class="widget-card glass-card ai-insights-card">
        <div class="widget-body">
            <div class="ai-insights-icon"><i class="fas fa-brain"></i></div>
            <div class="ai-insights-count" data-counter="{{ $charts['aiRecommendations']['total'] ?? 0 }}">0</div>
            <div class="ai-insights-label">nouvelles recommandations IA</div>
            <div class="ai-insights-tags">
                <span class="ai-tag">Comportement étudiant</span>
                <span class="ai-tag">Parcours personnalisé</span>
                <span class="ai-tag">Contenu adaptatif</span>
            </div>
            <div style="margin-top:20px">
                <div id="chartAiRecommendations" class="chart-canvas chart-canvas--compact"></div>
            </div>
            <div class="ai-confidence" style="margin-top:12px">
                <span class="ai-confidence-label">Confiance IA</span>
                <strong>{{ $charts['aiRecommendations']['confidence'] ?? 87 }}%</strong>
            </div>
        </div>
    </section>
</div>

<!-- Premium Table -->
<div class="dash-grid reveal-up">
    <section class="widget-card glass-card">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">Derniers cours</h3>
                <p class="widget-subtitle">Gestion et actions rapides</p>
            </div>
            <div class="table-tools">
                <div class="table-search">
                    <i class="fas fa-search"></i>
                    <input type="search" placeholder="Rechercher un cours..." data-table-search="coursesTable">
                </div>
                <a href="{{ route('admin.courses.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Ajouter
                </a>
            </div>
    </div>
        <div class="widget-body widget-body--flush">
            <div class="table-scroll">
                <table class="premium-table" id="coursesTable">
        <thead>
            <tr>
                            <th data-sort="text">Cours</th>
                            <th data-sort="text">Professeur</th>
                            <th data-sort="number">Vues</th>
                            <th data-sort="number">Note</th>
                <th>Statut</th>
                            <th></th>
            </tr>
        </thead>
        <tbody>
                    @forelse($latestCourses as $course)
                        <tr>
                            <td data-value="{{ $course->title }}">
                                <div class="table-course">
                                    <span class="table-course-icon"><i class="fas fa-book"></i></span>
                                    <span>{{ Str::limit($course->title, 34) }}</span>
                    </div>
                </td>
                            <td data-value="{{ $course->user?->name ?? '' }}">
                                @if($course->user)
                                    <div class="user-cell">
                                        <img src="{{ $course->user->avatarUrl() }}" alt="" class="user-cell-avatar">
                                        <span class="user-cell-name">{{ $course->user->name }}</span>
                                    </div>
                                @else — @endif
                            </td>
                            <td data-value="{{ $course->views }}">{{ number_format($course->views) }}</td>
                            <td data-value="{{ (float)($course->reviews_avg_rating ?? 0) }}">
                                <span class="rating-pill">★ {{ number_format((float)($course->reviews_avg_rating ?? 0), 1) }}</span>
                </td>
                            <td><span class="badge badge-success">Publié</span></td>
                            <td>
                                <div class="row-actions">
                                    <button type="button" class="row-action-btn" title="Actions"><i class="fas fa-ellipsis-vertical"></i></button>
                                    <div class="row-action-menu">
                                        <form method="POST" action="{{ route('admin.courses.delete', $course->id) }}" onsubmit="return confirm('Supprimer ce cours ?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"><i class="fas fa-trash-alt"></i> Supprimer</button>
                                        </form>
                                    </div>
                                </div>
                </td>
            </tr>
        @empty
                        <tr><td colspan="6"><div class="empty-state premium-empty"><i class="fas fa-book-open"></i><p>Aucun cours disponible.</p></div></td></tr>
        @endforelse
        </tbody>
    </table>
            </div>
        </div>
</section>
</div>

<!-- Quick Actions FAB -->
<div class="fab-actions" id="fabActions">
    <div class="fab-menu">
        <a href="{{ route('admin.courses.create') }}" class="fab-menu-item"><i class="fas fa-plus"></i> Nouveau cours</a>
        <a href="{{ route('admin.testimonials') }}" class="fab-menu-item"><i class="fas fa-quote-left"></i> Témoignages</a>
        <a href="{{ route('home') }}" class="fab-menu-item"><i class="fas fa-globe"></i> Voir le site</a>
    </div>
    <button type="button" class="fab-main" id="fabToggle" aria-label="Actions rapides">
        <i class="fas fa-plus"></i>
    </button>
</div>

@endsection

@section('scripts')
<script>
    window.__ADMIN_CHARTS__ = @json($charts);
    window.__PLATFORM_METRICS__ = @json($platformMetrics);
</script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.1/dist/apexcharts.min.js"></script>
<script src="{{ asset('js/admin-dashboard.js') }}" defer></script>
@endsection
