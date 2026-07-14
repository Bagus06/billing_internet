<main class="container py-4 py-md-5">
    <div class="page-toolbar mb-3"><a href="<?= site_url('/') ?>" class="back-button"><i class="fa-solid fa-arrow-left"></i><span>Kembali</span></a></div>
    <section class="menu-shell monitoring-shell">
        <div class="menu-heading"><h1>Profile</h1><p>Perbarui identitas akun dan keamanan password Anda.</p></div>
        <?php $this->load->view('template/flash'); ?>
        <div class="row g-4 mt-1">
            <div class="col-lg-7"><div class="card glass-card h-100"><div class="card-body"><h5>Informasi Akun</h5>
                <form method="post" action="<?= site_url('profile/update') ?>"><div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Nama</label><input name="name" class="form-control" value="<?= html_escape($user['name']) ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Username</label><input name="username" class="form-control" value="<?= html_escape($user['username']) ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= html_escape($user['email']) ?>"></div>
                    <div class="col-md-6"><label class="form-label">Role</label><input class="form-control" value="<?= html_escape($user['role_name'] ?: '-') ?>" disabled></div>
                </div><button class="back-button border-0 mt-3" type="submit"><i class="fa-solid fa-save"></i><span>Simpan Profile</span></button></form>
            </div></div></div>
            <div class="col-lg-5"><div class="card glass-card h-100"><div class="card-body"><h5>Ubah Password</h5>
                <form method="post" action="<?= site_url('profile/password') ?>">
                    <div class="mb-3"><label class="form-label">Password Saat Ini</label><input type="password" name="current_password" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Password Baru</label><input type="password" name="password" class="form-control" minlength="8" required></div>
                    <div class="mb-3"><label class="form-label">Konfirmasi Password</label><input type="password" name="password_confirmation" class="form-control" minlength="8" required></div>
                    <button class="back-button border-0" type="submit"><i class="fa-solid fa-key"></i><span>Ubah Password</span></button>
                </form>
            </div></div></div>
        </div>
    </section>
</main>
