<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model');
    }

    public function login()
    {
        if ($this->session->userdata('auth_user')) {
            redirect('/');
            return;
        }

        $this->load->view('login', [
            'title' => 'Login - ISP BATARA NET',
            'error' => $this->session->flashdata('login_error'),
        ]);
    }

    public function attempt()
    {
        $username = trim($this->input->post('username', true));
        $password = (string) $this->input->post('password');
        $user = $this->auth_model->find_by_username($username);

        if (!$user || empty($user['is_active']) || !password_verify($password, $user['password'])) {
            $this->session->set_flashdata('login_error', 'Username atau password tidak sesuai.');
            redirect('login');
            return;
        }

        $this->auth_model->touch_login($user['id']);
        $this->session->set_userdata('auth_user', [
            'id' => (int) $user['id'],
            'role_id' => (int) $user['role_id'],
            'role_name' => $user['role_name'],
            'name' => $user['name'],
            'username' => $user['username'],
            'email' => $user['email'],
        ]);
        $this->session->set_userdata('auth_permissions', $this->auth_model->permissions_for_role($user['role_id']));

        $intended = $this->session->userdata('intended_url');
        $this->session->unset_userdata('intended_url');

        redirect($intended ?: '/');
    }

    public function logout()
    {
        $this->session->unset_userdata(['auth_user', 'auth_permissions', 'intended_url']);
        redirect('login');
    }
}
