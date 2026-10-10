(function () {
    let reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const cards = document.querySelectorAll('.card, .hero-step');
    // Make cards float in once they're in scroll view
    if ('IntersectionObserver' in window && !reduce) {
        let io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });
        cards.forEach(function (el) {
            el.classList.add('reveal');
            io.observe(el);
        });
    }

    // JS logic for the accordions
    document.querySelectorAll('.accordion').forEach(function (button) {
        let panel = button.nextElementSibling;
        if (!panel) {
            return;
        }
        const hints = panel.querySelectorAll('details.hint');
        if (!hints.length) {
            return;
        }
        const badge = document.createElement('span');
        badge.className = 'hint-badge';
        button.appendChild(badge);

        // update the icon if a hint is opened or not
        function update() {
            const opened = panel.querySelectorAll('details.hint[open]').length;
            badge.textContent = opened + '/' + hints.length + ' hints';
            badge.classList.toggle('hint-badge-used', opened > 0);
        }
        hints.forEach(function (d) {
            d.addEventListener('toggle', update);
        });
        update();
    });
}());
