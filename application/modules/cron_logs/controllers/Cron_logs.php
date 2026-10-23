<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cron_logs extends MY_Controller
{
    protected $permission = 'settings';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('cron/cron_log_model');
    }

    public function index()
    {
        $this->render('index', [
            'title' => 'Log Cron - ' . app_setting('isp_name', 'ISP BATARA NET'),
            'body_class' => 'monitoring-page',
            'cron_log_available' => $this->cron_log_model->available(),
            'cron_runs' => $this->cron_log_model->recent_runs(50),
        ]);
    }
}
