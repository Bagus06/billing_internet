<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Payments extends MY_Controller
{
    protected $permission = 'payments';
    private $methods = ['CASH', 'SEABANK'];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('payments/payment_model');
        $this->load->model('customers/customer_model');
        $this->load->library('Customer_isolation');
        $this->load->library('Customer_billing_periods');
    }

    public function index()
    {
        $perPage = max(5, min(100, (int) $this->input->get('per_page') ?: (int) app_setting('default_per_page', 10)));
        $page = max(1, (int) $this->input->get('page') ?: 1);
        $filters = $this->filters();
        $totalRows = $this->payment_model->count_filtered($filters);
        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $this->render('index', [
            'title' => 'Data Pembayaran - ' . app_setting('isp_name', 'ISP Billing'),
            'payments' => $this->payment_model->get_paginated($filters, $perPage, $offset),
            'filters' => $filters,
            'page' => $page,
            'per_page' => $perPage,
            'total_rows' => $totalRows,
            'total_pages' => $totalPages,
        ]);
    }

    public function periods($customerId)
    {
        $customer = $this->customer_model->find((int) $customerId);
        if (!$customer) { show_404(); return; }
        $this->output->set_content_type('application/json')->set_output(json_encode($this->customer_billing_periods->options($customer), JSON_UNESCAPED_UNICODE));
    }

    public function store()
    {
        $customerId = (int) $this->input->post('customer_id');
        $customer = $this->customer_model->find($customerId);

        if (!$customer) {
            show_404();
            return;
        }

        $paymentDate = $this->input->post('payment_date') ?: date('Y-m-d');
        $parsedDate = DateTime::createFromFormat('!Y-m-d', $paymentDate);
        $billMonthInput = $this->input->post('bill_month');
        $billYearInput = $this->input->post('bill_year');
        $billMonth = $billMonthInput === null ? (int) date('n', strtotime($paymentDate)) : (int) $billMonthInput;
        $billYear = $billYearInput === null ? (int) date('Y', strtotime($paymentDate)) : (int) $billYearInput;
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $paymentDate
            || $billMonth < 1 || $billMonth > 12 || $billYear < 2000 || $billYear > 2100) {
            $this->session->set_flashdata('error', 'Tanggal pembayaran atau periode tagihan tidak valid.');
            redirect('customers');
            return;
        }
        $method = trim($this->input->post('payment_method', true));
        $periods = $this->customer_billing_periods->options($customer);
        $selectedPeriod = sprintf('%04d-%02d', $billYear, $billMonth);
        if (!in_array($selectedPeriod, array_column($periods['options'], 'value'), true)) {
            $this->session->set_flashdata('error', 'Periode tidak dapat dibayar: sudah lunas, dibebaskan karena isolir, atau belum tersedia.');
            redirect('customers'); return;
        }

        if (!in_array($method, $this->methods, true)) {
            $method = 'CASH';
        }

        $isFirstPayment = !$this->payment_model->has_customer_payment((int) $customer['id'], (string) $customer['customer_code']);
        $paymentType = $isFirstPayment ? 'PSB' : 'BULANAN';

        $saved = $this->payment_model->insert([
            'customer_id' => (int) $customer['id'],
            'customer_code' => $customer['customer_code'],
            'customer_name' => $customer['name'],
            'bill_month' => $billMonth,
            'bill_year' => $billYear,
            'package_name' => $customer['package_name'],
            'price' => (float) $customer['price'],
            'group_name' => $customer['group_name'],
            'payment_type' => $paymentType,
            'payment_date' => $paymentDate,
            'payment_method' => $method,
            'notes' => trim($this->input->post('notes', true)),
            'input_date' => date('Y-m-d'),
        ]);

        $message = $saved
            ? 'Pembayaran berhasil disimpan sebagai ' . $paymentType . ($isFirstPayment ? ' karena merupakan pembayaran pertama pelanggan.' : ' karena pelanggan sudah memiliki riwayat pembayaran sebelumnya.')
            : 'Pembayaran gagal disimpan.';
        $flashType = $saved ? 'success' : 'error';
        if ($saved && !empty($customer['is_isolated'])) {
            $restore = $this->customer_isolation->restoreCustomer((int) $customer['id'], 'payment');
            $message .= ' ' . $restore['message'];
            if (!$restore['success']) $flashType = 'error';
        }
        $this->session->set_flashdata($flashType, $message);

        redirect($this->input->post('redirect_to') ?: 'customers');
    }

    public function delete($id)
    {
        $this->payment_model->delete($id);
        redirect('payments');
    }

    protected function render($view, array $data = [])
    {
        $data['body_class'] = 'monitoring-page';
        parent::render($view, $data);
    }

    private function filters()
    {
        $fields = [
            'input_date_from',
            'input_date_to',
            'customer_code',
            'customer_name',
            'bill_month',
            'bill_year',
            'package_name',
            'group_name',
            'payment_type',
            'payment_date_from',
            'payment_date_to',
            'payment_method',
            'notes',
        ];

        $filters = [];

        foreach ($fields as $field) {
            $filters[$field] = trim($this->input->get($field, true));
        }

        return $filters;
    }
}
