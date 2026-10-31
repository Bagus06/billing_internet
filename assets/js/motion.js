(function () {
    'use strict';
    const preference = window.matchMedia('(prefers-reduced-motion: reduce)');
    let observer;
    function initialize() {
        if (preference.matches || !('IntersectionObserver' in window)) return;
        observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('liquid-revealed');
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.05, rootMargin: '0px 0px 24px 0px' });
        document.querySelectorAll('.glass-card, .profit-summary-card, .customer-profile-card, .cron-run-item').forEach(function (element) {
            // Only animate new content below the fold; visible controls remain immediately usable.
            if (element.getBoundingClientRect().top < window.innerHeight) return;
            element.classList.add('liquid-scroll-reveal');
            observer.observe(element);
        });
    }
    function revealAll() {
        if (observer) observer.disconnect();
        document.querySelectorAll('.liquid-scroll-reveal').forEach(function (element) { element.classList.add('liquid-revealed'); });
    }
    if (preference.addEventListener) preference.addEventListener('change', function () { if (preference.matches) revealAll(); });
    document.addEventListener('focusin', function (event) {
        const container = event.target.closest('.liquid-scroll-reveal');
        if (container) container.classList.add('liquid-revealed');
    });
    window.addEventListener('beforeprint', revealAll);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();
})();
