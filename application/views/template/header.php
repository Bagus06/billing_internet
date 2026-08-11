<?php
defined('BASEPATH') or exit('No direct script access allowed');
$authUser = $this->session->userdata('auth_user');
$authPermissions = $this->session->userdata('auth_permissions') ?: [];
$hasAccess = function ($key) use ($authPermissions) { return in_array($key,$authPermissions,true); };
$activeMenu = (string) $this->uri->segment(1);
?>
<header class="app-topbar">
    <a href="<?= site_url('/') ?>" class="app-brand">
        <img src="<?= base_url(app_setting('logo_path', 'assets/img/logo.jpeg')) ?>" alt="<?= html_escape(app_setting('isp_name', 'ISP BATARA NET')) ?>">
        <span><strong><?= html_escape(app_setting('isp_name', 'ISP BATARA NET')) ?></strong><small><?= html_escape(app_setting('app_subtitle', 'Mikrotik Network Tools')) ?></small></span>
    </a>
    <nav class="app-nav" aria-label="Menu utama">
        <?php if ($hasAccess('home')): ?><a href="<?= site_url('/') ?>" class="app-home-link"><i class="fa-solid fa-house"></i><span>Home</span></a><?php endif; ?>
        <details class="app-menu-dropdown"><summary><i class="fa-solid fa-table-cells-large"></i><span>Menu</span><i class="fa-solid fa-chevron-down menu-chevron"></i></summary>
            <div class="app-menu-panel">
                <?php if ($hasAccess('customers') || $hasAccess('monitoring')): ?><div class="app-menu-section"><span>Operasional</span></div><?php endif; ?>
                <?php if ($hasAccess('monitoring')): ?><a class="<?= $activeMenu === 'monitoring' ? 'is-active' : '' ?>" href="<?= site_url('monitoring') ?>"><i class="fa-solid fa-chart-line"></i><span><strong>Monitoring</strong><small>Status & trafik pelanggan</small></span></a><?php endif; ?>
                <?php if ($hasAccess('customers')): ?><a class="<?= $activeMenu === 'customers' ? 'is-active' : '' ?>" href="<?= site_url('customers') ?>"><i class="fa-solid fa-users"></i><span><strong>Pelanggan</strong><small>Data & layanan pelanggan</small></span></a><?php endif; ?>
                <?php if ($hasAccess('packages') || $hasAccess('payments') || $hasAccess('financial_reports')): ?><div class="app-menu-section"><span>Billing & Layanan</span></div><?php endif; ?>
                <?php if ($hasAccess('packages')): ?><a class="<?= $activeMenu === 'packages' ? 'is-active' : '' ?>" href="<?= site_url('packages') ?>"><i class="fa-solid fa-box-open"></i><span><strong>Paket Internet</strong><small>Harga & relasi profile</small></span></a><?php endif; ?>
                <?php if ($hasAccess('payments')): ?><a class="<?= $activeMenu === 'payments' ? 'is-active' : '' ?>" href="<?= site_url('payments') ?>"><i class="fa-solid fa-wallet"></i><span><strong>Pembayaran</strong><small>Tagihan & transaksi</small></span></a><?php endif; ?>
                <?php if ($hasAccess('financial_reports') || $hasAccess('payments')): ?><a class="<?= $activeMenu === 'financial-reports' ? 'is-active' : '' ?>" href="<?= site_url('financial-reports') ?>"><i class="fa-solid fa-chart-column"></i><span><strong>Laporan Keuangan</strong><small>Penghasilan bulanan & potongan PSB</small></span></a><?php endif; ?>
                <?php if ($hasAccess('payments')): ?><a class="<?= $activeMenu === 'profit-sharing' ? 'is-active' : '' ?>" href="<?= site_url('profit-sharing') ?>"><i class="fa-solid fa-handshake"></i><span><strong>Bagi Hasil</strong><small>Distribusi laba bersih mitra</small></span></a><?php endif; ?>
                <?php if ($hasAccess('routers') || $hasAccess('calculator')): ?><div class="app-menu-section"><span>Network</span></div><?php endif; ?>
                <?php if ($hasAccess('routers')): ?><a class="<?= $activeMenu === 'routers' ? 'is-active' : '' ?>" href="<?= site_url('routers') ?>"><i class="fa-solid fa-server"></i><span><strong>Router MikroTik</strong><small>Koneksi & API RouterOS</small></span></a><a class="<?= $activeMenu === 'mikrotik-profiles' ? 'is-active' : '' ?>" href="<?= site_url('mikrotik-profiles') ?>"><i class="fa-solid fa-gauge-high"></i><span><strong>PPP Profile</strong><small>Bandwidth profile RouterOS</small></span></a><?php endif; ?>
                <?php if ($hasAccess('calculator')): ?><a class="<?= $activeMenu === 'calculator' ? 'is-active' : '' ?>" href="<?= site_url('calculator') ?>"><i class="fa-solid fa-calculator"></i><span><strong>Kalkulator</strong><small>Perhitungan jaringan</small></span></a><?php endif; ?>
                <?php if ($hasAccess('users') || $hasAccess('roles') || $hasAccess('settings')): ?><div class="app-menu-section"><span>Administrasi</span></div><?php endif; ?>
                <?php if ($hasAccess('users')): ?><a class="<?= $activeMenu === 'users' ? 'is-active' : '' ?>" href="<?= site_url('users') ?>"><i class="fa-solid fa-user-gear"></i><span><strong>Pengguna</strong><small>Akun operator aplikasi</small></span></a><?php endif; ?>
                <?php if ($hasAccess('roles')): ?><a class="<?= $activeMenu === 'roles' ? 'is-active' : '' ?>" href="<?= site_url('roles') ?>"><i class="fa-solid fa-shield-halved"></i><span><strong>Role & Akses</strong><small>Hak akses pengguna</small></span></a><?php endif; ?>
                <?php if ($hasAccess('settings')): ?><a class="<?= $activeMenu === 'settings' ? 'is-active' : '' ?>" href="<?= site_url('settings') ?>"><i class="fa-solid fa-gears"></i><span><strong>Konfigurasi</strong><small>Identitas & parameter sistem</small></span></a><?php endif; ?>
                <div class="app-menu-divider"></div>
                <?php if ($hasAccess('profile')): ?><a class="<?= $activeMenu === 'profile' ? 'is-active' : '' ?>" href="<?= site_url('profile') ?>"><i class="fa-solid fa-circle-user"></i><span><strong><?= html_escape($authUser['name'] ?? 'Profile') ?></strong><small>Profile akun saya</small></span></a><?php endif; ?>
                <?php if ($authUser): ?><a href="<?= site_url('logout') ?>" class="is-logout"><i class="fa-solid fa-right-from-bracket"></i><span><strong>Keluar</strong><small>Akhiri sesi aplikasi</small></span></a><?php endif; ?>
            </div>
        </details>
    </nav>
    <div class="topbar-right">
        <div class="topbar-module-slot" data-topbar-module="<?= html_escape($module_name ?? '') ?>">
            <?php foreach (($topbar_elements ?? []) as $element): ?>
                <?php
                $elementView = is_array($element) ? ($element['view'] ?? '') : (string) $element;
                $elementData = is_array($element) && isset($element['data']) && is_array($element['data']) ? $element['data'] : [];
                if ($elementView === '' || strpos($elementView, '..') !== false) continue;
                $this->load->view($elementView, array_merge($elementData, ['module_name' => $module_name ?? '']));
                ?>
            <?php endforeach; ?>
        </div>
        <button type="button" class="theme-switch" data-theme-toggle aria-label="Ganti tema" title="Ganti tema">
            <i class="fa-solid fa-sun theme-icon-light" aria-hidden="true"></i>
            <i class="fa-solid fa-moon theme-icon-dark" aria-hidden="true"></i>
            <span class="visually-hidden" data-theme-label>Tema</span>
        </button>
        <div class="language-switch" aria-label="Language switcher"><a href="<?= site_url('language/id?return=' . rawurlencode(current_url())) ?>" class="<?= app_language() === 'id' ? 'is-active' : '' ?>" title="Bahasa Indonesia">IN</a><a href="<?= site_url('language/en?return=' . rawurlencode(current_url())) ?>" class="<?= app_language() === 'en' ? 'is-active' : '' ?>" title="English">EN</a></div>
    </div>
</header>
