<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set(app_setting('timezone', 'Asia/Jakarta'));
        $this->load->library('cron/Cron_scheduler');
    }

    public function five_minutes() { $this->execute('five_minutes'); }
    public function hourly() { $this->execute('hourly'); }
    public function daily() { $this->execute('daily'); }

    public function daily_preview()
    {
        if (!$this->authorized()) {
            $this->respond(403, ['success' => false, 'message' => 'Token cron tidak valid.']);
            return;
        }
        try {
            $this->load->library('Customer_isolation');
            $this->respond(200, $this->customer_isolation->preview($this->input->get('date', true)));
        } catch (Throwable $e) {
            $this->respond(500, ['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // Backward compatibility untuk endpoint lama.
    public function isolation() { $this->execute('daily', ['customer_isolation']); }

    private function execute($schedule, array $onlyTasks = [])
    {
        if (!$this->authorized()) {
            $this->respond(403, ['success' => false, 'message' => 'Token cron tidak valid.']);
            return;
        }

        @set_time_limit(0);
        try {
            $result = $this->cron_scheduler->run($schedule, $onlyTasks);
            $this->respond(!empty($result['success']) ? 200 : 500, $result);
        } catch (Throwable $e) {
            log_message('error', 'Cron ' . $schedule . ' gagal: ' . $e->getMessage());
            $this->respond(500, ['success' => false, 'schedule' => $schedule, 'message' => $e->getMessage()]);
        }
    }

    private function authorized()
    {
        if ($this->input->is_cli_request()) return true;
        $configured = trim((string) app_setting('cron_token', app_setting('isolation_cron_token', '')));
        if ($configured === '') return false;
        $header = trim((string) $this->input->get_request_header('Authorization', true));
        $provided = stripos($header, 'Bearer ') === 0
            ? trim(substr($header, 7))
            : trim((string) $this->input->get('token', true));
        return $provided !== '' && hash_equals($configured, $provided);
    }

    private function respond($status, array $payload)
    {
        $this->output
            ->set_status_header((int) $status)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
