<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cron_log_model extends CI_Model
{
    public function recent_runs($limit = 25)
    {
        if (!$this->available()) return [];

        $limit = max(1, min(100, (int) $limit));
        $runs = $this->db->order_by('id', 'DESC')->limit($limit)->get('cron_runs')->result_array();
        if (!$runs) return [];

        $runIds = array_map('intval', array_column($runs, 'id'));
        $tasks = $this->db->where_in('cron_run_id', $runIds)->order_by('id', 'ASC')->get('cron_task_logs')->result_array();
        $tasksByRun = [];
        foreach ($tasks as $task) {
            $payload = json_decode((string) ($task['result_json'] ?? ''), true);
            $task['result'] = is_array($payload) ? $payload : [];
            $tasksByRun[(int) $task['cron_run_id']][] = $task;
        }

        foreach ($runs as &$run) {
            $summary = json_decode((string) ($run['summary_json'] ?? ''), true);
            $run['summary'] = is_array($summary) ? $summary : [];
            $run['tasks'] = $tasksByRun[(int) $run['id']] ?? [];
        }
        unset($run);

        return $runs;
    }

    public function available()
    {
        return $this->db->table_exists('cron_runs') && $this->db->table_exists('cron_task_logs');
    }
}
