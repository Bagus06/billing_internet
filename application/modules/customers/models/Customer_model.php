<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Customer_model extends MY_Model
{
    private $table = 'customers';

    private $searchable = [
        'customer_code',
        'name',
        'phone',
        'promoter',
    ];

    private $selectFilters = ['package_name', 'group_name', 'customer_status'];

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

    public function filter_options()
    {
        $options = [];
        foreach (['package_name', 'group_name', 'customer_status'] as $field) {
            $rows = $this->db->select($field)->distinct()->where($field . ' IS NOT NULL', null, false)
                ->where($field . ' !=', '')->order_by($field, 'ASC')->get($this->table)->result_array();
            $options[$field] = array_values(array_filter(array_map(function ($row) use ($field) {
                return trim((string) $row[$field]);
            }, $rows)));
        }
        $options['payment_status'] = ['SUDAH BAYAR', 'BELUM BAYAR'];
        $options['isolation_status'] = ['ISOLIR', 'NORMAL'];
        $options['arrears_status'] = ['MENUNGGAK', 'TIDAK MENUNGGAK'];
        return $options;
    }

    public function find($id)
    {
        return $this->db
            ->where('id', (int) $id)
            ->get($this->table)
            ->row_array();
    }

    public function payment_periods_for_customers(array $customers)
    {
        if (!$customers) return [];
        $ids = []; $codes = [];
        foreach ($customers as $customer) {
            if (!empty($customer['id'])) $ids[] = (int) $customer['id'];
            if (!empty($customer['customer_code'])) $codes[] = (string) $customer['customer_code'];
        }
        $this->db->select('customer_id, customer_code, bill_month, bill_year');
        $this->db->group_start();
        if ($ids) $this->db->where_in('customer_id', array_values(array_unique($ids)));
        if ($codes) {
            if ($ids) $this->db->or_where_in('customer_code', array_values(array_unique($codes)));
            else $this->db->where_in('customer_code', array_values(array_unique($codes)));
        }
        return $this->db->group_end()->get('customer_payments')->result_array();
    }

    public function ont_pairing($customerId)
    {
        if (!$this->db->table_exists('customer_onts')) return null;
        return $this->db->where('customer_id', (int) $customerId)->get('customer_onts')->row_array();
    }

    public function ont_pairings_by_customer()
    {
        if (!$this->db->table_exists('customer_onts')) return [];
        $result = [];
        foreach ($this->db->get('customer_onts')->result_array() as $row) $result[(int) $row['customer_id']] = $row;
        return $result;
    }

    public function save_ont_pairing($customerId, array $device = null)
    {
        if (!$this->db->table_exists('customer_onts')) return false;
        $customerId = (int) $customerId;
        if (!$device || empty($device['serial_number'])) return $this->db->where('customer_id', $customerId)->delete('customer_onts');
        $serial = strtoupper(trim($device['serial_number']));
        $conflict = $this->db->where('ont_serial_number', $serial)->where('customer_id !=', $customerId)->get('customer_onts')->row_array();
        if ($conflict) throw new InvalidArgumentException('Serial ONT sudah dipasangkan ke pelanggan lain.');
        $data = ['customer_id' => $customerId, 'ont_serial_number' => $serial, 'ont_index' => $device['ont_index'] ?? null,
            'last_detected_name' => $device['ont_name'] ?? null, 'last_seen_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
        $existing = $this->ont_pairing($customerId);
        if ($existing) return $this->db->where('customer_id', $customerId)->update('customer_onts', $data);
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('customer_onts', $data);
    }

    public function isolation_candidates($month, $year)
    {
        $month = (int) $month; $year = (int) $year;
        return $this->db->select('customers.*')
            ->from($this->table)
            ->where('customers.customer_status', 'ACTIVE')
            ->where('customers.is_isolated', 0)
            ->where("NOT EXISTS (SELECT 1 FROM customer_payments p WHERE (p.customer_id = customers.id OR p.customer_code = customers.customer_code) AND p.bill_month = {$month} AND p.bill_year = {$year})", null, false)
            ->order_by('customers.id', 'ASC')->get()->result_array();
    }

    public function isolation_due_candidates(DateTimeImmutable $today = null)
    {
        $today = $today ?: new DateTimeImmutable('today');
        return $this->db->select('customers.*')
            ->from($this->table)
            ->where('customers.customer_status', 'ACTIVE')
            ->where('customers.is_isolated', 0)
            ->where($this->arrearsExistsExpression($today), null, false)
            ->order_by('customers.id', 'ASC')
            ->get()->result_array();
    }

    public function isolation_restore_candidates()
    {
        return $this->db->select('customers.*')
            ->from($this->table)
            ->where('customers.customer_status', 'ACTIVE')
            ->where('customers.is_isolated', 1)
            ->order_by('customers.id', 'ASC')
            ->get()->result_array();
    }

    public function update_isolation($id, $isolated, $isolatedAt = null, $originalProfile = null, $error = null)
    {
        return $this->db->where('id', (int) $id)->update($this->table, [
            'is_isolated' => $isolated ? 1 : 0,
            'isolated_at' => $isolatedAt,
            'isolation_original_profile' => $originalProfile,
            'isolation_last_error' => $error,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function set_isolation_error($id, $message)
    {
        return $this->db->where('id', (int) $id)->update($this->table, ['isolation_last_error' => (string) $message, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function get_by_package($packageId)
    {
        return $this->db->where('package_id', (int) $packageId)->get($this->table)->result_array();
    }

    public function count_by_package($packageId, $packageName = null)
    {
        $this->db->group_start()->where('package_id', (int) $packageId);
        if ($packageName !== null && $packageName !== '') $this->db->or_where('package_name', $packageName);
        return (int) $this->db->group_end()->count_all_results($this->table);
    }

    public function insert(array $data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        if (!$this->db->insert($this->table, $data)) return false;
        return $this->db->insert_id();
    }

    public function update($id, array $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->db
            ->where('id', (int) $id)
            ->update($this->table, $data);
    }

    public function log_status_change($customerId, $oldStatus, $newStatus, $userId = null)
    {
        return $this->db->insert('customer_status_history', ['customer_id' => (int) $customerId,
            'old_status' => (string) $oldStatus, 'new_status' => (string) $newStatus,
            'changed_by' => $userId ? (int) $userId : null, 'changed_at' => date('Y-m-d H:i:s')]);
    }

    public function delete($id)
    {
        $this->db->trans_start();
        if ($this->db->table_exists('customer_onts')) $this->db->where('customer_id', (int) $id)->delete('customer_onts');
        $this->db->where('id', (int) $id)->delete($this->table);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function deletion_dependencies($id)
    {
        $id = (int) $id;
        $tables = [
            'payments' => 'customer_payments',
            'status_history' => 'customer_status_history',
            'isolation_logs' => 'customer_isolation_logs',
        ];
        $counts = [];
        foreach ($tables as $key => $table) {
            $counts[$key] = (int) $this->db
                ->where('customer_id', $id)
                ->count_all_results($table);
        }
        $counts['total'] = array_sum($counts);
        return $counts;
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

        foreach ($this->selectFilters as $field) {
            if (!empty($filters[$field])) $this->db->where($field, trim($filters[$field]));
        }

        if (!empty($filters['payment_status']) && $paymentExists) {
            $status = strtoupper(trim($filters['payment_status']));

            if (strpos($status, 'SUDAH') !== false) {
                $this->db->where($paymentExists, null, false);
            } elseif (strpos($status, 'BELUM') !== false) {
                $this->db->where("NOT {$paymentExists}", null, false);
            }
        }

        if (!empty($filters['isolation_status'])) {
            $this->db->where('customers.is_isolated', strtoupper(trim($filters['isolation_status'])) === 'ISOLIR' ? 1 : 0);
        }

        if (!empty($filters['arrears_status'])) {
            $arrearsExists = $this->arrearsExistsExpression();
            if (strtoupper(trim($filters['arrears_status'])) === 'MENUNGGAK') {
                $this->db->where($arrearsExists, null, false);
            } else {
                $this->db->where("NOT ({$arrearsExists})", null, false);
            }
        }
    }

    private function arrearsExistsExpression(DateTimeImmutable $today = null)
    {
        $today = $today ?: new DateTimeImmutable('today');
        $groupOneLastDue = $this->lastDuePeriod($today, (int) app_setting('isolation_group_1_due_day', 10));
        $groupTwoLastDue = $this->lastDuePeriod($today, (int) app_setting('isolation_group_2_due_day', 25));
        $lastDue = "CASE WHEN LOWER(COALESCE(customers.group_name, '')) REGEXP '(^|[^0-9])2([^0-9]|$)' THEN '{$groupTwoLastDue}' ELSE '{$groupOneLastDue}' END";
        $startPeriod = "COALESCE(DATE_FORMAT(customers.psb_date, '%Y-%m-01'), DATE_FORMAT(customers.created_at, '%Y-%m-01'))";
        $paymentPeriod = "STR_TO_DATE(CONCAT(p.bill_year, '-', LPAD(p.bill_month, 2, '0'), '-01'), '%Y-%m-%d')";
        $paidPeriods = "(SELECT COUNT(DISTINCT CONCAT(p.bill_year, '-', LPAD(p.bill_month, 2, '0'))) FROM customer_payments p WHERE (p.customer_id = customers.id OR p.customer_code = customers.customer_code) AND {$paymentPeriod} BETWEEN {$startPeriod} AND {$lastDue})";
        $expectedPeriods = "GREATEST(0, TIMESTAMPDIFF(MONTH, {$startPeriod}, {$lastDue}) + 1)";
        return "COALESCE((UPPER(COALESCE(customers.customer_status, '')) <> 'LEAD' AND {$startPeriod} IS NOT NULL AND {$expectedPeriods} > {$paidPeriods}), 0) = 1";
    }

    private function lastDuePeriod(DateTimeImmutable $today, $dueDay)
    {
        $grace = max(0, (int) app_setting('isolation_grace_days', 5));
        $dueDay = max(1, min((int) $today->format('t'), (int) $dueDay));
        $deadline = $today->setDate((int) $today->format('Y'), (int) $today->format('n'), $dueDay)->modify('+' . $grace . ' days');
        return ($today >= $deadline ? $today->modify('first day of this month') : $today->modify('first day of previous month'))->format('Y-m-d');
    }
}
