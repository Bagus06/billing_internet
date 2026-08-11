<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Isolation_notice extends CI_Controller
{
    public function index()
    {
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        $this->output->set_header('Pragma: no-cache');
        $this->load->view('isolation_notice', [
            'isp_name' => app_setting('isp_name', 'ISP BATARA NET'),
            'logo_url' => base_url(app_setting('logo_path', 'assets/img/logo.jpeg')),
        ]);
    }
}
