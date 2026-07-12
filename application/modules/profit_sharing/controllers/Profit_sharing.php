<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Profit_sharing extends MY_Controller
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
        $years = $this->financial_report_model->available_years(); if (!in_array($year, $years, true)) $years[] = $year; rsort($years);
        $periodSettings = $this->profit_sharing_model->settings_for_year($year);
        $source = array_fill(1, 12, 0.0);
        foreach ($this->financial_report_model->monthly_summary($year) as $row) $source[(int) $row['bill_month']] = (float) $row['net_income'];
        $rows = []; $totals = ['net' => 0, 'party_1' => 0, 'party_2' => 0, 'reserve' => 0];
        foreach ($source as $month => $net) {
            $setting = $periodSettings[$month];
            $p1 = (float) $setting['party_1_percent']; $p2 = (float) $setting['party_2_percent']; $reserve = 100 - $p1 - $p2;
            $row = ['month' => $month, 'net' => $net, 'party_1' => round($net * $p1 / 100, 2),
                'party_2' => round($net * $p2 / 100, 2), 'reserve' => round($net * $reserve / 100, 2), 'setting' => $setting, 'reserve_percent' => $reserve];
            foreach ($totals as $key => $value) $totals[$key] += $row[$key]; $rows[] = $row;
        }
        $this->render('index', ['title' => 'Bagi Hasil - ' . app_setting('isp_name', 'ISP BATARA NET'), 'year' => $year,
            'years' => $years, 'rows' => $rows, 'totals' => $totals]);
    }

    public function settings()
    {
        $editId = (int) $this->input->get('edit');
        $this->render('settings', ['title' => (app_language() === 'en' ? 'Profit Sharing Settings' : 'Pengaturan Bagi Hasil'),
            'settings' => $this->profit_sharing_model->all(), 'editing' => $editId ? $this->profit_sharing_model->find($editId) : null]);
    }

    public function save_setting()
    {
        $month = trim($this->input->post('effective_month', true));
        $p1 = (float) $this->input->post('party_1_percent'); $p2 = (float) $this->input->post('party_2_percent');
        if (!preg_match('/^\d{4}-\d{2}$/', $month) || $p1 < 0 || $p2 < 0 || $p1 + $p2 > 100) {
            $this->session->set_flashdata('error', app_language() === 'en' ? 'Invalid period or percentage. The maximum total is 100%.' : 'Periode atau persentase tidak valid. Total persentase maksimal 100%.'); redirect('profit-sharing/settings');
        }
        $id = (int) $this->input->post('id');
        $success = $this->profit_sharing_model->save(['effective_month' => $month . '-01',
            'party_1_name' => trim($this->input->post('party_1_name', true)) ?: 'Pihak Pertama', 'party_1_percent' => $p1,
            'party_2_name' => trim($this->input->post('party_2_name', true)) ?: 'Pihak Kedua', 'party_2_percent' => $p2], $id ?: null);
        $en = app_language() === 'en';
        $this->session->set_flashdata($success ? 'success' : 'error', $success ? ($en ? 'Period settings saved successfully.' : 'Pengaturan periode berhasil disimpan.') : ($en ? 'Unable to save. The effective month may already be in use.' : 'Pengaturan gagal disimpan. Bulan berlaku mungkin sudah digunakan.')); redirect('profit-sharing/settings');
    }

    public function delete_setting($id)
    {
        $this->profit_sharing_model->delete($id); $this->session->set_flashdata('success', app_language() === 'en' ? 'Period settings deleted.' : 'Pengaturan periode dihapus.'); redirect('profit-sharing/settings');
    }

    protected function render($view, array $data = [], $moduleJsload = null) { $data['body_class'] = 'monitoring-page'; parent::render($view, $data, $moduleJsload); }
}
