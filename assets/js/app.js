(function () {
    const loader = document.getElementById('appLoader');
    let hideTimer = null;

    if (!loader) {
        return;
    }

    function showLoader() {
        window.clearTimeout(hideTimer);
        loader.classList.add('is-active');
    }

    function hideLoader(delay) {
        window.clearTimeout(hideTimer);
        hideTimer = window.setTimeout(function () {
            loader.classList.remove('is-active');
        }, delay || 160);
    }

    window.AppLoader = {
        show: showLoader,
        hide: hideLoader
    };

    window.addEventListener('load', function () {
        hideLoader(220);
    });

    document.addEventListener('submit', function (event) {
        const form = event.target;

        if (form && !form.hasAttribute('data-no-loader')) {
            showLoader();
        }
    }, true);

    document.addEventListener('click', function (event) {
        const target = event.target.closest('a, button');

        if (!target || target.hasAttribute('data-no-loader')) {
            return;
        }

        if (target.matches('[data-ktp-close], [data-payment-close], .customer-ktp-button, .customer-pay-button')) {
            return;
        }

        if (target.tagName === 'A') {
            const href = target.getAttribute('href') || '';

            if (!href || href === '#' || href.indexOf('javascript:') === 0 || target.target === '_blank') {
                return;
            }

            showLoader();
            return;
        }

        if (target.type === 'submit' || target.classList.contains('btn-disconnect-session')) {
            showLoader();
        }
    }, true);

    const originalFetch = window.fetch;

    if (typeof originalFetch === 'function') {
        window.fetch = function () {
            const options = arguments[1] || {};
            const silent = options.loader === false;

            if (!silent) {
                showLoader();
            }

            return originalFetch.apply(this, arguments)
                .finally(function () {
                    if (!silent) {
                        hideLoader(180);
                    }
                });
        };
    }
})();
