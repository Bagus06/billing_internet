<main class="auth-shell">
        <section class="auth-card">
            <div class="brand-bar">
                <img src="<?= base_url(app_setting('logo_path', 'assets/img/logo.jpeg')) ?>" alt="<?= html_escape(app_setting('isp_name', 'ISP BATARA NET')) ?>" class="brand-logo">
                <div>
                    <div class="menu-eyebrow">
                        <i class="fa-solid fa-shield-halved me-2"></i>
                        <?= html_escape(app_setting('isp_name', 'ISP BATARA NET')) ?>
                    </div>
                    <div class="brand-subtitle"><?= html_escape(app_setting('app_subtitle', 'Login Aplikasi')) ?></div>
                </div>
            </div>

            <h1>Masuk</h1>

            <?php if (!empty($error)): ?><div class="app-flash-message" data-type="error" data-message="<?= html_escape($error) ?>" hidden></div><?php endif; ?>
            <?php if (!empty($success)): ?><div class="app-flash-message" data-type="success" data-message="<?= html_escape($success) ?>" hidden></div><?php endif; ?>

            <form method="post" action="<?= site_url('login/attempt') ?>">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required autofocus autocomplete="username">
                    <div class="form-text">Masukkan username akun aplikasi Anda.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="back-button border-0 w-100 justify-content-center">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>Login</span>
                </button>
            </form>
        </section>
    </main>

    <div class="auth-appearance-switch"><button type="button" class="theme-switch" data-theme-toggle aria-label="Ganti tema"><i class="fa-solid fa-sun theme-icon-light"></i><i class="fa-solid fa-moon theme-icon-dark"></i><span class="visually-hidden" data-theme-label>Tema</span></button><div class="auth-language-switch language-switch"><a href="<?= site_url('language/id?return=' . rawurlencode(current_url())) ?>" class="<?= app_language() === 'id' ? 'is-active' : '' ?>">IN</a><a href="<?= site_url('language/en?return=' . rawurlencode(current_url())) ?>" class="<?= app_language() === 'en' ? 'is-active' : '' ?>">EN</a></div></div>

