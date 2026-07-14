<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Roles extends MY_Controller
{
    protected $permission = 'roles';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('roles/role_model');
    }

    public function index()
    {
        $this->render('index', ['title' => 'Roles - ISP BATARA NET', 'roles' => $this->role_model->get_all()]);
    }

    public function create()
    {
        $this->form('create', ['name' => '', 'description' => '', 'is_active' => 1], [], site_url('roles/store'));
    }

    public function store()
    {
        if (!$this->validateName()) { redirect('roles/create'); return; }
        $this->role_model->insert($this->payload(), $this->permissions());
        $this->session->set_flashdata('success', 'Role berhasil ditambahkan.');
        redirect('roles');
    }

    public function edit($id)
    {
        $role = $this->role_model->find($id);
        if (!$role) { show_404(); return; }
        $this->form('edit', $role, $this->role_model->permissions($id), site_url('roles/update/' . $id));
    }

    public function update($id)
    {
        if (!$this->role_model->find($id)) { show_404(); return; }
        if (!$this->validateName()) { redirect('roles/edit/' . $id); return; }
        $this->role_model->update($id, $this->payload(), $this->permissions());
        if ((int) $id === (int) $this->currentUser['role_id']) {
            $this->session->set_userdata('auth_permissions', $this->role_model->permissions($id));
        }
        $this->session->set_flashdata('success', 'Role berhasil diperbarui.');
        redirect('roles');
    }

    public function delete($id)
    {
        if ((int) $id === (int) $this->currentUser['role_id'] || $this->role_model->has_users($id)) {
            $this->session->set_flashdata('error', 'Role yang sedang digunakan tidak dapat dihapus.');
        } else {
            $this->role_model->delete($id);
            $this->session->set_flashdata('success', 'Role berhasil dihapus.');
        }
        redirect('roles');
    }

    private function form($mode, array $role, array $permissions, $action)
    {
        $this->render('form', ['title' => ($mode === 'create' ? 'Tambah' : 'Edit') . ' Role - ISP BATARA NET',
            'mode' => $mode, 'role' => $role, 'permissions' => $permissions,
            'feature_options' => $this->role_model->feature_options(), 'action' => $action]);
    }

    private function payload()
    {
        return ['name' => trim($this->input->post('name', true)), 'description' => trim($this->input->post('description', true)),
            'is_active' => $this->input->post('is_active') ? 1 : 0];
    }

    private function permissions() { return (array) $this->input->post('permissions'); }
    private function validateName()
    {
        if (trim($this->input->post('name', true)) !== '') { return true; }
        $this->session->set_flashdata('error', 'Nama role wajib diisi.'); return false;
    }
    protected function render($view, array $data = []) { $data['body_class'] = 'monitoring-page'; parent::render($view, $data); }
}
