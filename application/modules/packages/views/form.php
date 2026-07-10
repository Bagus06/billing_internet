<main class="container py-4 py-md-5">
    <div class="page-toolbar mb-3">
        <a href="<?= site_url('packages') ?>" class="back-button" aria-label="Kembali ke paket internet">
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
                    <i class="fa-solid fa-wifi me-2"></i>
                    ISP BATARA NET
                </div>
                <div class="brand-subtitle"><?= $mode === 'create' ? 'Tambah Paket' : 'Edit Paket' ?></div>
            </div>
        </div>

        <form method="post" action="<?= $action ?>" class="mt-3">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Nama Paket</label>
                    <input type="text" name="package_name" class="form-control" value="<?= html_escape($package['package_name']) ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Harga</label>
                    <input type="text" name="price" class="form-control" value="<?= number_format((float) $package['price'], 0, ',', '.') ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" <?= !empty($package['is_active']) ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= empty($package['is_active']) ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Keterangan</label>
                    <textarea name="notes" class="form-control" rows="3"><?= html_escape($package['notes']) ?></textarea>
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
