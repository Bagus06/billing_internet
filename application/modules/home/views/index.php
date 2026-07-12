<?php
$permissions=$this->session->userdata('auth_permissions')?:[];
$can=function($key)use($permissions){return in_array($key,$permissions,true);};
$groups=[
 ['id'=>'operations','title'=>'Operasional','subtitle'=>'Pelanggan dan monitoring','icon'=>'fa-chart-line','color'=>'cyan','items'=>array_values(array_filter([
  $can('monitoring')?['Monitoring','Status & trafik pelanggan','monitoring','fa-chart-line']:null,
  $can('customers')?['Pelanggan','Data & layanan pelanggan','customers','fa-users']:null]))],
 ['id'=>'billing','title'=>'Billing & Layanan','subtitle'=>'Paket, pembayaran, dan laporan','icon'=>'fa-wallet','color'=>'violet','items'=>array_values(array_filter([
  $can('packages')?['Paket Internet','Harga & relasi profile','packages','fa-box-open']:null,
  $can('payments')?['Pembayaran','Tagihan & transaksi','payments','fa-wallet']:null,
  ($can('financial_reports')||$can('payments'))?['Laporan Keuangan','Penghasilan dan potongan PSB','financial-reports','fa-chart-column']:null,
  $can('payments')?['Bagi Hasil','Distribusi laba bersih mitra','profit-sharing','fa-handshake']:null]))],
 ['id'=>'network','title'=>'Network','subtitle'=>'Router dan perhitungan jaringan','icon'=>'fa-network-wired','color'=>'blue','items'=>array_values(array_filter([
  $can('routers')?['Router MikroTik','Koneksi & API RouterOS','routers','fa-server']:null,
  $can('routers')?['PPP Profile','Bandwidth profile RouterOS','mikrotik-profiles','fa-gauge-high']:null,
  $can('calculator')?['Kalkulator','Perhitungan jaringan','calculator','fa-calculator']:null]))],
 ['id'=>'admin','title'=>'Administrasi','subtitle'=>'Pengguna dan konfigurasi','icon'=>'fa-shield-halved','color'=>'orange','items'=>array_values(array_filter([
  $can('users')?['Pengguna','Akun operator aplikasi','users','fa-user-gear']:null,
  $can('roles')?['Role & Akses','Hak akses pengguna','roles','fa-shield-halved']:null,
  $can('settings')?['Konfigurasi','Identitas & parameter sistem','settings','fa-gears']:null]))],
 ['id'=>'account','title'=>'Akun','subtitle'=>'Profile akun pengguna','icon'=>'fa-circle-user','color'=>'green','items'=>array_values(array_filter([
  $can('profile')?['Profile','Kelola profile akun','profile','fa-circle-user']:null]))],
];
$groups=array_values(array_filter($groups,function($group){return count($group['items'])>0;}));
?>
<main class="container py-4 py-md-5 home-launcher"><section class="menu-shell">
<div class="brand-bar"><img src="<?= base_url(app_setting('logo_path','assets/img/logo.jpeg')) ?>" alt="Logo" class="brand-logo"><div><div class="menu-eyebrow"><i class="fa-solid fa-circle-nodes me-2"></i><?= html_escape(app_setting('isp_name','ISP BATARA NET')) ?></div><div class="brand-subtitle"><?= html_escape(app_setting('app_subtitle','Mikrotik Network Tools')) ?></div></div></div>
<div class="menu-heading"><h1>Menu Utama</h1><p>Pilih kategori aplikasi untuk menampilkan fitur yang tersedia.</p></div>
<div class="android-launcher mt-4"><?php foreach($groups as $index=>$group): ?><button type="button" class="launcher-app" data-launcher="<?= $group['id'] ?>" aria-expanded="false"><span class="launcher-icon is-<?= $group['color'] ?>"><i class="fa-solid <?= $group['icon'] ?>"></i></span><strong><?= html_escape($group['title']) ?></strong><small><?= html_escape($group['subtitle']) ?></small></button><?php endforeach; ?></div>
<div class="launcher-panels mt-4"><?php foreach($groups as $group): ?><section class="launcher-panel" data-panel="<?= $group['id'] ?>" hidden><div class="launcher-panel-head"><div><small>KATEGORI</small><h2><?= html_escape($group['title']) ?></h2></div><button type="button" class="launcher-close" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button></div><div class="launcher-subgrid"><?php foreach($group['items'] as $item): ?><a href="<?= site_url($item[2]) ?>" class="launcher-subapp"><span><i class="fa-solid <?= $item[3] ?>"></i></span><div><strong><?= html_escape($item[0]) ?></strong><small><?= html_escape($item[1]) ?></small></div><i class="fa-solid fa-chevron-right"></i></a><?php endforeach; ?></div></section><?php endforeach; ?></div>
</section></main>
<script>document.querySelectorAll('[data-launcher]').forEach(function(button){button.addEventListener('click',function(){var id=this.dataset.launcher;document.querySelectorAll('[data-panel]').forEach(function(panel){panel.hidden=panel.dataset.panel!==id});document.querySelectorAll('[data-launcher]').forEach(function(item){item.setAttribute('aria-expanded',item.dataset.launcher===id?'true':'false')});document.querySelector('[data-panel="'+id+'"]').scrollIntoView({behavior:'smooth',block:'nearest'})})});document.querySelectorAll('.launcher-close').forEach(function(button){button.addEventListener('click',function(){this.closest('.launcher-panel').hidden=true;document.querySelectorAll('[data-launcher]').forEach(function(item){item.setAttribute('aria-expanded','false')})})});</script>
