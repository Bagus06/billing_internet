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

        $this->render('index', $data);
    }
}
