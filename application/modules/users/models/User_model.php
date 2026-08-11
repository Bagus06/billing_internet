<?php
defined('BASEPATH') or exit('No direct script access allowed');

class User_model extends MY_Model
{
    private $table = 'users';

    public function get_all()
    {
        return $this->db
            ->select('users.*, roles.name AS role_name')
            ->from($this->table)
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->order_by('users.name', 'ASC')
            ->get()
            ->result_array();
    }

    public function find($id)
    {
        return $this->db
            ->where('id', (int) $id)
            ->get($this->table)
            ->row_array();
    }

    public function find_with_role($id)
    {
        return $this->db->select('users.*, roles.name AS role_name')->from($this->table)
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->where('users.id', (int) $id)->get()->row_array();
    }

    public function username_exists($username, $ignoreId = null)
    {
        $this->db->where('username', $username);
        if ($ignoreId !== null) { $this->db->where('id !=', (int) $ignoreId); }
        return $this->db->count_all_results($this->table) > 0;
    }
    public function email_exists_global($email,$ignoreId=null)
    {
        $email=strtolower(trim((string)$email));if($email==='')return false;$this->db->where('LOWER(email)',$email);
        if($ignoreId!==null)$this->db->where('id !=',(int)$ignoreId);return $this->db->count_all_results($this->table)>0;
    }

    public function insert(array $data)
    {
        $data['email']=isset($data['email'])&&trim((string)$data['email'])!==''?strtolower(trim($data['email'])):null;
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->insert($this->table, $data);
    }

    public function update($id, array $data)
    {
        if(array_key_exists('email',$data))$data['email']=trim((string)$data['email'])!==''?strtolower(trim($data['email'])):null;
        if (isset($data['password'])) {
            if ($data['password'] === '') {
                unset($data['password']);
            } else {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
        }

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
