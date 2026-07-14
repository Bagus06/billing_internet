(function () {
    const root = document.documentElement;
    const themeMeta = document.querySelector('meta[name="theme-color"]');

    function applyTheme(theme, persist) {
        const nextTheme = theme === 'light' ? 'light' : 'dark';
        root.dataset.appTheme = nextTheme;
        root.style.colorScheme = nextTheme;
        if (themeMeta) themeMeta.content = nextTheme === 'light' ? '#eef4fb' : '#050914';
        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            const nextLabel = nextTheme === 'dark' ? 'Aktifkan tema terang' : 'Aktifkan tema gelap';
            button.setAttribute('aria-label', nextLabel);
            button.setAttribute('title', nextLabel);
            button.setAttribute('aria-pressed', nextTheme === 'light' ? 'true' : 'false');
            const label = button.querySelector('[data-theme-label]');
            if (label) label.textContent = nextLabel;
        });
        if (persist) {
            try { window.localStorage.setItem('app_theme', nextTheme); } catch (error) {}
            document.cookie = 'app_theme=' + nextTheme + '; path=/; max-age=31536000; SameSite=Lax';
        }
        window.dispatchEvent(new CustomEvent('appthemechange', { detail: { theme: nextTheme } }));
    }

    let savedTheme = null;
    try { savedTheme = window.localStorage.getItem('app_theme'); } catch (error) {}
    const usesAccountTheme = root.dataset.themeSource === 'user';
    applyTheme(usesAccountTheme ? root.dataset.appTheme : (savedTheme || root.dataset.appTheme || root.dataset.defaultTheme || 'dark'), true);

    function saveAccountTheme(theme, previousTheme) {
        if (!usesAccountTheme || !root.dataset.themeSaveUrl) return;
        const formData = new FormData();
        formData.append('theme', theme);
        fetch(root.dataset.themeSaveUrl, {
            method: 'POST', body: formData, credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        }).then(function (response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        }).then(function (result) {
            if (result.success) return;
            throw new Error(result.message || 'Preferensi tema gagal disimpan.');
        }).catch(function (error) {
            applyTheme(previousTheme, true);
            if (window.AppAlert) window.AppAlert.notify(error.message || 'Preferensi tema gagal disimpan.', 'error');
        });
    }

    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('[data-theme-toggle]');
        if (!toggle) return;
        const previousTheme = root.dataset.appTheme === 'light' ? 'light' : 'dark';
        const nextTheme = previousTheme === 'dark' ? 'light' : 'dark';
        applyTheme(nextTheme, true);
        saveAccountTheme(nextTheme, previousTheme);
    });

    const mobileMenuQuery = window.matchMedia('(max-width: 767.98px)');
    const menuDropdowns = Array.from(document.querySelectorAll('.app-menu-dropdown'));

    function positionMobileMenu(dropdown) {
        if (!mobileMenuQuery.matches || !dropdown || !dropdown.open) return;
        const summary = dropdown.querySelector(':scope > summary');
        if (!summary) return;
        const rect = summary.getBoundingClientRect();
        const top = Math.max(8, Math.min(rect.bottom + 8, window.innerHeight - 150));
        dropdown.style.setProperty('--mobile-menu-top', top + 'px');
    }

    menuDropdowns.forEach(function (dropdown) {
        dropdown.addEventListener('toggle', function () {
            const topbar = dropdown.closest('.app-topbar');
            if (topbar) topbar.classList.toggle('is-menu-open', dropdown.open);
            if (dropdown.open) {
                menuDropdowns.forEach(function (other) { if (other !== dropdown) other.open = false; });
                positionMobileMenu(dropdown);
            }
        });
    });
    window.addEventListener('resize', function () {
        menuDropdowns.forEach(positionMobileMenu);
    }, { passive: true });
    window.addEventListener('orientationchange', function () {
        window.setTimeout(function () { menuDropdowns.forEach(positionMobileMenu); }, 100);
    }, { passive: true });

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
            const phrase = target.dataset.confirmPhrase;
            const submitAction = function () {
                target.dataset.confirmed = 'true';
                if (target.tagName === 'A') window.location.href = target.href;
                else if (target.form) target.form.requestSubmit(target);
            };
            if (!phrase) { submitAction(); return; }
            window.AppAlert.fire({
                icon: 'warning', title: 'Konfirmasi Terakhir',
                text: 'Ketik ' + phrase + ' untuk mengizinkan perubahan fisik pada konfigurasi MikroTik.',
                input: 'text', inputAttributes: { autocomplete: 'off', autocapitalize: 'characters' },
                showCancelButton: true, confirmButtonText: 'Konfirmasi Delete', cancelButtonText: 'Batal',
                preConfirm: function (value) {
                    if (String(value || '').trim().toUpperCase() !== phrase.toUpperCase()) {
                        Swal.showValidationMessage('Teks konfirmasi tidak sesuai.'); return false;
                    }
                    return value;
                }
            }).then(function (typed) {
                if (!typed.isConfirmed) return;
                const input = target.form && target.form.querySelector('[name="confirm_phrase"]');
                if (input) input.value = phrase;
                submitAction();
            });
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

    if (document.readyState === 'complete') hideLoader(0);
    else window.addEventListener('load', function () { hideLoader(220); });

    document.addEventListener('submit', function (event) {
        const form = event.target;

        if (form && !form.hasAttribute('data-no-loader')) {
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                hideLoader(0);
                return;
            }
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

        if (target.classList.contains('btn-disconnect-session')) {
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
