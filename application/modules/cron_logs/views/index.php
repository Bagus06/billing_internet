<main class="container py-4 py-md-5">
    <div class="page-toolbar mb-3">
        <a href="<?= site_url('/') ?>" class="back-button"><i class="fa-solid fa-arrow-left"></i><span>Kembali</span></a>
        <div class="toolbar-actions"><a href="<?= site_url('cron-logs') ?>" class="back-button" data-no-loader><i class="fa-solid fa-rotate"></i><span>Refresh Log</span></a></div>
    </div>
    <section class="menu-shell monitoring-shell">
        <div class="menu-heading"><h1>Log Cron</h1><p>Pantau jadwal, task, durasi, serta hasil proses berkala aplikasi.</p></div>
        <p class="form-text mt-3">Hanya jadwal yang memiliki task yang dicatat. Log harian disimpan 30 hari; log per jam dan 5 menit disimpan 7 hari. Pembersihan berjalan otomatis melalui cron harian.</p>
        <?php $this->load->view('template/flash'); ?>
        <?php $this->load->view('_log_list', ['cron_log_available' => $cron_log_available, 'cron_runs' => $cron_runs]); ?>
    </section>
</main>
