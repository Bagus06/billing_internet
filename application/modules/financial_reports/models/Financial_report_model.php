<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Financial_report_model extends CI_Model
{
    private $table = 'customer_payments';

    public function available_years()
    {
        $rows = $this->db->select('bill_year')->distinct()->where('bill_year >', 0)->order_by('bill_year', 'DESC')->get($this->table)->result_array();
        $years = array_map('intval', array_column($rows, 'bill_year'));
        foreach ($this->db->select('YEAR(created_at) AS year', false)->distinct()->get('customers')->result_array() as $row) if (!empty($row['year'])) $years[] = (int) $row['year'];
        if ($this->db->table_exists('customer_status_history')) foreach ($this->db->select('YEAR(changed_at) AS year', false)->distinct()->get('customer_status_history')->result_array() as $row) if (!empty($row['year'])) $years[] = (int) $row['year'];
        $years = array_values(array_unique($years)); rsort($years); return $years;
    }

    public function monthly_summary($year)
    {
        return $this->db->select("bill_month,
            COUNT(*) AS transaction_count,
            SUM(CASE WHEN UPPER(payment_type) = 'PSB' THEN 1 ELSE 0 END) AS psb_count,
            SUM(CASE WHEN UPPER(payment_type) <> 'PSB' THEN 1 ELSE 0 END) AS monthly_count,
            SUM(price) AS gross_income,
            SUM(CASE WHEN UPPER(payment_type) = 'PSB' THEN 50000 ELSE 0 END) AS technician_deduction,
            SUM(CASE WHEN UPPER(payment_type) = 'PSB' THEN 50000 ELSE 0 END) AS promoter_deduction,
            SUM(CASE WHEN UPPER(payment_type) = 'PSB' THEN price - 100000 ELSE price END) AS net_income", false)
            ->where('bill_year', (int) $year)->group_by('bill_month')->order_by('bill_month', 'ASC')->get($this->table)->result_array();
    }

    public function month_detail($year, $month)
    {
        return $this->db->select("*, CASE WHEN UPPER(payment_type) = 'PSB' THEN 50000 ELSE 0 END AS technician_deduction,
            CASE WHEN UPPER(payment_type) = 'PSB' THEN 50000 ELSE 0 END AS promoter_deduction,
            CASE WHEN UPPER(payment_type) = 'PSB' THEN price - 100000 ELSE price END AS net_income", false)
            ->where('bill_year', (int) $year)->where('bill_month', (int) $month)
            ->order_by('payment_date', 'DESC')->order_by('id', 'DESC')->get($this->table)->result_array();
    }

    public function customer_movement($year)
    {
        $result = array_fill(1, 12, ['added' => 0, 'reactivated' => 0, 'removed' => 0, 'net' => 0]);
        $created = $this->db->select('MONTH(created_at) AS month, COUNT(*) AS total', false)->where('YEAR(created_at)', (int) $year, false)
            ->group_by('MONTH(created_at)')->get('customers')->result_array();
        foreach ($created as $row) $result[(int) $row['month']]['added'] = (int) $row['total'];
        if ($this->db->table_exists('customer_status_history')) {
            $history = $this->db->select("MONTH(changed_at) AS month,
                SUM(CASE WHEN UPPER(new_status) = 'ACTIVE' THEN 1 ELSE 0 END) AS reactivated,
                SUM(CASE WHEN UPPER(new_status) = 'NONACTIVE' THEN 1 ELSE 0 END) AS removed", false)
                ->where('YEAR(changed_at)', (int) $year, false)->group_by('MONTH(changed_at)')->get('customer_status_history')->result_array();
            foreach ($history as $row) { $result[(int) $row['month']]['reactivated'] = (int) $row['reactivated']; $result[(int) $row['month']]['removed'] = (int) $row['removed']; }
        }
        foreach ($result as &$row) $row['net'] = $row['added'] + $row['reactivated'] - $row['removed'];
        unset($row); return $result;
    }
}
