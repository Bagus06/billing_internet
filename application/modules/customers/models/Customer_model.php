<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Customer_model extends CI_Model
{
    private $table = 'customers';

    private $searchable = [
        'customer_code',
        'name',
        'phone',
        'package_name',
        'group_name',
        'customer_status',
        'promoter',
    ];

    public function get_all()
    {
        return $this->db
            ->order_by('created_at', 'DESC')
            ->get($this->table)
            ->result_array();
    }

    public function get_paginated(array $filters, $limit, $offset)
    {
        $this->baseCustomerQuery($filters, true);

        return $this->db
            ->order_by('created_at', 'DESC')
            ->limit((int) $limit, (int) $offset)
            ->get()
            ->result_array();
    }

    public function count_filtered(array $filters)
    {
        $this->baseCustomerQuery($filters, false);

        return (int) $this->db->count_all_results();
    }

    public function count_all()
    {
        return (int) $this->db->count_all($this->table);
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

    private function baseCustomerQuery(array $filters, $withSelect)
    {
        $month = (int) date('n');
        $year = (int) date('Y');
        $paymentExists = "EXISTS (
            SELECT 1
            FROM customer_payments p
            WHERE p.customer_code = customers.customer_code
            AND p.bill_month = {$month}
            AND p.bill_year = {$year}
        )";

        if ($withSelect) {
            $this->db->select("customers.*, CASE WHEN {$paymentExists} THEN 'SUDAH BAYAR' ELSE 'BELUM BAYAR' END AS payment_status", false);
        }

        $this->db->from($this->table);
        $this->applyFilters($filters, $paymentExists);
    }

    private function applyFilters(array $filters, $paymentExists = null)
    {
        foreach ($this->searchable as $field) {
            if (!isset($filters[$field]) || trim($filters[$field]) === '') {
                continue;
            }

            $this->db->like($field, trim($filters[$field]));
        }

        if (!empty($filters['payment_status']) && $paymentExists) {
            $status = strtoupper(trim($filters['payment_status']));

            if (strpos($status, 'SUDAH') !== false) {
                $this->db->where($paymentExists, null, false);
            } elseif (strpos($status, 'BELUM') !== false) {
                $this->db->where("NOT {$paymentExists}", null, false);
            }
        }
    }
}
