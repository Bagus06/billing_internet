<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Financial_reports extends MY_Controller
{
    protected $permission = 'payments';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('financial_reports/financial_report_model');
        $this->load->model('profit_sharing/profit_sharing_model');
    }

    public function index()
    {
        $year = (int) $this->input->get('year') ?: (int) date('Y');
        $month = max(1, min(12, (int) $this->input->get('month') ?: (int) date('n')));
        $years = $this->financial_report_model->available_years();
        if (!in_array($year, $years, true)) $years[] = $year;
        rsort($years);
        $summaryRows = $this->financial_report_model->monthly_summary($year);
        $summary = array_fill(1, 12, $this->emptySummary());
        foreach ($summaryRows as $row) $summary[(int) $row['bill_month']] = array_merge($this->emptySummary(), $row);
        $annual = $this->emptySummary();
        foreach ($summary as $row) foreach (array_keys($annual) as $key) $annual[$key] += (float) $row[$key];

        $data = ['title' => 'Laporan Keuangan - ' . app_setting('isp_name', 'ISP BATARA NET'),
            'year' => $year, 'month' => $month, 'years' => $years, 'summary' => $summary,
            'selected' => $summary[$month], 'annual' => $annual,
            'details' => $this->financial_report_model->month_detail($year, $month),
            'customer_movement' => $this->financial_report_model->customer_movement($year)];
        $this->render($this->input->get('view') === 'detail' ? 'index' : 'dashboard', $data);
    }

    public function print_report()
    {
        $year = (int) $this->input->get('year') ?: (int) date('Y');
        $month = max(1, min(12, (int) $this->input->get('month') ?: (int) date('n')));
        $summaryRows = $this->financial_report_model->monthly_summary($year);
        $summary = $this->emptySummary();
        foreach ($summaryRows as $row) if ((int) $row['bill_month'] === $month) { $summary = array_merge($summary, $row); break; }
        $sharingSetting = $this->profit_sharing_model->settings_for_year($year)[$month];
        $party1Percent = (float) $sharingSetting['party_1_percent'];
        $party2Percent = (float) $sharingSetting['party_2_percent'];
        $reservePercent = 100 - $party1Percent - $party2Percent;
        $netIncome = (float) $summary['net_income'];
        $profitSharing = ['party_1_name' => $sharingSetting['party_1_name'], 'party_1_percent' => $party1Percent,
            'party_1_amount' => round($netIncome * $party1Percent / 100, 2), 'party_2_name' => $sharingSetting['party_2_name'],
            'party_2_percent' => $party2Percent, 'party_2_amount' => round($netIncome * $party2Percent / 100, 2),
            'reserve_percent' => $reservePercent, 'reserve_amount' => round($netIncome * $reservePercent / 100, 2)];
        $printData = ['year' => $year, 'month' => $month, 'summary' => $summary,
            'details' => $this->financial_report_model->month_detail($year, $month),
            'profit_sharing' => $profitSharing,
            'report_number' => sprintf('FIN/%04d/%02d/%s', $year, $month, date('YmdHis')),
            'prepared_by' => isset($this->currentUser['name']) ? $this->currentUser['name'] : 'Administrator'];
        $html = $this->load->view('print', $printData, true);
        if (app_language() === 'en') $html = $this->translatePrintReport($html);
        $this->output->set_output($html);
    }

    private function emptySummary()
    {
        return ['transaction_count' => 0, 'psb_count' => 0, 'monthly_count' => 0, 'gross_income' => 0,
            'technician_deduction' => 0, 'promoter_deduction' => 0, 'net_income' => 0];
    }

    private function translatePrintReport($html)
    {
        $translations = [
            '<html lang="id">' => '<html lang="en">', 'LAPORAN KEUANGAN BULANAN' => 'MONTHLY FINANCIAL REPORT',
            '<title>Laporan Keuangan ' => '<title>Financial Report ',
            '>Periode<' => '>Period<', '>Tanggal Cetak<' => '>Print Date<', '>Disiapkan Oleh<' => '>Prepared By<',
            '>Jumlah Transaksi<' => '>Transaction Count<', ' transaksi<' => ' transactions<',
            '>Penghasilan Bruto<' => '>Gross Income<', '>Potongan Teknisi<' => '>Technician Deduction<',
            '>Potongan Promotor<' => '>Promoter Deduction<', '>Penghasilan Bersih<' => '>Net Income<',
            '>Pembagian Laba Bersih<' => '>Net Profit Distribution<', '>Pihak Pertama<' => '>First Party<',
            '>Pihak Kedua<' => '>Second Party<', '>Cadangan Perusahaan<' => '>Company Reserve<',
            'Pihak Pertama ·' => 'First Party ·', 'Pihak Kedua ·' => 'Second Party ·',
            'Cadangan Perusahaan (' => 'Company Reserve (',
            '<strong>Dasar perhitungan:</strong>' => '<strong>Calculation basis:</strong>',
            'Pembayaran BULANAN dihitung penuh. Setiap pembayaran PSB dikurangi Rp50.000 untuk teknisi dan Rp50.000 untuk promotor.' => 'MONTHLY payments are counted in full. Each PSB payment is deducted by Rp50,000 for the technician and Rp50,000 for the promoter.',
            '>Rincian Transaksi<' => '>Transaction Details<', '>Tanggal<' => '>Date<', '>ID Pelanggan<' => '>Customer ID<',
            '>Nama Pelanggan<' => '>Customer Name<', '>Paket<' => '>Plan<', '>Jenis<' => '>Type<', '>Pembayaran<' => '>Payment<',
            '>Teknisi<' => '>Technician<', '>Promotor<' => '>Promoter<', '>Bersih<' => '>Net<',
            'Tidak ada transaksi pada periode ini.' => 'No transactions in this period.', '>TOTAL PERIODE<' => '>PERIOD TOTAL<',
            '>Disusun oleh,<' => '>Prepared by,<', '>Disetujui oleh,<' => '>Approved by,<',
            '>Administrator / Pimpinan<' => '>Administrator / Management<', 'Laporan internal perusahaan' => 'Internal company report',
            'Dicetak otomatis oleh sistem billing' => 'Automatically generated by the billing system',
            '>Cetak / Simpan PDF<' => '>Print / Save PDF<', '>Tutup<' => '>Close<',
            'Januari' => 'January', 'Februari' => 'February', 'Maret' => 'March', 'Mei' => 'May',
            'Juni' => 'June', 'Juli' => 'July', 'Agustus' => 'August', 'Oktober' => 'October',
            'Desember' => 'December',
        ];
        return strtr($html, $translations);
    }

    protected function render($view, array $data = [])
    {
        $data['body_class'] = 'monitoring-page'; parent::render($view, $data);
    }
}
