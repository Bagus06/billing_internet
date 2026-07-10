<main class="container py-4 py-md-5">
    <div class="page-toolbar mb-3">
        <a href="<?= site_url('customers') ?>" class="back-button" aria-label="Kembali ke data pelanggan">
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
                    <i class="fa-solid fa-users me-2"></i>
                    ISP BATARA NET
                </div>
                <div class="brand-subtitle"><?= $mode === 'create' ? 'Tambah Pelanggan' : 'Edit Pelanggan' ?></div>
            </div>
        </div>

        <form method="post" action="<?= $action ?>" class="mt-3" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">ID Pelanggan</label>
                    <input type="text" class="form-control" value="<?= html_escape($customer['customer_code']) ?>" data-customer-code-preview readonly>
                </div>

                <div class="col-md-5">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control" value="<?= html_escape($customer['name']) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Telepon</label>
                    <input type="text" name="phone" class="form-control" value="<?= html_escape($customer['phone']) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">NIK KTP</label>
                    <input type="text" name="nik" class="form-control" value="<?= html_escape($customer['nik']) ?>" data-nik-input required>
                </div>

                <div class="col-md-8">
                    <label class="form-label">Foto KTP</label>
                    <input type="hidden" name="existing_ktp_photo" value="<?= html_escape($customer['ktp_photo']) ?>">
                    <input type="file" name="ktp_photo" class="form-control" accept="image/jpeg,image/png,image/webp" <?= $mode === 'create' ? 'required' : '' ?>>
                    <?php if (!empty($customer['ktp_photo'])): ?>
                        <div class="text-muted small mt-1">File saat ini: <?= html_escape(basename($customer['ktp_photo'])) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Alamat / Koordinat</label>
                    <textarea name="address" class="form-control" rows="2"><?= html_escape($customer['address']) ?></textarea>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Paket</label>
                    <select name="package_id" class="form-select" data-package-select required>
                        <option value="">Pilih Paket</option>
                        <?php foreach ($packages as $package): ?>
                            <option
                                value="<?= (int) $package['id'] ?>"
                                data-price="<?= (float) $package['price'] ?>"
                                <?= (int) ($customer['package_id'] ?? 0) === (int) $package['id'] || ($customer['package_name'] === $package['package_name']) ? 'selected' : '' ?>
                            >
                                <?= html_escape($package['package_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Harga</label>
                    <input type="text" class="form-control" value="<?= number_format((float) $customer['price'], 0, ',', '.') ?>" data-package-price readonly>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tanggal PSB</label>
                    <input type="date" name="psb_date" class="form-control" value="<?= html_escape($customer['psb_date']) ?>" data-psb-date>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Kelompok</label>
                    <input type="text" class="form-control" value="<?= html_escape($customer['group_name']) ?>" data-group-preview readonly>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Status Pelanggan</label>
                    <select name="customer_status" class="form-select">
                        <?php foreach (['ACTIVE', 'NONACTIVE', 'LEAD'] as $status): ?>
                            <option value="<?= $status ?>" <?= $customer['customer_status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Promotor</label>
                    <input type="text" name="promoter" class="form-control" value="<?= html_escape($customer['promoter']) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="notes" class="form-control" value="<?= html_escape($customer['notes']) ?>">
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
