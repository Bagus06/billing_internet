<main class="container py-4 py-md-5">
    <div class="page-toolbar mb-3"><a href="<?= site_url('/') ?>" class="back-button"><i class="fa-solid fa-arrow-left"></i><span>Kembali</span></a><a href="<?= site_url('users/create') ?>" class="back-button"><i class="fa-solid fa-plus"></i><span>Tambah User</span></a></div>
    <section class="menu-shell monitoring-shell">
        <div class="menu-heading"><h1>Users</h1><p>Kelola akun pengguna dan role akses aplikasi.</p></div>
        <?php $this->load->view('../../views/layout/flash'); ?>
        <div class="card glass-card shadow-sm mt-3"><div class="card-body table-responsive"><table class="table table-bordered table-striped">
            <thead><tr><th>Nama</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Login Terakhir</th><th>Action</th></tr></thead><tbody>
            <?php if (empty($users)): ?><tr><td colspan="7" class="text-center text-muted">Belum ada user.</td></tr><?php endif; ?>
            <?php foreach ($users as $row): ?><tr><td><?= html_escape($row['name']) ?></td><td><?= html_escape($row['username']) ?></td><td><?= html_escape($row['email']) ?></td><td><?= html_escape($row['role_name'] ?: '-') ?></td><td><?= !empty($row['is_active']) ? 'Aktif' : 'Nonaktif' ?></td><td><?= html_escape($row['last_login'] ?: '-') ?></td><td><a class="monitoring-action text-decoration-none" href="<?= site_url('users/edit/' . $row['id']) ?>">Edit</a> <a class="monitoring-action text-decoration-none" href="<?= site_url('users/delete/' . $row['id']) ?>" data-confirm="Hapus user ini?">Delete</a></td></tr><?php endforeach; ?>
            </tbody></table></div></div>
    </section>
</main>
