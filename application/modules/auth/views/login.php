<!doctype html>
<html lang="<?= app_language() ?>" data-app-language="<?= app_language() ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#050914">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= html_escape(app_setting('isp_name', 'ISP BATARA NET')) ?>">
    <meta name="app-base-url" content="<?= base_url() ?>">
    <title><?= html_escape($title) ?></title>
    <link rel="icon" href="<?= base_url(app_setting('logo_path', 'assets/img/logo.jpeg')) ?>">
    <link rel="apple-touch-icon" href="<?= base_url(app_setting('logo_path', 'assets/img/logo.jpeg')) ?>">
    <link rel="manifest" href="<?= site_url('pwa/manifest') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>

<body class="auth-page">
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

            <form method="post" action="<?= site_url('login/attempt') ?>">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required autofocus>
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

    <div class="auth-language-switch language-switch"><a href="<?= site_url('language/id?return=' . rawurlencode(current_url())) ?>" class="<?= app_language() === 'id' ? 'is-active' : '' ?>">IN</a><a href="<?= site_url('language/en?return=' . rawurlencode(current_url())) ?>" class="<?= app_language() === 'en' ? 'is-active' : '' ?>">EN</a></div>

    <div class="app-loader is-active" id="appLoader" aria-live="polite" aria-label="Loading">
        <div class="app-loader-panel">
            <div class="app-loader-ring" aria-hidden="true"></div>
            <strong>Memuat...</strong>
            <span>Mohon tunggu sebentar</span>
        </div>
    </div>
    <script src="<?= base_url('assets/js/i18n.js') ?>"></script>
    <script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>

</html>
