<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    protected $permission = null;
    protected $currentUser = null;

    public function __construct()
    {
        parent::__construct();

        $this->currentUser = $this->session->userdata('auth_user');

        if (!$this->currentUser) {
            $this->session->set_userdata('intended_url', current_url());
            redirect('login');
            return;
        }

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

    protected function render($view, array $data = [], $moduleJsload = null)
    {
        $data['current_user'] = $this->currentUser;

        $this->load->view('../../views/layout/header', $data);
        $this->load->view($view, $data);
        $this->load->view('../../views/layout/footer', [
            'module_jsload' => $moduleJsload,
        ]);
    }
}
