<main class="container py-4 py-md-5">
    <div class="page-toolbar mb-3">
        <a href="<?= site_url('/') ?>" class="back-button" aria-label="Kembali ke menu">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali</span>
        </a>
    </div>

    <section class="menu-shell monitoring-shell">
        <div class="brand-bar">
            <img src="<?= base_url('assets/img/logo.jpeg') ?>" alt="ISP BATARA NET" class="brand-logo">
            <div>
                <div class="menu-eyebrow">
                    <i class="fa-solid fa-money-bill-wave me-2"></i>
                    ISP BATARA NET
                </div>
                <div class="brand-subtitle">Data Pembayaran</div>
            </div>
        </div>

        <div class="menu-heading">
            <h1>Data Pembayaran</h1>
            <p>Riwayat pembayaran pelanggan berdasarkan struktur sheet PEMBAYARAN.</p>
        </div>

        <div class="card glass-card shadow-sm mt-3">
            <div class="card-body">
                <?php
                $queryBase = $filters;
                $queryBase['per_page'] = $per_page;
                ?>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <div class="text-muted small">
                        Menampilkan <?= count($payments) ?> dari <?= (int) $total_rows ?> pembayaran
                    </div>
                    <form method="get" action="<?= site_url('payments') ?>" class="d-flex align-items-center gap-2">
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

                <form method="get" action="<?= site_url('payments') ?>" class="customer-table-search table-responsive">
                    <input type="hidden" name="per_page" value="<?= (int) $per_page ?>">
                    <table class="table table-bordered table-striped table-nowrap">
                        <thead>
                            <tr class="customer-search-row">
                                <th>
                                    <div class="table-date-range">
                                        <input type="date" name="input_date_from" class="form-control" aria-label="Tanggal input dari" title="Tanggal input dari" value="<?= html_escape($filters['input_date_from']) ?>">
                                        <span>—</span>
                                        <input type="date" name="input_date_to" class="form-control" aria-label="Tanggal input sampai" title="Tanggal input sampai" value="<?= html_escape($filters['input_date_to']) ?>">
                                    </div>
                                </th>
                                <th><input type="text" name="customer_code" class="form-control" placeholder="ID" value="<?= html_escape($filters['customer_code']) ?>"></th>
                                <th><input type="text" name="customer_name" class="form-control" placeholder="Nama" value="<?= html_escape($filters['customer_name']) ?>"></th>
                                <th><input type="text" name="bill_month" class="form-control" placeholder="Bulan" value="<?= html_escape($filters['bill_month']) ?>"></th>
                                <th><input type="text" name="bill_year" class="form-control" placeholder="Tahun" value="<?= html_escape($filters['bill_year']) ?>"></th>
                                <th><input type="text" name="package_name" class="form-control" placeholder="Paket" value="<?= html_escape($filters['package_name']) ?>"></th>
                                <th></th>
                                <th><input type="text" name="group_name" class="form-control" placeholder="Kelompok" value="<?= html_escape($filters['group_name']) ?>"></th>
                                <th><input type="text" name="payment_type" class="form-control" placeholder="Bayar" value="<?= html_escape($filters['payment_type']) ?>"></th>
                                <th>
                                    <div class="table-date-range">
                                        <input type="date" name="payment_date_from" class="form-control" aria-label="Tanggal bayar dari" title="Tanggal bayar dari" value="<?= html_escape($filters['payment_date_from']) ?>">
                                        <span>—</span>
                                        <input type="date" name="payment_date_to" class="form-control" aria-label="Tanggal bayar sampai" title="Tanggal bayar sampai" value="<?= html_escape($filters['payment_date_to']) ?>">
                                    </div>
                                </th>
                                <th><input type="text" name="payment_method" class="form-control" placeholder="Metode" value="<?= html_escape($filters['payment_method']) ?>"></th>
                                <th><input type="text" name="notes" class="form-control" placeholder="Ket." value="<?= html_escape($filters['notes']) ?>"></th>
                                <th>
                                    <div class="customer-search-actions">
                                        <button type="submit" class="monitoring-action border-0">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                            Search
                                        </button>
                                        <a href="<?= site_url('payments') ?>" class="monitoring-action text-decoration-none d-inline-flex align-items-center">
                                            Reset
                                        </a>
                                    </div>
                                </th>
                            </tr>
                            <tr>
                                <th>Tanggal Input</th>
                                <th>ID Pelanggan</th>
                                <th>Nama</th>
                                <th>Bulan</th>
                                <th>Tahun</th>
                                <th>Paket</th>
                                <th class="text-end">Harga</th>
                                <th>Kelompok</th>
                                <th>Pembayaran</th>
                                <th>Tanggal Bayar</th>
                                <th>Metode</th>
                                <th>Keterangan</th>
                                <th width="120">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="13" class="text-center text-muted">Belum ada data pembayaran.</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= html_escape($payment['input_date']) ?></td>
                                    <td><?= html_escape($payment['customer_code']) ?></td>
                                    <td><?= html_escape($payment['customer_name']) ?></td>
                                    <td><?= (int) $payment['bill_month'] ?></td>
                                    <td><?= (int) $payment['bill_year'] ?></td>
                                    <td><?= html_escape($payment['package_name']) ?></td>
                                    <td class="text-end">Rp <?= number_format((float) $payment['price'], 0, ',', '.') ?></td>
                                    <td><?= html_escape($payment['group_name']) ?></td>
                                    <td><?= html_escape($payment['payment_type']) ?></td>
                                    <td><?= html_escape($payment['payment_date']) ?></td>
                                    <td><?= html_escape($payment['payment_method']) ?></td>
                                    <td><?= html_escape($payment['notes']) ?></td>
                                    <td>
                                        <a class="monitoring-action text-decoration-none d-inline-flex align-items-center" href="<?= site_url('payments/delete/' . $payment['id']) ?>" data-confirm="Hapus pembayaran ini?">Delete</a>
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
                    <a class="back-button <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= $page <= 1 ? '#' : site_url('payments?' . http_build_query($prevQuery)) ?>">
                        <i class="fa-solid fa-chevron-left"></i>
                        <span>Prev</span>
                    </a>
                    <span class="customer-page-indicator">Page <?= (int) $page ?> / <?= (int) $total_pages ?></span>
                    <a class="back-button <?= $page >= $total_pages ? 'is-disabled' : '' ?>" href="<?= $page >= $total_pages ? '#' : site_url('payments?' . http_build_query($nextQuery)) ?>">
                        <span>Next</span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>
