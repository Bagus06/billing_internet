(function () {
    document.querySelectorAll('.page-toolbar').forEach(function (toolbar) {
        if (toolbar.querySelector(':scope > .toolbar-actions')) return;
        const buttons = Array.from(toolbar.children).filter(function (child) {
            return child.classList && child.classList.contains('back-button');
        });
        if (buttons.length < 2) return;
        const actions = document.createElement('div');
        actions.className = 'toolbar-actions';
        buttons.slice(1).forEach(function (button) { actions.appendChild(button); });
        toolbar.appendChild(actions);
    });

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            const baseMeta = document.querySelector('meta[name="app-base-url"]');
            const baseUrl = baseMeta ? baseMeta.content : '/';
            navigator.serviceWorker.register(baseUrl + 'sw.js', { scope: baseUrl }).catch(function () {});
        });
    }

    const swalTheme = {
        customClass: {
            popup: 'app-swal-popup', title: 'app-swal-title', htmlContainer: 'app-swal-text',
            confirmButton: 'app-swal-confirm', cancelButton: 'app-swal-cancel', actions: 'app-swal-actions'
        },
        buttonsStyling: false,
        background: 'rgba(9, 18, 32, .92)',
        color: '#eaf6ff'
    };

    window.AppAlert = {
        fire: function (options) {
            if (typeof Swal === 'undefined') return Promise.resolve({ isConfirmed: true });
            if (typeof window.AppI18n === 'function' && options) {
                ['title', 'text', 'confirmButtonText', 'cancelButtonText'].forEach(function (key) {
                    if (typeof options[key] === 'string') options[key] = window.AppI18n(options[key]);
                });
            }
            return Swal.fire(Object.assign({}, swalTheme, options || {}));
        },
        notify: function (message, type) {
            return this.fire({
                icon: type || 'info', title: type === 'error' ? 'Terjadi Kesalahan' : type === 'success' ? 'Berhasil' : 'Informasi',
                text: message, confirmButtonText: 'Oke'
            });
        },
        confirm: function (message, options) {
            return this.fire(Object.assign({
                icon: 'question', title: 'Konfirmasi', text: message,
                showCancelButton: true, confirmButtonText: 'Ya, lanjutkan', cancelButtonText: 'Batal',
                reverseButtons: true, focusCancel: true
            }, options || {}));
        }
    };

    document.querySelectorAll('.app-flash-message').forEach(function (message) {
        window.AppAlert.notify(message.dataset.message || '', message.dataset.type || 'info');
    });

    document.addEventListener('click', function (event) {
        const target = event.target.closest('[data-confirm]');
        if (!target || target.dataset.confirmed === 'true') return;
        event.preventDefault();
        event.stopImmediatePropagation();
        window.AppAlert.confirm(target.dataset.confirm).then(function (result) {
            if (!result.isConfirmed) return;
            target.dataset.confirmed = 'true';
            if (target.tagName === 'A') window.location.href = target.href;
            else if (target.form) target.form.requestSubmit(target);
        });
    }, true);

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

    document.addEventListener('click', function (event) {
        document.querySelectorAll('.app-menu-dropdown[open]').forEach(function (menu) {
            if (!menu.contains(event.target)) menu.removeAttribute('open');
        });
        document.querySelectorAll('.topbar-sync-dropdown[open]').forEach(function (menu) {
            if (!menu.contains(event.target)) menu.removeAttribute('open');
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.app-menu-dropdown[open]').forEach(function (menu) {
                menu.removeAttribute('open');
            });
            document.querySelectorAll('.topbar-sync-dropdown[open]').forEach(function (menu) {
                menu.removeAttribute('open');
            });
        }
    });
})();
