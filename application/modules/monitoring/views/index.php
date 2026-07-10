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
    >
        <div class="brand-bar">
            <img src="<?= base_url('assets/img/logo.jpeg') ?>" alt="ISP BATARA NET" class="brand-logo">
            <div>
                <div class="menu-eyebrow">
                    <i class="fa-solid fa-chart-line me-2"></i>
                    ISP BATARA NET
                </div>
                <div class="brand-subtitle"><?= !empty($router) ? html_escape($router['name'] . ' - ' . $router['host']) : 'Mikrotik API Monitoring' ?></div>
            </div>
        </div>

        <div class="menu-heading">
            <h1><?= !empty($router) ? html_escape($router['name']) : 'Monitoring Mikrotik' ?></h1>
            <p>Dashboard awal untuk status router, resource, dan PPPoE active sessions.</p>
        </div>

        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <div class="glass-panel result-box">
                    <div class="text-muted">Router Online</div>
                    <h3 id="routerOnlineCount">0</h3>
                </div>
            </div>

            <div class="col-md-4">
                <div class="glass-panel result-box">
                    <div class="text-muted">Active PPPoE</div>
                    <h3 id="activeSessionCount">0</h3>
                </div>
            </div>

            <div class="col-md-4">
                <div class="glass-panel result-box">
                    <div class="text-muted">Polling</div>
                    <h3 id="pollingStatus">30s</h3>
                </div>
            </div>
        </div>

        <div class="small text-muted mt-2" id="monitoringMessage">
            Menghubungkan ke Mikrotik API...
        </div>

        <div class="card glass-card shadow-sm mt-3">
            <div class="card-header glass-header">
                <i class="fa-solid fa-microchip me-2"></i>
                Resource Mikrotik
            </div>

            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Router</th>
                            <th>Host</th>
                            <th>Status</th>
                            <th>Uptime</th>
                            <th class="text-end">CPU Load</th>
                            <th class="text-end">Free Memory</th>
                        </tr>
                    </thead>
                    <tbody id="routerResourceRows">
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                Memuat resource Mikrotik...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card glass-card shadow-sm mt-3">
            <div class="card-header glass-header">
                <i class="fa-solid fa-list-check me-2"></i>
                PPPoE Active Sessions
            </div>

            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>IP Address</th>
                            <th>Caller ID</th>
                            <th>Uptime</th>
                            <th>Router</th>
                            <th>Status</th>
                            <th width="130">Action</th>
                        </tr>
                    </thead>
                    <tbody id="pppoeSessionRows">
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                Memuat data PPPoE active sessions...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
