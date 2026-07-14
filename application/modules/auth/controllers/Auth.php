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

        $data = [
            'title' => 'Login - ISP BATARA NET',
            'body_class' => 'auth-page',
            'hide_navigation' => true,
            'module_name' => 'auth',
            'error' => $this->session->flashdata('login_error'),
        ];
        $this->load->view('template/index', [
            'content_view' => 'login',
            'view_data' => $data,
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
        $this->session->sess_regenerate(true);
        $preferredTheme = isset($user['preferred_theme']) && in_array($user['preferred_theme'], ['light', 'dark'], true) ? $user['preferred_theme'] : null;
        $preferredLanguage = isset($user['preferred_language']) && in_array($user['preferred_language'], ['id', 'en'], true) ? $user['preferred_language'] : null;
        $this->session->set_userdata('auth_user', [
            'id' => (int) $user['id'],
            'role_id' => (int) $user['role_id'],
            'role_name' => $user['role_name'],
            'name' => $user['name'],
            'username' => $user['username'],
            'email' => $user['email'],
            'preferred_theme' => $preferredTheme,
            'preferred_language' => $preferredLanguage,
        ]);
        $this->session->set_userdata('app_language', $preferredLanguage ?: app_setting('default_language', 'id'));
        $this->session->set_userdata('auth_permissions', $this->auth_model->permissions_for_role($user['role_id']));

        $intended = $this->session->userdata('intended_url');
        $this->session->unset_userdata('intended_url');

        redirect($intended ?: '/');
    }

    public function logout()
    {
        $this->session->sess_destroy();
        redirect('login');
    }
}
