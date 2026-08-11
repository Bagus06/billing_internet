<?php
$resource = $device['resource'] ?? [];
$identity = $device['identity'] ?? [];
$board = $device['routerboard'] ?? [];
$health = $device['health'] ?? [];
$interfaces = $device['interfaces'] ?? [];
$bytes = function ($value) {
    $value = (float) $value;
    if ($value <= 0) return '-';
    $units = ['B','KB','MB','GB','TB']; $index = 0;
    while ($value >= 1024 && $index < count($units) - 1) { $value /= 1024; $index++; }
    return number_format($value, $index ? 1 : 0, ',', '.') . ' ' . $units[$index];
};
$percent = function ($used, $total) { return $total > 0 ? max(0, min(100, round(($used / $total) * 100))) : 0; };
$totalMemory = (float) ($resource['total-memory'] ?? 0); $freeMemory = (float) ($resource['free-memory'] ?? 0); $memoryPercent = $percent($totalMemory - $freeMemory, $totalMemory);
$totalDisk = (float) ($resource['total-hdd-space'] ?? 0); $freeDisk = (float) ($resource['free-hdd-space'] ?? 0); $diskPercent = $percent($totalDisk - $freeDisk, $totalDisk);
$cpuLoad = max(0, min(100, (int) ($resource['cpu-load'] ?? 0)));
$runningInterfaces = 0; $enabledInterfaces = 0;
foreach ($interfaces as $interface) { if (($interface['disabled'] ?? 'false') !== 'true') $enabledInterfaces++; if (($interface['running'] ?? 'false') === 'true') $runningInterfaces++; }
$temperature = $health['temperature'] ?? ($health['cpu-temperature'] ?? '-');
$parseUptime = function ($value) {
    $parts = ['w'=>0,'d'=>0,'h'=>0,'m'=>0,'s'=>0];
    preg_match_all('/(\d+)([wdhms])/', strtolower((string) $value), $matches, PREG_SET_ORDER);
    foreach ($matches as $match) $parts[$match[2]] = (int) $match[1];
    $seconds = $parts['w']*604800+$parts['d']*86400+$parts['h']*3600+$parts['m']*60+$parts['s'];
    $short = implode(' ', array_filter([$parts['w']?$parts['w'].'W':'',$parts['d']?$parts['d'].'D':'',$parts['h']?$parts['h'].'H':'',$parts['m']?$parts['m'].'M':''])) ?: '0M';
    $detail = $parts['w'].' minggu · '.$parts['d'].' hari · '.$parts['h'].' jam · '.$parts['m'].' menit';
    return ['short'=>$short,'detail'=>$detail,'since'=>$seconds ? date('d M Y, H:i', time()-$seconds) : '-'];
};
$uptimeInfo = $parseUptime($resource['uptime'] ?? '');
$largestFile = null;
foreach (($device['files'] ?? []) as $file) {
    $size = isset($file['size']) && is_numeric($file['size']) ? (float) $file['size'] : 0;
    if ($size > 0 && (!$largestFile || $size > $largestFile['size'])) $largestFile = ['name' => (string) ($file['name'] ?? 'File'), 'size' => $size];
}
?>
<main class="container py-4 py-md-5" data-router-status data-status-url="<?= site_url('routers/status-data/' . (int) $router['id']) ?>" data-poll-seconds="<?= max(3, min(300, (int) app_setting('router_status_refresh_seconds', 3))) ?>">
    <div class="page-toolbar mb-3"><a href="<?= site_url('routers') ?>" class="back-button"><i class="fa-solid fa-arrow-left"></i><span>Kembali</span></a><div class="toolbar-actions"><a href="<?= site_url('routers/edit/' . (int) $router['id']) ?>" class="back-button"><i class="fa-solid fa-pen-to-square"></i><span>Edit Router</span></a><a href="<?= site_url('routers/status/' . (int) $router['id']) ?>" class="back-button"><i class="fa-solid fa-rotate"></i><span>Refresh</span></a></div></div>
    <section class="menu-shell monitoring-shell">
        <div class="menu-heading"><h1>Status Perangkat</h1><p>Resource, identitas sistem, dan kondisi operasional MikroTik secara langsung.</p></div>
        <div class="router-status-hero mt-3">
            <div class="router-status-identity"><div class="router-status-icon"><i class="fa-solid fa-server"></i></div><div><h2><?= html_escape($identity['name'] ?? $router['name']) ?></h2><p><?= html_escape($router['host']) ?>:<?= (int) $router['port'] ?> · <?= html_escape($resource['board-name'] ?? 'MikroTik RouterOS') ?></p></div></div>
            <div class="router-status-state <?= $connection_error ? 'is-error' : '' ?>" data-status-state><i class="fa-solid <?= $connection_error ? 'fa-circle-xmark' : 'fa-circle-check' ?>"></i><span><?= $connection_error ? 'Tidak Terhubung' : 'Terhubung' ?></span><i class="fa-solid fa-arrows-rotate router-status-sync" data-status-sync title="Sinkronisasi status"></i></div>
        </div>
        <?php if ($connection_error): ?><div class="router-error-box" data-status-error><i class="fa-solid fa-triangle-exclamation me-2"></i><?= html_escape($connection_error) ?></div><?php endif; ?>
        <div class="router-metric-grid">
            <div class="router-metric router-history-card"><div class="router-history-head"><div><span><i class="fa-solid fa-microchip"></i> CPU Load</span><strong data-status-cpu><?= $cpuLoad ?>%</strong></div><small>Riwayat sesi halaman</small></div><canvas class="router-history-chart" data-history-chart="cpu" data-initial-value="<?= $cpuLoad ?>" aria-label="Grafik riwayat CPU"></canvas></div>
            <div class="router-metric router-history-card"><div class="router-history-head"><div><span><i class="fa-solid fa-memory"></i> Penggunaan Memori</span><strong data-status-memory><?= $memoryPercent ?>% · <?= $bytes($totalMemory - $freeMemory) ?></strong></div><small>Total <?= $bytes($totalMemory) ?></small></div><canvas class="router-history-chart" data-history-chart="memory" data-initial-value="<?= $memoryPercent ?>" aria-label="Grafik riwayat memori"></canvas></div>
            <div class="router-metric router-history-card"><div class="router-history-head"><div><span><i class="fa-solid fa-hard-drive"></i> Penggunaan Storage</span><strong data-status-storage><?= $diskPercent ?>% · <?= $bytes($totalDisk - $freeDisk) ?></strong></div><small class="router-metric-note"><?= $largestFile ? 'File terbesar: ' . html_escape($largestFile['name']) . ' (' . $bytes($largestFile['size']) . ')' : 'Rincian file tidak tersedia' ?></small></div><canvas class="router-history-chart" data-history-chart="storage" data-initial-value="<?= $diskPercent ?>" aria-label="Grafik riwayat storage"></canvas></div>
            <div class="router-metric router-uptime-card"><div class="router-uptime-head"><span><i class="fa-solid fa-clock"></i> Uptime Perangkat</span><i class="fa-solid fa-power-off"></i></div><strong data-status-uptime data-status-uptime-main><?= html_escape($uptimeInfo['short']) ?></strong><small data-status-uptime-detail><?= html_escape($uptimeInfo['detail']) ?></small><div class="router-uptime-since"><span>Estimasi aktif sejak</span><b data-status-uptime-since><?= html_escape($uptimeInfo['since']) ?></b></div></div>
            <div class="router-metric"><span><i class="fa-solid fa-network-wired"></i> Interface Berjalan</span><strong data-status-interfaces><?= $runningInterfaces ?> / <?= $enabledInterfaces ?></strong></div>
            <div class="router-metric"><span><i class="fa-solid fa-users"></i> PPP Aktif</span><strong data-status-sessions><?= (int) ($device['active_sessions'] ?? 0) ?> sesi</strong></div>
            <div class="router-metric"><span><i class="fa-solid fa-key"></i> Total PPP Secret</span><strong><?= (int) ($device['total_secrets'] ?? 0) ?></strong></div>
            <div class="router-metric"><span><i class="fa-solid fa-temperature-half"></i> Temperatur</span><strong data-status-temperature><?= html_escape($temperature) ?><?= is_numeric($temperature) ? ' °C' : '' ?></strong></div>
        </div>
        <div class="router-info-grid">
            <div class="glass-panel router-info-card"><h3><i class="fa-solid fa-circle-info me-2 text-info"></i>Informasi Sistem</h3><div class="router-info-list"><div><span>RouterOS</span><strong><?= html_escape($resource['version'] ?? '-') ?></strong></div><div><span>Platform</span><strong><?= html_escape($resource['platform'] ?? '-') ?></strong></div><div><span>Arsitektur</span><strong><?= html_escape($resource['architecture-name'] ?? '-') ?></strong></div><div><span>CPU</span><strong><?= html_escape($resource['cpu'] ?? '-') ?> · <?= html_escape($resource['cpu-count'] ?? '-') ?> core</strong></div><div><span>Frekuensi CPU</span><strong><?= html_escape($resource['cpu-frequency'] ?? '-') ?> MHz</strong></div><div><span>Build Time</span><strong><?= html_escape($resource['build-time'] ?? '-') ?></strong></div></div></div>
            <div class="glass-panel router-info-card"><h3><i class="fa-solid fa-box me-2 text-info"></i>RouterBOARD</h3><div class="router-info-list"><div><span>Model Board</span><strong><?= html_escape($resource['board-name'] ?? '-') ?></strong></div><div><span>Serial Number</span><strong><?= html_escape($board['serial-number'] ?? '-') ?></strong></div><div><span>Firmware Saat Ini</span><strong><?= html_escape($board['current-firmware'] ?? '-') ?></strong></div><div><span>Firmware Upgrade</span><strong><?= html_escape($board['upgrade-firmware'] ?? '-') ?></strong></div><div><span>Factory Firmware</span><strong><?= html_escape($board['factory-firmware'] ?? ($resource['factory-software'] ?? '-')) ?></strong></div><div><span>Voltage</span><strong><?= html_escape($health['voltage'] ?? '-') ?></strong></div></div></div>
        </div>
    </section>
</main>
