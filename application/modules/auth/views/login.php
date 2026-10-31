<?php $en = app_language() === 'en'; $t = function ($id, $english) use ($en) { return $en ? $english : $id; }; ?>
<main class="auth-shell auth-welcome">
    <div class="auth-orb auth-orb-blue" aria-hidden="true"></div><div class="auth-orb auth-orb-peach" aria-hidden="true"></div>
    <div class="auth-welcome-layout">
        <section class="auth-intro">
            <span class="auth-intro-kicker"><span></span><?= $t('RUANG KERJA ISP ANDA','YOUR ISP WORKSPACE') ?></span>
            <h2><?= $t('Jaringan terhubung.<br>Bisnis terkendali.','Connected networks.<br>Business in control.') ?></h2>
            <p><?= $t('Dari pelanggan hingga pembayaran, kelola operasional internet Anda dalam satu tempat.','From customers to payments, manage your internet operations in one place.') ?></p>
            <div class="auth-network-art" aria-hidden="true"><div class="auth-network-orbit orbit-one"></div><div class="auth-network-orbit orbit-two"></div><div class="auth-network-core"><i class="fa-solid fa-tower-broadcast"></i></div><span class="auth-network-node node-users"><i class="fa-solid fa-users"></i></span><span class="auth-network-node node-wallet"><i class="fa-solid fa-wallet"></i></span><span class="auth-network-node node-router"><i class="fa-solid fa-network-wired"></i></span><span class="auth-network-caption"><?= $t('Satu koneksi. Banyak kemungkinan.','One connection. More possibilities.') ?></span></div>
            <div class="auth-feature-list"><span><i class="fa-solid fa-users"></i><?= $t('Pelanggan','Customers') ?></span><span><i class="fa-solid fa-chart-line"></i><?= $t('Monitoring','Monitoring') ?></span><span><i class="fa-solid fa-wallet"></i><?= $t('Pembayaran','Payments') ?></span></div>
        </section>
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

            <div class="auth-form-heading"><span class="auth-form-eyebrow"><?= $t('SELAMAT DATANG KEMBALI','WELCOME BACK') ?></span><h1><?= $t('Masuk ke ruang kerja','Sign in to your workspace') ?></h1><p><?= $t('Gunakan akun Anda untuk melanjutkan.','Use your account to continue.') ?></p></div>

            <?php if (!empty($error)): ?><div class="app-flash-message" data-type="error" data-message="<?= html_escape($error) ?>" hidden></div><?php endif; ?>
            <?php if (!empty($success)): ?><div class="app-flash-message" data-type="success" data-message="<?= html_escape($success) ?>" hidden></div><?php endif; ?>

            <form method="post" action="<?= site_url('login/attempt') ?>">
                <div class="mb-3">
                    <label class="form-label" for="loginUsername">Username</label>
                    <div class="auth-input-wrap"><i class="fa-regular fa-user" aria-hidden="true"></i><input id="loginUsername" type="text" name="username" class="form-control" required autocomplete="username" placeholder="<?= $t('Masukkan username','Enter username') ?>" autocapitalize="none" spellcheck="false"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="loginPassword">Password</label>
                    <div class="auth-input-wrap"><i class="fa-solid fa-lock" aria-hidden="true"></i><input id="loginPassword" type="password" name="password" class="form-control" required autocomplete="current-password" placeholder="<?= $t('Masukkan password','Enter password') ?>"><button type="button" class="auth-password-toggle" data-login-password-toggle aria-label="<?= $t('Tampilkan password','Show password') ?>" aria-pressed="false"><i class="fa-regular fa-eye" aria-hidden="true"></i></button></div>
                </div>

                <button type="submit" class="auth-login-submit">
                    <span><?= $t('Masuk','Sign in') ?></span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>
            </form>
            <div class="auth-login-note"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span><?= $t('Akses khusus pengelola dan tim operasional.','For administrators and operations teams.') ?></span></div>
        </section>
    </div>
    </main>

    <div class="auth-appearance-switch"><button type="button" class="theme-switch" data-theme-toggle aria-label="Ganti tema"><i class="fa-solid fa-sun theme-icon-light"></i><i class="fa-solid fa-moon theme-icon-dark"></i><span class="visually-hidden" data-theme-label>Tema</span></button><div class="auth-language-switch language-switch"><a href="<?= site_url('language/id?return=' . rawurlencode(current_url())) ?>" class="<?= app_language() === 'id' ? 'is-active' : '' ?>">IN</a><a href="<?= site_url('language/en?return=' . rawurlencode(current_url())) ?>" class="<?= app_language() === 'en' ? 'is-active' : '' ?>">EN</a></div></div>

