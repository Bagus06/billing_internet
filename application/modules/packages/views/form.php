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
            <img src="<?= base_url(app_setting('logo_path', 'assets/img/logo.jpeg')) ?>" alt="<?= html_escape(app_setting('isp_name', 'ISP BATARA NET')) ?>" class="brand-logo">
            <div>
                <div class="menu-eyebrow">
                    <i class="fa-solid fa-wifi me-2"></i>
                    <?= html_escape(app_setting('isp_name', 'ISP BATARA NET')) ?>
                </div>
                <div class="brand-subtitle"><?= $mode === 'create' ? 'Tambah Paket' : 'Edit Paket' ?></div>
            </div>
        </div>

        <?php $this->load->view('template/flash'); ?>

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

                <div class="col-12"><div class="glass-panel p-3"><h5 class="mb-3"><i class="fa-solid fa-network-wired me-2"></i>Provisioning PPP Profile MikroTik</h5><div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Router <span class="text-danger">*</span></label><select name="router_id" id="packageRouter" class="form-select" required><option value="">Pilih router aktif</option><?php foreach ($routers as $router): ?><option value="<?= (int) $router['id'] ?>" <?= (int) $package['router_id'] === (int) $router['id'] ? 'selected' : '' ?>><?= html_escape($router['name'] . ' - ' . $router['host']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label class="form-label">PPP Profile <span class="text-danger">*</span></label><select name="profile_selection" id="packageProfile" class="form-select" required><option value="">Pilih profile</option><option value="__new__">+ Other / Create New Profile</option><?php foreach ($ppp_profiles as $profile): ?><option value="<?= html_escape($profile['profile_key']) ?>" data-router-id="<?= (int) $profile['router_id'] ?>" data-name="<?= html_escape($profile['name']) ?>" data-local="<?= html_escape($profile['local-address']) ?>" data-remote="<?= html_escape($profile['remote-address']) ?>" data-rate="<?= html_escape($profile['rate-limit']) ?>" data-dns="<?= html_escape($profile['dns-server']) ?>" data-only-one="<?= html_escape($profile['only-one']) ?>" data-tcp-mss="<?= html_escape($profile['change-tcp-mss']) ?>" <?= (int) $package['router_id'] === (int) $profile['router_id'] && (string) $package['ppp_profile_key'] === (string) $profile['profile_key'] ? 'selected' : '' ?>><?= html_escape($profile['name']) ?></option><?php endforeach; ?></select><div class="form-text">Perubahan field pada profile existing otomatis diterapkan ke MikroTik saat form disimpan.</div></div>
                    <div class="col-md-6"><label class="form-label">Nama PPP Profile <span class="text-danger">*</span></label><input name="ppp_profile_name" class="form-control" maxlength="64" pattern="[A-Za-z0-9_.@ \-]+" value="<?= html_escape($package['ppp_profile_name']) ?>" placeholder="Contoh: PAKET-20M" required><div class="form-text">Harus unik pada router yang dipilih.</div></div>
                    <div class="col-md-6"><label class="form-label">Local Address</label><input name="ppp_local_address" class="form-control" value="<?= html_escape($package['ppp_local_address']) ?>" placeholder="Contoh: 10.10.10.1"><div class="form-text">Alamat IP gateway PPP. Kosongkan untuk default.</div></div>
                    <div class="col-md-6"><label class="form-label">Remote Address / IP Pool <span class="text-danger">*</span></label><select name="ppp_remote_address" id="packageRemotePool" class="form-select" required><option value="">Pilih router terlebih dahulu</option><?php foreach ($ip_pools as $pool): ?><option data-router-id="<?= (int) $pool['router_id'] ?>" value="<?= html_escape($pool['name']) ?>" <?= (int) $package['router_id'] === (int) $pool['router_id'] && $package['ppp_remote_address'] === $pool['name'] ? 'selected' : '' ?>><?= html_escape($pool['name'] . ' (' . $pool['ranges'] . ')') ?></option><?php endforeach; ?></select></div>
                    <div class="col-12"><label class="form-label">Rate Limit MikroTik <span class="text-danger">*</span></label><input type="text" name="ppp_rate_limit" class="form-control font-monospace" maxlength="100" value="<?= html_escape($package['ppp_rate_limit']) ?>" placeholder="35M/35M 40M/40M 26250K/26250K 23/23 8 4375K/4375K" required><div class="form-text">Format: upload/download [burst limit] [burst threshold] [burst time] [priority 1-8] [minimum rate]. Satuan rate yang didukung: K, M, dan G. Contoh sederhana: 15M/15M.</div></div>
                    <div class="col-md-6"><label class="form-label">DNS Server</label><input name="ppp_dns_server" class="form-control" value="<?= html_escape($package['ppp_dns_server']) ?>" placeholder="8.8.8.8,1.1.1.1"><div class="form-text">Pisahkan beberapa IP dengan koma.</div></div>
                    <div class="col-md-6"><label class="form-label">Only One Session</label><select name="ppp_only_one" class="form-select"><?php foreach (['yes' => 'Ya', 'no' => 'Tidak', 'default' => 'Default'] as $key => $label): ?><option value="<?= $key ?>" <?= $package['ppp_only_one'] === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label class="form-label">Change TCP MSS</label><select name="ppp_change_tcp_mss" class="form-select"><?php foreach (['yes' => 'Ya', 'no' => 'Tidak', 'default' => 'Default'] as $key => $label): ?><option value="<?= $key ?>" <?= $package['ppp_change_tcp_mss'] === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                    <?php if (!empty($router_errors)): ?><div class="col-12 text-warning small"><?= html_escape(implode(' | ', $router_errors)) ?></div><?php endif; ?>
                </div></div></div>

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
<script>
(function () {
    const router = document.getElementById('packageRouter');
    const pool = document.getElementById('packageRemotePool');
    const profile = document.getElementById('packageProfile');
    const options = Array.from(pool.querySelectorAll('option[data-router-id]'));
    const profileOptions = Array.from(profile.querySelectorAll('option[data-router-id]'));
    const fields = {
        name: document.querySelector('[name="ppp_profile_name"]'), local: document.querySelector('[name="ppp_local_address"]'),
        remote: pool, rate: document.querySelector('[name="ppp_rate_limit"]'),
        dns: document.querySelector('[name="ppp_dns_server"]'), onlyOne: document.querySelector('[name="ppp_only_one"]'), tcpMss: document.querySelector('[name="ppp_change_tcp_mss"]')
    };
    function filterPools() {
        const selected = router.value;
        let visible = 0;
        options.forEach(function (option) {
            const show = option.dataset.routerId === selected;
            option.hidden = !show;
            option.disabled = !show;
            if (show) visible++;
        });
        profileOptions.forEach(function (option) { const show = option.dataset.routerId === selected; option.hidden = !show; option.disabled = !show; });
        if (profile.selectedOptions.length && profile.selectedOptions[0].disabled) profile.value = '';
        if (pool.selectedOptions.length && pool.selectedOptions[0].disabled) pool.value = '';
        pool.options[0].textContent = selected ? (visible ? 'Pilih IP Pool' : 'Router tidak memiliki IP Pool') : 'Pilih router terlebih dahulu';
    }
    function setFieldsEditable(editable) {
        [fields.name, fields.local, fields.rate, fields.dns].forEach(function (field) { field.readOnly = !editable; });
        [fields.remote, fields.onlyOne, fields.tcpMss].forEach(function (field) { field.disabled = !editable; });
    }
    function fillProfile() {
        const option = profile.selectedOptions[0];
        const isNew = profile.value === '__new__';
        const isExisting = option && option.dataset.routerId;
        if (isNew) {
            fields.name.value = ''; fields.local.value = ''; fields.remote.value = ''; fields.rate.value = '';
            setFieldsEditable(true); filterPools(); return;
        }
        if (isExisting) {
            fields.name.value = option.dataset.name || ''; fields.local.value = option.dataset.local || '';
            fields.rate.value = option.dataset.rate || '';
            fields.dns.value = option.dataset.dns || ''; fields.onlyOne.value = option.dataset.onlyOne || 'default'; fields.tcpMss.value = option.dataset.tcpMss || 'default';
            filterPools(); fields.remote.value = option.dataset.remote || '';
            setFieldsEditable(true); return;
        }
        setFieldsEditable(false);
    }
    router.addEventListener('change', function () { filterPools(); profile.value = ''; fillProfile(); });
    profile.addEventListener('change', fillProfile);
    filterPools();
    fillProfile();
})();
</script>
