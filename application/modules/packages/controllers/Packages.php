<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Packages extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('packages/package_model');
    }

    public function index()
    {
        $this->render('index', [
            'title' => 'Paket Internet - ISP BATARA NET',
            'packages' => $this->package_model->get_all(),
        ]);
    }

    public function create()
    {
        $this->render('form', [
            'title' => 'Tambah Paket Internet - ISP BATARA NET',
            'mode' => 'create',
            'package' => $this->blankPackage(),
            'action' => site_url('packages/store'),
        ]);
    }

    public function store()
    {
        $this->package_model->insert($this->payload());
        redirect('packages');
    }

    public function edit($id)
    {
        $package = $this->package_model->find($id);

        if (!$package) {
            show_404();
            return;
        }

        $this->render('form', [
            'title' => 'Edit Paket Internet - ISP BATARA NET',
            'mode' => 'edit',
            'package' => $package,
            'action' => site_url('packages/update/' . $id),
        ]);
    }

    public function update($id)
    {
        $this->package_model->update($id, $this->payload());
        redirect('packages');
    }

    public function delete($id)
    {
        $this->package_model->delete($id);
        redirect('packages');
    }

    private function render($view, array $data)
    {
        $data['body_class'] = 'monitoring-page';

        $this->load->view('../../views/layout/header', $data);
        $this->load->view($view, $data);
        $this->load->view('../../views/layout/footer');
    }

    private function payload()
    {
        return [
            'package_name' => trim($this->input->post('package_name', true)),
            'price' => $this->normalizePrice($this->input->post('price', true)),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
            'notes' => trim($this->input->post('notes', true)),
        ];
    }

    private function normalizePrice($value)
    {
        $value = str_replace(['.', ','], ['', '.'], (string) $value);

        return is_numeric($value) ? (float) $value : 0;
    }

    private function blankPackage()
    {
        return [
            'package_name' => '',
            'price' => 0,
            'is_active' => 1,
            'notes' => '',
        ];
    }
}
