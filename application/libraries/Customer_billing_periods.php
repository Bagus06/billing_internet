<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Customer_billing_periods
{
    public function options(array $customer)
    {
        $CI =& get_instance();
        $today = new DateTimeImmutable('today');
        $current = $today->modify('first day of this month');
        $origin = substr((string) ($customer['psb_date'] ?: $customer['created_at']), 0, 10);
        $start = $origin ? (new DateTimeImmutable($origin))->modify('first day of this month') : $current;
        $payments = $CI->db->select('bill_month, bill_year')->group_start()->where('customer_id', (int) $customer['id'])
            ->or_where('customer_code', $customer['customer_code'])->group_end()->get('customer_payments')->result_array();
        $paid = [];
        foreach ($payments as $payment) $paid[sprintf('%04d-%02d', $payment['bill_year'], $payment['bill_month'])] = true;
        $windows = []; $open = null;
        if ($CI->db->table_exists('customer_isolation_logs')) {
            $logs = $CI->db->where('customer_id', (int) $customer['id'])->where('status', 'SUCCESS')->order_by('created_at')->order_by('id')->get('customer_isolation_logs')->result_array();
            foreach ($logs as $log) {
                if ($log['action'] === 'ISOLATE' && $open === null) $open = new DateTimeImmutable($log['created_at']);
                if ($log['action'] === 'RESTORE' && $open !== null) {
                    $windows[] = [$open, new DateTimeImmutable($log['created_at'])]; $open = null;
                }
            }
        }
        if (!empty($customer['is_isolated'])) {
            if ($open === null && !empty($customer['isolated_at'])) $open = new DateTimeImmutable($customer['isolated_at']);
            if ($open !== null) $windows[] = [$open, $today];
        }
        $groupTwo = preg_match('/(?:^|\D)2(?:\D|$)/', (string) $customer['group_name']) === 1;
        $dueDay = max(1, min(28, (int) app_setting($groupTwo ? 'isolation_group_2_due_day' : 'isolation_group_1_due_day', $groupTwo ? 25 : 10)));
        $grace = max(0, (int) app_setting('isolation_grace_days', 5));
        $options = []; $waived = [];
        for ($period = $start; $period <= $current; $period = $period->modify('+1 month')) {
            $key = $period->format('Y-m');
            if (isset($paid[$key])) continue;
            $cycleStart = $period->modify('+' . ($dueDay - 1) . ' days')->modify('+' . $grace . ' days');
            $next = $period->modify('+1 month');
            $cycleEnd = $next->modify('+' . ($dueDay - 1) . ' days')->modify('+' . $grace . ' days');
            $free = false;
            foreach ($windows as $window) {
                // Bandingkan hari, agar isolir pada hari jatuh tempo tetap mencakup siklus tersebut.
                if ($window[0]->format('Y-m-d') <= $cycleStart->format('Y-m-d') && $window[1]->format('Y-m-d') >= $cycleEnd->format('Y-m-d')) { $free = true; break; }
            }
            $row = ['value' => $key, 'month' => (int) $period->format('n'), 'year' => (int) $period->format('Y'),
                'label' => $period->format('m/Y') . ($free ? ' — Dibebaskan karena isolir' : ($period < $current ? ' — Tunggakan' : ' — Periode berjalan')),
                'amount' => (float) $customer['price']];
            if ($free) $waived[] = $row; else $options[] = $row;
        }
        return ['success' => true, 'options' => $options, 'waived' => $waived];
    }
}
