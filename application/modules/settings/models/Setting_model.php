<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Setting_model extends MY_Model
{
    private $table = 'app_settings';

    public function get_all_keyed()
    {
        $rows = $this->db->order_by('setting_group')->order_by('setting_key')->get($this->table)->result_array();
        $result = [];
        foreach ($rows as $row) $result[$row['setting_key']] = $row['setting_value'];
        return $result;
    }

    public function ensure_defaults()
    {
        $defaults = [
            'isp_name' => ['branding', 'ISP BATARA NET', 'string'],
            'app_subtitle' => ['branding', 'Mikrotik Network Tools', 'string'],
            'logo_path' => ['branding', 'assets/img/default-isp-logo.svg', 'string'],
            'footer_text' => ['branding', 'ISP BATARA NET', 'string'],
            'brand_primary_color' => ['branding', '#0A84FF', 'string'],
            'brand_background_color' => ['branding', '#050914', 'string'],
            'pwa_description' => ['branding', 'Aplikasi billing dan monitoring internet', 'string'],
            'timezone' => ['application', 'Asia/Jakarta', 'string'],
            'default_language' => ['application', 'id', 'string'],
            'default_theme' => ['application', 'dark', 'string'],
            'olt_snmp_host' => ['network', '', 'string'],
            'olt_snmp_port' => ['network', '161', 'integer'],
            'olt_snmp_version' => ['network', '1', 'string'],
            'olt_snmp_community' => ['network', '', 'string'],
        ];
        foreach ($defaults as $key => $definition) {
            $exists = $this->db->where('setting_key', $key)->count_all_results($this->table) > 0;
            if ($exists) continue;
            $this->db->insert($this->table, [
                'setting_group' => $definition[0],
                'setting_key' => $key, 'setting_value' => (string) $definition[1],
                'setting_type' => $definition[2], 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function set_many(array $settings)
    {
        $this->db->trans_start();
        foreach ($settings as $key => $setting) {
            $row = [
                'setting_group' => $setting['group'],
                'setting_key' => $key,
                'setting_value' => (string) $setting['value'],
                'setting_type' => $setting['type'],
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $exists = $this->db->where('setting_key', $key)->count_all_results($this->table) > 0;
            if ($exists) $this->db->where('setting_key', $key)->update($this->table, $row);
            else $this->db->insert($this->table, $row);
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
}
