<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Profile extends MY_Controller
{
    protected $permission = 'profile';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('users/user_model');
    }

    public function index()
    {
        $this->render('index', ['title' => 'Profile - ISP BATARA NET', 'user' => $this->user_model->find_with_role($this->currentUser['id'])]);
    }

    public function update()
    {
        $id = $this->currentUser['id'];
        $username = trim($this->input->post('username', true));
        $email = trim($this->input->post('email', true));
        $name = trim($this->input->post('name', true));
        if ($name === '' || $username === '' || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) || $this->user_model->username_exists($username, $id)) {
            $this->session->set_flashdata('error', 'Data profile tidak valid atau username sudah digunakan.');
            redirect('profile'); return;
        }
        $this->user_model->update($id, ['name' => $name, 'username' => $username, 'email' => $email]);
        $this->refreshCurrentUser($this->user_model->find_with_role($id));
        $this->session->set_flashdata('success', 'Profile berhasil diperbarui.');
        redirect('profile');
    }

    public function password()
    {
        $user = $this->user_model->find($this->currentUser['id']);
        $current = (string) $this->input->post('current_password');
        $password = (string) $this->input->post('password');
        $confirmation = (string) $this->input->post('password_confirmation');
        if (!$user || !password_verify($current, $user['password'])) {
            $this->session->set_flashdata('error', 'Password saat ini tidak sesuai.');
        } elseif (strlen($password) < 8) {
            $this->session->set_flashdata('error', 'Password baru minimal 8 karakter.');
        } elseif ($password !== $confirmation) {
            $this->session->set_flashdata('error', 'Konfirmasi password baru tidak sesuai.');
        } else {
            $this->user_model->update($user['id'], ['password' => $password]);
            $this->session->set_flashdata('success', 'Password berhasil diubah.');
        }
        redirect('profile');
    }

    protected function render($view, array $data = [], $moduleJsload = null) { $data['body_class'] = 'monitoring-page'; parent::render($view, $data, $moduleJsload); }
}
