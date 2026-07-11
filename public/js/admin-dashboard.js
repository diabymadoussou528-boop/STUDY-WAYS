/**
 * StudyWays — Premium Admin Dashboard
 * ApexCharts, counters, sparklines, table interactions
 */
(function () {
    'use strict';

    const BRAND = {
        primary: '#8B2032',
        primaryLight: '#a82841',
        primaryDark: '#6b1826',
        primaryDeep: '#4a1018',
        black: '#111827',
        muted: '#6b7280',
        border: 'rgba(0,0,0,0.06)',
        white: '#ffffff',
    };

    const charts = window.__ADMIN_CHARTS__ || {};
    const chartInstances = {};

    const baseChartOptions = {
        chart: {
            fontFamily: 'Inter, sans-serif',
            toolbar: { show: false },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 900,
                animateGradually: { enabled: true, delay: 120 },
                dynamicAnimation: { enabled: true, speed: 350 },
            },
        },
        grid: {
            borderColor: BRAND.border,
            strokeDashArray: 4,
            padding: { left: 8, right: 8 },
        },
        tooltip: {
            theme: 'light',
            style: { fontSize: '12px' },
        },
        dataLabels: { enabled: false },
    };

    function initCounters() {
        document.querySelectorAll('[data-counter]').forEach((el) => {
            const target = parseInt(el.getAttribute('data-counter'), 10) || 0;
            const duration = 1200;
            const start = performance.now();

            function tick(now) {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                el.textContent = Math.floor(target * eased).toLocaleString('fr-FR');
                if (progress < 1) requestAnimationFrame(tick);
            }

            requestAnimationFrame(tick);
        });
    }

    function initSparklines() {
        document.querySelectorAll('.kpi-sparkline[data-series]').forEach((el) => {
            let series;
            try {
                series = JSON.parse(el.getAttribute('data-series'));
            } catch {
                return;
            }

            if (!series.length) return;

            const options = {
                ...baseChartOptions,
                chart: {
                    ...baseChartOptions.chart,
                    type: 'area',
                    height: 56,
                    sparkline: { enabled: true },
                },
                series: [{ data: series }],
                stroke: { curve: 'smooth', width: 2, colors: [BRAND.primary] },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.35,
                        opacityTo: 0.02,
                        stops: [0, 100],
                        colorStops: [
                            { offset: 0, color: BRAND.primary, opacity: 0.35 },
                            { offset: 100, color: BRAND.primary, opacity: 0.02 },
                        ],
                    },
                },
                colors: [BRAND.primary],
            };

            new ApexCharts(el, options).render();
        });
    }

    function renderWebsiteViews(range = 'week') {
        const data = charts.websiteViews?.[range];
        if (!data) return;

        const options = {
            ...baseChartOptions,
            chart: {
                ...baseChartOptions.chart,
                type: 'area',
                height: 320,
                id: 'websiteViews',
            },
            series: [{ name: 'Activité', data: data.data }],
            xaxis: {
                categories: data.labels,
                labels: { style: { colors: BRAND.muted, fontSize: '11px' } },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                labels: { style: { colors: BRAND.muted, fontSize: '11px' } },
            },
            stroke: { curve: 'smooth', width: 3, colors: [BRAND.primary] },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.42,
                    opacityTo: 0.04,
                    stops: [0, 90, 100],
                    colorStops: [
                        { offset: 0, color: BRAND.primary, opacity: 0.45 },
                        { offset: 100, color: BRAND.primary, opacity: 0.03 },
                    ],
                },
            },
            colors: [BRAND.primary],
            markers: {
                size: 0,
                hover: { size: 6, sizeOffset: 2 },
                colors: [BRAND.white],
                strokeColors: BRAND.primary,
                strokeWidth: 2,
            },
        };

        const el = document.querySelector('#chartWebsiteViews');
        if (!el) return;

        if (chartInstances.websiteViews) {
            chartInstances.websiteViews.updateOptions(options);
            return;
        }

        chartInstances.websiteViews = new ApexCharts(el, options);
        chartInstances.websiteViews.render();
    }

    function renderStudentGrowth() {
        const data = charts.studentGrowth;
        if (!data) return;

        const options = {
            ...baseChartOptions,
            chart: { ...baseChartOptions.chart, type: 'area', height: 320 },
            series: data.series,
            xaxis: {
                categories: data.labels,
                labels: { style: { colors: BRAND.muted, fontSize: '11px' } },
            },
            yaxis: { labels: { style: { colors: BRAND.muted, fontSize: '11px' } } },
            stroke: { curve: 'smooth', width: 2.5 },
            fill: {
                type: 'gradient',
                gradient: { opacityFrom: 0.35, opacityTo: 0.05 },
            },
            colors: [BRAND.primary, BRAND.primaryLight, BRAND.primaryDark],
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                fontSize: '12px',
                markers: { radius: 12 },
            },
        };

        const el = document.querySelector('#chartStudentGrowth');
        if (el) new ApexCharts(el, options).render();
    }

    function renderTeacherPerformance() {
        const data = charts.teacherPerformance;
        if (!data || !data.labels?.length) return;

        const options = {
            ...baseChartOptions,
            chart: { ...baseChartOptions.chart, type: 'bar', height: 320, stacked: false },
            series: [
                { name: 'Étudiants (vues)', data: data.students },
                { name: 'Cours', data: data.courses },
                { name: 'Avis', data: data.reviews },
            ],
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    columnWidth: '48%',
                },
            },
            xaxis: {
                categories: data.labels,
                labels: { style: { colors: BRAND.muted, fontSize: '11px' } },
            },
            yaxis: { labels: { style: { colors: BRAND.muted, fontSize: '11px' } } },
            colors: [BRAND.primary, BRAND.primaryLight, BRAND.primaryDark],
            legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px' },
        };

        const el = document.querySelector('#chartTeacherPerformance');
        if (el) new ApexCharts(el, options).render();
    }

    function renderCourseEngagement() {
        const data = charts.courseEngagement;
        if (!data || !data.labels?.length) return;

        const options = {
            ...baseChartOptions,
            chart: { ...baseChartOptions.chart, type: 'line', height: 320 },
            series: [
                { name: 'Vues', type: 'column', data: data.views },
                { name: 'Note moy.', type: 'line', data: data.ratings },
                { name: 'Complétion %', type: 'area', data: data.completion },
            ],
            stroke: { width: [0, 3, 2], curve: 'smooth' },
            plotOptions: { bar: { borderRadius: 8, columnWidth: '42%' } },
            fill: {
                type: ['solid', 'solid', 'gradient'],
                gradient: { opacityFrom: 0.3, opacityTo: 0.05 },
            },
            xaxis: {
                categories: data.labels,
                labels: { style: { colors: BRAND.muted, fontSize: '11px' } },
            },
            yaxis: [
                { labels: { style: { colors: BRAND.muted } } },
                { opposite: true, max: 5, labels: { style: { colors: BRAND.muted } } },
            ],
            colors: [BRAND.primaryDark, BRAND.primary, BRAND.primaryLight],
            legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px' },
        };

        const el = document.querySelector('#chartCourseEngagement');
        if (el) new ApexCharts(el, options).render();
    }

    function renderCategories() {
        const data = charts.categories;
        if (!data) return;

        const options = {
            ...baseChartOptions,
            chart: { ...baseChartOptions.chart, type: 'donut', height: 300 },
            series: data.series,
            labels: data.labels,
            colors: [BRAND.primary, BRAND.primaryLight, BRAND.primaryDark, BRAND.primaryDeep, '#3d0f18', '#2a0a11'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '72%',
                        labels: {
                            show: true,
                            name: { fontSize: '13px', color: BRAND.muted },
                            value: { fontSize: '22px', fontWeight: 800, color: BRAND.black },
                            total: {
                                show: true,
                                label: 'Total',
                                fontSize: '12px',
                                color: BRAND.muted,
                                formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0),
                            },
                        },
                    },
                },
            },
            legend: { position: 'bottom', fontSize: '12px' },
            stroke: { width: 0 },
        };

        const el = document.querySelector('#chartCategories');
        if (el) new ApexCharts(el, options).render();
    }

    function renderAiPageCharts() {
        const data = window.__AI_CHARTS__;
        if (!data || typeof ApexCharts === 'undefined') {
            return;
        }

        const genEl = document.querySelector('#chartAiGenerated');
        if (genEl) {
            new ApexCharts(genEl, {
                ...baseChartOptions,
                chart: { ...baseChartOptions.chart, type: 'area', height: 260 },
                series: [{ name: 'Générées', data: data.generated }],
                xaxis: { categories: data.labels, labels: { style: { colors: BRAND.muted, fontSize: '11px' } } },
                colors: [BRAND.primary],
                fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
            }).render();
        }

        const accEl = document.querySelector('#chartAiAccepted');
        if (accEl) {
            new ApexCharts(accEl, {
                ...baseChartOptions,
                chart: { ...baseChartOptions.chart, type: 'line', height: 260 },
                series: [{ name: 'Acceptées', data: data.accepted }],
                xaxis: { categories: data.labels, labels: { style: { colors: BRAND.muted, fontSize: '11px' } } },
                colors: [BRAND.primaryDeep],
                stroke: { curve: 'smooth', width: 3 },
            }).render();
        }
    }

    function renderTestimonialCharts() {
        const data = window.__TESTIMONIAL_CHARTS__;
        if (!data || typeof ApexCharts === 'undefined') {
            return;
        }

        const ratingsEl = document.querySelector('#chartTestimonialRatings');
        if (ratingsEl && data.ratings?.length) {
            new ApexCharts(ratingsEl, {
                ...baseChartOptions,
                chart: { ...baseChartOptions.chart, type: 'bar', height: 260 },
                series: [{ name: 'Note moyenne', data: data.ratings }],
                xaxis: { categories: data.labels, labels: { style: { colors: BRAND.muted, fontSize: '11px' } } },
                colors: [BRAND.primary],
                plotOptions: { bar: { borderRadius: 8, columnWidth: '50%' } },
            }).render();
        }

        const countsEl = document.querySelector('#chartMostReviewed');
        if (countsEl && data.counts?.length) {
            new ApexCharts(countsEl, {
                ...baseChartOptions,
                chart: { ...baseChartOptions.chart, type: 'bar', height: 260 },
                series: [{ name: 'Avis', data: data.counts }],
                xaxis: { categories: data.labels, labels: { style: { colors: BRAND.muted, fontSize: '11px' } } },
                colors: [BRAND.primaryDeep],
                plotOptions: { bar: { borderRadius: 8, columnWidth: '50%' } },
            }).render();
        }
    }

    function renderAiRecommendations() {
        const data = charts.aiRecommendations;
        if (!data) return;

        const options = {
            ...baseChartOptions,
            chart: { ...baseChartOptions.chart, type: 'bar', height: 180, stacked: true },
            series: [
                { name: 'Approuvées', data: data.approved },
                { name: 'En attente', data: data.pending },
            ],
            xaxis: {
                categories: data.labels,
                labels: { style: { colors: BRAND.muted, fontSize: '11px' } },
            },
            plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
            colors: [BRAND.primary, BRAND.primaryDeep],
            legend: { position: 'top', horizontalAlign: 'right', fontSize: '11px' },
        };

        const el = document.querySelector('#chartAiRecommendations');
        if (el) new ApexCharts(el, options).render();
    }

    function renderProgressRings() {
        document.querySelectorAll('.performance-ring-canvas').forEach((el) => {
            const value = parseInt(el.dataset.value, 10) || 0;

            const options = {
                ...baseChartOptions,
                chart: {
                    ...baseChartOptions.chart,
                    type: 'radialBar',
                    height: 90,
                    sparkline: { enabled: true },
                },
                series: [value],
                plotOptions: {
                    radialBar: {
                        hollow: { size: '62%' },
                        track: { background: 'rgba(139,32,50,0.08)' },
                        dataLabels: {
                            name: { show: false },
                            value: {
                                show: true,
                                fontSize: '14px',
                                fontWeight: 800,
                                color: BRAND.black,
                                formatter: (val) => `${Math.round(val)}%`,
                            },
                        },
                    },
                },
                colors: [BRAND.primary],
                stroke: { lineCap: 'round' },
            };

            new ApexCharts(el, options).render();
        });
    }

    function initChartTabs() {
        document.querySelectorAll('[data-chart-tabs]').forEach((wrap) => {
            const indicator = wrap.querySelector('.chart-tabs-indicator');

            function moveIndicator(tab) {
                if (!indicator || !tab) {
                    return;
                }
                indicator.style.width = `${tab.offsetWidth}px`;
                indicator.style.transform = `translateX(${tab.offsetLeft}px)`;
            }

            const active = wrap.querySelector('.chart-tab.active') || wrap.querySelector('.chart-tab');
            requestAnimationFrame(() => moveIndicator(active));

            wrap.querySelectorAll('.chart-tab').forEach((tab) => {
                tab.addEventListener('click', () => {
                    wrap.querySelectorAll('.chart-tab').forEach((t) => t.classList.remove('active'));
                    tab.classList.add('active');
                    moveIndicator(tab);
                    if (wrap.dataset.chartTabs === 'websiteViews') {
                        renderWebsiteViews(tab.dataset.range);
                    }
                });
            });

            window.addEventListener('resize', () => {
                moveIndicator(wrap.querySelector('.chart-tab.active'));
            });
        });
    }

    function initTableSearch() {
        document.querySelectorAll('[data-table-search]').forEach((input) => {
            const table = document.getElementById(input.dataset.tableSearch);
            if (!table) return;

            input.addEventListener('input', () => {
                const q = input.value.toLowerCase();
                table.querySelectorAll('tbody tr').forEach((row) => {
                    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
                });
            });
        });
    }

    function initTableSort() {
        document.querySelectorAll('.premium-table').forEach((table) => {
            table.querySelectorAll('th[data-sort]').forEach((th, index) => {
                th.style.cursor = 'pointer';
                th.addEventListener('click', () => {
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    const type = th.dataset.sort;
                    const asc = th.classList.toggle('sort-asc') && !th.classList.contains('sort-desc');
                    th.classList.toggle('sort-desc', !asc);

                    rows.sort((a, b) => {
                        const av = a.children[index]?.dataset.value ?? a.children[index]?.textContent ?? '';
                        const bv = b.children[index]?.dataset.value ?? b.children[index]?.textContent ?? '';
                        if (type === 'number') {
                            return asc ? Number(av) - Number(bv) : Number(bv) - Number(av);
                        }
                        return asc ? av.localeCompare(bv) : bv.localeCompare(av);
                    });

                    rows.forEach((r) => tbody.appendChild(r));
                });
            });
        });
    }

    function initRowMenus() {
        document.querySelectorAll('.row-actions').forEach((wrap) => {
            const btn = wrap.querySelector('.row-action-btn');
            const menu = wrap.querySelector('.row-action-menu');
            if (!btn || !menu) return;

            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                document.querySelectorAll('.row-action-menu.is-open').forEach((m) => {
                    if (m !== menu) m.classList.remove('is-open');
                });
                menu.classList.toggle('is-open');
            });
        });

        document.addEventListener('click', () => {
            document.querySelectorAll('.row-action-menu.is-open').forEach((m) => m.classList.remove('is-open'));
        });
    }

    function initRevealAnimations() {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
        );

        document.querySelectorAll('.reveal-up, .reveal-stagger, [data-animate]').forEach((el) => observer.observe(el));
    }

    function initFab() {
        const fab = document.getElementById('fabTop');
        if (!fab) return;

        window.addEventListener('scroll', () => {
            fab.classList.toggle('is-visible', window.scrollY > 400);
        }, { passive: true });

        fab.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    }

    function initRefresh() {
        document.getElementById('refreshDashboard')?.addEventListener('click', () => {
            document.body.classList.add('is-refreshing');
            setTimeout(() => window.location.reload(), 350);
        });
    }

    function init() {
        initTableSearch();
        initTableSort();
        initRowMenus();
        initRevealAnimations();
        initFab();
        initRefresh();

        if (typeof ApexCharts === 'undefined') {
            initCounters();
            return;
        }

        initCounters();
        initSparklines();
        renderWebsiteViews('week');
        renderStudentGrowth();
        renderTeacherPerformance();
        renderCourseEngagement();
        renderCategories();
        renderAiRecommendations();
        renderAiPageCharts();
        renderTestimonialCharts();
        renderProgressRings();
        initChartTabs();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
