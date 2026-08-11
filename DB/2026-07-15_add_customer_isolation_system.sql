-- Sistem isolir pelanggan PPPoE berdasarkan kelompok dan pembayaran bulanan.

SET @has_is_isolated = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'is_isolated');
SET @sql = IF(@has_is_isolated = 0, 'ALTER TABLE customers ADD COLUMN is_isolated TINYINT(1) NOT NULL DEFAULT 0 AFTER customer_status', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @has_isolated_at = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'isolated_at');
SET @sql = IF(@has_isolated_at = 0, 'ALTER TABLE customers ADD COLUMN isolated_at DATETIME NULL AFTER is_isolated', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @has_original_profile = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'isolation_original_profile');
SET @sql = IF(@has_original_profile = 0, 'ALTER TABLE customers ADD COLUMN isolation_original_profile VARCHAR(150) NULL AFTER isolated_at', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @has_isolation_error = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'isolation_last_error');
SET @sql = IF(@has_isolation_error = 0, 'ALTER TABLE customers ADD COLUMN isolation_last_error TEXT NULL AFTER isolation_original_profile', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

CREATE TABLE IF NOT EXISTS customer_isolation_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id INT UNSIGNED NOT NULL,
    action VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL,
    secret_name VARCHAR(180) NULL,
    from_profile VARCHAR(150) NULL,
    to_profile VARCHAR(150) NULL,
    message TEXT NULL,
    source VARCHAR(20) NOT NULL DEFAULT 'cron',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_isolation_customer_date (customer_id, created_at),
    KEY idx_isolation_status_date (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO app_settings (setting_group, setting_key, setting_value, setting_type, updated_at) VALUES
('isolation', 'isolation_enabled', '0', 'boolean', NOW()),
('isolation', 'isolation_profile_name', 'ISOLIR', 'string', NOW()),
('isolation', 'isolation_group_1_due_day', '10', 'integer', NOW()),
('isolation', 'isolation_group_2_due_day', '25', 'integer', NOW()),
('isolation', 'isolation_grace_days', '5', 'integer', NOW()),
('isolation', 'isolation_cron_token', SHA2(CONCAT(UUID(), UUID(), NOW()), 256), 'string', NOW())
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);
