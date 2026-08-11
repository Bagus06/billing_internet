<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Role_model extends MY_Model
{
    private $table = 'roles';

    public function feature_options()
    {
        return [
            'home' => 'Home',
            'calculator' => 'Calculator Redaman',
            'monitoring' => 'Monitoring Mikrotik',
            'routers' => 'Data Mikrotik',
            'customers' => 'Data Pelanggan',
            'packages' => 'Paket Internet',
            'payments' => 'Data Pembayaran',
            'financial_reports' => 'Laporan Keuangan',
            'users' => 'Users',
            'roles' => 'Roles',
            'profile' => 'Profile',
            'settings' => 'Settings',
        ];
    }

    public function get_all($activeOnly = false)
    {
        if ($activeOnly) {
            $this->db->where('is_active', 1);
        }

        return $this->db
            ->order_by('name', 'ASC')
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

    public function permissions($roleId)
    {
        $rows = $this->db
            ->select('permission_key')
            ->where('role_id', (int) $roleId)
            ->get('role_permissions')
            ->result_array();

        return array_column($rows, 'permission_key');
    }

    public function insert(array $data, array $permissions)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        $roleId = $this->db->insert_id();
        $this->sync_permissions($roleId, $permissions);

        return $roleId;
    }

    public function update($id, array $data, array $permissions)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db
            ->where('id', (int) $id)
            ->update($this->table, $data);
        $this->sync_permissions($id, $permissions);
    }

    public function delete($id)
    {
        $this->db->where('role_id', (int) $id)->delete('role_permissions');

        return $this->db
            ->where('id', (int) $id)
            ->delete($this->table);
    }

    public function has_users($id)
    {
        return $this->db->where('role_id', (int) $id)->count_all_results('users') > 0;
    }

    public function name_exists($name, $ignoreId = null)
    {
        $this->db->where('name', trim((string) $name));
        if ($ignoreId !== null) $this->db->where('id !=', (int) $ignoreId);
        return $this->db->count_all_results($this->table) > 0;
    }

    private function sync_permissions($roleId, array $permissions)
    {
        $this->db->where('role_id', (int) $roleId)->delete('role_permissions');

        foreach (array_unique($permissions) as $permission) {
            if (!array_key_exists($permission, $this->feature_options())) {
                continue;
            }

            $this->db->insert('role_permissions', [
                'role_id' => (int) $roleId,
                'permission_key' => $permission,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
