<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Settings extends MY_Controller
{
    protected $permission = 'settings';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('settings/setting_model');
    }

    public function index()
    {
        $this->render('index', [
            'title' => 'Settings - ' . app_setting('isp_name', 'ISP BATARA NET'),
            'body_class' => 'monitoring-page',
            'settings' => $this->setting_model->get_all_keyed(),
        ]);
    }

    public function update()
    {
        $currentLogo = app_setting('logo_path', 'assets/img/logo.jpeg');
        $logo = $this->uploadLogo($currentLogo);
        $monitoringRefresh = max(10, min(300, (int) $this->input->post('monitoring_refresh_seconds')));
        $trafficRefresh = max(2, min(60, (int) $this->input->post('traffic_refresh_seconds')));
        $normalMin = (float) $this->input->post('signal_normal_min');
        $warningMin = (float) $this->input->post('signal_warning_min');
        if ($warningMin >= $normalMin) $warningMin = $normalMin - 3;
        $timezone = trim($this->input->post('timezone', true));
        if (!in_array($timezone, timezone_identifiers_list(), true)) $timezone = 'Asia/Jakarta';
        $profitParty1 = max(0, min(100, (float) $this->input->post('profit_party_1_percent')));
        $profitParty2 = max(0, min(100 - $profitParty1, (float) $this->input->post('profit_party_2_percent')));

        $settings = [
            'isp_name' => $this->entry('branding', trim($this->input->post('isp_name', true)) ?: 'ISP BATARA NET'),
            'app_subtitle' => $this->entry('branding', trim($this->input->post('app_subtitle', true)) ?: 'Mikrotik Network Tools'),
            'logo_path' => $this->entry('branding', $logo),
            'footer_text' => $this->entry('branding', trim($this->input->post('footer_text', true))),
            'monitoring_refresh_seconds' => $this->entry('monitoring', $monitoringRefresh, 'integer'),
            'traffic_refresh_seconds' => $this->entry('monitoring', $trafficRefresh, 'integer'),
            'pppoe_username_suffix' => $this->entry('monitoring', trim($this->input->post('pppoe_username_suffix', true)) ?: '@BATARA.net'),
            'signal_normal_min' => $this->entry('monitoring', $normalMin, 'decimal'),
            'signal_warning_min' => $this->entry('monitoring', $warningMin, 'decimal'),
            'default_per_page' => $this->entry('application', max(5, min(100, (int) $this->input->post('default_per_page'))), 'integer'),
            'timezone' => $this->entry('application', $timezone),
            'default_language' => $this->entry('application', in_array($this->input->post('default_language'), ['id', 'en'], true) ? $this->input->post('default_language') : 'id'),
            'profit_party_1_name' => $this->entry('finance', trim($this->input->post('profit_party_1_name', true)) ?: 'Pihak Pertama'),
            'profit_party_1_percent' => $this->entry('finance', $profitParty1, 'decimal'),
            'profit_party_2_name' => $this->entry('finance', trim($this->input->post('profit_party_2_name', true)) ?: 'Pihak Kedua'),
            'profit_party_2_percent' => $this->entry('finance', $profitParty2, 'decimal'),
        ];

        $success = $this->setting_model->set_many($settings);
        $this->session->set_flashdata($success ? 'success' : 'error', $success ? 'Konfigurasi berhasil disimpan.' : 'Konfigurasi gagal disimpan.');
        redirect('settings');
    }

    private function entry($group, $value, $type = 'string')
    {
        return ['group' => $group, 'value' => $value, 'type' => $type];
    }

    private function uploadLogo($existing)
    {
        if (empty($_FILES['logo']['name'])) return $existing;
        $path = FCPATH . 'assets/img/branding/';
        if (!is_dir($path)) mkdir($path, 0755, true);
        $this->load->library('upload', [
            'upload_path' => $path, 'allowed_types' => 'jpg|jpeg|png|webp|gif',
            'max_size' => 2048, 'encrypt_name' => true,
        ]);
        if (!$this->upload->do_upload('logo')) {
            $this->session->set_flashdata('error', strip_tags($this->upload->display_errors('', '')));
            return $existing;
        }
        return 'assets/img/branding/' . $this->upload->data('file_name');
    }
}
