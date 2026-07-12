<main class="container py-4 py-md-5">
    <div class="page-toolbar mb-3">
        <a href="<?= site_url('/') ?>" class="back-button" aria-label="Kembali ke menu">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali</span>
        </a>

        <div class="toolbar-orbit" aria-hidden="true"></div>
    </div>

    <section
        class="menu-shell monitoring-shell"
        id="monitoringApp"
        data-summary-url="<?= site_url('monitoring/summary' . (!empty($router) ? '/' . $router['id'] : '')) ?>"
        data-sessions-url="<?= site_url('monitoring/sessions' . (!empty($router) ? '/' . $router['id'] : '')) ?>"
        data-disconnect-url="<?= site_url('monitoring/disconnect') ?>"
        data-connect-url="<?= site_url('monitoring/connect') ?>"
        data-traffic-url="<?= site_url('monitoring/traffic' . (!empty($router) ? '/' . $router['id'] : '')) ?>"
        data-refresh-seconds="<?= (int) app_setting('monitoring_refresh_seconds', 30) ?>"
        data-traffic-seconds="<?= (int) app_setting('traffic_refresh_seconds', 3) ?>"
    >
        <div class="brand-bar">
            <img src="<?= base_url(app_setting('logo_path', 'assets/img/logo.jpeg')) ?>" alt="<?= html_escape(app_setting('isp_name', 'ISP BATARA NET')) ?>" class="brand-logo">
            <div>
                <div class="menu-eyebrow">
                    <i class="fa-solid fa-chart-line me-2"></i>
                    <?= html_escape(app_setting('isp_name', 'ISP BATARA NET')) ?>
                </div>
                <div class="brand-subtitle"><?= !empty($router) ? html_escape($router['name'] . ' - ' . $router['host']) : 'Mikrotik API Monitoring' ?></div>
            </div>
        </div>

        <div class="menu-heading">
            <h1><?= !empty($router) ? html_escape($router['name']) : 'Monitoring Mikrotik' ?></h1>
            <p>Status pelanggan PPPoE dengan username <strong>@BATARA.net</strong>, terhubung ke data pelanggan berdasarkan NIK.</p>
        </div>

        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <div class="glass-panel result-box">
                    <div class="text-muted">PPPoE ON</div>
                    <h3 id="activeSessionCount">0</h3>
                </div>
            </div>

            <div class="col-md-4">
                <div class="glass-panel result-box">
                    <div class="text-muted">PPPoE OFF</div>
                    <h3 id="offlineSessionCount">0</h3>
                </div>
            </div>

            <div class="col-md-4">
                <div class="glass-panel result-box">
                    <div class="text-muted">Total Secret</div>
                    <h3 id="totalSecretCount">0</h3>
                </div>
            </div>
        </div>

        <div class="small text-muted mt-2" id="monitoringMessage">
            Menghubungkan ke Mikrotik API...
        </div>

        <div class="monitoring-filter-bar mt-3">
            <div class="monitoring-search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" id="pppoeSearch" class="form-control" placeholder="Cari nama, NIK, ID atau kode pelanggan..." autocomplete="off">
            </div>
            <select id="pppoeSort" class="form-select monitoring-sort" aria-label="Urutkan pelanggan">
                <option value="status">Status: ON dahulu</option>
                <option value="name_asc">Nama: A–Z</option>
                <option value="name_desc">Nama: Z–A</option>
                <option value="nik_asc">NIK: terkecil</option>
                <option value="id_asc">ID pelanggan: terkecil</option>
                <option value="id_desc">ID pelanggan: terbesar</option>
                <option value="signal_strong">Sinyal: terkuat</option>
                <option value="signal_weak">Sinyal: terlemah</option>
            </select>
            <span class="monitoring-result-count" id="pppoeResultCount">0 pelanggan</span>
        </div>

        <div class="card glass-card shadow-sm mt-3">
            <div class="card-header glass-header">
                <i class="fa-solid fa-list-check me-2"></i>
                Status Pelanggan PPPoE
            </div>
            <div class="card-body">
                <div class="pppoe-card-grid" id="pppoeSessionCards">
                    <div class="pppoe-empty text-muted">Memuat data PPPoE...</div>
                </div>
            </div>
        </div>
    </section>
</main>
