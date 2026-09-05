// Blade ya renderizó los eventos reales. Este script solo cambia el paso visible.
(() => {
    function initialize(root) {
        if (root.dataset.demoReady === 'true') return;

        const steps = Array.from(root.querySelectorAll('[data-demo-step]'));
        const controls = root.querySelector('[data-demo-controls]');
        if (!steps.length || !controls) return;

        const previous = root.querySelector('[data-demo-previous]');
        const next = root.querySelector('[data-demo-next]');
        const reset = root.querySelector('[data-demo-reset]');
        const status = root.querySelector('[data-demo-status]');
        let current = 0;

        function show(index) {
            current = Math.max(0, Math.min(index, steps.length - 1));
            steps.forEach((step, position) => { step.hidden = position !== current; });
            previous.disabled = current === 0;
            next.disabled = current === steps.length - 1;
            reset.disabled = current === 0;

            const event = steps[current].dataset;
            status.textContent = `Paso ${Number(event.demoStep) + 1} de ${steps.length} · ${event.demoLabel} · Comparaciones: ${event.demoComparisons}`;
        }

        previous.addEventListener('click', () => show(current - 1));
        next.addEventListener('click', () => show(current + 1));
        reset.addEventListener('click', () => show(0));
        root.dataset.demoReady = 'true';
        show(0);
        controls.hidden = false;
    }

    function initializeAll() {
        document.querySelectorAll('[data-algorithm-demo]').forEach(initialize);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeAll, { once: true });
    } else {
        initializeAll();
    }
})();
