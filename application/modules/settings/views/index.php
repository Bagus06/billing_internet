<?php
$value = function ($key, $default = '') use ($settings) { return isset($settings[$key]) ? $settings[$key] : $default; };
$oltHost = trim((string) $value('olt_snmp_host', '')) ?: ($_ENV['OLT_SNMP_HOST'] ?? '');
$oltCommunity = trim((string) $value('olt_snmp_community', '')) ?: ($_ENV['OLT_SNMP_COMMUNITY'] ?? '');
?>
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
                <div class="col-md-3"><label class="form-label">Warna Utama</label><input type="color" class="form-control form-control-color w-100" name="brand_primary_color" value="<?= html_escape($value('brand_primary_color', '#1687ff')) ?>"></div>
                <div class="col-md-3"><label class="form-label">Warna Latar PWA</label><input type="color" class="form-control form-control-color w-100" name="brand_background_color" value="<?= html_escape($value('brand_background_color', '#050914')) ?>"></div>
                <div class="col-12"><label class="form-label">Deskripsi Aplikasi PWA</label><textarea class="form-control" name="pwa_description" rows="2" maxlength="300"><?= html_escape($value('pwa_description', 'Aplikasi billing, pelanggan, pembayaran, dan operasi jaringan ISP.')) ?></textarea></div>
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
                <div class="col-md-4"><label class="form-label">Cache Data OLT (detik)</label><input type="number" min="30" max="600" class="form-control" name="olt_cache_seconds" value="<?= (int) $value('olt_cache_seconds', 60) ?>"><div class="form-text">Mencegah SNMP walk berulang saat halaman monitoring dimuat ulang.</div></div>
                <div class="col-md-4"><label class="form-label">Polling Status Router (detik)</label><input type="number" min="3" max="300" class="form-control" name="router_status_refresh_seconds" value="<?= max(3, min(300, (int) $value('router_status_refresh_seconds', 3))) ?>"><div class="form-text">Minimum 3 detik untuk membatasi beban API MikroTik.</div></div>
                <div class="col-md-4"><label class="form-label">Suffix Username PPPoE</label><input class="form-control" name="pppoe_username_suffix" value="<?= html_escape($value('pppoe_username_suffix', '@BATARA.net')) ?>"><div class="form-text">Contoh: @BATARA.net</div></div>
                <div class="col-md-4"><label class="form-label">Batas Sinyal Normal (dBm)</label><input type="number" step="0.01" class="form-control" name="signal_normal_min" value="<?= html_escape($value('signal_normal_min', -25)) ?>"></div>
                <div class="col-md-4"><label class="form-label">Batas Sinyal Warning (dBm)</label><input type="number" step="0.01" class="form-control" name="signal_warning_min" value="<?= html_escape($value('signal_warning_min', -28)) ?>"></div>
            </div></div></div>
            <div class="card glass-card mb-3"><div class="card-header glass-header"><i class="fa-solid fa-network-wired me-2"></i>OLT &amp; SNMP</div><div class="card-body"><div class="row g-3">
                <div class="col-md-5"><label class="form-label">Host OLT</label><input class="form-control" name="olt_snmp_host" value="<?= html_escape($oltHost) ?>" placeholder="192.168.99.1"></div>
                <div class="col-md-2"><label class="form-label">Port UDP</label><input type="number" min="1" max="65535" class="form-control" name="olt_snmp_port" value="<?= (int) $value('olt_snmp_port', ($_ENV['OLT_SNMP_PORT'] ?? 161)) ?>"></div>
                <div class="col-md-2"><label class="form-label">Versi SNMP</label><select class="form-select" name="olt_snmp_version"><option value="1" <?= $value('olt_snmp_version', '1') === '1' ? 'selected' : '' ?>>v1</option><option value="2c" <?= $value('olt_snmp_version', '1') === '2c' ? 'selected' : '' ?>>v2c</option></select></div>
                <div class="col-md-3"><label class="form-label">Community</label><input type="password" class="form-control" name="olt_snmp_community" value="<?= html_escape($oltCommunity) ?>" autocomplete="new-password"></div>
                <div class="col-12"><div class="form-text">Konfigurasi ini hanya digunakan oleh ISP aktif. Tenant baru tidak pernah mewarisi host atau community BATARA.</div></div>
            </div></div></div>
            <div class="card glass-card mb-3"><div class="card-header glass-header"><i class="fa-solid fa-user-lock me-2"></i>Sistem Isolir</div><div class="card-body"><div class="row g-3">
                <div class="col-12"><div class="form-check form-switch"><input type="checkbox" class="form-check-input" id="isolationEnabled" name="isolation_enabled" value="1" <?= in_array(strtolower((string) $value('isolation_enabled', '0')), ['1','true','yes','on'], true) ? 'checked' : '' ?>><label class="form-check-label" for="isolationEnabled">Aktifkan isolir otomatis melalui cron</label></div><div class="form-text">Aktifkan setelah profile ISOLIR tersedia di seluruh router yang digunakan pelanggan.</div></div>
                <div class="col-md-3"><label class="form-label">Profile Isolir</label><input class="form-control" name="isolation_profile_name" value="<?= html_escape($value('isolation_profile_name', 'ISOLIR')) ?>" required></div>
                <div class="col-md-3"><label class="form-label">Jatuh Tempo Kelompok 1</label><input type="number" min="1" max="28" class="form-control" name="isolation_group_1_due_day" value="<?= (int) $value('isolation_group_1_due_day', 10) ?>" required></div>
                <div class="col-md-3"><label class="form-label">Jatuh Tempo Kelompok 2</label><input type="number" min="1" max="28" class="form-control" name="isolation_group_2_due_day" value="<?= (int) $value('isolation_group_2_due_day', 25) ?>" required></div>
                <div class="col-md-3"><label class="form-label">Toleransi (hari)</label><input type="number" min="0" max="15" class="form-control" name="isolation_grace_days" value="<?= (int) $value('isolation_grace_days', 5) ?>" required></div>
                <div class="col-12"><label class="form-label">Token Scheduler Cron</label><input class="form-control" name="cron_token" value="<?= html_escape($value('cron_token', $value('isolation_cron_token', ''))) ?>" autocomplete="off" required><div class="form-text">Satu token untuk seluruh jadwal. Endpoint: <?= html_escape(site_url('cron/5-minutes')) ?>, <?= html_escape(site_url('cron/hourly')) ?>, dan <?= html_escape(site_url('cron/daily')) ?>. Rahasiakan token ini.</div></div>
            </div></div></div>
            <div class="card glass-card mb-3"><div class="card-header glass-header"><i class="fa-solid fa-sliders me-2"></i>Aplikasi</div><div class="card-body"><div class="row g-3">
                <div class="col-md-6"><label class="form-label">Default Data per Halaman</label><input type="number" min="5" max="100" class="form-control" name="default_per_page" value="<?= (int) $value('default_per_page', 10) ?>"></div>
                <div class="col-md-6"><label class="form-label">Timezone</label><input class="form-control" name="timezone" value="<?= html_escape($value('timezone', 'Asia/Jakarta')) ?>"><div class="form-text">Gunakan identifier timezone PHP, misalnya Asia/Jakarta.</div></div>
                <div class="col-md-6"><label class="form-label">Bahasa Default</label><select class="form-select" name="default_language"><option value="id" <?= $value('default_language', 'id') === 'id' ? 'selected' : '' ?>>Indonesia (IN)</option><option value="en" <?= $value('default_language', 'id') === 'en' ? 'selected' : '' ?>>English (EN)</option></select><div class="form-text">Digunakan jika pengguna belum memilih bahasa melalui top bar.</div></div>
                <div class="col-md-6"><label class="form-label">Tema Default</label><select class="form-select" name="default_theme"><option value="dark" <?= $value('default_theme', 'dark') === 'dark' ? 'selected' : '' ?>>Dark</option><option value="light" <?= $value('default_theme', 'dark') === 'light' ? 'selected' : '' ?>>Light</option></select><div class="form-text">Digunakan jika perangkat belum memiliki preferensi tema.</div></div>
            </div></div></div>
            <button type="submit" class="back-button border-0"><i class="fa-solid fa-floppy-disk"></i><span>Simpan Konfigurasi</span></button>
        </form>
        <div class="card glass-card mt-4 mb-3">
            <div class="card-header glass-header d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <span><i class="fa-solid fa-database me-2"></i>Database Migration</span>
                <?php if ((int) $migration_status['modified'] > 0): ?>
                    <span class="badge text-bg-danger">File berubah</span>
                <?php elseif ((int) $migration_status['pending'] > 0): ?>
                    <span class="badge text-bg-warning"><?= (int) $migration_status['pending'] ?> pending</span>
                <?php else: ?>
                    <span class="badge text-bg-success">Database terbaru</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <p class="mb-2">Mendeteksi file SQL baru di folder <code>DB</code> dan hanya menjalankan migration yang belum pernah diterapkan.</p>
                <div class="form-text mb-3">Data migration dicatat berdasarkan nama file dan checksum SHA-256. File yang sudah diterapkan tidak boleh diedit; buat file bertanggal baru untuk perubahan berikutnya.</div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge rounded-pill text-bg-primary">Total <?= (int) $migration_status['total'] ?></span>
                    <span class="badge rounded-pill text-bg-warning">Pending <?= (int) $migration_status['pending'] ?></span>
                    <span class="badge rounded-pill text-bg-danger">Berubah <?= (int) $migration_status['modified'] ?></span>
                </div>
                <div class="table-responsive mb-3" style="max-height:260px;overflow-y:auto">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>File migration</th><th>Status</th><th>Diterapkan</th></tr></thead>
                        <tbody>
                        <?php foreach (array_reverse($migration_status['items']) as $migration): ?>
                            <tr>
                                <td><code><?= html_escape($migration['name']) ?></code></td>
                                <td><?php if ($migration['status'] === 'applied'): ?><span class="badge text-bg-success">Applied</span><?php elseif ($migration['status'] === 'pending'): ?><span class="badge text-bg-warning">Pending</span><?php else: ?><span class="badge text-bg-danger">Modified</span><?php endif; ?></td>
                                <td><?= $migration['applied_at'] ? html_escape($migration['applied_at']) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <form method="post" action="<?= site_url('settings/migrations/run') ?>" class="migration-run-form">
                    <label class="form-label" for="migrationConfirmation">Konfirmasi</label>
                    <div class="migration-run-controls">
                        <input id="migrationConfirmation" class="form-control" name="migration_confirmation" placeholder="Ketik JALANKAN MIGRATION" autocomplete="off" required>
                        <button type="submit" class="back-button border-0 justify-content-center migration-run-button" <?= ((int) $migration_status['pending'] < 1 || (int) $migration_status['modified'] > 0) ? 'disabled' : '' ?> data-confirm="Jalankan seluruh database migration yang masih pending?"><i class="fa-solid fa-rotate"></i><span>Jalankan Migration</span></button>
                    </div>
                    <div class="form-text migration-run-help"><i class="fa-solid fa-shield-halved"></i><span>Operasi DROP TABLE, TRUNCATE, DELETE, dan ALTER TABLE DROP otomatis ditolak.</span></div>
                </form>
            </div>
        </div>
    </section>
</main>
