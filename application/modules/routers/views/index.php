<main class="container py-4 py-md-5">
    <div class="page-toolbar mb-3">
        <a href="<?= site_url('/') ?>" class="back-button" aria-label="Kembali ke menu">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali</span>
        </a>
        <a href="<?= site_url('routers/create') ?>" class="back-button">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Router</span>
        </a>
    </div>

    <section class="menu-shell monitoring-shell">
        <div class="brand-bar">
            <img src="<?= base_url('assets/img/logo.jpeg') ?>" alt="ISP BATARA NET" class="brand-logo">
            <div>
                <div class="menu-eyebrow">
                    <i class="fa-solid fa-server me-2"></i>
                    ISP BATARA NET
                </div>
                <div class="brand-subtitle">Data Mikrotik</div>
            </div>
        </div>

        <div class="menu-heading">
            <h1>Data Mikrotik</h1>
            <p>Kelola router Mikrotik yang akan dipantau melalui API.</p>
        </div>

        <div class="card glass-card shadow-sm mt-3">
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Host</th>
                            <th>Port</th>
                            <th>Username</th>
                            <th>SSL</th>
                            <th>Status</th>
                            <th width="230">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($routers)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada data router.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($routers as $router): ?>
                            <tr>
                                <td><?= html_escape($router['name']) ?></td>
                                <td><?= html_escape($router['host']) ?></td>
                                <td><?= (int) $router['port'] ?></td>
                                <td><?= html_escape($router['username']) ?></td>
                                <td><?= !empty($router['use_ssl']) ? 'Ya' : 'Tidak' ?></td>
                                <td>
                                    <span class="monitoring-badge <?= !empty($router['is_active']) ? 'is-online' : '' ?>">
                                        <?= !empty($router['is_active']) ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                </td>
                                <td>
                                    <a class="monitoring-action text-decoration-none d-inline-flex align-items-center" href="<?= site_url('monitoring/router/' . $router['id']) ?>">View</a>
                                    <a class="monitoring-action text-decoration-none d-inline-flex align-items-center" href="<?= site_url('routers/edit/' . $router['id']) ?>">Edit</a>
                                    <a class="monitoring-action text-decoration-none d-inline-flex align-items-center" href="<?= site_url('routers/delete/' . $router['id']) ?>" onclick="return confirm('Hapus router ini?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
