<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($title) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>

<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-card">
            <div class="brand-bar">
                <img src="<?= base_url('assets/img/logo.jpeg') ?>" alt="ISP BATARA NET" class="brand-logo">
                <div>
                    <div class="menu-eyebrow">
                        <i class="fa-solid fa-shield-halved me-2"></i>
                        ISP BATARA NET
                    </div>
                    <div class="brand-subtitle">Login Aplikasi</div>
                </div>
            </div>

            <h1>Masuk</h1>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2"><?= html_escape($error) ?></div>
            <?php endif; ?>

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

    <div class="app-loader is-active" id="appLoader" aria-live="polite" aria-label="Loading">
        <div class="app-loader-panel">
            <div class="app-loader-ring" aria-hidden="true"></div>
            <strong>Memuat...</strong>
            <span>Mohon tunggu sebentar</span>
        </div>
    </div>
    <script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>

</html>
