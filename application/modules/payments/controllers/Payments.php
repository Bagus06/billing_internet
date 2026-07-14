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
            'title' => 'Data Pembayaran - ISP BATARA NET',
            'payments' => $this->payment_model->get_paginated($filters, $perPage, $offset),
            'filters' => $filters,
            'page' => $page,
            'per_page' => $perPage,
            'total_rows' => $totalRows,
            'total_pages' => $totalPages,
        ]);
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
        $billMonth = (int) date('n', strtotime($paymentDate));
        $billYear = (int) date('Y', strtotime($paymentDate));
        $method = trim($this->input->post('payment_method', true));

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

        $this->session->set_flashdata($saved ? 'success' : 'error', $saved
            ? 'Pembayaran berhasil disimpan sebagai ' . $paymentType . ($isFirstPayment ? ' karena merupakan pembayaran pertama pelanggan.' : ' karena pelanggan sudah memiliki riwayat pembayaran sebelumnya.')
            : 'Pembayaran gagal disimpan.');

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
