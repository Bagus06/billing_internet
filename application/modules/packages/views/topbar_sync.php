<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (!empty($sync_status)): ?>
<details class="topbar-sync-dropdown">
    <summary class="topbar-sync-status <?= !empty($sync_status['success']) ? 'is-success' : 'is-warning' ?>">
        <span class="topbar-sync-dot" aria-hidden="true"></span>
        <span><strong>Sync</strong> <?= html_escape($sync_status['message'] ?? '') ?></span>
        <i class="fa-solid fa-chevron-down"></i>
    </summary>
    <div class="topbar-sync-panel">
        <div class="sync-panel-note"><i class="fa-solid fa-circle-info"></i><span>Dropdown hanya menampilkan data yang gagal disinkronkan beserta penyebabnya.</span></div>
        <?php foreach (['profiles' => ['Profile', 'fa-sliders'], 'secrets' => ['PPP Secret', 'fa-key']] as $key => $meta): $group = $sync_status[$key] ?? ['success' => [], 'failed' => []]; ?>
            <section class="sync-result-group">
                <h6><i class="fa-solid <?= $meta[1] ?>"></i><?= $meta[0] ?></h6>
                <?php if (empty($group['failed'])): ?><p class="sync-result-empty"><i class="fa-solid fa-circle-check me-1"></i>Tidak ada kegagalan.</p><?php endif; ?>
                <?php foreach ($group['failed'] as $item): ?><div class="sync-result-item is-failed"><i class="fa-solid fa-circle-xmark"></i><span><?= html_escape($item) ?></span></div><?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </div>
</details>
<?php endif; ?>
