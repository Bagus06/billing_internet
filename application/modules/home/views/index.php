<main class="container py-4 py-md-5">
    <section class="menu-shell">
        <div class="brand-bar">
            <img src="<?= base_url('assets/img/logo.jpeg') ?>" alt="ISP BATARA NET" class="brand-logo">
            <div>
                <div class="menu-eyebrow">
                    <i class="fa-solid fa-circle-nodes me-2"></i>
                    ISP BATARA NET
                </div>
                <div class="brand-subtitle">Mikrotik Network Tools</div>
            </div>
        </div>

        <div class="menu-heading">
            <h1>ISP BATARA NET</h1>
            <p>Pilih fitur operasional jaringan yang ingin digunakan.</p>
        </div>

        <div class="row g-3 mt-2">
            <div class="col-md-6 col-xl-4">
                <a class="tool-card" href="<?= site_url('calculator') ?>">
                    <span class="tool-icon">
                        <i class="fa-solid fa-network-wired"></i>
                    </span>
                    <span class="tool-copy">
                        <strong>Calculator Redaman</strong>
                        <small>Hitung loss splitter cascade dan Rx Power ONT secara otomatis.</small>
                    </span>
                    <i class="fa-solid fa-arrow-right tool-arrow"></i>
                </a>
            </div>

            <div class="col-md-6 col-xl-4">
                <a class="tool-card" href="<?= site_url('routers') ?>">
                    <span class="tool-icon">
                        <i class="fa-solid fa-server"></i>
                    </span>
                    <span class="tool-copy">
                        <strong>Data Mikrotik</strong>
                        <small>Tambah, edit, hapus, dan buka monitoring router Mikrotik.</small>
                    </span>
                    <i class="fa-solid fa-arrow-right tool-arrow"></i>
                </a>
            </div>

            <div class="col-md-6 col-xl-4">
                <a class="tool-card" href="<?= site_url('customers') ?>">
                    <span class="tool-icon">
                        <i class="fa-solid fa-users"></i>
                    </span>
                    <span class="tool-copy">
                        <strong>Data Pelanggan</strong>
                        <small>Kelola pelanggan, paket internet, tagihan, dan status layanan.</small>
                    </span>
                    <i class="fa-solid fa-arrow-right tool-arrow"></i>
                </a>
            </div>

            <div class="col-md-6 col-xl-4">
                <a class="tool-card" href="<?= site_url('packages') ?>">
                    <span class="tool-icon">
                        <i class="fa-solid fa-wifi"></i>
                    </span>
                    <span class="tool-copy">
                        <strong>Paket Internet</strong>
                        <small>Kelola master paket dan harga untuk data pelanggan.</small>
                    </span>
                    <i class="fa-solid fa-arrow-right tool-arrow"></i>
                </a>
            </div>

            <div class="col-md-6 col-xl-4">
                <a class="tool-card" href="<?= site_url('payments') ?>">
                    <span class="tool-icon">
                        <i class="fa-solid fa-money-bill-wave"></i>
                    </span>
                    <span class="tool-copy">
                        <strong>Data Pembayaran</strong>
                        <small>Lihat riwayat pembayaran pelanggan bulanan dan PSB.</small>
                    </span>
                    <i class="fa-solid fa-arrow-right tool-arrow"></i>
                </a>
            </div>
        </div>
    </section>
</main>
