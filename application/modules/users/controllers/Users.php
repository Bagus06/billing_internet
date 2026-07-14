<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Users extends MY_Controller
{
    protected $permission = 'users';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('users/user_model');
        $this->load->model('roles/role_model');
    }

    public function index()
    {
        $this->render('index', ['title' => 'Users - ISP BATARA NET', 'users' => $this->user_model->get_all()]);
    }

    public function create()
    {
        $this->form('create', $this->blankUser(), site_url('users/store'));
    }

    public function store()
    {
        $payload = $this->payload();
        if (!$this->valid($payload, true)) {
            redirect('users/create');
            return;
        }
        $this->user_model->insert($payload);
        $this->session->set_flashdata('success', 'User berhasil ditambahkan.');
        redirect('users');
    }

    public function edit($id)
    {
        $user = $this->user_model->find($id);
        if (!$user) { show_404(); return; }
        $user['password'] = '';
        $this->form('edit', $user, site_url('users/update/' . $id));
    }

    public function update($id)
    {
        $user = $this->user_model->find($id);
        if (!$user) { show_404(); return; }
        $payload = $this->payload();
        if ((int) $id === (int) $this->currentUser['id']) {
            $payload['is_active'] = 1;
        }
        if (!$this->valid($payload, false, $id)) {
            redirect('users/edit/' . $id);
            return;
        }
        $this->user_model->update($id, $payload);
        if ((int) $id === (int) $this->currentUser['id']) {
            $updated = $this->user_model->find_with_role($id);
            $this->refreshCurrentUser($updated);
            $this->session->set_userdata('auth_permissions', $this->role_model->permissions($updated['role_id']));
        }
        $this->session->set_flashdata('success', 'User berhasil diperbarui.');
        redirect('users');
    }

    public function delete($id)
    {
        if ((int) $id === (int) $this->currentUser['id']) {
            $this->session->set_flashdata('error', 'Akun yang sedang digunakan tidak dapat dihapus.');
        } else {
            $this->user_model->delete($id);
            $this->session->set_flashdata('success', 'User berhasil dihapus.');
        }
        redirect('users');
    }

    private function form($mode, array $user, $action)
    {
        $this->render('form', [
            'title' => ($mode === 'create' ? 'Tambah' : 'Edit') . ' User - ISP BATARA NET',
            'mode' => $mode, 'user' => $user, 'roles' => $this->role_model->get_all(true), 'action' => $action,
        ]);
    }

    private function payload()
    {
        return [
            'role_id' => (int) $this->input->post('role_id'),
            'name' => trim($this->input->post('name', true)),
            'username' => trim($this->input->post('username', true)),
            'email' => trim($this->input->post('email', true)),
            'password' => (string) $this->input->post('password'),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
        ];
    }

    private function valid(array $data, $passwordRequired, $ignoreId = null)
    {
        if ($data['name'] === '' || $data['username'] === '' || !$data['role_id'] || ($passwordRequired && $data['password'] === '')) {
            $this->session->set_flashdata('error', 'Nama, username, role, dan password wajib dilengkapi.'); return false;
        }
        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('error', 'Format email tidak valid.'); return false;
        }
        if ($this->user_model->username_exists($data['username'], $ignoreId)) {
            $this->session->set_flashdata('error', 'Username sudah digunakan.'); return false;
        }
        return true;
    }

    protected function render($view, array $data = [])
    {
        $data['body_class'] = 'monitoring-page';
        parent::render($view, $data);
    }

    private function blankUser()
    {
        return ['role_id' => '', 'name' => '', 'username' => '', 'email' => '', 'password' => '', 'is_active' => 1];
    }
}
