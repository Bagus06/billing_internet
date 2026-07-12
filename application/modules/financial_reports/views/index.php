<?php
$months = app_language() === 'en'
    ? [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December']
    : [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$money = function ($value) { return 'Rp ' . number_format((float) $value, 0, ',', '.'); };
?>
<main class="container py-4 py-md-5">
    <div class="page-toolbar mb-3"><a href="<?= site_url('financial-reports?year=' . $year) ?>" class="back-button"><i class="fa-solid fa-chart-column"></i><span>Kembali ke Grafik</span></a><a target="_blank" href="<?= site_url('financial-reports/print?year='.$year.'&month='.$month) ?>" class="back-button"><i class="fa-solid fa-file-pdf"></i><span>Cetak / PDF</span></a></div>
    <section class="menu-shell monitoring-shell">
        <div class="menu-heading"><h1><i class="fa-solid fa-chart-column me-2"></i>Laporan Keuangan</h1><p>Rekap penghasilan bulanan setelah potongan teknisi dan promotor untuk pembayaran PSB.</p></div>

        <form method="get" action="<?= site_url('financial-reports') ?>" class="glass-panel p-3 mt-3"><input type="hidden" name="view" value="detail"><div class="row g-2 align-items-end">
            <div class="col-md-5"><label class="form-label">Tahun</label><select name="year" class="form-select"><?php foreach ($years as $item): ?><option value="<?= $item ?>" <?= $year === $item ? 'selected' : '' ?>><?= $item ?></option><?php endforeach; ?></select></div>
            <div class="col-md-5"><label class="form-label">Bulan</label><select name="month" class="form-select"><?php foreach ($months as $number=>$name): ?><option value="<?= $number ?>" <?= $month === $number ? 'selected' : '' ?>><?= $name ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2"><button class="back-button border-0 w-100 justify-content-center" type="submit"><i class="fa-solid fa-filter"></i><span>Tampilkan</span></button></div>
        </div></form>

        <div class="row g-3 mt-1">
            <div class="col-md-6 col-xl-3"><div class="glass-panel result-box"><div class="text-muted">Penghasilan Bruto</div><h3><?= $money($selected['gross_income']) ?></h3><small><?= (int) $selected['transaction_count'] ?> transaksi</small></div></div>
            <div class="col-md-6 col-xl-3"><div class="glass-panel result-box"><div class="text-muted">Potongan Teknisi</div><h3 class="text-warning"><?= $money($selected['technician_deduction']) ?></h3><small>Rp50.000 × <?= (int) $selected['psb_count'] ?> PSB</small></div></div>
            <div class="col-md-6 col-xl-3"><div class="glass-panel result-box"><div class="text-muted">Potongan Promotor</div><h3 class="text-warning"><?= $money($selected['promoter_deduction']) ?></h3><small>Rp50.000 × <?= (int) $selected['psb_count'] ?> PSB</small></div></div>
            <div class="col-md-6 col-xl-3"><div class="glass-panel result-box"><div class="text-muted">Penghasilan Bersih</div><h3 class="text-success"><?= $money($selected['net_income']) ?></h3><small><?= $months[$month] ?> <?= $year ?></small></div></div>
        </div>

        <div class="card glass-card shadow-sm mt-3"><div class="card-header glass-header"><i class="fa-solid fa-calendar-days me-2"></i>Rekap Bulanan Tahun <?= $year ?></div><div class="card-body table-responsive"><table class="table table-bordered table-striped table-nowrap"><thead><tr><th>Bulan</th><th class="text-end">BULANAN</th><th class="text-end">PSB</th><th class="text-end">Bruto</th><th class="text-end">Teknisi</th><th class="text-end">Promotor</th><th class="text-end">Bersih</th></tr></thead><tbody>
            <?php foreach ($months as $number=>$name): $row=$summary[$number]; ?><tr class="<?= $month === $number ? 'table-active' : '' ?>"><td><a href="<?= site_url('financial-reports?year='.$year.'&month='.$number) ?>" class="text-decoration-none text-info"><?= $name ?></a></td><td class="text-end"><?= (int) $row['monthly_count'] ?></td><td class="text-end"><?= (int) $row['psb_count'] ?></td><td class="text-end"><?= $money($row['gross_income']) ?></td><td class="text-end text-warning">-<?= $money($row['technician_deduction']) ?></td><td class="text-end text-warning">-<?= $money($row['promoter_deduction']) ?></td><td class="text-end fw-bold"><?= $money($row['net_income']) ?></td></tr><?php endforeach; ?>
        </tbody><tfoot><tr class="fw-bold"><td>Total <?= $year ?></td><td class="text-end"><?= (int) $annual['monthly_count'] ?></td><td class="text-end"><?= (int) $annual['psb_count'] ?></td><td class="text-end"><?= $money($annual['gross_income']) ?></td><td class="text-end text-warning">-<?= $money($annual['technician_deduction']) ?></td><td class="text-end text-warning">-<?= $money($annual['promoter_deduction']) ?></td><td class="text-end text-success"><?= $money($annual['net_income']) ?></td></tr></tfoot></table></div></div>

        <div class="card glass-card shadow-sm mt-3"><div class="card-header glass-header"><i class="fa-solid fa-receipt me-2"></i>Detail <?= $months[$month] ?> <?= $year ?></div><div class="card-body table-responsive"><table class="table table-bordered table-striped table-nowrap"><thead><tr><th>Tanggal</th><th>ID Pelanggan</th><th>Nama</th><th>Jenis</th><th>Paket</th><th class="text-end">Pembayaran</th><th class="text-end">Teknisi</th><th class="text-end">Promotor</th><th class="text-end">Bersih</th></tr></thead><tbody>
            <?php if (!$details): ?><tr><td colspan="9" class="text-center text-muted">Belum ada transaksi pada bulan ini.</td></tr><?php endif; ?>
            <?php foreach ($details as $item): ?><tr><td><?= html_escape($item['payment_date']) ?></td><td><?= html_escape($item['customer_code']) ?></td><td><?= html_escape($item['customer_name']) ?></td><td><span class="monitoring-badge <?= strtoupper($item['payment_type']) === 'PSB' ? 'is-offline' : 'is-online' ?>"><?= html_escape($item['payment_type']) ?></span></td><td><?= html_escape($item['package_name']) ?></td><td class="text-end"><?= $money($item['price']) ?></td><td class="text-end"><?= $money($item['technician_deduction']) ?></td><td class="text-end"><?= $money($item['promoter_deduction']) ?></td><td class="text-end fw-bold"><?= $money($item['net_income']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
    </section>
</main>
