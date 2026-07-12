(function () {
    'use strict';
    const language = document.documentElement.dataset.appLanguage || 'id';
    if (language !== 'en') return;

    const dictionary = {
        'Home':'Home','Menu':'Menu','Menu Utama':'Main Menu','Operasional':'Operations','Monitoring':'Monitoring','Status & trafik pelanggan':'Customer status & traffic',
        'Pilih kategori aplikasi untuk menampilkan fitur yang tersedia.':'Select an application category to display its available features.','Pelanggan dan monitoring':'Customers and monitoring','Paket, pembayaran, dan laporan':'Plans, payments, and reports','Router dan perhitungan jaringan':'Routers and network calculations','Pengguna dan konfigurasi':'Users and configuration','Akun':'Account','Profile akun pengguna':'User account profile','KATEGORI':'CATEGORY','Tutup':'Close',
        'Pelanggan':'Customers','Data & layanan pelanggan':'Customer data & services','Billing & Layanan':'Billing & Services',
        'Paket Internet':'Internet Plans','Harga & relasi profile':'Pricing & profile mapping','Pembayaran':'Payments','Tagihan & transaksi':'Bills & transactions',
        'Network':'Network','Router MikroTik':'MikroTik Routers','Koneksi & API RouterOS':'RouterOS connection & API','PPP Profile':'PPP Profiles',
        'Bandwidth profile RouterOS':'RouterOS bandwidth profiles','Kalkulator':'Calculator','Perhitungan jaringan':'Network calculations',
        'Administrasi':'Administration','Pengguna':'Users','Akun operator aplikasi':'Application operator accounts','Role & Akses':'Roles & Access',
        'Hak akses pengguna':'User permissions','Konfigurasi':'Configuration','Identitas & parameter sistem':'Identity & system parameters',
        'Profile akun saya':'My account profile','Keluar':'Sign Out','Akhiri sesi aplikasi':'End application session',
        'Kembali':'Back','Simpan':'Save','Edit':'Edit','Delete':'Delete','Tambah':'Add','Cari':'Search','Refresh API':'Refresh API',
        'Action':'Actions','Status':'Status','Aktif':'Active','Nonaktif':'Inactive','Nama':'Name','Keterangan':'Description','Harga':'Price',
        'Berhasil':'Success','Terjadi Kesalahan':'An Error Occurred','Informasi':'Information','Konfirmasi':'Confirmation','Oke':'OK',
        'Ya, lanjutkan':'Yes, continue','Batal':'Cancel','Memuat...':'Loading...','Mohon tunggu sebentar':'Please wait a moment',
        'Masuk':'Sign In','Username':'Username','Password':'Password','Login Aplikasi':'Application Login','Login':'Login',
        'Data Pelanggan':'Customer Data','Tambah Pelanggan':'Add Customer','Edit Pelanggan':'Edit Customer','Nama Pelanggan':'Customer Name',
        'Nomor Telepon':'Phone Number','Alamat':'Address','Paket':'Plan','Group':'Group','Promotor':'Promoter','Tanggal Pasang':'Installation Date',
        'Data belum tersedia':'No data available','Belum ada data.':'No data yet.','Tidak ada kegagalan.':'No failures.',
        'Tambah Paket':'Add Plan','Edit Paket':'Edit Plan','Nama Paket':'Plan Name','PPP Profile MikroTik':'MikroTik PPP Profiles',
        'Tambah Profile':'Add Profile','Nama Profile':'Profile Name','Local Address':'Local Address','Remote Address / Pool':'Remote Address / Pool',
        'Rate Limit':'Rate Limit','DNS Server':'DNS Server','Only One':'Only One','Change TCP MSS':'Change TCP MSS','Dipakai Secret':'Used by Secrets',
        'Simpan ke MikroTik':'Save to MikroTik','Tidak ada profile atau router belum dipilih.':'No profiles found or no router selected.',
        'Konfigurasi Aplikasi':'Application Configuration','Kelola identitas ISP, perilaku monitoring, dan parameter umum aplikasi.':'Manage ISP identity, monitoring behavior, and general application parameters.',
        'Branding':'Branding','Nama ISP':'ISP Name','Subtitle Aplikasi':'Application Subtitle','Logo ISP':'ISP Logo','Teks Footer':'Footer Text',
        'Refresh Data Utama (detik)':'Main Data Refresh (seconds)','Refresh Traffic (detik)':'Traffic Refresh (seconds)',
        'Suffix Username PPPoE':'PPPoE Username Suffix','Batas Sinyal Normal (dBm)':'Normal Signal Threshold (dBm)',
        'Batas Sinyal Warning (dBm)':'Warning Signal Threshold (dBm)','Aplikasi':'Application','Default Data per Halaman':'Default Rows per Page',
        'Bahasa Default':'Default Language','Simpan Konfigurasi':'Save Configuration','Digunakan jika pengguna belum memilih bahasa melalui top bar.':'Used when a user has not selected a language from the top bar.',
        'Tambah User':'Add User','Edit User':'Edit User','Tambah Role':'Add Role','Edit Role':'Edit Role','Hak Akses':'Permissions',
        'Data Mikrotik':'MikroTik Data','Tambah Mikrotik':'Add MikroTik','Edit Mikrotik':'Edit MikroTik','Router':'Router',
        'Detail Pelanggan':'Customer Details','Disconnect':'Disconnect','Connect':'Connect','Online':'Online','Offline':'Offline',
        'Search nama, NIK, atau ID customer':'Search name, ID number, or customer ID','Urutkan':'Sort','Semua':'All',
        'Sync':'Sync','Profile':'Profile','PPP Secret':'PPP Secret','Dropdown hanya menampilkan data yang gagal disinkronkan beserta penyebabnya.':'The dropdown only shows failed synchronization items and their causes.',

        'Pilih fitur operasional jaringan yang ingin digunakan.':'Choose the network operation feature you want to use.',
        'Calculator Redaman':'Optical Loss Calculator','Hitung loss splitter cascade dan Rx Power ONT secara otomatis.':'Automatically calculate cascaded splitter loss and ONT Rx Power.',
        'Data Mikrotik':'MikroTik Data','Tambah, edit, hapus, dan buka monitoring router Mikrotik.':'Add, edit, delete, and monitor MikroTik routers.',
        'Data Pelanggan':'Customer Data','Kelola pelanggan, paket internet, tagihan, dan status layanan.':'Manage customers, internet plans, bills, and service status.',
        'Kelola master paket dan harga untuk data pelanggan.':'Manage plan catalog and pricing for customers.',
        'Data Pembayaran':'Payment Data','Lihat riwayat pembayaran pelanggan bulanan dan PSB.':'View monthly customer and installation payment history.',
        'Atur branding, interval monitoring, filter PPPoE, optical, dan parameter aplikasi.':'Configure branding, monitoring intervals, PPPoE filters, optical values, and application parameters.',

        'Monitoring Mikrotik':'MikroTik Monitoring','Mikrotik API Monitoring':'MikroTik API Monitoring',
        'Status pelanggan PPPoE dengan username':'PPPoE customer status with username','terhubung ke data pelanggan berdasarkan NIK.':'linked to customer data by national ID.',
        'Total Secret':'Total Secrets','Menghubungkan ke Mikrotik API...':'Connecting to MikroTik API...',
        'Cari nama, NIK, ID atau kode pelanggan...':'Search name, national ID, customer ID, or code...',
        'Urutkan pelanggan':'Sort customers','Status: ON dahulu':'Status: ON first','Nama: terkecil':'Name: smallest',
        'Nama: A–Z':'Name: A–Z','Nama: Z–A':'Name: Z–A','NIK: terkecil':'National ID: ascending',
        'ID pelanggan: terkecil':'Customer ID: ascending','ID pelanggan: terbesar':'Customer ID: descending',
        'Sinyal: terkuat':'Signal: strongest','Sinyal: terlemah':'Signal: weakest','Status Pelanggan PPPoE':'PPPoE Customer Status',
        'Memuat data PPPoE...':'Loading PPPoE data...','Detail Data Pelanggan':'Customer Details','Tutup':'Close',
        'Uptime':'Uptime','Last Off':'Last Offline','Redaman':'Optical Signal','Traffic Aktif':'Active Traffic',

        'Kelola pelanggan, paket layanan, status aktif, dan status pembayaran.':'Manage customers, service plans, active status, and payment status.',
        'ID Pelanggan':'Customer ID','Telepon':'Phone','NIK KTP':'National ID','Foto KTP':'ID Card Photo','Alamat / Koordinat':'Address / Coordinates',
        'Pilih Paket':'Select Plan','Tanggal PSB':'Installation Date','Kelompok':'Group','Status Pelanggan':'Customer Status',
        'File saat ini:':'Current file:','Per page':'Per page','Cari ID':'Search ID','Cari nama':'Search name','Cari telepon':'Search phone',
        'Cari paket':'Search plan','Cari kelompok':'Search group','Cari status':'Search status','Cari bayar':'Search payment',
        'SUDAH BAYAR':'PAID','BELUM BAYAR':'UNPAID','Lihat KTP':'View ID Card','Bayar':'Pay','Reset':'Reset','Search':'Search',
        'Prev':'Previous','Next':'Next','Page':'Page','Belum ada data pelanggan.':'No customer data yet.',

        'Riwayat pembayaran pelanggan berdasarkan struktur sheet PEMBAYARAN.':'Customer payment history based on the PAYMENT sheet structure.',
        'Input':'Input','Bulan':'Month','Tahun':'Year','Metode':'Method','Ket.':'Notes','Tanggal Input':'Input Date',
        'Pembayaran':'Payment','Tanggal Bayar':'Payment Date','Belum ada data pembayaran.':'No payment data yet.',

        'Kelola router Mikrotik yang akan dipantau melalui API.':'Manage MikroTik routers monitored through the API.',
        'Tambah Router':'Add Router','Nama Router':'Router Name','Host / IP':'Host / IP','Port API':'API Port','API SSL':'API SSL',
        'Username API':'API Username','Password API':'API Password','Kosongkan jika tidak ingin mengubah password':'Leave blank to keep the current password',
        'Belum ada data router.':'No router data yet.','View':'View','Ya':'Yes','Tidak':'No',

        'Provisioning PPP Profile MikroTik':'MikroTik PPP Profile Provisioning','Pilih router aktif':'Select an active router',
        'Pilih profile':'Select a profile','Perubahan field pada profile existing otomatis diterapkan ke MikroTik saat form disimpan.':'Changes to an existing profile are automatically applied to MikroTik when the form is saved.',
        'Nama PPP Profile':'PPP Profile Name','Harus unik pada router yang dipilih.':'Must be unique on the selected router.',
        'Alamat IP gateway PPP. Kosongkan untuk default.':'PPP gateway IP address. Leave blank to use the default.',
        'Remote Address / IP Pool':'Remote Address / IP Pool','Pilih router terlebih dahulu':'Select a router first','Pilih IP Pool':'Select an IP Pool',
        'Router tidak memiliki IP Pool':'The router has no IP Pool','Rate Limit MikroTik':'MikroTik Rate Limit',
        'Pisahkan beberapa IP dengan koma.':'Separate multiple IP addresses with commas.','Only One Session':'Only One Session',
        'Belum ada paket internet.':'No internet plans yet.','Sync Profile':'Sync Profiles','Sync Secret':'Sync Secrets','Remote Pool':'Remote Pool',

        'CRUD profile dilakukan langsung melalui API RouterOS tanpa menyimpan data ke database aplikasi.':'Profile CRUD is performed directly through the RouterOS API without storing data in the application database.',
        'Perubahan langsung diterapkan ke MikroTik dan tidak disimpan pada database aplikasi.':'Changes are applied directly to MikroTik and are not stored in the application database.',
        'Default / kosong':'Default / empty','Kosong berarti unlimited. Mendukung K, M, G dan format burst RouterOS.':'Empty means unlimited. Supports K, M, G and the RouterOS burst format.',

        'Atur kelompok pengguna dan akses setiap fitur.':'Manage user groups and access to each feature.','Tambah Role':'Add Role','Nama Role':'Role Name',
        'Deskripsi':'Description','Belum ada role.':'No roles yet.','Roles':'Roles','Email':'Email','Role':'Role','Terakhir Login':'Last Login',
        'Belum ada user.':'No users yet.','Password Baru':'New Password','Konfirmasi Password':'Confirm Password',
        'Profile Saya':'My Profile','Ubah Password':'Change Password','Password Saat Ini':'Current Password',

        'Parameter Link':'Link Parameters','Hover untuk membuka input Tx Power, fiber, connector, splice, dan margin.':'Hover to open Tx Power, fiber, connector, splice, and margin inputs.',
        'Panjang Fiber (Km)':'Fiber Length (Km)','Redaman Fiber (dB/Km)':'Fiber Loss (dB/Km)','Engineering Margin (dB)':'Engineering Margin (dB)',
        'Jumlah Connector':'Connector Count','Loss / Connector (dB)':'Loss / Connector (dB)','Jumlah Fusion Splice':'Fusion Splice Count',
        'Loss / Splice (dB)':'Loss / Splice (dB)','None':'None','Total Loss':'Total Loss','Device / Tahapan':'Device / Stage','Output Power (dBm)':'Output Power (dBm)',

        'Ditampilkan pada header, login, dan judul aplikasi.':'Displayed in the header, login page, and application title.',
        'Maksimal 2 MB. JPG, PNG, WebP, atau GIF.':'Maximum 2 MB. JPG, PNG, WebP, or GIF.',
        'Secret, active session, pelanggan, dan optical.':'Secrets, active sessions, customers, and optical data.',
        'Gunakan identifier timezone PHP, misalnya Asia/Jakarta.':'Use a PHP timezone identifier, such as Asia/Jakarta.'
        ,'Kelola akun pengguna dan role akses aplikasi.':'Manage application user accounts and access roles.'
        ,'Perbarui identitas akun dan keamanan password Anda.':'Update your account identity and password security.'
        ,'Kosongkan jika tidak diubah':'Leave blank to keep unchanged','Opsional':'Optional','Contoh: @BATARA.net':'Example: @BATARA.net'
        ,'Format: upload/download [burst limit] [burst threshold] [burst time] [priority 1-8] [minimum rate]. Satuan rate yang didukung: K, M, dan G. Contoh sederhana: 15M/15M.':'Format: upload/download [burst limit] [burst threshold] [burst time] [priority 1-8] [minimum rate]. Supported units: K, M, and G. Simple example: 15M/15M.'
        ,'20M/20M atau format burst lengkap':'20M/20M or the full burst format'
        ,'Hapus user ini?':'Delete this user?','Hapus role ini?':'Delete this role?','Hapus pelanggan ini?':'Delete this customer?'
        ,'Hapus router ini?':'Delete this router?','Hapus paket ini?':'Delete this plan?','Hapus pembayaran ini?':'Delete this payment?'
        ,'Sinkronkan seluruh parameter PPP Profile dari MikroTik?':'Synchronize all PPP Profile parameters from MikroTik?'
        ,'Terapkan profile setiap paket ke seluruh PPP Secret pelanggan?':'Apply each plan profile to all customer PPP Secrets?'
        ,'Detail Output Setiap Tahap':'Output Details for Each Stage','Detail Pelanggan':'Customer Details'
        ,'Tidak ada PPP Secret yang ditemukan.':'No PPP Secrets found.','Aktifkan PPP Secret':'Enable PPP Secret'
        ,'Gagal mengambil data monitoring.':'Failed to retrieve monitoring data.','Gagal mengirim perintah disconnect.':'Failed to send the disconnect command.'
        ,'Gagal mengaktifkan PPP Secret.':'Failed to enable the PPP Secret.','Disconnect user dan nonaktifkan PPP Secret ini?':'Disconnect this user and disable the PPP Secret?'
        ,'Aktifkan kembali PPP Secret user ini?':'Re-enable this user’s PPP Secret?','Ya, disconnect':'Yes, disconnect','Ya, aktifkan':'Yes, enable'
        ,'Secret dinonaktifkan; sesi aktif diputus jika tersedia.':'Secret disabled; the active session was disconnected when available.'
        ,'Gagal menonaktifkan Secret atau memutus koneksi.':'Failed to disable the Secret or disconnect the session.'
        ,'Router atau PPP Secret tidak valid.':'Invalid router or PPP Secret.','PPP Secret diaktifkan. Menunggu modem melakukan koneksi.':'PPP Secret enabled. Waiting for the modem to connect.'
        ,'Sinyal':'Signal','Detail':'Details','Terakhir Offline':'Last Offline','Nama Customer':'Customer Name','ID Customer':'Customer ID'
        ,'Profile masih digunakan PPP Secret':'The profile is still used by PPP Secrets'
        ,'Pilih role':'Select a role','Login Terakhir':'Last Login','Tanggal':'Date','Metode Pembayaran':'Payment Method'
        ,'Otomatis oleh sistem':'Automatically determined by the system'
        ,'Pembayaran pertama menjadi PSB. Pembayaran berikutnya otomatis BULANAN.':'The first payment is classified as PSB. Subsequent payments are automatically classified as MONTHLY.'
        ,'Laporan Keuangan':'Financial Report','Penghasilan bulanan & potongan PSB':'Monthly income & PSB deductions'
        ,'Rekap penghasilan bulanan setelah potongan teknisi dan promotor untuk pembayaran PSB.':'Monthly income summary after technician and promoter deductions for PSB payments.'
        ,'Tampilkan':'Show','Penghasilan Bruto':'Gross Income','Potongan Teknisi':'Technician Deduction','Potongan Promotor':'Promoter Deduction','Penghasilan Bersih':'Net Income'
        ,'Rekap Bulanan Tahun':'Monthly Summary for','Bruto':'Gross','Teknisi':'Technician','Promotor':'Promoter','Bersih':'Net','Jenis':'Type','Belum ada transaksi pada bulan ini.':'No transactions for this month.'
        ,'Kembali ke Grafik':'Back to Charts','Lihat Detail Report':'View Detailed Report','Dashboard Laporan':'Report Dashboard'
        ,'Grafik penghasilan dan pergerakan pelanggan aktif selama satu tahun.':'Income and active customer movement charts for a full year.'
        ,'Filter Tahun':'Filter Year','Bruto Tahunan':'Annual Gross','Total Potongan PSB':'Total PSB Deductions','Bersih Tahunan':'Annual Net'
        ,'Penghasilan Bulanan':'Monthly Income','Penambahan / Pengurangan Pelanggan Aktif':'Active Customer Additions / Reductions'
        ,'Penambahan':'Additions','Pengurangan':'Reductions','Perubahan Bersih':'Net Change'
        ,'Penambahan mencakup pelanggan baru dan pelanggan yang diaktifkan kembali. Pengurangan dicatat sejak histori status diterapkan.':'Additions include new and reactivated customers. Reductions are recorded from the point status history was introduced.'
        ,'Klik untuk mengubah status pelanggan':'Click to change customer status','Ya, nonaktifkan':'Yes, deactivate'
        ,'Proses perubahan status selesai.':'Status change completed.','Gagal mengubah status pelanggan.':'Failed to change customer status.'
        ,'Bagi Hasil':'Profit Sharing','Distribusi laba bersih mitra':'Partner net profit distribution'
        ,'Distribusi penghasilan bersih bulanan berdasarkan persentase kerja sama para pihak.':'Monthly net income distribution based on the parties’ partnership percentages.'
        ,'Laba Bersih':'Net Profit','Dasar pembagian 100%':'100% distribution basis','Pihak pertama':'First party','Pihak kedua':'Second party','Pihak Pertama':'First Party','Pihak Kedua':'Second Party'
        ,'Cadangan Perusahaan':'Company Reserve','Sisa setelah pembagian mitra':'Balance after partner distribution','Distribusi Bagi Hasil':'Profit Sharing Distribution'
        ,'Formula:':'Formula:','Nama Pihak Pertama':'First Party Name','Persentase Pihak Pertama':'First Party Percentage','Total Pihak Pertama':'First Party Total','Total Pihak Kedua':'Second Party Total','Cadangan':'Reserve'
        ,'Perubahan pengaturan hanya berlaku mulai bulan efektif dan tidak mengubah pembagian pada bulan sebelumnya.':'Settings changes only apply from the effective month and do not alter previous months.'
        ,'Nama Pihak Kedua':'Second Party Name','Persentase Pihak Kedua':'Second Party Percentage'
        ,'Sisa persentase otomatis dihitung sebagai saldo/cadangan perusahaan. Total pihak pertama dan kedua tidak boleh melebihi 100%.':'The remaining percentage is automatically treated as company reserve. The first and second party percentages must not exceed 100% in total.'
    };

    const patterns = [
        [/^Menampilkan (\d+) dari (\d+) pelanggan$/, 'Showing $1 of $2 customers'],
        [/^Menampilkan (\d+) dari (\d+) pembayaran$/, 'Showing $1 of $2 payments'],
        [/^(\d+) pelanggan$/, '$1 customers'],
        [/^(\d+) Secret$/, '$1 Secrets'],
        [/^Halaman (\d+) dari (\d+)$/, 'Page $1 of $2'],
        [/^Page (\d+) \/ (\d+)$/, 'Page $1 / $2'],
        [/^Tambah (.+)$/, 'Add $1'],
        [/^Edit (.+)$/, 'Edit $1']
        ,[/^Nama dikunci karena profile dipakai oleh (\d+) PPP Secret: (.+)\.$/, 'Name is locked because the profile is used by $1 PPP Secrets: $2.']
        ,[/^Hapus PPP Profile (.+) langsung dari MikroTik\?$/, 'Delete PPP Profile $1 directly from MikroTik?']
        ,[/^(\d+) dari (\d+) pelanggan$/, '$1 of $2 customers']
        ,[/^Aktifkan pelanggan (.+) dan enable PPP Secret\?$/, 'Activate customer $1 and enable the PPP Secret?']
        ,[/^Nonaktifkan pelanggan (.+), disable PPP Secret, dan putus sesi aktif\?$/, 'Deactivate customer $1, disable the PPP Secret, and disconnect the active session?']
        ,[/^Penghasilan Bulanan (\d{4})$/, 'Monthly Net Income $1']
        ,[/^Penambahan \/ Pengurangan Pelanggan Aktif (\d{4})$/, 'Active Customer Additions / Reductions $1']
        ,[/^Rekap Bulanan Tahun (\d{4})$/, 'Monthly Summary for $1']
        ,[/^Detail (January|February|March|April|May|June|July|August|September|October|November|December) (\d{4})$/, 'Details for $1 $2']
        ,[/^Total (\d{4})$/, 'Total $1']
        ,[/^(\d+) transaksi$/, '$1 transactions']
        ,[/^Laba Bersih (\d{4})$/, 'Net Profit $1']
        ,[/^Cadangan Perusahaan · ([\d.]+)%$/, 'Company Reserve · $1%']
        ,[/^Distribusi Bagi Hasil (\d{4})$/, 'Profit Sharing Distribution $1']
        ,[/^laba bersih setelah potongan PSB × persentase pihak\. Sisa ([\d.]+)% dicatat sebagai saldo\/cadangan perusahaan\.$/, 'net profit after PSB deductions × party percentage. The remaining $1% is recorded as company reserve.']
    ];

    function translated(value) {
        const original = String(value || '');
        const leading = original.match(/^\s*/)[0];
        const trailing = original.match(/\s*$/)[0];
        const key = original.trim();
        if (dictionary[key]) return leading + dictionary[key] + trailing;
        for (let index = 0; index < patterns.length; index++) {
            if (patterns[index][0].test(key)) return leading + key.replace(patterns[index][0], patterns[index][1]) + trailing;
        }
        return original;
    }
    window.AppI18n = translated;

    function translateElement(root) {
        if (!root || root.nodeType !== 1 || root.closest('[data-no-translate]')) return;
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
        const nodes = [];
        while (walker.nextNode()) nodes.push(walker.currentNode);
        nodes.forEach(function (node) {
            if (node.parentElement && !node.parentElement.closest('script,style,[data-no-translate]')) node.nodeValue = translated(node.nodeValue);
        });
        root.querySelectorAll('[placeholder],[title],[aria-label],[data-confirm],[data-message]').forEach(function (element) {
            ['placeholder','title','aria-label','data-confirm','data-message'].forEach(function (attribute) {
                if (element.hasAttribute(attribute)) element.setAttribute(attribute, translated(element.getAttribute(attribute)));
            });
        });
    }

    translateElement(document.body);
    new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
                if (node.nodeType === 1) translateElement(node);
                else if (node.nodeType === 3 && node.parentElement) node.nodeValue = translated(node.nodeValue);
            });
        });
    }).observe(document.body, { childList: true, subtree: true });
})();
