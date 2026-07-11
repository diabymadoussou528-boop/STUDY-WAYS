<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — StudyWays</title>
    <script>
        (function () {
            const theme = localStorage.getItem('sw_theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    @yield('styles')
</head>
<body>

<!-- Mobile Sidebar Toggle -->
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">
    <i class="fas fa-bars"></i>
</button>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="wrapper">

    <!-- ─── SIDEBAR ─── -->
    <aside class="sidebar" id="sidebar">

        <!-- Header -->
        <div class="sidebar-header">
            <div style="display:flex;align-items:center;gap:10px;min-width:0;">
            <x-sw-brand :href="route('dashboard')" variant="default" size="md" class="sidebar-brand-link" />
            </div>
            <button id="themeToggleBtn" class="theme-toggle-btn" aria-label="Toggle theme">
                <i class="fas fa-moon"></i>
            </button>
        </div>

        <!-- User -->
        <div class="sidebar-user">
            <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" class="sidebar-avatar">
            <div style="min-width:0;">
                <div class="sidebar-user-name">{{ auth()->user()->name }}</div>
                <div class="sidebar-user-role">{{ auth()->user()->role }}</div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            <div class="sidebar-section-title">Principal</div>

            @if(auth()->user()->role === 'admin')
                <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <span class="sidebar-link-icon"><i class="fas fa-chart-pie"></i></span>
                    Dashboard
                </a>
                <a href="{{ route('admin.testimonials') }}" class="sidebar-link {{ request()->routeIs('admin.testimonials') ? 'active' : '' }}">
                    <span class="sidebar-link-icon"><i class="fas fa-comments"></i></span>
                    Témoignages
                </a>
                @if(auth()->user()->isSuperAdmin())
                    <div class="sidebar-section-title">Super Admin</div>
                    <a href="{{ route('admin.admins') }}" class="sidebar-link {{ request()->routeIs('admin.admins') ? 'active' : '' }}">
                        <span class="sidebar-link-icon"><i class="fas fa-user-shield"></i></span>
                        Gérer Admins
                    </a>
                @endif

            @elseif(auth()->user()->role === 'professor')
                <a href="{{ route('professor.dashboard') }}" class="sidebar-link {{ request()->routeIs('professor.dashboard') ? 'active' : '' }}">
                    <span class="sidebar-link-icon"><i class="fas fa-chart-pie"></i></span>
                    Dashboard
                </a>
                <a href="{{ route('courses.create') }}" class="sidebar-link {{ request()->routeIs('courses.create') ? 'active' : '' }}">
                    <span class="sidebar-link-icon"><i class="fas fa-plus-circle"></i></span>
                    Ajouter un cours
                </a>

            @else
                <a href="{{ route('student.dashboard') }}" class="sidebar-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
                    <span class="sidebar-link-icon"><i class="fas fa-chart-pie"></i></span>
                    Dashboard
                </a>
                <a href="#" class="sidebar-link">
                    <span class="sidebar-link-icon"><i class="fas fa-book-open"></i></span>
                    Mes Cours
                </a>
                <a href="#" class="sidebar-link">
                    <span class="sidebar-link-icon"><i class="fas fa-certificate"></i></span>
                    Certificats
                </a>
            @endif

            <div class="sidebar-section-title">Navigation</div>
            <a href="{{ route('home') }}" class="sidebar-link">
                <span class="sidebar-link-icon"><i class="fas fa-home"></i></span>
                Accueil
            </a>
            <a href="{{ route('profile.edit') }}" class="sidebar-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
                <span class="sidebar-link-icon"><i class="fas fa-user-cog"></i></span>
                Mon Profil
            </a>
        </nav>

        <!-- Logout -->
        <div class="sidebar-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sidebar-logout-btn">
                    <span class="sidebar-link-icon"><i class="fas fa-sign-out-alt"></i></span>
                    Se déconnecter
                </button>
            </form>
        </div>
    </aside>

    <!-- ─── CONTENT ─── -->
    <main class="content">
        @if(session('success'))
            <div class="flash-toast flash-toast--success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="flash-toast flash-toast--error">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

</div>

<script>
    // ── Sidebar Toggle (mobile)
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar       = document.getElementById('sidebar');
    const overlay       = document.getElementById('sidebarOverlay');

    sidebarToggle?.addEventListener('click', () => {
        sidebar.classList.toggle('is-open');
        overlay.classList.toggle('is-visible');
    });
    overlay?.addEventListener('click', () => {
        sidebar.classList.remove('is-open');
        overlay.classList.remove('is-visible');
    });

    // ── Theme Toggle
    const themeBtn  = document.getElementById('themeToggleBtn');
    const themeIcon = themeBtn?.querySelector('i');

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('sw_theme', theme);
        if (themeIcon) {
            themeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }

    applyTheme(localStorage.getItem('sw_theme') || 'light');

    themeBtn?.addEventListener('click', () => {
        const cur = document.documentElement.getAttribute('data-theme') || 'light';
        applyTheme(cur === 'dark' ? 'light' : 'dark');
    });

    // ── Auto-dismiss flash messages
    document.querySelectorAll('.flash-toast').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-8px)';
            setTimeout(() => el.remove(), 500);
        }, 4000);
    });
</script>

@yield('scripts')
</body>
</html>
