<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Package_model extends MY_Model
{
    private $table = 'internet_packages';

    public function get_all($activeOnly = false)
    {
        if ($activeOnly) {
            $this->db->where('internet_packages.is_active', 1);
        }

        return $this->db
            ->select('internet_packages.*, mikrotik_routers.name AS router_name')
            ->from($this->table)
            ->join('mikrotik_routers', 'mikrotik_routers.id = internet_packages.router_id', 'left')
            ->order_by('package_name', 'ASC')
            ->get()
            ->result_array();
    }

    public function find($id)
    {
        return $this->db
            ->select('internet_packages.*, mikrotik_routers.name AS router_name')
            ->from($this->table)
            ->join('mikrotik_routers', 'mikrotik_routers.id = internet_packages.router_id', 'left')
            ->where('internet_packages.id', (int) $id)
            ->get()
            ->row_array();
    }

    public function find_by_name($name)
    {
        return $this->db->where('package_name', (string) $name)->get($this->table)->row_array();
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

    public function count_by_router($routerId)
    {
        return (int) $this->db
            ->where('router_id', (int) $routerId)
            ->count_all_results($this->table);
    }
}
