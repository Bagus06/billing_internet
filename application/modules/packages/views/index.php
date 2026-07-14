<main class="container py-4 py-md-5">
    <div class="page-toolbar mb-3">
        <a href="<?= site_url('/') ?>" class="back-button" aria-label="Kembali ke menu">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali</span>
        </a>
        <div class="toolbar-actions">
        <a href="<?= site_url('packages/create') ?>" class="back-button">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Paket</span>
        </a>
        <a href="<?= site_url('packages/sync-profiles') ?>" class="back-button" data-confirm="Sinkronkan seluruh parameter PPP Profile dari MikroTik?">
            <i class="fa-solid fa-rotate"></i><span>Sync Profile</span>
        </a>
        <a href="<?= site_url('packages/sync-secrets') ?>" class="back-button" data-confirm="Terapkan profile setiap paket ke seluruh PPP Secret pelanggan?">
            <i class="fa-solid fa-users-gear"></i><span>Sync Secret</span>
        </a>
        </div>
    </div>

    <section class="menu-shell monitoring-shell">
        <div class="brand-bar">
            <img src="<?= base_url('assets/img/logo.jpeg') ?>" alt="ISP BATARA NET" class="brand-logo">
            <div>
                <div class="menu-eyebrow">
                    <i class="fa-solid fa-wifi me-2"></i>
                    ISP BATARA NET
                </div>
                <div class="brand-subtitle">Paket Internet</div>
            </div>
        </div>

        <div class="menu-heading">
            <h1>Paket Internet</h1>
            <p>Kelola master paket dan harga yang digunakan pada data pelanggan.</p>
        </div>

        <?php $this->load->view('template/flash'); ?>

        <div class="card glass-card shadow-sm mt-3">
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped table-nowrap">
                    <thead>
                        <tr>
                            <th>Nama Paket</th>
                            <th class="text-end">Harga</th>
                            <th>Router</th>
                            <th>PPP Profile</th>
                            <th>Rate Limit</th>
                            <th>Remote Pool</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                            <th width="170">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($packages)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">Belum ada paket internet.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($packages as $package): ?>
                            <tr>
                                <td><?= html_escape($package['package_name']) ?></td>
                                <td class="text-end">Rp <?= number_format((float) $package['price'], 0, ',', '.') ?></td>
                                <td><?= html_escape($package['router_name'] ?: '-') ?></td>
                                <td>
                                    <?= html_escape($package['ppp_profile_name'] ?: '-') ?>
                                    <?php if (!empty($package['ppp_profile_key'])): ?><small class="d-block text-muted"><?= html_escape($package['ppp_profile_key']) ?></small><?php endif; ?>
                                </td>
                                <td><?= html_escape($package['ppp_rate_limit'] ?: '-') ?></td>
                                <td><?= html_escape($package['ppp_remote_address'] ?: '-') ?></td>
                                <td>
                                    <span class="monitoring-badge <?= !empty($package['is_active']) ? 'is-online' : 'is-offline' ?>">
                                        <?= !empty($package['is_active']) ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                </td>
                                <td><?= html_escape($package['notes']) ?></td>
                                <td>
                                    <a class="monitoring-action text-decoration-none d-inline-flex align-items-center" href="<?= site_url('packages/edit/' . $package['id']) ?>">Edit</a>
                                    <a class="monitoring-action text-decoration-none d-inline-flex align-items-center" href="<?= site_url('packages/delete/' . $package['id']) ?>" data-confirm="Hapus paket ini?">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
