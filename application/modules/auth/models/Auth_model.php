<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Auth_model extends CI_Model
{
    public function find_by_username($username)
    {
        return $this->db
            ->select('users.*, roles.name AS role_name')
            ->from('users')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->where('users.username', $username)
            ->get()
            ->row_array();
    }

    public function permissions_for_role($roleId)
    {
        $rows = $this->db
            ->select('permission_key')
            ->where('role_id', (int) $roleId)
            ->get('role_permissions')
            ->result_array();

        return array_column($rows, 'permission_key');
    }

    public function touch_login($userId)
    {
        return $this->db
            ->where('id', (int) $userId)
            ->update('users', ['last_login' => date('Y-m-d H:i:s')]);
    }
}
