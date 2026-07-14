<main class="container py-4 py-md-5">
    <div class="page-toolbar mb-3">
        <a href="<?= site_url('customers') ?>" class="back-button" aria-label="Kembali ke data pelanggan">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali</span>
        </a>
        <div class="toolbar-orbit" aria-hidden="true"></div>
    </div>

    <section class="menu-shell monitoring-shell">
        <?php $this->load->view('template/flash'); ?>
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

        <form method="post" action="<?= $action ?>" class="mt-3" enctype="multipart/form-data" data-customer-form data-mode="<?= html_escape($mode) ?>">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">ID Pelanggan</label>
                    <input type="text" class="form-control" value="<?= html_escape($customer['customer_code']) ?>" data-customer-code-preview readonly>
                </div>

                <div class="col-md-5">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control text-uppercase" value="<?= html_escape($customer['name']) ?>" data-uppercase-input data-customer-name-input autocomplete="name" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Telepon</label>
                    <input type="tel" name="phone" class="form-control" value="<?= html_escape($customer['phone']) ?>" data-phone-input inputmode="tel" maxlength="16" placeholder="Contoh: 081234567890" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">NIK KTP</label>
                    <input type="text" name="nik" class="form-control" value="<?= html_escape($customer['nik']) ?>" data-nik-input inputmode="numeric" minlength="16" maxlength="16" pattern="[0-9]{16}" title="NIK harus tepat 16 digit" required>
                </div>

                <div class="col-md-12 order-first">
                    <label class="form-label">Foto KTP</label>
                    <div class="ktp-first-guide mb-2"><i class="fa-solid fa-circle-1"></i><span>Upload foto KTP terlebih dahulu. Field pelanggan akan terbuka setelah proses identifikasi selesai.</span></div>
                    <input type="hidden" name="existing_ktp_photo" value="<?= html_escape($customer['ktp_photo']) ?>">
                    <div class="ktp-input-row"><input type="file" name="ktp_photo" class="form-control" accept="image/jpeg,image/png,image/webp" data-ktp-file <?= $mode === 'create' ? 'required' : '' ?>><div class="ktp-identification-status"><div class="ktp-ocr-state"><i class="fa-solid fa-id-card"></i><span data-ktp-ocr-state><?= !empty($customer['ktp_photo']) ? 'KTP tersimpan. Pilih file baru untuk identifikasi ulang.' : 'Pilih gambar KTP untuk memulai identifikasi.' ?></span></div><button type="button" class="monitoring-action" data-ktp-form-view <?= empty($customer['ktp_photo']) ? 'disabled' : '' ?>><i class="fa-solid fa-eye"></i> Lihat KTP</button></div></div>
                    <?php if (!empty($customer['ktp_photo'])): ?>
                        <div class="text-muted small mt-1">File saat ini: <?= html_escape(basename($customer['ktp_photo'])) ?></div>
                    <?php endif; ?>
                    <img src="<?= !empty($customer['ktp_photo']) ? html_escape(base_url($customer['ktp_photo'])) : '' ?>" alt="" data-ktp-preview hidden>
                    <div class="form-text">Sistem mencoba mengambil NIK dan nama secara otomatis. Periksa kembali hasil OCR sebelum menyimpan.</div>
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
    <div class="ktp-modal" data-ktp-form-modal aria-hidden="true"><div class="ktp-modal-backdrop" data-ktp-form-close></div><div class="ktp-modal-panel" role="dialog" aria-modal="true"><div class="ktp-modal-header"><strong>Preview KTP</strong><button type="button" class="ktp-modal-close" data-ktp-form-close><i class="fa-solid fa-xmark"></i></button></div><img src="" alt="Preview KTP" data-ktp-form-modal-image></div></div>
    <div class="ktp-ocr-blocker" data-ktp-ocr-blocker hidden aria-live="assertive"><div class="ktp-ocr-blocker-card"><div class="app-loader-ring"></div><strong>Identifikasi KTP sedang berjalan</strong><span data-ktp-blocker-status>Mohon tunggu dan jangan menutup halaman.</span></div></div>
</main>
