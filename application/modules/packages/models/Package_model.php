<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Package_model extends CI_Model
{
    private $table = 'internet_packages';

    public function get_all($activeOnly = false)
    {
        if ($activeOnly) {
            $this->db->where('is_active', 1);
        }

        return $this->db
            ->order_by('package_name', 'ASC')
            ->get($this->table)
            ->result_array();
    }

    public function find($id)
    {
        return $this->db
            ->where('id', (int) $id)
            ->get($this->table)
            ->row_array();
    }

    public function insert(array $data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->insert($this->table, $data);
    }

    public function update($id, array $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->db
            ->where('id', (int) $id)
            ->update($this->table, $data);
    }

    public function delete($id)
    {
        return $this->db
            ->where('id', (int) $id)
            ->delete($this->table);
    }
}
