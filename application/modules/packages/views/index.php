<main class="container py-4 py-md-5">
    <div class="page-toolbar mb-3">
        <a href="<?= site_url('/') ?>" class="back-button" aria-label="Kembali ke menu">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali</span>
        </a>
        <a href="<?= site_url('packages/create') ?>" class="back-button">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Paket</span>
        </a>
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

        <div class="card glass-card shadow-sm mt-3">
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped table-nowrap">
                    <thead>
                        <tr>
                            <th>Nama Paket</th>
                            <th class="text-end">Harga</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                            <th width="170">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($packages)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">Belum ada paket internet.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($packages as $package): ?>
                            <tr>
                                <td><?= html_escape($package['package_name']) ?></td>
                                <td class="text-end">Rp <?= number_format((float) $package['price'], 0, ',', '.') ?></td>
                                <td>
                                    <span class="monitoring-badge <?= !empty($package['is_active']) ? 'is-online' : 'is-offline' ?>">
                                        <?= !empty($package['is_active']) ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                </td>
                                <td><?= html_escape($package['notes']) ?></td>
                                <td>
                                    <a class="monitoring-action text-decoration-none d-inline-flex align-items-center" href="<?= site_url('packages/edit/' . $package['id']) ?>">Edit</a>
                                    <a class="monitoring-action text-decoration-none d-inline-flex align-items-center" href="<?= site_url('packages/delete/' . $package['id']) ?>" onclick="return confirm('Hapus paket ini?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
