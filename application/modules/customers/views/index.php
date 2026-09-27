<main class="container py-4 py-md-5">
    <div class="page-toolbar mb-3">
        <a href="<?= site_url('/') ?>" class="back-button" aria-label="Kembali ke menu"><i class="fa-solid fa-arrow-left"></i><span>Kembali</span></a>
        <div class="toolbar-actions">
            <a href="<?= site_url('customers/create') ?>" class="back-button"><i class="fa-solid fa-user-plus"></i><span>Tambah Pelanggan</span></a>
            <button type="button" class="back-button customer-bulk-isolation" data-customer-bulk-isolation data-url="<?= site_url('customers/isolate-due') ?>"><i class="fa-solid fa-user-lock"></i><span>Isolir Jatuh Tempo</span></button>
            <button type="button" class="back-button customer-bulk-restore" data-customer-bulk-restore data-url="<?= site_url('customers/restore-all-isolation') ?>"><i class="fa-solid fa-unlock-keyhole"></i><span>Pulihkan Isolir</span></button>
            <form method="post" action="<?= site_url('customers/import-spreadsheet') ?>" class="d-inline"><button type="submit" class="back-button" data-confirm="Import pelanggan dan pembayaran dari Google Spreadsheet?"><i class="fa-solid fa-file-import"></i><span>Import Spreadsheet</span></button></form>
        </div>
    </div>

    <section class="menu-shell monitoring-shell">
        <div class="menu-heading"><h1>Data Pelanggan</h1><p>Kelola pelanggan, paket layanan, status aktif, dan pembayaran.</p></div>
        <?php $this->load->view('template/flash'); ?>

        <?php
        $queryBase = $filters; $queryBase['per_page'] = $per_page;
        $filterLabels = ['customer_code'=>'ID','name'=>'Nama','phone'=>'Telepon','package_name'=>'Paket','group_name'=>'Kelompok','customer_status'=>'Status','payment_status'=>'Pembayaran','isolation_status'=>'Isolir','arrears_status'=>'Tunggakan','promoter'=>'Promotor'];
        $activeFilters = array_filter($filters, function($value){ return trim((string)$value) !== ''; });
        ?>
        <div class="customer-card-toolbar mt-3">
            <div class="customer-search-launcher"><button type="button" class="filter-submit-button" data-customer-search-open><i class="fa-solid fa-sliders"></i><span>Pencarian Lanjutan</span><?php if($activeFilters): ?><b><?= count($activeFilters) ?></b><?php endif; ?></button><a href="<?= site_url('customers') ?>" class="filter-reset-button"><i class="fa-solid fa-rotate-left"></i><span>Reset</span></a><div class="customer-active-filters"><?php if($activeFilters): ?><?php foreach($activeFilters as $field=>$value): ?><span><small><?= html_escape($filterLabels[$field]??$field) ?></small><?= html_escape($value) ?></span><?php endforeach; ?><?php else: ?><span class="is-empty"><i class="fa-solid fa-circle-info"></i> Tidak ada filter aktif</span><?php endif; ?></div></div>
            <div class="customer-card-summary">
                <span>Menampilkan <strong><?= count($customers) ?></strong> dari <strong><?= (int) $total_rows ?></strong> pelanggan</span>
                <form method="get" action="<?= site_url('customers') ?>" class="d-flex align-items-center gap-2">
                    <?php foreach ($filters as $field => $value): ?><input type="hidden" name="<?= html_escape($field) ?>" value="<?= html_escape($value) ?>"><?php endforeach; ?>
                    <label for="customerPageSize">Per halaman</label><select id="customerPageSize" name="per_page" class="form-select customer-page-size" onchange="this.form.submit()"><?php foreach ([10, 25, 50, 100] as $size): ?><option value="<?= $size ?>" <?= (int) $per_page === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach; ?></select>
                </form>
            </div>
        </div>

        <div class="customer-search-modal" data-customer-search-modal aria-hidden="true"><div class="customer-search-backdrop" data-customer-search-close></div><section class="customer-search-panel" role="dialog" aria-modal="true" aria-labelledby="customerSearchTitle"><header><div><small>Data Pelanggan</small><h2 id="customerSearchTitle"><i class="fa-solid fa-magnifying-glass"></i> Pencarian Lanjutan</h2></div><button type="button" data-customer-search-close aria-label="Tutup pencarian"><i class="fa-solid fa-xmark"></i></button></header><form method="get" action="<?= site_url('customers') ?>" class="customer-card-filter" data-customer-search-form><input type="hidden" name="per_page" value="<?= (int)$per_page ?>">
            <div class="customer-search-fields"><label class="customer-filter-field"><span><i class="fa-solid fa-id-badge"></i> ID Pelanggan</span><input type="search" enterkeyhint="search" name="customer_code" class="form-control" placeholder="Cari ID" value="<?= html_escape($filters['customer_code']) ?>"></label><label class="customer-filter-field"><span><i class="fa-solid fa-user"></i> Nama</span><input type="search" enterkeyhint="search" name="name" class="form-control" placeholder="Cari nama" value="<?= html_escape($filters['name']) ?>"></label><label class="customer-filter-field"><span><i class="fa-solid fa-phone"></i> Telepon</span><input type="search" inputmode="tel" enterkeyhint="search" name="phone" class="form-control" placeholder="Cari telepon" value="<?= html_escape($filters['phone']) ?>"></label><label class="customer-filter-field"><span><i class="fa-solid fa-wifi"></i> Paket</span><select name="package_name" class="form-select"><option value="">Semua Paket</option><?php foreach($filter_options['package_name'] as $option): ?><option value="<?= html_escape($option) ?>" <?= $filters['package_name']===$option?'selected':'' ?>><?= html_escape($option) ?></option><?php endforeach; ?></select></label><label class="customer-filter-field"><span><i class="fa-solid fa-layer-group"></i> Kelompok</span><select name="group_name" class="form-select"><option value="">Semua Kelompok</option><?php foreach($filter_options['group_name'] as $option): ?><option value="<?= html_escape($option) ?>" <?= $filters['group_name']===$option?'selected':'' ?>><?= html_escape($option) ?></option><?php endforeach; ?></select></label><label class="customer-filter-field"><span><i class="fa-solid fa-signal"></i> Status</span><select name="customer_status" class="form-select"><option value="">Semua Status</option><?php foreach($filter_options['customer_status'] as $option): ?><option value="<?= html_escape($option) ?>" <?= $filters['customer_status']===$option?'selected':'' ?>><?= html_escape($option) ?></option><?php endforeach; ?></select></label><label class="customer-filter-field"><span><i class="fa-solid fa-wallet"></i> Pembayaran</span><select name="payment_status" class="form-select"><option value="">Semua Pembayaran</option><?php foreach($filter_options['payment_status'] as $option): ?><option value="<?= html_escape($option) ?>" <?= $filters['payment_status']===$option?'selected':'' ?>><?= html_escape($option) ?></option><?php endforeach; ?></select></label><label class="customer-filter-field"><span><i class="fa-solid fa-user-lock"></i> Isolir</span><select name="isolation_status" class="form-select"><option value="">Semua Status Isolir</option><?php foreach($filter_options['isolation_status'] as $option): ?><option value="<?= html_escape($option) ?>" <?= $filters['isolation_status']===$option?'selected':'' ?>><?= html_escape($option) ?></option><?php endforeach; ?></select></label><label class="customer-filter-field"><span><i class="fa-solid fa-calendar-xmark"></i> Tunggakan</span><select name="arrears_status" class="form-select"><option value="">Semua Status Tunggakan</option><?php foreach($filter_options['arrears_status'] as $option): ?><option value="<?= html_escape($option) ?>" <?= $filters['arrears_status']===$option?'selected':'' ?>><?= html_escape($option) ?></option><?php endforeach; ?></select></label></div>
            <footer><button type="button" class="customer-search-cancel" data-customer-search-close>Batal</button><button type="submit" class="filter-submit-button"><i class="fa-solid fa-magnifying-glass"></i><span>Cari Pelanggan</span></button></footer></form></section></div>

        <?php if (empty($customers)): ?>
            <div class="customer-empty-state"><i class="fa-solid fa-users-slash"></i><strong>Data pelanggan tidak ditemukan</strong><span>Coba ubah kata kunci atau reset filter pencarian.</span></div>
        <?php else: ?>
            <div class="customer-card-grid">
                <?php foreach ($customers as $customer): ?>
                    <?php
                    $isActive = strtoupper((string) $customer['customer_status']) === 'ACTIVE';
                    $isPaid = strtoupper((string) $customer['payment_status']) === 'SUDAH BAYAR';
                    $hasArrears = (int) ($customer['arrears_count'] ?? 0) > 0;
                    $ktpPhoto = trim((string) $customer['ktp_photo']);
                    $ktpUrl = strpos($ktpPhoto, 'private://') === 0 ? site_url('customers/ktp/' . (int) $customer['id']) : '';
                    $secretNik = preg_replace('/\D+/', '', (string) $customer['nik']);
                    $secretName = $secretNik !== '' ? $secretNik . app_setting('pppoe_username_suffix', '@BATARA.net') : '-';
                    $secretPassword = $secretNik !== '' ? 'BTN-' . substr($secretNik, -6) : '-';
                    $initial = function_exists('mb_substr') ? mb_substr(trim((string) $customer['name']), 0, 1, 'UTF-8') : substr(trim((string) $customer['name']), 0, 1);
                    ?>
                    <article class="customer-profile-card <?= $isActive ? 'is-active' : 'is-inactive' ?>" tabindex="0" role="button" data-customer-detail
                        data-id="<?= (int) $customer['id'] ?>" data-code="<?= html_escape($customer['customer_code']) ?>" data-name="<?= html_escape($customer['name']) ?>"
                        data-phone="<?= html_escape($customer['phone']) ?>" data-nik="<?= html_escape($customer['nik']) ?>" data-address="<?= html_escape($customer['address']) ?>"
                        data-package="<?= html_escape($customer['package_name']) ?>" data-price="Rp <?= number_format((float) $customer['price'], 0, ',', '.') ?>"
                        data-group="<?= html_escape($customer['group_name']) ?>" data-status="<?= html_escape($customer['customer_status']) ?>" data-payment="<?= html_escape($customer['payment_status']) ?>"
                        data-arrears="<?= $hasArrears ? (int) $customer['arrears_count'] . ' bulan — Rp ' . number_format((float) $customer['arrears_amount'], 0, ',', '.') : 'Tidak ada tunggakan' ?>" data-arrears-periods="<?= html_escape($customer['arrears_periods'] ?: '-') ?>"
                        data-promoter="<?= html_escape($customer['promoter']) ?>" data-psb-date="<?= html_escape($customer['psb_date']) ?>" data-notes="<?= html_escape($customer['notes']) ?>"
                        data-secret-name="<?= html_escape($secretName) ?>" data-secret-password="<?= html_escape($secretPassword) ?>" data-ktp-src="<?= html_escape($ktpUrl) ?>"
                        data-latitude="<?= html_escape($customer['latitude'] ?? '') ?>" data-longitude="<?= html_escape($customer['longitude'] ?? '') ?>">
                        <div class="customer-card-main">
                            <div class="customer-avatar" aria-hidden="true"><?= html_escape(strtoupper($initial ?: '?')) ?></div>
                            <div class="customer-card-identity"><span class="customer-code"><?= html_escape($customer['customer_code']) ?></span><h2><?= html_escape($customer['name']) ?></h2><span><i class="fa-solid fa-phone"></i> <?= html_escape($customer['phone'] ?: '-') ?></span></div>
                            <div class="customer-card-status"><?php if (!empty($customer['is_isolated'])): ?><span class="monitoring-badge is-isolated">ISOLIR</span><?php endif; ?><span class="monitoring-badge <?= $isActive ? 'is-online' : 'is-offline' ?>"><?= html_escape($customer['customer_status']) ?></span><span class="monitoring-badge <?= $isPaid ? 'is-online' : 'is-offline' ?>"><?= html_escape($customer['payment_status']) ?></span></div>
                        </div>
                        <div class="customer-card-service"><div><span>Paket Internet</span><strong><?= html_escape($customer['package_name'] ?: '-') ?></strong></div><div><span>Biaya Bulanan</span><strong>Rp <?= number_format((float) $customer['price'], 0, ',', '.') ?></strong></div><div><span>Kelompok</span><strong><?= html_escape($customer['group_name'] ?: '-') ?></strong><?php if ($hasArrears): ?><small class="customer-arrears-note" title="Periode: <?= html_escape($customer['arrears_periods']) ?>"><i class="fa-solid fa-circle-exclamation"></i><?= (int) $customer['arrears_count'] ?> bulan menunggak</small><?php endif; ?></div></div>
                        <div class="customer-card-footer"><span><i class="fa-regular fa-eye"></i> Klik card untuk melihat detail</span>
                            <details class="customer-action-menu" data-customer-action-menu><summary aria-label="Kelola pelanggan <?= html_escape($customer['name']) ?>" title="Kelola pelanggan"><i class="fa-solid fa-ellipsis-vertical"></i><span>Kelola</span></summary><div class="customer-action-dropdown">
                                <button type="button" class="customer-action-item customer-pay-button" data-customer-id="<?= (int) $customer['id'] ?>" data-customer-code="<?= html_escape($customer['customer_code']) ?>" data-customer-name="<?= html_escape($customer['name']) ?>" data-customer-price="Rp <?= number_format((float) $customer['price'], 0, ',', '.') ?>"><i class="fa-solid fa-wallet"></i> Bayar</button>
                                <button type="button" class="customer-action-item customer-status-toggle" data-toggle-url="<?= site_url('customers/toggle-status') ?>" data-customer-id="<?= (int) $customer['id'] ?>" data-customer-name="<?= html_escape($customer['name']) ?>" data-active="<?= $isActive ? '1' : '0' ?>"><i class="fa-solid <?= $isActive ? 'fa-user-slash' : 'fa-user-check' ?>"></i> <?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                                <button type="button" class="customer-action-item customer-remote-ont" data-url="<?= site_url('customers/remote-ont') ?>" data-customer-id="<?= (int) $customer['id'] ?>" data-customer-name="<?= html_escape($customer['name']) ?>"><i class="fa-solid fa-network-wired"></i> Remote ONT</button>
                                <a class="customer-action-item" href="<?= site_url('customers/edit/' . $customer['id']) ?>"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                <a class="customer-action-item is-danger" href="<?= site_url('customers/delete/' . $customer['id']) ?>" data-confirm="Hapus pelanggan ini?"><i class="fa-solid fa-trash"></i> Hapus</a>
                            </div></details>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="customer-pagination mt-4">
            <?php $prevQuery = $queryBase; $nextQuery = $queryBase; $prevQuery['page'] = max(1, $page - 1); $nextQuery['page'] = min($total_pages, $page + 1); ?>
            <a class="back-button <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= $page <= 1 ? '#' : site_url('customers?' . http_build_query($prevQuery)) ?>"><i class="fa-solid fa-chevron-left"></i><span>Prev</span></a>
            <span class="customer-page-indicator">Page <?= (int) $page ?> / <?= (int) $total_pages ?></span>
            <a class="back-button <?= $page >= $total_pages ? 'is-disabled' : '' ?>" href="<?= $page >= $total_pages ? '#' : site_url('customers?' . http_build_query($nextQuery)) ?>"><span>Next</span><i class="fa-solid fa-chevron-right"></i></a>
        </div>
    </section>

    <div class="pppoe-hover-detail customer-monitoring-detail" id="customerDetailModal" role="dialog" aria-modal="true" aria-labelledby="customerDetailTitle" aria-hidden="true">
        <div class="pppoe-detail-title"><h6 id="customerDetailTitle"><i class="fa-solid fa-address-card" aria-hidden="true"></i> Detail Pelanggan</h6><button type="button" class="pppoe-detail-close" data-customer-detail-close aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button></div>
        <section class="pppoe-detail-group"><div class="pppoe-detail-group-title"><i class="fa-solid fa-user" aria-hidden="true"></i>Data Pelanggan</div><div class="pppoe-detail-group-body">
            <div class="pppoe-detail-item is-wide"><span><i class="fa-solid fa-user"></i>Nama</span><strong data-detail="name">-</strong></div>
            <div class="pppoe-detail-item"><span><i class="fa-solid fa-hashtag"></i>ID</span><strong data-detail="id">-</strong></div><div class="pppoe-detail-item"><span><i class="fa-solid fa-id-badge"></i>Kode</span><strong data-detail="code">-</strong></div>
            <div class="pppoe-detail-item has-action"><span><i class="fa-solid fa-address-card"></i>NIK</span><div class="pppoe-detail-value-action"><strong data-detail="nik">-</strong><button type="button" class="pppoe-whatsapp-button customer-ktp-inline" data-detail-ktp disabled><i class="fa-solid fa-id-card"></i>Lihat KTP</button></div></div><div class="pppoe-detail-item has-action"><span><i class="fa-solid fa-phone"></i>Telepon</span><div class="pppoe-detail-value-action"><strong data-detail="phone">-</strong><a class="pppoe-whatsapp-button" data-detail-whatsapp href="#" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-whatsapp"></i>Chat</a></div></div>
            <div class="pppoe-detail-item is-wide"><span><i class="fa-solid fa-location-dot"></i>Alamat</span><strong data-detail="address">-</strong></div><div class="pppoe-detail-map-action"><button type="button" class="pppoe-map-button" data-customer-map-popup disabled><i class="fa-solid fa-map-location-dot"></i>Lihat Peta</button></div>
        </div></section>
        <section class="pppoe-detail-group"><div class="pppoe-detail-group-title"><i class="fa-solid fa-box-open" aria-hidden="true"></i>Layanan</div><div class="pppoe-detail-group-body">
            <div class="pppoe-detail-item"><span><i class="fa-solid fa-box-open"></i>Paket</span><strong data-detail="package">-</strong></div><div class="pppoe-detail-item"><span><i class="fa-solid fa-money-bill-wave"></i>Biaya</span><strong data-detail="price">-</strong></div>
            <div class="pppoe-detail-item"><span><i class="fa-solid fa-users"></i>Kelompok</span><strong data-detail="group">-</strong></div><div class="pppoe-detail-item"><span><i class="fa-solid fa-user-check"></i>Pelanggan</span><strong data-detail="status">-</strong></div>
            <div class="pppoe-detail-item"><span><i class="fa-solid fa-receipt"></i>Pembayaran</span><strong data-detail="payment">-</strong></div><div class="pppoe-detail-item"><span><i class="fa-solid fa-calendar-check"></i>Tanggal PSB</span><strong data-detail="psbDate">-</strong></div>
            <div class="pppoe-detail-item"><span><i class="fa-solid fa-triangle-exclamation"></i>Tunggakan</span><strong data-detail="arrears">-</strong></div><div class="pppoe-detail-item"><span><i class="fa-solid fa-calendar-xmark"></i>Periode Tunggakan</span><strong data-detail="arrearsPeriods">-</strong></div>
            <div class="pppoe-detail-item"><span><i class="fa-solid fa-user-tag"></i>Promotor</span><strong data-detail="promoter">-</strong></div><div class="pppoe-detail-item is-wide"><span><i class="fa-solid fa-note-sticky"></i>Catatan</span><strong data-detail="notes">-</strong></div>
        </div></section>
        <section class="pppoe-detail-group"><div class="pppoe-detail-group-title"><i class="fa-solid fa-key" aria-hidden="true"></i>PPP Secret</div><div class="pppoe-detail-group-body">
            <div class="pppoe-detail-item has-action"><span><i class="fa-solid fa-at"></i>Username</span><div class="pppoe-detail-value-action"><strong data-detail="secretName">-</strong><button type="button" class="pppoe-whatsapp-button customer-copy-secret" data-detail-copy="secretName" aria-label="Salin username"><i class="fa-regular fa-copy"></i></button></div></div>
            <div class="pppoe-detail-item has-action"><span><i class="fa-solid fa-lock"></i>Password</span><div class="pppoe-detail-value-action"><strong data-detail="secretPassword">-</strong><button type="button" class="pppoe-whatsapp-button customer-copy-secret" data-detail-copy="secretPassword" aria-label="Salin password"><i class="fa-regular fa-copy"></i></button></div></div>
        </div></section>
    </div>

    <div class="ktp-modal" id="ktpModal" aria-hidden="true"><div class="ktp-modal-backdrop" data-ktp-close></div><div class="ktp-modal-panel" role="dialog" aria-modal="true" aria-labelledby="ktpModalTitle"><div class="ktp-modal-header"><strong id="ktpModalTitle">Foto KTP</strong><button type="button" class="ktp-modal-close" data-ktp-close aria-label="Tutup preview KTP"><i class="fa-solid fa-xmark"></i></button></div><img src="" alt="Foto KTP" id="ktpModalImage"></div></div>

    <div class="ktp-modal" id="paymentModal" aria-hidden="true"><div class="ktp-modal-backdrop" data-payment-close></div><div class="ktp-modal-panel payment-modal-panel" role="dialog" aria-modal="true" aria-labelledby="paymentModalTitle"><div class="ktp-modal-header"><strong id="paymentModalTitle">Pembayaran Pelanggan</strong><button type="button" class="ktp-modal-close" data-payment-close aria-label="Tutup pembayaran"><i class="fa-solid fa-xmark"></i></button></div>
        <form method="post" action="<?= site_url('payments/store') ?>" data-customer-payment-form><input type="hidden" name="customer_id" id="paymentCustomerId"><input type="hidden" name="redirect_to" value="<?= html_escape(uri_string() . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')) ?>">
            <div class="payment-customer-summary mb-3"><strong id="paymentCustomerName">-</strong><span id="paymentCustomerCode">-</span><span id="paymentCustomerPrice">-</span></div>
            <div class="row g-3"><div class="col-md-6"><label class="form-label">Pembayaran</label><input type="text" class="form-control" value="Otomatis oleh sistem" readonly><div class="form-text">Pembayaran pertama menjadi PSB. Pembayaran berikutnya otomatis BULANAN.</div></div><div class="col-md-6"><label class="form-label">Metode Bayar</label><select name="payment_method" class="form-select" required><option value="CASH">CASH</option><option value="SEABANK">SEABANK</option></select></div><div class="col-md-12"><label class="form-label">Tanggal Bayar</label><input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div><div class="col-md-12"><label class="form-label">Keterangan</label><input type="text" name="notes" class="form-control" placeholder="Opsional"></div></div>
            <div class="mt-3"><button type="submit" class="back-button border-0" data-payment-submit><i class="fa-solid fa-check"></i><span>Submit Pembayaran</span></button></div>
        </form></div></div>
</main>
