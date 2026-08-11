<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Payment_model extends MY_Model
{
    private $table = 'customer_payments';
    private $searchable = [
        'customer_code',
        'customer_name',
        'bill_month',
        'bill_year',
        'package_name',
        'group_name',
        'payment_type',
        'payment_method',
        'notes',
    ];

    public function get_all()
    {
        return $this->db
            ->order_by('payment_date', 'DESC')
            ->order_by('id', 'DESC')
            ->get($this->table)
            ->result_array();
    }

    public function get_paginated(array $filters, $limit, $offset)
    {
        $this->applyFilters($filters);

        return $this->db
            ->order_by('payment_date', 'DESC')
            ->order_by('id', 'DESC')
            ->limit((int) $limit, (int) $offset)
            ->get($this->table)
            ->result_array();
    }

    public function count_filtered(array $filters)
    {
        $this->applyFilters($filters);

        return (int) $this->db->count_all_results($this->table);
    }

    public function insert(array $data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->insert($this->table, $data);
    }

    public function has_customer_payment($customerId, $customerCode = '')
    {
        $this->db->group_start()->where('customer_id', (int) $customerId);
        if ($customerCode !== '') $this->db->or_where('customer_code', $customerCode);
        return $this->db->group_end()->limit(1)->count_all_results($this->table) > 0;
    }

    public function delete($id)
    {
        return $this->db
            ->where('id', (int) $id)
            ->delete($this->table);
    }

    private function applyFilters(array $filters)
    {
        foreach ($this->searchable as $field) {
            if (!isset($filters[$field]) || trim($filters[$field]) === '') {
                continue;
            }

            $this->db->like($field, trim($filters[$field]));
        }

        $this->applyDateRange($filters, 'input_date');
        $this->applyDateRange($filters, 'payment_date');
    }

    private function applyDateRange(array $filters, $field)
    {
        $from = $this->validDate(isset($filters[$field . '_from']) ? $filters[$field . '_from'] : '');
        $to = $this->validDate(isset($filters[$field . '_to']) ? $filters[$field . '_to'] : '');

        if ($from !== '') {
            $this->db->where($field . ' >=', $from);
        }

        if ($to !== '') {
            $this->db->where($field . ' <=', $to);
        }
    }

    private function validDate($value)
    {
        $value = trim((string) $value);
        $date = DateTime::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : '';
    }
}
