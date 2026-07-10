<?php
$splitterLoss = [
    'PLC' => [
        '1:2' => ['P1' => 3.8, 'P2' => 3.8],
        '1:4' => ['P1' => 7.2, 'P2' => 7.2, 'P3' => 7.2, 'P4' => 7.2],
        '1:8' => ['P1' => 10.5, 'P2' => 10.5, 'P3' => 10.5, 'P4' => 10.5, 'P5' => 10.5, 'P6' => 10.5, 'P7' => 10.5, 'P8' => 10.5],
        '1:16' => array_fill_keys(array_map(function ($i) {
            return 'P' . $i;
        }, range(1, 16)), 13.8),
    ],

    'FBT Unequal' => [
        '99:1'  => ['99%' => 0.2, '1%' => 20.5],
        '95:5'  => ['95%' => 0.3, '5%' => 13.8],
        '90:10' => ['90%' => 0.5, '10%' => 10.5],
        '80:20' => ['80%' => 1.0, '20%' => 7.4],
        '70:30' => ['70%' => 1.6, '30%' => 5.8],
        '60:40' => ['60%' => 2.3, '40%' => 4.4],
        '50:50' => ['50% A' => 3.6, '50% B' => 3.6],
    ]
];

$result = null;
$steps = [];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {

    $txPower        = isset($_POST['tx_power']) ? floatval($_POST['tx_power']) : 4;
    $fiberKm        = isset($_POST['fiber']) ? floatval($_POST['fiber']) : 0;
    $attenuation    = isset($_POST['attenuation']) ? floatval($_POST['attenuation']) : 0.35;
    $connectorQty   = isset($_POST['connector']) ? intval($_POST['connector']) : 0;
    $connectorLoss  = isset($_POST['connector_loss']) ? floatval($_POST['connector_loss']) : 0.2;
    $spliceQty      = isset($_POST['splice']) ? intval($_POST['splice']) : 0;
    $spliceLossVal  = isset($_POST['splice_loss']) ? floatval($_POST['splice_loss']) : 0.05;
    $margin         = isset($_POST['margin']) ? floatval($_POST['margin']) : 0;

    $currentPower = $txPower;
    $totalLoss = 0;

    $steps[] = [
        'device' => 'Tx Power OLT',
        'loss' => 0,
        'output' => $currentPower
    ];

    $fiberLoss = $fiberKm * $attenuation;
    $currentPower -= $fiberLoss;
    $totalLoss += $fiberLoss;

    $steps[] = [
        'device' => 'Fiber Cable (' . $fiberKm . ' Km)',
        'loss' => $fiberLoss,
        'output' => $currentPower
    ];

    $connLoss = $connectorQty * $connectorLoss;
    $currentPower -= $connLoss;
    $totalLoss += $connLoss;

    $steps[] = [
        'device' => 'Connector (' . $connectorQty . ' pcs)',
        'loss' => $connLoss,
        'output' => $currentPower
    ];

    $spliceLoss = $spliceQty * $spliceLossVal;
    $currentPower -= $spliceLoss;
    $totalLoss += $spliceLoss;

    $steps[] = [
        'device' => 'Fusion Splice (' . $spliceQty . ' titik)',
        'loss' => $spliceLoss,
        'output' => $currentPower
    ];

    $types  = isset($_POST['splitter_type']) ? $_POST['splitter_type'] : [];
    $ratios = isset($_POST['splitter_ratio']) ? $_POST['splitter_ratio'] : [];
    $ports  = isset($_POST['splitter_port']) ? $_POST['splitter_port'] : [];

    if (!is_array($types)) {
        $types = [];
    }

    if (!is_array($ratios)) {
        $ratios = [];
    }

    if (!is_array($ports)) {
        $ports = [];
    }

    foreach ($types as $i => $type) {

        $ratio = isset($ratios[$i]) ? $ratios[$i] : '';
        $port  = isset($ports[$i]) ? $ports[$i] : '';

        if ($type == '' || $ratio == '' || $port == '') {
            continue;
        }

        if (isset($splitterLoss[$type][$ratio][$port])) {

            $loss = $splitterLoss[$type][$ratio][$port];

            $currentPower -= $loss;
            $totalLoss += $loss;

            $steps[] = [
                'device' => 'Splitter ' . ($i + 1) . ' - ' . $type . ' ' . $ratio . ' / Output ' . $port,
                'loss' => $loss,
                'output' => $currentPower
            ];
        }
    }

    $totalLossWithMargin = $totalLoss + $margin;
    $rxPower = $txPower - $totalLossWithMargin;

    if ($rxPower >= -8) {
        $status = 'Terlalu Kuat';
        $color = 'warning';
    } elseif ($rxPower >= -28) {
        $status = 'Normal';
        $color = 'success';
    } else {
        $status = 'Terlalu Lemah';
        $color = 'danger';
    }

    $result = [
        'txPower' => $txPower,
        'totalLoss' => $totalLossWithMargin,
        'rxPower' => $rxPower,
        'status' => $status,
        'color' => $color
    ];
}
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Calculator Redaman - ISP BATARA NET</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="calculator-page">

    <div class="container py-4">

        <div class="page-toolbar mb-3">
            <a href="../index.php" class="back-button" aria-label="Kembali ke menu">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Kembali</span>
            </a>

            <div class="toolbar-orbit" aria-hidden="true"></div>
        </div>

        <div class="card glass-card shadow-sm mb-4 calculator-shell">
            <div class="card-header glass-header calculator-header">
                <div class="brand-bar compact-brand">
                    <img src="../assets/img/logo.jpeg" alt="ISP BATARA NET" class="brand-logo brand-logo-sm">
                    <div>
                        <div class="header-kicker">ISP BATARA NET</div>
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
                            <input type="number" step="0.01" name="tx_power" class="form-control calc-input" value="<?= $_POST['tx_power'] ?? '4' ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Panjang Fiber (Km)</label>
                            <input type="number" step="0.01" name="fiber" class="form-control calc-input" value="<?= $_POST['fiber'] ?? '5' ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Redaman Fiber (dB/Km)</label>
                            <input type="number" step="0.01" name="attenuation" class="form-control calc-input" value="<?= $_POST['attenuation'] ?? '0.35' ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Engineering Margin (dB)</label>
                            <input type="number" step="0.01" name="margin" class="form-control calc-input" value="<?= $_POST['margin'] ?? '2' ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Jumlah Connector</label>
                            <input type="number" name="connector" class="form-control calc-input" value="<?= $_POST['connector'] ?? '2' ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Loss / Connector (dB)</label>
                            <input type="number" step="0.01" name="connector_loss" class="form-control calc-input" value="<?= $_POST['connector_loss'] ?? '0.2' ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Jumlah Fusion Splice</label>
                            <input type="number" name="splice" class="form-control calc-input" value="<?= $_POST['splice'] ?? '8' ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Loss / Splice (dB)</label>
                            <input type="number" step="0.01" name="splice_loss" class="form-control calc-input" value="<?= $_POST['splice_loss'] ?? '0.05' ?>">
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
                                        <?php foreach ($splitterLoss as $type => $data): ?>
                                            <option value="<?= $type ?>">
                                                <?= $type ?>
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

    <script id="splitter-loss-data" type="application/json"><?= json_encode($splitterLoss, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
    <script src="../assets/js/calculator.js"></script>

</body>

</html>








