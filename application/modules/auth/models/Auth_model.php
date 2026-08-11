<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Auth_model extends MY_Model
{
    public function find_by_username($username)
    {
        return $this->db
            ->select('users.*, roles.name AS role_name')
            ->from('users')
            ->join('roles', 'roles.id = users.role_id', 'inner')
            ->where('users.username', $username)
            ->where('roles.is_active', 1)
            ->limit(1)
            ->get()
            ->row_array();
    }

    public function find_session_user($userId)
    {
        return $this->db
            ->select('users.*, roles.name AS role_name')
            ->from('users')
            ->join('roles', 'roles.id = users.role_id', 'inner')
            ->where('users.id', (int) $userId)
            ->where('users.is_active', 1)
            ->where('roles.is_active', 1)
            ->limit(1)
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
