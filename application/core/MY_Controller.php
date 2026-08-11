<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    protected $permission = null;
    protected $currentUser = null;

    public function __construct()
    {
        parent::__construct();

        if (!$this->request_guard->enforce()) return;

        date_default_timezone_set(app_setting('timezone', 'Asia/Jakarta'));

        $this->currentUser = $this->session->userdata('auth_user');

        if (!$this->currentUser) {
            $this->session->set_userdata('intended_url', current_url());
            redirect('login');
            return;
        }

        $this->load->model('auth/auth_model');
        $verifiedUser = $this->auth_model->find_session_user($this->currentUser['id']);
        if (!$verifiedUser) {
            $this->clearAuthSession();
            redirect('login');
            return;
        }
        $this->refreshCurrentUser($verifiedUser);
        $this->session->set_userdata('auth_permissions', $this->auth_model->permissions_for_role($verifiedUser['role_id']));

        if ($this->permission && !$this->can($this->permission)) {
            show_error('Anda tidak memiliki akses ke fitur ini.', 403, 'Akses Ditolak');
            return;
        }
    }

    protected function can($permission)
    {
        $permissions = $this->session->userdata('auth_permissions') ?: [];

        return in_array($permission, $permissions, true);
    }

    protected function refreshCurrentUser(array $user)
    {
        $authUser = [
            'id' => (int) $user['id'],
            'role_id' => (int) $user['role_id'],
            'role_name' => isset($user['role_name']) ? $user['role_name'] : $this->currentUser['role_name'],
            'name' => $user['name'],
            'username' => $user['username'],
            'email' => $user['email'],
            'preferred_theme' => isset($user['preferred_theme']) ? $user['preferred_theme'] : ($this->currentUser['preferred_theme'] ?? null),
            'preferred_language' => isset($user['preferred_language']) ? $user['preferred_language'] : ($this->currentUser['preferred_language'] ?? null),
        ];
        $this->session->set_userdata('auth_user', $authUser);
        $this->currentUser = $authUser;
    }

    protected function clearAuthSession()
    {
        $this->session->unset_userdata(['auth_user', 'auth_permissions']);
    }

    protected function render($view, array $data = [])
    {
        $data['current_user'] = $this->currentUser;
        $data['module_name'] = strtolower(get_class($this));
        $this->load->view('template/index', [
            'content_view' => $view,
            'view_data' => $data,
        ]);
    }
}
