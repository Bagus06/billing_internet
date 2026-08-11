<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Router_model extends MY_Model
{
    private $table = 'mikrotik_routers';
    private $prefix = 'enc:';

    public function get_all($activeOnly = false)
    {
        if ($activeOnly) {
            $this->db->where('is_active', 1);
        }

        $rows = $this->db
            ->order_by('name', 'ASC')
            ->get($this->table)
            ->result_array();

        return array_map([$this, 'withDecryptedPassword'], $rows);
    }

    public function find($id)
    {
        $row = $this->db
            ->where('id', (int) $id)
            ->get($this->table)
            ->row_array();

        return $row ? $this->withDecryptedPassword($row) : $row;
    }

    public function find_raw($id)
    {
        return $this->db
            ->where('id', (int) $id)
            ->get($this->table)
            ->row_array();
    }

    public function insert(array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = $this->encryptPassword($data['password']);
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->insert($this->table, $data);
    }

    public function update($id, array $data)
    {
        if (array_key_exists('password', $data)) {
            $data['password'] = $this->encryptPassword($data['password']);
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

    public function encrypt_existing_plain_passwords()
    {
        $rows = $this->db->get($this->table)->result_array();

        foreach ($rows as $row) {
            if (!isset($row['password']) || $this->isEncrypted($row['password'])) {
                continue;
            }

            $this->db
                ->where('id', (int) $row['id'])
                ->update($this->table, [
                    'password' => $this->encryptPassword($row['password']),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }
    }

    private function withDecryptedPassword(array $row)
    {
        if (isset($row['password'])) {
            $row['password'] = $this->decryptPassword($row['password']);
        }

        return $row;
    }

    private function encryptPassword($plainText)
    {
        if ($this->isEncrypted($plainText)) {
            return $plainText;
        }

        $iv = random_bytes(16);
        $cipherText = openssl_encrypt(
            (string) $plainText,
            'AES-256-CBC',
            $this->key(),
            OPENSSL_RAW_DATA,
            $iv
        );

        return $this->prefix . base64_encode($iv . $cipherText);
    }

    private function decryptPassword($value)
    {
        if (!$this->isEncrypted($value)) {
            return $value;
        }

        $payload = base64_decode(substr($value, strlen($this->prefix)), true);

        if ($payload === false || strlen($payload) <= 16) {
            return '';
        }

        $iv = substr($payload, 0, 16);
        $cipherText = substr($payload, 16);

        $plainText = openssl_decrypt(
            $cipherText,
            'AES-256-CBC',
            $this->key(),
            OPENSSL_RAW_DATA,
            $iv
        );

        return $plainText === false ? '' : $plainText;
    }

    private function isEncrypted($value)
    {
        return is_string($value) && strpos($value, $this->prefix) === 0;
    }

    private function key()
    {
        return hash('sha256', config_item('encryption_key'), true);
    }
}
