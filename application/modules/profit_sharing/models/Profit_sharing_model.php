<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Profit_sharing_model extends CI_Model
{
    private $table = 'profit_sharing_settings';

    public function __construct()
    {
        parent::__construct();
        $this->ensure_table();
    }

    private function ensure_table()
    {
        if ($this->db->table_exists($this->table)) return;

        $sql = "CREATE TABLE IF NOT EXISTS `profit_sharing_settings` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `effective_month` DATE NOT NULL,
            `party_1_name` VARCHAR(150) NOT NULL,
            `party_1_percent` DECIMAL(5,2) NOT NULL DEFAULT 75.00,
            `party_2_name` VARCHAR(150) NOT NULL,
            `party_2_percent` DECIMAL(5,2) NOT NULL DEFAULT 14.00,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`), UNIQUE KEY `uq_profit_sharing_effective_month` (`effective_month`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        if (!$this->db->query($sql)) return;

        $this->db->insert($this->table, [
            'effective_month' => date('Y-01-01'),
            'party_1_name' => app_setting('profit_party_1_name', 'Pihak Pertama'),
            'party_1_percent' => (float) app_setting('profit_party_1_percent', 75),
            'party_2_name' => app_setting('profit_party_2_name', 'Pihak Kedua'),
            'party_2_percent' => (float) app_setting('profit_party_2_percent', 14),
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function all()
    {
        if (!$this->db->table_exists($this->table)) return [];
        $query = $this->db->order_by('effective_month', 'DESC')->get($this->table);
        return $query ? $query->result_array() : [];
    }

    public function find($id)
    {
        if (!$this->db->table_exists($this->table)) return null;
        $query = $this->db->get_where($this->table, ['id' => (int) $id]);
        return $query ? $query->row_array() : null;
    }

    public function save(array $data, $id = null)
    {
        if ($id) {
            $duplicate = $this->db->where('effective_month', $data['effective_month'])->where('id !=', (int) $id)->get($this->table);
            if ($duplicate && $duplicate->num_rows()) return false;
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $this->db->where('id', (int) $id)->update($this->table, $data);
        }
        $existing = $this->db->get_where($this->table, ['effective_month' => $data['effective_month']])->row_array();
        $data['updated_at'] = date('Y-m-d H:i:s');
        if ($existing) return $this->db->where('id', $existing['id'])->update($this->table, $data);
        $data['created_at'] = $data['updated_at'];
        return $this->db->insert($this->table, $data);
    }

    public function settings_for_year($year)
    {
        $result = [];
        for ($month = 1; $month <= 12; $month++) {
            $date = sprintf('%04d-%02d-01', $year, $month);
            $query = $this->db->where('effective_month <=', $date)->order_by('effective_month', 'DESC')->limit(1)->get($this->table);
            $row = $query ? $query->row_array() : null;
            $result[$month] = $row ?: ['party_1_name' => 'Pihak Pertama', 'party_1_percent' => 75, 'party_2_name' => 'Pihak Kedua', 'party_2_percent' => 14, 'effective_month' => null];
        }
        return $result;
    }

    public function delete($id)
    {
        return $this->db->where('id', (int) $id)->delete($this->table);
    }
}
