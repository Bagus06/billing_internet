<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Payments extends CI_Controller
{
    private $methods = ['CASH', 'SEABANK'];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('payments/payment_model');
        $this->load->model('customers/customer_model');
    }

    public function index()
    {
        $perPage = max(5, min(100, (int) $this->input->get('per_page') ?: 10));
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

        $this->payment_model->insert([
            'customer_id' => (int) $customer['id'],
            'customer_code' => $customer['customer_code'],
            'customer_name' => $customer['name'],
            'bill_month' => $billMonth,
            'bill_year' => $billYear,
            'package_name' => $customer['package_name'],
            'price' => (float) $customer['price'],
            'group_name' => $customer['group_name'],
            'payment_type' => trim($this->input->post('payment_type', true)) ?: 'BULANAN',
            'payment_date' => $paymentDate,
            'payment_method' => $method,
            'notes' => trim($this->input->post('notes', true)),
            'input_date' => date('Y-m-d'),
        ]);

        redirect($this->input->post('redirect_to') ?: 'customers');
    }

    public function delete($id)
    {
        $this->payment_model->delete($id);
        redirect('payments');
    }

    private function render($view, array $data)
    {
        $data['body_class'] = 'monitoring-page';

        $this->load->view('../../views/layout/header', $data);
        $this->load->view($view, $data);
        $this->load->view('../../views/layout/footer');
    }

    private function filters()
    {
        $fields = [
            'input_date',
            'customer_code',
            'customer_name',
            'bill_month',
            'bill_year',
            'package_name',
            'group_name',
            'payment_type',
            'payment_date',
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
