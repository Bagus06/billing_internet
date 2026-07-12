<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Home extends MY_Controller
{
    protected $permission = 'home';
    public function index()
    {
        $data = [
            'title' => 'ISP BATARA NET',
            'body_class' => '',
        ];

        $this->load->view('../../views/layout/header', $data);
        $this->load->view('index');
        $this->load->view('../../views/layout/footer');
    }
}
