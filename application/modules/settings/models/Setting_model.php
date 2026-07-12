<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Setting_model extends CI_Model
{
    private $table = 'app_settings';

    public function get_all_keyed()
    {
        $rows = $this->db->order_by('setting_group')->order_by('setting_key')->get($this->table)->result_array();
        $result = [];
        foreach ($rows as $row) $result[$row['setting_key']] = $row['setting_value'];
        return $result;
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
