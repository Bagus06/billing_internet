<div class="card glass-card mt-3 mb-3" id="cron-log">
    <div class="card-header glass-header"><i class="fa-solid fa-clock-rotate-left me-2"></i>Riwayat Eksekusi</div>
    <div class="card-body">
        <p class="mb-2">Riwayat proses berkala yang dipanggil oleh cPanel, termasuk task isolir dan alasan kegagalannya.</p>
        <div class="form-text mb-3">Token cron tidak dicatat. Detail hasil disimpan di database agar tetap tersedia setelah proses cron selesai.</div>

        <?php if (empty($cron_log_available)): ?>
            <div class="cron-log-empty is-warning"><i class="fa-solid fa-triangle-exclamation"></i><span>Tabel log cron belum tersedia. Jalankan database migration terlebih dahulu.</span></div>
        <?php elseif (empty($cron_runs)): ?>
            <div class="cron-log-empty"><i class="fa-solid fa-clock"></i><span>Belum ada cron yang pernah dijalankan.</span></div>
        <?php else: ?>
            <div class="cron-log-list">
                <?php foreach ($cron_runs as $run): ?>
                    <?php
                    $status = strtoupper((string) ($run['status'] ?? 'UNKNOWN'));
                    $statusClass = $status === 'SUCCESS' ? 'is-success' : ($status === 'RUNNING' ? 'is-running' : 'is-failed');
                    $duration = isset($run['duration_ms']) && $run['duration_ms'] !== null ? number_format(((int) $run['duration_ms']) / 1000, 2, ',', '.') . ' detik' : '-';
                    $scheduleLabels = ['five_minutes' => 'Setiap 5 Menit', 'hourly' => 'Setiap Jam', 'daily' => 'Harian'];
                    $schedule = (string) ($run['schedule_name'] ?? '-');
                    ?>
                    <details class="cron-run-item <?= $statusClass ?>">
                        <summary>
                            <span class="cron-run-status"><i class="fa-solid <?= $status === 'SUCCESS' ? 'fa-circle-check' : ($status === 'RUNNING' ? 'fa-spinner fa-spin' : 'fa-circle-xmark') ?>"></i><?= html_escape($status) ?></span>
                            <span class="cron-run-main"><strong><?= html_escape($scheduleLabels[$schedule] ?? $schedule) ?></strong><small><?= html_escape($run['started_at'] ?? '-') ?></small></span>
                            <span class="cron-run-metric"><small>Durasi</small><strong><?= html_escape($duration) ?></strong></span>
                            <span class="cron-run-metric"><small>Task</small><strong><?= count($run['tasks']) ?></strong></span>
                            <i class="fa-solid fa-chevron-down cron-run-chevron"></i>
                        </summary>
                        <div class="cron-task-list">
                            <?php if (empty($run['tasks'])): ?>
                                <div class="cron-log-empty"><i class="fa-solid fa-minus"></i><span>Tidak ada task pada eksekusi ini.</span></div>
                            <?php else: ?>
                                <?php foreach ($run['tasks'] as $task): ?>
                                    <?php
                                    $taskStatus = strtoupper((string) ($task['status'] ?? 'UNKNOWN'));
                                    $result = $task['result'] ?? [];
                                    $isolated = isset($result['isolated']) ? (int) $result['isolated'] : null;
                                    $skipped = isset($result['skipped']) ? (int) $result['skipped'] : null;
                                    $failed = isset($result['failed']) ? (int) $result['failed'] : null;
                                    ?>
                                    <article class="cron-task-item">
                                        <header><span><i class="fa-solid <?= $taskStatus === 'SUCCESS' ? 'fa-check' : 'fa-xmark' ?>"></i><?= html_escape($task['task_key'] ?? '-') ?></span><b class="<?= $taskStatus === 'SUCCESS' ? 'is-success' : 'is-failed' ?>"><?= html_escape($taskStatus) ?></b></header>
                                        <p><?= html_escape($task['message'] ?: ($taskStatus === 'SUCCESS' ? 'Task selesai.' : 'Task gagal.')) ?></p>
                                        <?php if ($isolated !== null || $skipped !== null || $failed !== null): ?>
                                            <div class="cron-task-counters"><span>Berhasil <b><?= (int) $isolated ?></b></span><span>Dilewati <b><?= (int) $skipped ?></b></span><span>Gagal <b><?= (int) $failed ?></b></span></div>
                                        <?php endif; ?>
                                        <?php if (!empty($result['messages']) && is_array($result['messages'])): ?>
                                            <ul><?php foreach ($result['messages'] as $message): ?><li><?= html_escape($message) ?></li><?php endforeach; ?></ul>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
