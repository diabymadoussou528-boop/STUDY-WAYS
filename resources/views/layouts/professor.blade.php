<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — StudyWays Professeur</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-premium.css') }}">
    <link rel="stylesheet" href="{{ asset('css/course-search.css') }}">
    @yield('styles')
</head>
<body class="admin-premium">

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
            <x-sw-brand :href="route('professor.dashboard')" variant="default" size="md" class="sidebar-brand-link" />
            <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" aria-label="Réduire le menu">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('professor.dashboard') }}" class="sidebar-link {{ request()->routeIs('professor.dashboard') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="fas fa-grid-2"></i></span>
                <span class="sidebar-link-text">Tableau de bord</span>
            </a>
            <a href="{{ route('professor.courses.index') }}" class="sidebar-link {{ request()->routeIs('professor.courses.index') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="fas fa-book-open"></i></span>
                <span class="sidebar-link-text">Mes cours</span>
            </a>
            <a href="{{ route('professor.students') }}" class="sidebar-link {{ request()->routeIs('professor.students') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="fas fa-user-graduate"></i></span>
                <span class="sidebar-link-text">Étudiants</span>
            </a>
            <a href="{{ route('professor.messages') }}" class="sidebar-link {{ request()->routeIs('professor.messages') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="fas fa-envelope"></i></span>
                <span class="sidebar-link-text">Messages</span>
                @if(($unreadMessages ?? 0) > 0)
                    <span class="sidebar-badge">{{ $unreadMessages }}</span>
                @endif
            </a>
            <a href="{{ route('professor.appointments') }}" class="sidebar-link {{ request()->routeIs('professor.appointments') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="fas fa-calendar-check"></i></span>
                <span class="sidebar-link-text">Rendez-vous</span>
                @if(($pendingAppointments ?? 0) > 0)
                    <span class="sidebar-badge sidebar-badge--warning">{{ $pendingAppointments }}</span>
                @endif
            </a>
            <a href="{{ route('professor.reviews') }}" class="sidebar-link {{ request()->routeIs('professor.reviews') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="fas fa-star"></i></span>
                <span class="sidebar-link-text">Avis</span>
            </a>
            <a href="{{ route('professor.quizzes.index') }}" class="sidebar-link {{ request()->routeIs('professor.quizzes.*') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="fas fa-clipboard-question"></i></span>
                <span class="sidebar-link-text">Quiz</span>
            </a>
            <a href="{{ route('courses.create') }}" class="sidebar-link {{ request()->routeIs('courses.create') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="fas fa-plus"></i></span>
                <span class="sidebar-link-text">Créer un cours</span>
            </a>
            <a href="{{ route('profile.edit') }}" class="sidebar-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="fas fa-user"></i></span>
                <span class="sidebar-link-text">Mon profil</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <button type="button" class="sidebar-expand-btn" id="sidebarExpandBtn" aria-label="Développer le menu" title="Développer le menu">
                <i class="fas fa-angles-right"></i>
            </button>
            <div class="sidebar-footer-row">
                <div class="sidebar-user">
                    <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="sidebar-user-avatar">
                    <div class="sidebar-user-info">
                        <span class="sidebar-user-name">{{ auth()->user()->name }}</span>
                        <span class="sidebar-user-role">Professeur</span>
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
                <input type="text" class="topbar-search-input" id="globalSearch" placeholder="Rechercher..." autocomplete="off">
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
                        <a href="{{ route('professor.messages') }}" class="notif-item">
                            <span class="notif-item-icon"><i class="fas fa-comment"></i></span>
                            <div>
                                <strong>Voir mes messages</strong>
                                <p>Échanges avec vos étudiants.</p>
                            </div>
                        </a>
                    </div>
                </div>
                <a href="{{ route('courses.create') }}" class="topbar-icon-btn" title="Créer un cours">
                    <i class="fas fa-plus"></i>
                </a>
                <a href="{{ route('home') }}" class="topbar-icon-btn" title="Voir le site">
                    <i class="fas fa-external-link"></i>
                </a>
                <div class="topbar-profile-wrap" id="profileWrap">
                    <button type="button" class="topbar-profile-btn" id="profileToggle">
                        <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="topbar-avatar">
                        <span class="topbar-profile-info">
                            <span class="topbar-profile-name">{{ auth()->user()->name }}</span>
                            <span class="topbar-profile-role">Professeur</span>
                        </span>
                        <i class="fas fa-chevron-down topbar-profile-chevron"></i>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown">
                        <a href="{{ route('profile.edit') }}"><i class="fas fa-user"></i> Mon profil</a>
                        <a href="{{ route('professor.dashboard') }}"><i class="fas fa-grid-2"></i> Dashboard</a>
                        <a href="{{ route('professor.courses.index') }}"><i class="fas fa-book-open"></i> Mes cours</a>
                        <a href="{{ route('courses.create') }}"><i class="fas fa-plus"></i> Créer un cours</a>
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
@yield('scripts')
</body>
</html>
