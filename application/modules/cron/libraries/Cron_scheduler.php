<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cron_scheduler
{
    private $CI;
    private $schedules = ['five_minutes', 'hourly', 'daily'];

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    public function run($schedule, array $onlyTasks = [])
    {
        $schedule = strtolower(trim((string) $schedule));
        if (!in_array($schedule, $this->schedules, true)) {
            throw new InvalidArgumentException('Jadwal cron tidak dikenal: ' . $schedule);
        }

        $lockName = 'billing_internet_cron_' . $schedule;
        if (!$this->acquireLock($lockName)) {
            return [
                'success' => true,
                'skipped' => true,
                'schedule' => $schedule,
                'message' => 'Cron dilewati karena jadwal yang sama masih berjalan.',
                'tasks' => [],
            ];
        }

        $startedAt = microtime(true);
        $runId = null;
        $results = [];
        $success = true;

        try {
            $runId = $this->startRun($schedule);
            foreach ($this->tasksFor($schedule) as $task) {
                if ($onlyTasks && !in_array($task['key'], $onlyTasks, true)) continue;
                $result = $this->runTask($runId, $schedule, $task);
                $results[] = $result;
                if (empty($result['success'])) $success = false;
            }

            $duration = (int) round((microtime(true) - $startedAt) * 1000);
            $summary = [
                'success' => $success,
                'skipped' => false,
                'schedule' => $schedule,
                'started_at' => date('Y-m-d H:i:s', (int) $startedAt),
                'finished_at' => date('Y-m-d H:i:s'),
                'duration_ms' => $duration,
                'task_count' => count($results),
                'tasks' => $results,
            ];
            $this->finishRun($runId, $success ? 'SUCCESS' : 'FAILED', $duration, $summary);
            return $summary;
        } finally {
            $this->releaseLock($lockName);
        }
    }

    /**
     * Registry seluruh pekerjaan berkala.
     *
     * Tambahkan task baru pada salah satu jadwal berikut. Handler harus berupa
     * method private dalam class ini dan mengembalikan array yang memiliki key
     * success. Jangan menaruh logika bisnis panjang di registry; panggil library
     * domain yang sama dengan action manual aplikasi.
     */
    private function tasksFor($schedule)
    {
        $registry = [
            'five_minutes' => [
                // Contoh: ['key' => 'queue_worker', 'handler' => 'runQueueWorker'],
            ],
            'hourly' => [
                // Contoh: ['key' => 'router_health', 'handler' => 'runRouterHealth'],
            ],
            'daily' => [
                ['key' => 'customer_isolation', 'handler' => 'runCustomerIsolation'],
            ],
        ];
        return $registry[$schedule];
    }

    private function runCustomerIsolation()
    {
        $this->CI->load->library('Customer_isolation');
        return $this->CI->customer_isolation->run('cron');
    }

    private function runTask($runId, $schedule, array $task)
    {
        $started = microtime(true);
        try {
            if (empty($task['handler']) || !method_exists($this, $task['handler'])) {
                throw new RuntimeException('Handler task tidak ditemukan.');
            }
            $payload = call_user_func([$this, $task['handler']]);
            if (!is_array($payload)) $payload = ['success' => true, 'result' => $payload];
            $success = !empty($payload['success']);
            $message = isset($payload['message']) ? (string) $payload['message'] : ($success ? 'Task berhasil.' : 'Task gagal.');
        } catch (Throwable $e) {
            $success = false;
            $message = $e->getMessage();
            $payload = ['success' => false, 'message' => $message];
            log_message('error', 'Cron task ' . $task['key'] . ' gagal: ' . $message);
        }

        $duration = (int) round((microtime(true) - $started) * 1000);
        $result = [
            'key' => $task['key'],
            'success' => $success,
            'duration_ms' => $duration,
            'message' => $message,
            'result' => $payload,
        ];
        $this->logTask($runId, $schedule, $result);
        return $result;
    }

    private function acquireLock($name)
    {
        $query = $this->CI->db->query('SELECT GET_LOCK(?, 0) AS acquired', [$name]);
        return $query && (int) $query->row()->acquired === 1;
    }

    private function releaseLock($name)
    {
        $this->CI->db->query('SELECT RELEASE_LOCK(?)', [$name]);
    }

    private function loggingAvailable()
    {
        return $this->CI->db->table_exists('cron_runs') && $this->CI->db->table_exists('cron_task_logs');
    }

    private function startRun($schedule)
    {
        if (!$this->loggingAvailable()) return null;
        $this->CI->db->insert('cron_runs', [
            'schedule_name' => $schedule,
            'status' => 'RUNNING',
            'started_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->CI->db->insert_id();
    }

    private function finishRun($runId, $status, $duration, array $summary)
    {
        if (!$runId) return;
        $this->CI->db->where('id', $runId)->update('cron_runs', [
            'status' => $status,
            'finished_at' => date('Y-m-d H:i:s'),
            'duration_ms' => $duration,
            'summary_json' => json_encode($summary, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function logTask($runId, $schedule, array $result)
    {
        if (!$runId || !$this->loggingAvailable()) return;
        $this->CI->db->insert('cron_task_logs', [
            'cron_run_id' => $runId,
            'schedule_name' => $schedule,
            'task_key' => $result['key'],
            'status' => $result['success'] ? 'SUCCESS' : 'FAILED',
            'duration_ms' => $result['duration_ms'],
            'message' => $result['message'],
            'result_json' => json_encode($result['result'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
