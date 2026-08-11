<div class="container py-4">
    <div class="page-toolbar mb-3">
        <a href="<?= site_url('/') ?>" class="back-button" aria-label="Kembali ke menu">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali</span>
        </a>

        <div class="toolbar-orbit" aria-hidden="true"></div>
    </div>

    <div class="card glass-card shadow-sm mb-4 calculator-shell">
        <div class="card-header glass-header calculator-header">
            <div class="brand-bar compact-brand">
                <img src="<?= base_url(app_setting('logo_path', 'assets/img/default-isp-logo.svg')) ?>" alt="<?= html_escape(app_setting('isp_name', 'ISP Billing')) ?>" class="brand-logo brand-logo-sm">
                <div>
                    <div class="header-kicker"><?= html_escape(app_setting('isp_name', 'ISP Billing')) ?></div>
                    <h4 class="mb-0">
                        <i class="fa-solid fa-network-wired me-2"></i>
                        Calculator Redaman
                    </h4>
                </div>
            </div>
            <div class="header-pulse" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>

        <div class="card-body">
            <form method="post">
                <div class="primary-inputs-panel">
                    <div class="primary-inputs-summary">
                        <span><i class="fa-solid fa-sliders me-2"></i>Parameter Link</span>
                        <small>Hover untuk membuka input Tx Power, fiber, connector, splice, dan margin.</small>
                    </div>

                    <div class="row g-3 primary-inputs-grid">
                        <div class="col-md-3">
                            <label class="form-label">Tx Power OLT (dBm)</label>
                            <input type="number" step="0.01" name="tx_power" class="form-control calc-input" value="4">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Panjang Fiber (Km)</label>
                            <input type="number" step="0.01" name="fiber" class="form-control calc-input" value="5">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Redaman Fiber (dB/Km)</label>
                            <input type="number" step="0.01" name="attenuation" class="form-control calc-input" value="0.35">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Engineering Margin (dB)</label>
                            <input type="number" step="0.01" name="margin" class="form-control calc-input" value="2">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Jumlah Connector</label>
                            <input type="number" name="connector" class="form-control calc-input" value="2">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Loss / Connector (dB)</label>
                            <input type="number" step="0.01" name="connector_loss" class="form-control calc-input" value="0.2">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Jumlah Fusion Splice</label>
                            <input type="number" name="splice" class="form-control calc-input" value="8">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Loss / Splice (dB)</label>
                            <input type="number" step="0.01" name="splice_loss" class="form-control calc-input" value="0.05">
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="mb-3">
                    <i class="fa-solid fa-code-branch me-2 text-primary"></i>
                    Splitter Cascade
                </h5>

                <div class="row g-3">
                    <?php for ($i = 0; $i < 4; $i++): ?>
                        <div class="col-md-3">
                            <div class="splitter-card p-3">
                                <label class="form-label">Splitter <?= $i + 1 ?></label>

                                <select name="splitter_type[]" class="form-select splitter-type mb-2">
                                    <option value="">None</option>
                                    <?php foreach ($splitter_loss as $type => $data): ?>
                                        <option value="<?= html_escape($type) ?>">
                                            <?= html_escape($type) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <select name="splitter_ratio[]" class="form-select splitter-ratio mb-2">
                                    <option value="">Ratio</option>
                                </select>

                                <select name="splitter_port[]" class="form-select splitter-port mb-2">
                                    <option value="">Output Port</option>
                                </select>

                                <div class="small text-muted splitter-loss-info">
                                    Loss: -
                                </div>

                                <div class="small splitter-output-info">
                                    Output Laser: -
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="mt-2 text-muted small">
                    <i class="fa-solid fa-bolt me-1 text-primary"></i>
                    Kalkulasi berjalan otomatis setiap input berubah.
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4" id="liveResultCards">
        <div class="col-md-4">
            <div class="glass-panel shadow-sm result-box">
                <div class="text-muted">Tx Power</div>
                <h3><span id="resultTxPower">0.00</span> dBm</h3>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-panel shadow-sm result-box">
                <div class="text-muted">Total Loss</div>
                <h3><span id="resultTotalLoss">0.00</span> dB</h3>
            </div>
        </div>

        <div class="col-md-4">
            <div class="rx-card rx-ideal shadow-sm result-box" id="rxResultBox">
                <div>Rx Power ONT</div>
                <h3><span id="resultRxPower">0.00</span> dBm</h3>
                <strong id="resultStatus">Normal</strong>
            </div>
        </div>
    </div>

    <div class="card glass-card shadow-sm">
        <div class="card-header glass-header">
            <i class="fa-solid fa-list-check me-2"></i>
            Detail Output Setiap Tahap
        </div>

        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th width="60">No</th>
                        <th>Device / Tahapan</th>
                        <th width="150" class="text-end">Loss (dB)</th>
                        <th width="180" class="text-end">Output Power (dBm)</th>
                    </tr>
                </thead>

                <tbody id="detailStepsBody"></tbody>
            </table>
        </div>
    </div>
</div>

<script id="splitter-loss-data" type="application/json"><?= json_encode($splitter_loss, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
