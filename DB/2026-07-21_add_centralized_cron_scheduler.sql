-- Scheduler cron terpusat: log eksekusi per jadwal dan per task.

CREATE TABLE IF NOT EXISTS cron_runs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    schedule_name VARCHAR(30) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'RUNNING',
    started_at DATETIME NOT NULL,
    finished_at DATETIME NULL,
    duration_ms INT UNSIGNED NULL,
    summary_json LONGTEXT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_cron_runs_schedule_started (schedule_name, started_at),
    KEY idx_cron_runs_status_started (status, started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cron_task_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    cron_run_id BIGINT UNSIGNED NOT NULL,
    schedule_name VARCHAR(30) NOT NULL,
    task_key VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL,
    duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
    message TEXT NULL,
    result_json LONGTEXT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_cron_task_run (cron_run_id),
    KEY idx_cron_task_schedule_date (schedule_name, created_at),
    KEY idx_cron_task_status_date (status, created_at),
    CONSTRAINT fk_cron_task_run FOREIGN KEY (cron_run_id) REFERENCES cron_runs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO app_settings (setting_group, setting_key, setting_value, setting_type, updated_at)
SELECT 'scheduler', 'cron_token', setting_value, 'string', NOW()
FROM app_settings
WHERE setting_key = 'isolation_cron_token'
  AND NOT EXISTS (SELECT 1 FROM app_settings x WHERE x.setting_key = 'cron_token')
LIMIT 1;
