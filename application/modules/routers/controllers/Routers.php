<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Routers extends MY_Controller
{
    protected $permission = 'routers';
    public function __construct()
    {
        parent::__construct();
        $this->load->model('routers/router_model');
    }

    public function index()
    {
        $this->router_model->encrypt_existing_plain_passwords();

        $this->render('index', [
            'title' => 'Data Mikrotik - ISP BATARA NET',
            'routers' => $this->router_model->get_all(),
        ]);
    }

    public function create()
    {
        $this->render('form', [
            'title' => 'Tambah Mikrotik - ISP BATARA NET',
            'mode' => 'create',
            'router' => $this->blankRouter(),
            'action' => site_url('routers/store'),
        ]);
    }

    public function store()
    {
        $this->router_model->insert($this->payload());
        redirect('routers');
    }

    public function edit($id)
    {
        $router = $this->router_model->find($id);

        if (!$router) {
            show_404();
            return;
        }

        $this->render('form', [
            'title' => 'Edit Mikrotik - ISP BATARA NET',
            'mode' => 'edit',
            'router' => $router,
            'action' => site_url('routers/update/' . $id),
        ]);
    }

    public function update($id)
    {
        $payload = $this->payload();

        if ($payload['password'] === '') {
            unset($payload['password']);
        }

        $this->router_model->update($id, $payload);
        redirect('routers');
    }

    public function delete($id)
    {
        $this->router_model->delete($id);
        redirect('routers');
    }

    protected function render($view, array $data = [])
    {
        $data['body_class'] = 'monitoring-page';
        parent::render($view, $data);
    }

    private function payload()
    {
        return [
            'name' => trim($this->input->post('name', true)),
            'host' => trim($this->input->post('host', true)),
            'port' => (int) $this->input->post('port'),
            'username' => trim($this->input->post('username', true)),
            'password' => (string) $this->input->post('password'),
            'use_ssl' => $this->input->post('use_ssl') ? 1 : 0,
            'timeout' => (int) $this->input->post('timeout'),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
        ];
    }

    private function blankRouter()
    {
        return [
            'name' => '',
            'host' => '',
            'port' => 8728,
            'username' => '',
            'password' => '',
            'use_ssl' => 0,
            'timeout' => 5,
            'is_active' => 1,
        ];
    }
}
