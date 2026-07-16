/**
 * StudyWays — Homepage Interactions
 * Premium UI enhancements without altering page logic
 */
(function () {
    'use strict';

    /* ── Page Loader ── */
    window.addEventListener('load', () => {
        const loader = document.getElementById('pageLoader');
        if (loader) {
            setTimeout(() => loader.classList.add('is-hidden'), 300);
        }
    });

    /* ── Navbar scroll + back to top ── */
    const navbar = document.getElementById('navbar');
    const backToTop = document.getElementById('backToTop');

    window.addEventListener('scroll', () => {
        if (navbar) {
            navbar.classList.toggle('scrolled', window.scrollY > 40);
        }
        if (backToTop) {
            backToTop.classList.toggle('is-visible', window.scrollY > 400);
        }
        updateActiveNavLink();
    }, { passive: true });

    if (backToTop) {
        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* ── Active nav indicator ── */
    const navLinks = document.querySelectorAll('#mainNav > a[href^="#"]');
    const sections = Array.from(navLinks)
        .map((link) => {
            const id = link.getAttribute('href').slice(1);
            return document.getElementById(id);
        })
        .filter(Boolean);

    function updateActiveNavLink() {
        const scrollPos = window.scrollY + (navbar?.offsetHeight || 84) + 60;
        let current = sections[0]?.id || '';

        sections.forEach((section) => {
            if (section.offsetTop <= scrollPos) {
                current = section.id;
            }
        });

        navLinks.forEach((link) => {
            link.classList.toggle('is-active', link.getAttribute('href') === `#${current}`);
        });
    }

    updateActiveNavLink();

    /* ── Smooth scroll with navbar offset ── */
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener('click', (e) => {
            const href = anchor.getAttribute('href');
            if (!href || href === '#') {
                return;
            }

            const target = document.querySelector(href);
            if (!target) {
                return;
            }

            e.preventDefault();
            const offset = (navbar?.offsetHeight || 84) + 16;
            const top = target.getBoundingClientRect().top + window.scrollY - offset;
            window.scrollTo({ top, behavior: 'smooth' });

            const mainNav = document.getElementById('mainNav');
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            if (mainNav?.classList.contains('is-open')) {
                mainNav.classList.remove('is-open');
                mobileMenuBtn?.classList.remove('is-active');
            }
        });
    });

    /* ── Mobile Menu ── */
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mainNav = document.getElementById('mainNav');

    mobileMenuBtn?.addEventListener('click', () => {
        const isOpen = mainNav.classList.toggle('is-open');
        mobileMenuBtn.classList.toggle('is-active', isOpen);
    });

    /* ── Force light mode (dark mode removed) ── */
    document.documentElement.removeAttribute('data-theme');
    localStorage.removeItem('sw_theme');

    /* ── Button ripple effect ── */
    document.querySelectorAll('.btn').forEach((btn) => {
        btn.addEventListener('click', function (e) {
            const rect = this.getBoundingClientRect();
            this.style.setProperty('--ripple-x', `${((e.clientX - rect.left) / rect.width) * 100}%`);
            this.style.setProperty('--ripple-y', `${((e.clientY - rect.top) / rect.height) * 100}%`);
            this.classList.add('is-rippling');
            setTimeout(() => this.classList.remove('is-rippling'), 600);
        });
    });

    /* ── Counter animation ── */
    function animateCounter(el) {
        const target = parseInt(el.dataset.counter, 10);
        const duration = 1800;
        const step = target / (duration / 16);
        let current = 0;

        const timer = setInterval(() => {
            current = Math.min(current + step, target);
            el.textContent = Math.floor(current).toLocaleString() + '+';
            if (current >= target) {
                clearInterval(timer);
            }
        }, 16);
    }

    const counters = document.querySelectorAll('[data-counter]');
    let countersStarted = false;

    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting && !countersStarted) {
                countersStarted = true;
                counters.forEach(animateCounter);
            }
        });
    }, { threshold: 0.5 });

    if (counters.length) {
        counterObserver.observe(counters[0].closest('.hero-kpis') || counters[0]);
    }

    /* ── Reveal on scroll ── */
    const revealEls = document.querySelectorAll('.reveal, .reveal-stagger');

    const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
        if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    revealEls.forEach((el) => observer.observe(el));

    /* ── Hero animate-up on load ── */
    document.querySelectorAll('.animate-up').forEach((el) => {
        requestAnimationFrame(() => el.classList.add('is-visible'));
    });

    /* ── Hero parallax (mouse + scroll) ── */
    const hero = document.getElementById('hero');
    const heroImage = hero?.querySelector('.hero-image');
    const heroShapes = hero?.querySelectorAll('.hero-shape');

    if (hero && heroImage && window.matchMedia('(pointer: fine)').matches) {
        let rafId = null;

        hero.addEventListener('mousemove', (e) => {
            const rect = hero.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width - 0.5;
            const y = (e.clientY - rect.top) / rect.height - 0.5;

            if (rafId) {
                cancelAnimationFrame(rafId);
            }

            rafId = requestAnimationFrame(() => {
                heroImage.style.transform = `translate3d(${x * 12}px, ${y * 12}px, 0)`;
                heroShapes?.forEach((shape, i) => {
                    const factor = (i + 1) * 8;
                    shape.style.transform = `translate3d(${x * factor}px, ${y * factor}px, 0)`;
                });
            });
        });

        hero.addEventListener('mouseleave', () => {
            if (rafId) {
                cancelAnimationFrame(rafId);
            }
            heroImage.style.transform = '';
            heroShapes?.forEach((shape) => {
                shape.style.transform = '';
            });
        });
    }

    /* ── Premium Testimonial Carousel ── */
    const stage = document.getElementById('testiStage');
    const prevBtn = document.getElementById('testiPrev');
    const nextBtn = document.getElementById('testiNext');
    const dotsWrap = document.getElementById('testiDots');
    const carousel = document.getElementById('testiCarousel');

    if (stage && dotsWrap) {
        const cards = Array.from(stage.querySelectorAll('.testi-card'));
        let activeIndex = 0;
        let autoplayTimer = null;
        let touchStartX = 0;
        let touchDeltaX = 0;
        let isAnimating = false;

        function getMode() {
            if (window.innerWidth < 768) {
                return 'single';
            }

            if (window.innerWidth < 1024) {
                return 'double';
            }

            return 'triple';
        }

        function normalizeOffset(index, center) {
            const total = cards.length;
            let diff = index - center;

            if (diff > total / 2) {
                diff -= total;
            }

            if (diff < -total / 2) {
                diff += total;
            }

            return diff;
        }

        function buildDots() {
            dotsWrap.innerHTML = '';
            cards.forEach((_, i) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'testi-dot';
                dot.setAttribute('role', 'tab');
                dot.setAttribute('aria-label', `Témoignage ${i + 1}`);
                dot.addEventListener('click', () => goTo(i));
                dotsWrap.appendChild(dot);
            });
        }

        function syncDots() {
            dotsWrap.querySelectorAll('.testi-dot').forEach((dot, i) => {
                dot.classList.toggle('is-active', i === activeIndex);
                dot.setAttribute('aria-selected', i === activeIndex ? 'true' : 'false');
            });
        }

        function layoutCards() {
            const mode = getMode();

            cards.forEach((card, i) => {
                const diff = normalizeOffset(i, activeIndex);

                card.classList.remove('is-active', 'is-prev', 'is-next', 'is-visible', 'is-sliding');

                if (mode === 'single') {
                    if (diff === 0) {
                        card.classList.add('is-active', 'is-visible');
                    }

                    return;
                }

                if (mode === 'double') {
                    if (diff === 0) {
                        card.classList.add('is-active', 'is-visible');
                    } else if (diff === -1) {
                        card.classList.add('is-prev', 'is-visible');
                    } else if (diff === 1) {
                        card.classList.add('is-next', 'is-visible');
                    }

                    return;
                }

                if (diff === 0) {
                    card.classList.add('is-active', 'is-visible');
                } else if (diff === -1) {
                    card.classList.add('is-prev', 'is-visible');
                } else if (diff === 1) {
                    card.classList.add('is-next', 'is-visible');
                }
            });

            syncDots();
        }

        function flashTextTransition() {
            const active = cards[activeIndex];
            if (!active) {
                return;
            }

            active.classList.add('is-sliding');
            setTimeout(() => active.classList.remove('is-sliding'), 380);
        }

        function goTo(index, direction) {
            if (!cards.length || isAnimating) {
                return;
            }

            const total = cards.length;
            let nextIndex = index;

            if (direction === 'next') {
                nextIndex = (activeIndex + 1) % total;
            } else if (direction === 'prev') {
                nextIndex = (activeIndex - 1 + total) % total;
            } else {
                nextIndex = ((index % total) + total) % total;
            }

            if (nextIndex === activeIndex) {
                return;
            }

            isAnimating = true;
            flashTextTransition();
            activeIndex = nextIndex;
            layoutCards();

            setTimeout(() => {
                isAnimating = false;
            }, 720);
        }

        function startAutoplay() {
            stopAutoplay();
            autoplayTimer = setInterval(() => goTo(0, 'next'), 6000);
        }

        function stopAutoplay() {
            if (autoplayTimer) {
                clearInterval(autoplayTimer);
                autoplayTimer = null;
            }
        }

        buildDots();
        layoutCards();
        startAutoplay();

        prevBtn?.addEventListener('click', () => goTo(0, 'prev'));
        nextBtn?.addEventListener('click', () => goTo(0, 'next'));

        carousel?.addEventListener('mouseenter', stopAutoplay);
        carousel?.addEventListener('mouseleave', startAutoplay);

        window.addEventListener('resize', () => layoutCards(), { passive: true });

        carousel?.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            touchDeltaX = 0;
            stopAutoplay();
        }, { passive: true });

        carousel?.addEventListener('touchmove', (e) => {
            touchDeltaX = e.changedTouches[0].screenX - touchStartX;
        }, { passive: true });

        carousel?.addEventListener('touchend', () => {
            if (Math.abs(touchDeltaX) > 50) {
                goTo(0, touchDeltaX > 0 ? 'prev' : 'next');
            }

            startAutoplay();
        }, { passive: true });

        document.addEventListener('keydown', (e) => {
            if (!carousel?.matches(':hover') && document.activeElement?.closest('#temoignages') === null) {
                return;
            }

            if (e.key === 'ArrowLeft') {
                goTo(0, 'prev');
            }

            if (e.key === 'ArrowRight') {
                goTo(0, 'next');
            }
        });
    }

    /* ── Icon card interactive highlight ── */
    document.querySelectorAll('.icon-card').forEach((card) => {
        card.addEventListener('mouseenter', () => {
            document.querySelectorAll('.icon-card').forEach((c) => c.classList.remove('icon-card--active'));
            card.classList.add('icon-card--active');
        });
    });
})();
