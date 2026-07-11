/**
 * StudyWays Admin — Layout interactions
 */
(function () {
    'use strict';

    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const collapseBtn = document.getElementById('sidebarCollapseBtn');
    const expandBtn = document.getElementById('sidebarExpandBtn');
    const wrapper = document.getElementById('adminWrapper') || document.querySelector('.admin-wrapper');
    const notifToggle = document.getElementById('notifToggle');
    const notifPanel = document.getElementById('notifPanel');
    const messagesToggle = document.getElementById('messagesToggle');
    const messagesPanel = document.getElementById('messagesPanel');
    const profileWrap = document.getElementById('profileWrap');
    const profileToggle = document.getElementById('profileToggle');
    const profileDropdown = document.getElementById('profileDropdown');
    const globalSearch = document.getElementById('globalSearch');
    const fabActions = document.getElementById('fabActions');
    const fabToggle = document.getElementById('fabToggle');

    function setCollapsed(collapsed) {
        wrapper?.classList.toggle('sidebar-collapsed', collapsed);
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        localStorage.setItem('sw_sidebar_collapsed', collapsed ? 'true' : 'false');
    }

    sidebarToggle?.addEventListener('click', () => {
        sidebar?.classList.toggle('is-open');
        sidebarOverlay?.classList.toggle('is-visible');
    });

    sidebarOverlay?.addEventListener('click', () => {
        sidebar?.classList.remove('is-open');
        sidebarOverlay?.classList.remove('is-visible');
    });

    collapseBtn?.addEventListener('click', () => {
        setCollapsed(!wrapper?.classList.contains('sidebar-collapsed'));
    });

    expandBtn?.addEventListener('click', () => {
        setCollapsed(false);
    });

    if (localStorage.getItem('sw_sidebar_collapsed') === 'true') {
        setCollapsed(true);
    }

    if (window.innerWidth < 1024) {
        setCollapsed(true);
    }

    window.addEventListener('resize', () => {
        if (window.innerWidth < 1024) {
            setCollapsed(true);
        }
    });

    notifToggle?.addEventListener('click', (e) => {
        e.stopPropagation();
        notifPanel?.classList.toggle('is-open');
        messagesPanel?.classList.remove('is-open');
        profileDropdown?.classList.remove('is-open');
        profileWrap?.classList.remove('is-open');
    });

    messagesToggle?.addEventListener('click', (e) => {
        e.stopPropagation();
        messagesPanel?.classList.toggle('is-open');
        notifPanel?.classList.remove('is-open');
        profileDropdown?.classList.remove('is-open');
        profileWrap?.classList.remove('is-open');
    });

    profileToggle?.addEventListener('click', (e) => {
        e.stopPropagation();
        profileDropdown?.classList.toggle('is-open');
        profileWrap?.classList.toggle('is-open');
        notifPanel?.classList.remove('is-open');
        messagesPanel?.classList.remove('is-open');
    });

    document.addEventListener('click', () => {
        notifPanel?.classList.remove('is-open');
        messagesPanel?.classList.remove('is-open');
        profileDropdown?.classList.remove('is-open');
        profileWrap?.classList.remove('is-open');
    });

    document.addEventListener('keydown', (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            globalSearch?.focus();
        }
    });

    fabToggle?.addEventListener('click', () => {
        fabActions?.classList.toggle('is-open');
    });

    document.addEventListener('click', (e) => {
        if (fabActions && !fabActions.contains(e.target)) {
            fabActions.classList.remove('is-open');
        }
    });

    document.querySelectorAll('.flash-toast').forEach((el) => {
        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(-8px)';
            setTimeout(() => el.remove(), 400);
        }, 5000);
    });

    document.querySelectorAll('.btn-primary, .sidebar-promo-btn, .growth-card-btn, .btn-glow, .sidebar-expand-btn').forEach((btn) => {
        btn.addEventListener('click', function (e) {
            const rect = this.getBoundingClientRect();
            const ripple = document.createElement('span');
            const size = Math.max(rect.width, rect.height);
            ripple.style.cssText = `position:absolute;border-radius:50%;pointer-events:none;background:rgba(255,255,255,0.35);width:${size}px;height:${size}px;left:${e.clientX - rect.left - size / 2}px;top:${e.clientY - rect.top - size / 2}px;transform:scale(0);animation:adminRipple 0.55s ease-out;`;
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    });

    document.querySelectorAll('[data-tilt]').forEach((card) => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width - 0.5;
            const y = (e.clientY - rect.top) / rect.height - 0.5;
            card.style.transform = `perspective(800px) rotateY(${x * 6}deg) rotateX(${-y * 6}deg) translateY(-4px)`;
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = '';
        });
    });

    document.querySelectorAll('.reveal-up, .reveal-stagger, [data-animate]').forEach((el, i) => {
        el.style.animationDelay = `${Math.min(i * 0.06, 0.5)}s`;
    });
})();
