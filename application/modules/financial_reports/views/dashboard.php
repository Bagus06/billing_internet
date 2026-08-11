<?php
$monthNames = app_language() === 'en' ? ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] : ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$net=[]; $added=[]; $removed=[]; $netCustomers=[];
for ($monthIndex=1; $monthIndex<=12; $monthIndex++) {
    $net[]=(float)$summary[$monthIndex]['net_income'];
    $added[]=(int)$customer_movement[$monthIndex]['added']+(int)$customer_movement[$monthIndex]['reactivated'];
    $removed[]=(int)$customer_movement[$monthIndex]['removed']; $netCustomers[]=(int)$customer_movement[$monthIndex]['net'];
}
?>
<main class="container-fluid report-dashboard-container py-4 py-md-5">
    <div class="page-toolbar mb-3"><a href="<?= site_url('/') ?>" class="back-button"><i class="fa-solid fa-arrow-left"></i><span>Kembali</span></a><a href="<?= site_url('financial-reports?view=detail&year='.$year.'&month='.$month) ?>" class="back-button"><i class="fa-solid fa-table-list"></i><span>Lihat Detail Report</span></a></div>
    <section class="menu-shell monitoring-shell">
        <div class="menu-heading"><h1><i class="fa-solid fa-chart-column me-2"></i>Dashboard Laporan</h1><p>Grafik penghasilan dan pergerakan pelanggan aktif selama satu tahun.</p></div>
        <form method="get" action="<?= site_url('financial-reports') ?>" class="glass-panel p-3 mt-3"><div class="row g-2 align-items-end"><div class="col-md-10"><label class="form-label">Filter Tahun</label><select name="year" class="form-select"><?php foreach($years as $item): ?><option value="<?= $item ?>" <?= $year===$item?'selected':'' ?>><?= $item ?></option><?php endforeach; ?></select></div><div class="col-md-2"><button class="filter-submit-button w-100 justify-content-center"><i class="fa-solid fa-filter"></i><span>Tampilkan</span></button></div></div></form>

        <div class="row g-3 mt-1"><div class="col-md-4"><div class="glass-panel result-box"><div class="text-muted">Bruto Tahunan</div><h3>Rp <?= number_format($annual['gross_income'],0,',','.') ?></h3></div></div><div class="col-md-4"><div class="glass-panel result-box"><div class="text-muted">Total Potongan PSB</div><h3 class="text-warning">Rp <?= number_format($annual['technician_deduction']+$annual['promoter_deduction'],0,',','.') ?></h3></div></div><div class="col-md-4"><div class="glass-panel result-box"><div class="text-muted">Bersih Tahunan</div><h3 class="text-success">Rp <?= number_format($annual['net_income'],0,',','.') ?></h3></div></div></div>

        <div class="row g-3 mt-1"><div class="col-12 col-lg-6"><div class="card glass-card chart-report-card h-100"><div class="card-header glass-header"><i class="fa-solid fa-money-bill-trend-up me-2"></i>Penghasilan Bulanan <?= $year ?></div><div class="card-body"><div class="report-chart-wrap"><canvas id="incomeChart"></canvas></div></div></div></div>
        <div class="col-12 col-lg-6"><div class="card glass-card chart-report-card h-100"><div class="card-header glass-header"><i class="fa-solid fa-users-line me-2"></i>Penambahan / Pengurangan Pelanggan Aktif <?= $year ?></div><div class="card-body"><div class="report-chart-wrap"><canvas id="customerChart"></canvas></div><div class="form-text mt-2">Penambahan mencakup pelanggan baru dan pelanggan yang diaktifkan kembali. Pengurangan dicatat sejak histori status diterapkan.</div></div></div></div></div>
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
<script>
(function(){
if(typeof Chart==='undefined')return;
function chartTheme(){var light=document.documentElement.dataset.appTheme==='light';return{light:light,text:light?'#53657b':'#aebfd2',grid:light?'rgba(65,88,116,.13)':'rgba(148,163,184,.1)'};}
var palette=chartTheme(); Chart.defaults.color=palette.text; Chart.defaults.borderColor=palette.grid;
const labels=<?= json_encode($monthNames) ?>;
const common={responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},plugins:{legend:{labels:{usePointStyle:true,padding:18,color:palette.text}}},scales:{x:{ticks:{color:palette.text},grid:{display:false}},y:{beginAtZero:true,ticks:{color:palette.text},grid:{color:palette.grid}}}};
const incomeChart=new Chart(document.getElementById('incomeChart'),{type:'bar',data:{labels:labels,datasets:[
{label:<?= json_encode(app_language()==='en'?'Net Income':'Pendapatan Bersih') ?>,data:<?= json_encode($net) ?>,backgroundColor:'rgba(85,232,155,.3)',borderColor:'#55e89b',borderWidth:1.25,borderRadius:8,borderSkipped:false,maxBarThickness:24,categoryPercentage:.62,barPercentage:.7}
]},options:Object.assign({},common,{plugins:{legend:common.plugins.legend,tooltip:{callbacks:{label:function(ctx){return ctx.dataset.label+': Rp '+Number(ctx.raw||0).toLocaleString('id-ID')}}}}})});
const customerChart=new Chart(document.getElementById('customerChart'),{type:'bar',data:{labels:labels,datasets:[
{label:<?= json_encode(app_language()==='en'?'Additions':'Penambahan') ?>,data:<?= json_encode($added) ?>,backgroundColor:'rgba(85,232,155,.34)',borderColor:'#55e89b',borderWidth:1,borderRadius:7,borderSkipped:false,maxBarThickness:18,categoryPercentage:.58,barPercentage:.68},
{label:<?= json_encode(app_language()==='en'?'Reductions':'Pengurangan') ?>,data:<?= json_encode($removed) ?>,backgroundColor:'rgba(255,100,130,.34)',borderColor:'#ff6482',borderWidth:1,borderRadius:7,borderSkipped:false,maxBarThickness:18,categoryPercentage:.58,barPercentage:.68},
{label:<?= json_encode(app_language()==='en'?'Net Change':'Perubahan Bersih') ?>,data:<?= json_encode($netCustomers) ?>,type:'line',borderColor:'#64d2ff',pointBackgroundColor:'#64d2ff',pointRadius:4,tension:.35}
]},options:common});
window.addEventListener('appthemechange',function(){var next=chartTheme();[incomeChart,customerChart].forEach(function(chart){chart.options.plugins.legend.labels.color=next.text;chart.options.scales.x.ticks.color=next.text;chart.options.scales.y.ticks.color=next.text;chart.options.scales.y.grid.color=next.grid;chart.update('none');});});
})();
</script>
