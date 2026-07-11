document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.course-tab');
    const panels = document.querySelectorAll('.course-tab-panel');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tab;

            tabs.forEach((t) => t.classList.remove('is-active'));
            panels.forEach((p) => p.classList.remove('is-active'));

            tab.classList.add('is-active');
            const panel = document.getElementById(`tab-${target}`);
            if (panel) {
                panel.classList.add('is-active');
            }
        });
    });

    document.querySelectorAll('[data-accordion]').forEach((accordion) => {
        const trigger = accordion.querySelector('.course-accordion__trigger');
        if (!trigger) {
            return;
        }

        trigger.addEventListener('click', () => {
            accordion.classList.toggle('is-open');
        });
    });
});
