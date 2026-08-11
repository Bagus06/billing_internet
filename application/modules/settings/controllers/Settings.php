<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Settings extends MY_Controller
{
    protected $permission = 'settings';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('settings/setting_model');
        $this->load->library('App_storage');
        $this->load->library('Database_migration');
    }

    public function index()
    {
        $this->setting_model->ensure_defaults();
        $this->render('index', [
            'title' => 'Settings - ' . app_setting('isp_name', 'ISP BATARA NET'),
            'body_class' => 'monitoring-page',
            'settings' => $this->setting_model->get_all_keyed(),
            'migration_status' => $this->database_migration->status(),
        ]);
    }

    public function run_migrations()
    {
        if (strtoupper($this->input->method()) !== 'POST') { show_404(); return; }
        $confirmation = strtoupper(trim((string) $this->input->post('migration_confirmation', true)));
        if ($confirmation !== 'JALANKAN MIGRATION') {
            $this->session->set_flashdata('error', 'Konfirmasi migration tidak sesuai.');
            redirect('settings');
            return;
        }

        try {
            $results = $this->database_migration->migrate($this->currentUser['id'] ?? null);
            $message = $results
                ? count($results) . ' migration berhasil diterapkan: ' . implode(', ', array_column($results, 'name')) . '.'
                : 'Database sudah menggunakan versi terbaru. Tidak ada migration pending.';
            $this->session->set_flashdata('success', $message);
        } catch (Throwable $e) {
            log_message('error', 'Database migration gagal: ' . $e->getMessage());
            $this->session->set_flashdata('error', $e->getMessage());
        }
        redirect('settings');
    }

    public function update()
    {
        $currentLogo = app_setting('logo_path', 'assets/img/logo.jpeg');
        try { $logo = $this->uploadLogo($currentLogo); }
        catch (InvalidArgumentException $e) { $this->session->set_flashdata('error', $e->getMessage()); redirect('settings'); return; }
        $monitoringRefresh = max(10, min(300, (int) $this->input->post('monitoring_refresh_seconds')));
        $trafficRefresh = max(2, min(60, (int) $this->input->post('traffic_refresh_seconds')));
        $oltCacheSeconds = max(30, min(600, (int) $this->input->post('olt_cache_seconds')));
        $routerStatusRefresh = max(3, min(300, (int) $this->input->post('router_status_refresh_seconds')));
        $normalMin = (float) $this->input->post('signal_normal_min');
        $warningMin = (float) $this->input->post('signal_warning_min');
        if ($warningMin >= $normalMin) $warningMin = $normalMin - 3;
        $timezone = trim($this->input->post('timezone', true));
        if (!in_array($timezone, timezone_identifiers_list(), true)) $timezone = 'Asia/Jakarta';
        $profitParty1 = max(0, min(100, (float) $this->input->post('profit_party_1_percent')));
        $profitParty2 = max(0, min(100 - $profitParty1, (float) $this->input->post('profit_party_2_percent')));
        $cronToken = trim((string) $this->input->post('cron_token', true));
        if ($cronToken === '') $cronToken = trim((string) app_setting('cron_token', app_setting('isolation_cron_token', '')));
        if ($cronToken === '') $cronToken = bin2hex(random_bytes(32));
        $primaryColor = $this->validColor($this->input->post('brand_primary_color', true), '#1687ff');
        $backgroundColor = $this->validColor($this->input->post('brand_background_color', true), '#050914');
        $tenantName = app_setting('isp_name', 'ISP BATARA NET');

        $settings = [
            'isp_name' => $this->entry('branding', trim($this->input->post('isp_name', true)) ?: $tenantName),
            'app_subtitle' => $this->entry('branding', trim($this->input->post('app_subtitle', true)) ?: 'Mikrotik Network Tools'),
            'logo_path' => $this->entry('branding', $logo),
            'footer_text' => $this->entry('branding', trim($this->input->post('footer_text', true))),
            'brand_primary_color' => $this->entry('branding', $primaryColor),
            'brand_background_color' => $this->entry('branding', $backgroundColor),
            'pwa_description' => $this->entry('branding', trim($this->input->post('pwa_description', true)) ?: 'Aplikasi billing, pelanggan, pembayaran, dan operasi jaringan ISP.'),
            'monitoring_refresh_seconds' => $this->entry('monitoring', $monitoringRefresh, 'integer'),
            'traffic_refresh_seconds' => $this->entry('monitoring', $trafficRefresh, 'integer'),
            'olt_cache_seconds' => $this->entry('monitoring', $oltCacheSeconds, 'integer'),
            'router_status_refresh_seconds' => $this->entry('monitoring', $routerStatusRefresh, 'integer'),
            'pppoe_username_suffix' => $this->entry('monitoring', trim($this->input->post('pppoe_username_suffix', true)) ?: '@BATARA.net'),
            'signal_normal_min' => $this->entry('monitoring', $normalMin, 'decimal'),
            'signal_warning_min' => $this->entry('monitoring', $warningMin, 'decimal'),
            'olt_snmp_host' => $this->entry('network', trim($this->input->post('olt_snmp_host', true))),
            'olt_snmp_port' => $this->entry('network', max(1, min(65535, (int) $this->input->post('olt_snmp_port'))), 'integer'),
            'olt_snmp_version' => $this->entry('network', in_array($this->input->post('olt_snmp_version'), ['1', '2c'], true) ? $this->input->post('olt_snmp_version') : '1'),
            'olt_snmp_community' => $this->entry('network', trim((string) $this->input->post('olt_snmp_community', true))),
            'default_per_page' => $this->entry('application', max(5, min(100, (int) $this->input->post('default_per_page'))), 'integer'),
            'timezone' => $this->entry('application', $timezone),
            'default_language' => $this->entry('application', in_array($this->input->post('default_language'), ['id', 'en'], true) ? $this->input->post('default_language') : 'id'),
            'default_theme' => $this->entry('application', in_array($this->input->post('default_theme'), ['light', 'dark'], true) ? $this->input->post('default_theme') : 'dark'),
            'isolation_enabled' => $this->entry('isolation', $this->input->post('isolation_enabled') ? '1' : '0', 'boolean'),
            'isolation_profile_name' => $this->entry('isolation', trim($this->input->post('isolation_profile_name', true)) ?: 'ISOLIR'),
            'isolation_group_1_due_day' => $this->entry('isolation', max(1, min(28, (int) $this->input->post('isolation_group_1_due_day'))), 'integer'),
            'isolation_group_2_due_day' => $this->entry('isolation', max(1, min(28, (int) $this->input->post('isolation_group_2_due_day'))), 'integer'),
            'isolation_grace_days' => $this->entry('isolation', max(0, min(15, (int) $this->input->post('isolation_grace_days'))), 'integer'),
            'cron_token' => $this->entry('scheduler', $cronToken),
            // Dipertahankan agar instalasi/endpoint lama tetap kompatibel.
            'isolation_cron_token' => $this->entry('isolation', $cronToken),
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
        return $this->app_storage->storePublicBrandImage('logo', 2048);
    }

    private function validColor($value, $fallback)
    {
        $value = trim((string) $value);
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $fallback;
    }
}
