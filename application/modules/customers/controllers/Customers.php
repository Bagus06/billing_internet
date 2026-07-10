<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Customers extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('customers/customer_model');
        $this->load->model('packages/package_model');
    }

    public function index()
    {
        $perPage = max(5, min(100, (int) $this->input->get('per_page') ?: 10));
        $page = max(1, (int) $this->input->get('page') ?: 1);
        $filters = $this->filters();
        $totalRows = $this->customer_model->count_filtered($filters);
        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $this->render('index', [
            'title' => 'Data Pelanggan - ISP BATARA NET',
            'customers' => $this->customer_model->get_paginated($filters, $perPage, $offset),
            'filters' => $filters,
            'page' => $page,
            'per_page' => $perPage,
            'total_rows' => $totalRows,
            'total_pages' => $totalPages,
        ]);
    }

    public function create()
    {
        $this->render('form', [
            'title' => 'Tambah Pelanggan - ISP BATARA NET',
            'mode' => 'create',
            'customer' => $this->blankCustomer(),
            'packages' => $this->package_model->get_all(true),
            'action' => site_url('customers/store'),
        ]);
    }

    public function store()
    {
        $this->customer_model->insert($this->payload());
        redirect('customers');
    }

    public function edit($id)
    {
        $customer = $this->customer_model->find($id);

        if (!$customer) {
            show_404();
            return;
        }

        $this->render('form', [
            'title' => 'Edit Pelanggan - ISP BATARA NET',
            'mode' => 'edit',
            'customer' => $customer,
            'packages' => $this->package_model->get_all(true),
            'action' => site_url('customers/update/' . $id),
        ]);
    }

    public function update($id)
    {
        $this->customer_model->update($id, $this->payload());
        redirect('customers');
    }

    public function delete($id)
    {
        $this->customer_model->delete($id);
        redirect('customers');
    }

    private function render($view, array $data)
    {
        $data['body_class'] = 'monitoring-page';

        $this->load->view('../../views/layout/header', $data);
        $this->load->view($view, $data);
        $this->load->view('../../views/layout/footer', [
            'module_jsload' => APPPATH . 'modules/customers/jsload.php',
        ]);
    }

    private function payload()
    {
        $nik = trim($this->input->post('nik', true));
        $ktpPhoto = $this->uploadKtpPhoto();
        $package = $this->package_model->find((int) $this->input->post('package_id'));
        $psbDate = $this->input->post('psb_date') ?: null;

        return [
            'customer_code' => $this->generateCustomerCode($nik),
            'name' => trim($this->input->post('name', true)),
            'phone' => trim($this->input->post('phone', true)),
            'nik' => $nik,
            'ktp_photo' => $ktpPhoto,
            'address' => trim($this->input->post('address', true)),
            'package_id' => $package ? (int) $package['id'] : null,
            'package_name' => $package ? $package['package_name'] : '',
            'price' => $package ? (float) $package['price'] : 0,
            'psb_date' => $psbDate,
            'group_name' => $this->groupFromPsbDate($psbDate),
            'customer_status' => trim($this->input->post('customer_status', true)),
            'promoter' => trim($this->input->post('promoter', true)),
            'notes' => trim($this->input->post('notes', true)),
        ];
    }

    private function normalizePrice($value)
    {
        $value = str_replace(['.', ','], ['', '.'], (string) $value);

        return is_numeric($value) ? (float) $value : 0;
    }

    private function filters()
    {
        $fields = [
            'customer_code',
            'name',
            'phone',
            'package_name',
            'group_name',
            'customer_status',
            'payment_status',
            'promoter',
        ];

        $filters = [];

        foreach ($fields as $field) {
            $filters[$field] = trim($this->input->get($field, true));
        }

        return $filters;
    }

    private function groupFromPsbDate($date)
    {
        if (!$date) {
            return '';
        }

        $day = (int) date('j', strtotime($date));

        return $day >= 16 ? 'Kelompok 2' : 'Kelompok 1';
    }

    private function generateCustomerCode($nik)
    {
        $digits = preg_replace('/\D+/', '', (string) $nik);
        $suffix = substr(str_pad($digits, 6, '0', STR_PAD_LEFT), -6);

        return 'BTN-' . $suffix;
    }

    private function uploadKtpPhoto()
    {
        $existing = trim($this->input->post('existing_ktp_photo', true));

        if (empty($_FILES['ktp_photo']['name'])) {
            return $existing;
        }

        $uploadPath = FCPATH . 'assets/img/ktp/';

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $this->load->library('upload', [
            'upload_path' => $uploadPath,
            'allowed_types' => 'jpg|jpeg|png|webp',
            'max_size' => 4096,
            'encrypt_name' => true,
        ]);

        if (!$this->upload->do_upload('ktp_photo')) {
            return $existing;
        }

        return 'assets/img/ktp/' . $this->upload->data('file_name');
    }

    private function blankCustomer()
    {
        return [
            'customer_code' => '',
            'name' => '',
            'phone' => '',
            'nik' => '',
            'ktp_photo' => '',
            'package_id' => '',
            'address' => '',
            'package_name' => '',
            'price' => 0,
            'psb_date' => date('Y-m-d'),
            'group_name' => $this->groupFromPsbDate(date('Y-m-d')),
            'customer_status' => 'ACTIVE',
            'payment_status' => 'BELUM BAYAR',
            'promoter' => '',
            'notes' => '',
        ];
    }
}
