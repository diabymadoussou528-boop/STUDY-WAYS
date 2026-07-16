<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#8B2032">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <title>@yield('title', 'Dashboard') — StudyWays Étudiant</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-premium.css') }}">
    <link rel="stylesheet" href="{{ asset('css/course-search.css') }}">
    <style>
        /* Prevent page-content from stretching below actual content */
        body.student-layout .page-content { flex: 0 0 auto; }
        body.student-layout .dash-grid--2 { align-items: start; }
    </style>
    @yield('styles')
</head>
<body class="admin-premium student-layout">

<div class="admin-mesh" aria-hidden="true"></div>
<div class="admin-orb admin-orb--1" aria-hidden="true"></div>
<div class="admin-orb admin-orb--2" aria-hidden="true"></div>

<button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">
    <i class="fas fa-bars"></i>
</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="admin-wrapper" id="adminWrapper">

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <x-sw-brand :href="route('student.dashboard')" variant="default" size="md" class="sidebar-brand-link" />
            <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" aria-label="Réduire le menu">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('student.dashboard') }}" class="sidebar-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="fas fa-grid-2"></i></span>
                <span class="sidebar-link-text">Tableau de bord</span>
            </a>
            <a href="{{ route('student.courses') }}" class="sidebar-link {{ request()->routeIs('student.courses') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="fas fa-book-open"></i></span>
                <span class="sidebar-link-text">Mes cours</span>
            </a>
            <a href="{{ route('student.favorites') }}" class="sidebar-link {{ request()->routeIs('student.favorites') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="fas fa-heart"></i></span>
                <span class="sidebar-link-text">Favoris</span>
            </a>
            <a href="{{ route('student.certificates.index') }}" class="sidebar-link {{ request()->routeIs('student.certificates.*') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="fas fa-certificate"></i></span>
                <span class="sidebar-link-text">Certificats</span>
            </a>
            <a href="{{ route('student.quizzes.index') }}" class="sidebar-link {{ request()->routeIs('student.quizzes.*') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="fas fa-clipboard-check"></i></span>
                <span class="sidebar-link-text">Quiz</span>
            </a>
            <a href="{{ route('student.progress') }}" class="sidebar-link {{ request()->routeIs('student.progress') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="fas fa-chart-line"></i></span>
                <span class="sidebar-link-text">Progression</span>
            </a>
            <a href="{{ route('student.ai-tutor') }}" class="sidebar-link {{ request()->routeIs('student.ai-tutor') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="fas fa-robot"></i></span>
                <span class="sidebar-link-text">Tuteur IA</span>
            </a>
            <a href="{{ route('student.messages') }}" class="sidebar-link {{ request()->routeIs('student.messages') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="fas fa-envelope"></i></span>
                <span class="sidebar-link-text">Messages</span>
                @if(($unreadMessages ?? 0) > 0)
                    <span class="sidebar-badge">{{ $unreadMessages }}</span>
                @endif
            </a>
            <a href="{{ route('student.appointments') }}" class="sidebar-link {{ request()->routeIs('student.appointments') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="fas fa-calendar-check"></i></span>
                <span class="sidebar-link-text">Rendez-vous</span>
            </a>
            <a href="{{ route('profile.edit') }}" class="sidebar-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="fas fa-user"></i></span>
                <span class="sidebar-link-text">Mon profil</span>
            </a>
        </nav>

        @unless($isPremium ?? auth()->user()->is_premium)
            <div class="sidebar-promo">
                <div class="sidebar-promo-icon"><i class="fas fa-crown"></i></div>
                <div class="sidebar-promo-title">Passez en Premium</div>
                <div class="sidebar-promo-text">Accédez au tuteur IA illimité, aux certificats et au contenu exclusif.</div>
                <a href="{{ route('student.premium') }}" class="sidebar-promo-btn">
                    <i class="fas fa-crown"></i> Découvrir
                </a>
            </div>
        @endunless

        <div class="sidebar-footer">
            <button type="button" class="sidebar-expand-btn" id="sidebarExpandBtn" aria-label="Développer le menu" title="Développer le menu">
                <i class="fas fa-angles-right"></i>
            </button>
            <div class="sidebar-footer-row">
                <div class="sidebar-user">
                    <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="sidebar-user-avatar">
                    <div class="sidebar-user-info">
                        <span class="sidebar-user-name">{{ auth()->user()->name }}</span>
                        <span class="sidebar-user-role">{{ ($isPremium ?? auth()->user()->is_premium) ? 'Étudiant Premium' : 'Étudiant' }}</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('home') }}" class="sidebar-logout" style="text-decoration:none;margin-bottom:0;">
                <i class="fas fa-globe"></i>
                <span>Accueil du site</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sidebar-logout">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                    <span>Déconnexion</span>
                </button>
            </form>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-search">
                <i class="fas fa-search topbar-search-icon"></i>
                <input type="text" class="topbar-search-input" id="globalSearch" placeholder="Rechercher un cours..." autocomplete="off">
                <span class="topbar-search-kbd">⌘K</span>
            </div>
            <div class="topbar-actions">
                <x-notification-bell :notifications-route="route('notifications.index')" />

                <div class="topbar-notif-wrap">
                    <button class="topbar-icon-btn" id="messagesToggle" title="Messages" type="button">
                        <i class="fas fa-envelope"></i>
                        @if(($unreadMessages ?? 0) > 0)
                            <span class="topbar-icon-badge">{{ $unreadMessages }}</span>
                        @endif
                    </button>
                    <div class="messages-panel" id="messagesPanel">
                        <div class="notif-panel-header">Messages</div>
                        <a href="{{ route('student.messages') }}" class="notif-item">
                            <span class="notif-item-icon"><i class="fas fa-comment"></i></span>
                            <div>
                                <strong>Voir mes messages</strong>
                                <p>Échanges avec vos professeurs.</p>
                            </div>
                        </a>
                    </div>
                </div>
                <a href="{{ route('home') }}" class="topbar-icon-btn" title="Voir le site">
                    <i class="fas fa-external-link"></i>
                </a>
                <div class="topbar-profile-wrap" id="profileWrap">
                    <button type="button" class="topbar-profile-btn" id="profileToggle">
                        <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="topbar-avatar">
                        <span class="topbar-profile-info">
                            <span class="topbar-profile-name">{{ auth()->user()->name }}</span>
                            <span class="topbar-profile-role">{{ ($isPremium ?? auth()->user()->is_premium) ? 'Étudiant Premium' : 'Étudiant' }}</span>
                        </span>
                        <i class="fas fa-chevron-down topbar-profile-chevron"></i>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown">
                        <a href="{{ route('profile.edit') }}"><i class="fas fa-user"></i> Mon profil</a>
                        <a href="{{ route('student.dashboard') }}"><i class="fas fa-grid-2"></i> Dashboard</a>
                        <a href="{{ route('student.courses') }}"><i class="fas fa-book-open"></i> Mes cours</a>
                        @unless($isPremium ?? auth()->user()->is_premium)
                            <a href="{{ route('student.premium') }}"><i class="fas fa-crown"></i> Passer Premium</a>
                        @endunless
                        <a href="{{ route('home') }}"><i class="fas fa-globe"></i> Voir le site</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"><i class="fas fa-arrow-right-from-bracket"></i> Déconnexion</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        @if(session('success'))
            <div class="flash-toast flash-toast--success" id="flashToast">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
                <button class="flash-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            </div>
        @endif
        @if(session('error'))
            <div class="flash-toast flash-toast--error" id="flashToast">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ session('error') }}</span>
                <button class="flash-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            </div>
        @endif

        <div class="page-content">
            @yield('content')
        </div>
    </main>

</div>

<script src="{{ asset('js/admin.js') }}" defer></script>
<script src="{{ asset('js/global-search.js') }}" defer></script>
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}
</script>
@yield('scripts')
</body>
</html>
