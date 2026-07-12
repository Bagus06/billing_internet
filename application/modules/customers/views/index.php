<main class="container py-4 py-md-5">
    <div class="page-toolbar mb-3">
        <a href="<?= site_url('/') ?>" class="back-button" aria-label="Kembali ke menu">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali</span>
        </a>
        <a href="<?= site_url('customers/create') ?>" class="back-button">
            <i class="fa-solid fa-user-plus"></i>
            <span>Tambah Pelanggan</span>
        </a>
    </div>

    <section class="menu-shell monitoring-shell">
        <div class="brand-bar">
            <img src="<?= base_url('assets/img/logo.jpeg') ?>" alt="ISP BATARA NET" class="brand-logo">
            <div>
                <div class="menu-eyebrow">
                    <i class="fa-solid fa-users me-2"></i>
                    ISP BATARA NET
                </div>
                <div class="brand-subtitle">Data Pelanggan</div>
            </div>
        </div>

        <div class="menu-heading">
            <h1>Data Pelanggan</h1>
            <p>Kelola pelanggan, paket layanan, status aktif, dan status pembayaran.</p>
        </div>

        <?php $this->load->view('../../views/layout/flash'); ?>

        <div class="card glass-card shadow-sm mt-3">
            <div class="card-body table-responsive">
                <?php
                $queryBase = $filters;
                $queryBase['per_page'] = $per_page;
                ?>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <div class="text-muted small">
                        Menampilkan <?= count($customers) ?> dari <?= (int) $total_rows ?> pelanggan
                    </div>
                    <form method="get" action="<?= site_url('customers') ?>" class="d-flex align-items-center gap-2">
                        <?php foreach ($filters as $field => $value): ?>
                            <input type="hidden" name="<?= html_escape($field) ?>" value="<?= html_escape($value) ?>">
                        <?php endforeach; ?>
                        <label class="text-muted small">Per page</label>
                        <select name="per_page" class="form-select customer-page-size" onchange="this.form.submit()">
                            <?php foreach ([10, 25, 50, 100] as $size): ?>
                                <option value="<?= $size ?>" <?= (int) $per_page === $size ? 'selected' : '' ?>><?= $size ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <form method="get" action="<?= site_url('customers') ?>" class="customer-table-search">
                    <input type="hidden" name="per_page" value="<?= (int) $per_page ?>">
                    <table class="table table-bordered table-striped table-nowrap">
                        <thead>
                            <tr class="customer-search-row">
                                <th><input type="text" name="customer_code" class="form-control" placeholder="Cari ID" value="<?= html_escape($filters['customer_code']) ?>"></th>
                                <th><input type="text" name="name" class="form-control" placeholder="Cari nama" value="<?= html_escape($filters['name']) ?>"></th>
                                <th><input type="text" name="phone" class="form-control" placeholder="Cari telepon" value="<?= html_escape($filters['phone']) ?>"></th>
                                <th></th>
                                <th></th><th></th>
                                <th><input type="text" name="package_name" class="form-control" placeholder="Cari paket" value="<?= html_escape($filters['package_name']) ?>"></th>
                                <th></th>
                                <th><input type="text" name="group_name" class="form-control" placeholder="Cari kelompok" value="<?= html_escape($filters['group_name']) ?>"></th>
                                <th><input type="text" name="customer_status" class="form-control" placeholder="Cari status" value="<?= html_escape($filters['customer_status']) ?>"></th>
                                <th><input type="text" name="payment_status" class="form-control" placeholder="Cari bayar" value="<?= html_escape($filters['payment_status']) ?>"></th>
                                <th>
                                    <div class="customer-search-actions">
                                        <button type="submit" class="monitoring-action border-0">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                            Search
                                        </button>
                                        <a href="<?= site_url('customers') ?>" class="monitoring-action text-decoration-none d-inline-flex align-items-center">
                                            Reset
                                        </a>
                                    </div>
                                </th>
                            </tr>
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Telepon</th>
                                <th>KTP</th><th>Secret Name</th><th>Secret Password</th>
                                <th>Paket</th>
                                <th class="text-end">Harga</th>
                                <th>Kelompok</th>
                                <th>Status</th>
                                <th>Pembayaran</th>
                                <th width="230">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="12" class="text-center text-muted">Belum ada data pelanggan.</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($customers as $customer): ?>
                                <?php
                                $isActive = strtoupper($customer['customer_status']) === 'ACTIVE';
                                $isPaid = strtoupper($customer['payment_status']) === 'SUDAH BAYAR';
                                $ktpPhoto = trim($customer['ktp_photo']);
                                $ktpUrl = preg_match('/^https?:\/\//', $ktpPhoto) ? $ktpPhoto : base_url($ktpPhoto);
                                $secretNik = preg_replace('/\D+/', '', (string) $customer['nik']);
                                $secretName = $secretNik !== '' ? $secretNik . app_setting('pppoe_username_suffix', '@BATARA.net') : '-';
                                $secretPassword = $secretNik !== '' ? 'BTN-' . substr($secretNik, -6) : '-';
                                ?>
                                <tr>
                                    <td><?= html_escape($customer['customer_code']) ?></td>
                                    <td>
                                        <strong><?= html_escape($customer['name']) ?></strong>
                                        <?php if (!empty($customer['promoter'])): ?>
                                            <div class="text-muted small">Promotor: <?= html_escape($customer['promoter']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= html_escape($customer['phone']) ?></td>
                                    <td>
                                        <?php if (!empty($ktpPhoto)): ?>
                                            <button type="button" class="monitoring-action customer-ktp-button" data-ktp-src="<?= html_escape($ktpUrl) ?>" data-ktp-name="<?= html_escape($customer['name']) ?>">
                                                View KTP
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small">Belum ada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><div class="d-flex align-items-center gap-1"><code><?= html_escape($secretName) ?></code><?php if($secretNik!==''): ?><button type="button" class="monitoring-action customer-copy-secret" data-copy="<?= html_escape($secretName) ?>" title="Copy secret name"><i class="fa-regular fa-copy"></i></button><?php endif; ?></div></td>
                                    <td><div class="d-flex align-items-center gap-1"><code><?= html_escape($secretPassword) ?></code><?php if($secretNik!==''): ?><button type="button" class="monitoring-action customer-copy-secret" data-copy="<?= html_escape($secretPassword) ?>" title="Copy password"><i class="fa-regular fa-copy"></i></button><?php endif; ?></div></td>
                                    <td><?= html_escape($customer['package_name']) ?></td>
                                    <td class="text-end">Rp <?= number_format((float) $customer['price'], 0, ',', '.') ?></td>
                                    <td><?= html_escape($customer['group_name']) ?></td>
                                    <td>
                                        <button type="button" class="monitoring-badge customer-status-toggle border-0 <?= $isActive ? 'is-online' : 'is-offline' ?>" data-toggle-url="<?= site_url('customers/toggle-status') ?>" data-customer-id="<?= (int) $customer['id'] ?>" data-customer-name="<?= html_escape($customer['name']) ?>" data-active="<?= $isActive ? '1' : '0' ?>" title="Klik untuk mengubah status pelanggan">
                                            <?= html_escape($customer['customer_status']) ?>
                                        </button>
                                    </td>
                                    <td>
                                        <span class="monitoring-badge <?= $isPaid ? 'is-online' : 'is-offline' ?>">
                                            <?= html_escape($customer['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                    <button
                                        type="button"
                                        class="monitoring-action customer-pay-button"
                                        data-customer-id="<?= (int) $customer['id'] ?>"
                                        data-customer-code="<?= html_escape($customer['customer_code']) ?>"
                                        data-customer-name="<?= html_escape($customer['name']) ?>"
                                        data-customer-price="Rp <?= number_format((float) $customer['price'], 0, ',', '.') ?>"
                                    >
                                        Bayar
                                    </button>
                                    <a class="monitoring-action text-decoration-none d-inline-flex align-items-center" href="<?= site_url('customers/edit/' . $customer['id']) ?>">Edit</a>
                                    <a class="monitoring-action text-decoration-none d-inline-flex align-items-center" href="<?= site_url('customers/delete/' . $customer['id']) ?>" data-confirm="Hapus pelanggan ini?">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>

                <div class="customer-pagination mt-3">
                    <?php
                    $prevQuery = $queryBase;
                    $nextQuery = $queryBase;
                    $prevQuery['page'] = max(1, $page - 1);
                    $nextQuery['page'] = min($total_pages, $page + 1);
                    ?>
                    <a class="back-button <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= $page <= 1 ? '#' : site_url('customers?' . http_build_query($prevQuery)) ?>">
                        <i class="fa-solid fa-chevron-left"></i>
                        <span>Prev</span>
                    </a>
                    <span class="customer-page-indicator">Page <?= (int) $page ?> / <?= (int) $total_pages ?></span>
                    <a class="back-button <?= $page >= $total_pages ? 'is-disabled' : '' ?>" href="<?= $page >= $total_pages ? '#' : site_url('customers?' . http_build_query($nextQuery)) ?>">
                        <span>Next</span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="ktp-modal" id="ktpModal" aria-hidden="true">
        <div class="ktp-modal-backdrop" data-ktp-close></div>
        <div class="ktp-modal-panel" role="dialog" aria-modal="true" aria-labelledby="ktpModalTitle">
            <div class="ktp-modal-header">
                <strong id="ktpModalTitle">Foto KTP</strong>
                <button type="button" class="ktp-modal-close" data-ktp-close aria-label="Tutup preview KTP">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <img src="" alt="Foto KTP" id="ktpModalImage">
        </div>
    </div>

    <div class="ktp-modal" id="paymentModal" aria-hidden="true">
        <div class="ktp-modal-backdrop" data-payment-close></div>
        <div class="ktp-modal-panel payment-modal-panel" role="dialog" aria-modal="true" aria-labelledby="paymentModalTitle">
            <div class="ktp-modal-header">
                <strong id="paymentModalTitle">Pembayaran Pelanggan</strong>
                <button type="button" class="ktp-modal-close" data-payment-close aria-label="Tutup pembayaran">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="post" action="<?= site_url('payments/store') ?>">
                <input type="hidden" name="customer_id" id="paymentCustomerId">
                <input type="hidden" name="redirect_to" value="<?= html_escape(uri_string() . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '')) ?>">

                <div class="payment-customer-summary mb-3">
                    <strong id="paymentCustomerName">-</strong>
                    <span id="paymentCustomerCode">-</span>
                    <span id="paymentCustomerPrice">-</span>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Pembayaran</label>
                        <input type="text" class="form-control" value="Otomatis oleh sistem" readonly>
                        <div class="form-text">Pembayaran pertama menjadi PSB. Pembayaran berikutnya otomatis BULANAN.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Metode Bayar</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="CASH">CASH</option>
                            <option value="SEABANK">SEABANK</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Tanggal Bayar</label>
                        <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="notes" class="form-control" placeholder="Opsional">
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="back-button border-0">
                        <i class="fa-solid fa-check"></i>
                        <span>Submit Pembayaran</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
