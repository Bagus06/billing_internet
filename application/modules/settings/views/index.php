<?php $value = function ($key, $default = '') use ($settings) { return isset($settings[$key]) ? $settings[$key] : $default; }; ?>
<main class="container py-4 py-md-5">
    <div class="page-toolbar mb-3"><a href="<?= site_url('/') ?>" class="back-button"><i class="fa-solid fa-arrow-left"></i><span>Kembali</span></a></div>
    <section class="menu-shell monitoring-shell">
        <div class="menu-heading"><h1>Konfigurasi Aplikasi</h1><p>Kelola identitas ISP, perilaku monitoring, dan parameter umum aplikasi.</p></div>
        <?php $this->load->view('template/flash'); ?>
        <form method="post" action="<?= site_url('settings/update') ?>" enctype="multipart/form-data" class="settings-form mt-3">
            <div class="card glass-card mb-3"><div class="card-header glass-header"><i class="fa-solid fa-palette me-2"></i>Branding</div><div class="card-body"><div class="row g-3">
                <div class="col-md-6"><label class="form-label">Nama ISP</label><input class="form-control" name="isp_name" value="<?= html_escape($value('isp_name', 'ISP BATARA NET')) ?>" required><div class="form-text">Ditampilkan pada header, login, dan judul aplikasi.</div></div>
                <div class="col-md-6"><label class="form-label">Subtitle Aplikasi</label><input class="form-control" name="app_subtitle" value="<?= html_escape($value('app_subtitle', 'Mikrotik Network Tools')) ?>"></div>
                <div class="col-md-6"><label class="form-label">Logo ISP</label><input type="file" class="form-control" name="logo" accept="image/png,image/jpeg,image/webp,image/gif"><div class="form-text">Maksimal 2 MB. JPG, PNG, WebP, atau GIF.</div></div>
                <div class="col-md-6"><label class="form-label">Teks Footer</label><input class="form-control" name="footer_text" value="<?= html_escape($value('footer_text', 'ISP BATARA NET')) ?>"></div>
            </div></div></div>
            <div class="card glass-card mb-3"><div class="card-header glass-header"><i class="fa-solid fa-handshake me-2"></i>Bagi Hasil</div><div class="card-body"><div class="row g-3">
                <div class="col-md-4"><label class="form-label">Nama Pihak Pertama</label><input class="form-control" name="profit_party_1_name" value="<?= html_escape($value('profit_party_1_name', 'Pihak Pertama')) ?>"></div>
                <div class="col-md-2"><label class="form-label">Persentase Pihak Pertama</label><input type="number" min="0" max="100" step="0.01" class="form-control" name="profit_party_1_percent" value="<?= html_escape($value('profit_party_1_percent', 75)) ?>"></div>
                <div class="col-md-4"><label class="form-label">Nama Pihak Kedua</label><input class="form-control" name="profit_party_2_name" value="<?= html_escape($value('profit_party_2_name', 'Pihak Kedua')) ?>"></div>
                <div class="col-md-2"><label class="form-label">Persentase Pihak Kedua</label><input type="number" min="0" max="100" step="0.01" class="form-control" name="profit_party_2_percent" value="<?= html_escape($value('profit_party_2_percent', 14)) ?>"></div>
                <div class="col-12"><div class="form-text">Sisa persentase otomatis dihitung sebagai saldo/cadangan perusahaan. Total pihak pertama dan kedua tidak boleh melebihi 100%.</div></div>
            </div></div></div>
            <div class="card glass-card mb-3"><div class="card-header glass-header"><i class="fa-solid fa-chart-line me-2"></i>Monitoring</div><div class="card-body"><div class="row g-3">
                <div class="col-md-4"><label class="form-label">Refresh Data Utama (detik)</label><input type="number" min="10" max="300" class="form-control" name="monitoring_refresh_seconds" value="<?= (int) $value('monitoring_refresh_seconds', 30) ?>"><div class="form-text">Secret, active session, pelanggan, dan optical.</div></div>
                <div class="col-md-4"><label class="form-label">Refresh Traffic (detik)</label><input type="number" min="2" max="60" class="form-control" name="traffic_refresh_seconds" value="<?= (int) $value('traffic_refresh_seconds', 3) ?>"></div>
                <div class="col-md-4"><label class="form-label">Suffix Username PPPoE</label><input class="form-control" name="pppoe_username_suffix" value="<?= html_escape($value('pppoe_username_suffix', '@BATARA.net')) ?>"><div class="form-text">Contoh: @BATARA.net</div></div>
                <div class="col-md-4"><label class="form-label">Batas Sinyal Normal (dBm)</label><input type="number" step="0.01" class="form-control" name="signal_normal_min" value="<?= html_escape($value('signal_normal_min', -25)) ?>"></div>
                <div class="col-md-4"><label class="form-label">Batas Sinyal Warning (dBm)</label><input type="number" step="0.01" class="form-control" name="signal_warning_min" value="<?= html_escape($value('signal_warning_min', -28)) ?>"></div>
            </div></div></div>
            <div class="card glass-card mb-3"><div class="card-header glass-header"><i class="fa-solid fa-sliders me-2"></i>Aplikasi</div><div class="card-body"><div class="row g-3">
                <div class="col-md-6"><label class="form-label">Default Data per Halaman</label><input type="number" min="5" max="100" class="form-control" name="default_per_page" value="<?= (int) $value('default_per_page', 10) ?>"></div>
                <div class="col-md-6"><label class="form-label">Timezone</label><input class="form-control" name="timezone" value="<?= html_escape($value('timezone', 'Asia/Jakarta')) ?>"><div class="form-text">Gunakan identifier timezone PHP, misalnya Asia/Jakarta.</div></div>
                <div class="col-md-6"><label class="form-label">Bahasa Default</label><select class="form-select" name="default_language"><option value="id" <?= $value('default_language', 'id') === 'id' ? 'selected' : '' ?>>Indonesia (IN)</option><option value="en" <?= $value('default_language', 'id') === 'en' ? 'selected' : '' ?>>English (EN)</option></select><div class="form-text">Digunakan jika pengguna belum memilih bahasa melalui top bar.</div></div>
                <div class="col-md-6"><label class="form-label">Tema Default</label><select class="form-select" name="default_theme"><option value="dark" <?= $value('default_theme', 'dark') === 'dark' ? 'selected' : '' ?>>Dark</option><option value="light" <?= $value('default_theme', 'dark') === 'light' ? 'selected' : '' ?>>Light</option></select><div class="form-text">Digunakan jika perangkat belum memiliki preferensi tema.</div></div>
            </div></div></div>
            <button type="submit" class="back-button border-0"><i class="fa-solid fa-floppy-disk"></i><span>Simpan Konfigurasi</span></button>
        </form>
    </section>
</main>
