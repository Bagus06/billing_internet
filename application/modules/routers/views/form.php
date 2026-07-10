<main class="container py-4 py-md-5">
    <div class="page-toolbar mb-3">
        <a href="<?= site_url('routers') ?>" class="back-button" aria-label="Kembali ke data router">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali</span>
        </a>
        <div class="toolbar-orbit" aria-hidden="true"></div>
    </div>

    <section class="menu-shell monitoring-shell">
        <div class="brand-bar">
            <img src="<?= base_url('assets/img/logo.jpeg') ?>" alt="ISP BATARA NET" class="brand-logo">
            <div>
                <div class="menu-eyebrow">
                    <i class="fa-solid fa-server me-2"></i>
                    ISP BATARA NET
                </div>
                <div class="brand-subtitle"><?= $mode === 'create' ? 'Tambah Router' : 'Edit Router' ?></div>
            </div>
        </div>

        <form method="post" action="<?= $action ?>" class="mt-3">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Router</label>
                    <input type="text" name="name" class="form-control" value="<?= html_escape($router['name']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Host / IP</label>
                    <input type="text" name="host" class="form-control" value="<?= html_escape($router['host']) ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Port API</label>
                    <input type="number" name="port" class="form-control" value="<?= (int) $router['port'] ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Timeout</label>
                    <input type="number" name="timeout" class="form-control" value="<?= (int) $router['timeout'] ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">API SSL</label>
                    <select name="use_ssl" class="form-select">
                        <option value="0" <?= empty($router['use_ssl']) ? 'selected' : '' ?>>Tidak</option>
                        <option value="1" <?= !empty($router['use_ssl']) ? 'selected' : '' ?>>Ya</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" <?= !empty($router['is_active']) ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= empty($router['is_active']) ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Username API</label>
                    <input type="text" name="username" class="form-control" value="<?= html_escape($router['username']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Password API</label>
                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        value=""
                        placeholder="<?= $mode === 'edit' ? 'Kosongkan jika tidak ingin mengubah password' : '' ?>"
                        <?= $mode === 'create' ? 'required' : '' ?>
                    >
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="back-button border-0">
                    <i class="fa-solid fa-save"></i>
                    <span>Simpan</span>
                </button>
            </div>
        </form>
    </section>
</main>
