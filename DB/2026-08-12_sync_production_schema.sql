-- Sinkronisasi schema instalasi production single ISP ke fitur aplikasi terbaru.
-- Migration ini idempoten dan tidak menghapus tabel, kolom, maupun data.

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

SET @has_latitude = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'latitude');
SET @sql = IF(@has_latitude = 0, 'ALTER TABLE customers ADD COLUMN latitude DECIMAL(10,7) NULL AFTER address', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @has_longitude = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'longitude');
SET @sql = IF(@has_longitude = 0, 'ALTER TABLE customers ADD COLUMN longitude DECIMAL(10,7) NULL AFTER latitude', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

CREATE TABLE IF NOT EXISTS customer_address_coordinate_backup_20260717 (
    customer_id INT NOT NULL,
    address TEXT NOT NULL,
    backed_up_at DATETIME NOT NULL,
    PRIMARY KEY (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO customer_address_coordinate_backup_20260717 (customer_id, address, backed_up_at)
SELECT id, address, NOW() FROM customers
WHERE address IS NOT NULL AND TRIM(address) <> ''
  AND TRIM(address) REGEXP '^-?[0-9]+([.][0-9]+)?[[:space:]]*,[[:space:]]*-?[0-9]+([.][0-9]+)?$'
ON DUPLICATE KEY UPDATE address = VALUES(address);

UPDATE customers
SET latitude = CAST(TRIM(SUBSTRING_INDEX(address, ',', 1)) AS DECIMAL(10,7)),
    longitude = CAST(TRIM(SUBSTRING_INDEX(address, ',', -1)) AS DECIMAL(10,7)),
    updated_at = NOW()
WHERE address IS NOT NULL AND TRIM(address) <> ''
  AND TRIM(address) REGEXP '^-?[0-9]+([.][0-9]+)?[[:space:]]*,[[:space:]]*-?[0-9]+([.][0-9]+)?$';

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

CREATE TABLE IF NOT EXISTS customer_onts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id BIGINT UNSIGNED NOT NULL,
    ont_serial_number VARCHAR(100) NOT NULL,
    ont_index VARCHAR(50) NULL,
    pon_port SMALLINT UNSIGNED NULL,
    onu_id SMALLINT UNSIGNED NULL,
    last_detected_name VARCHAR(190) NULL,
    last_seen_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_customer_onts_customer (customer_id),
    UNIQUE KEY uq_customer_onts_serial (ont_serial_number),
    KEY idx_customer_onts_index (ont_index),
    KEY idx_customer_onts_last_seen (last_seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO app_settings (setting_group, setting_key, setting_value, setting_type, updated_at) VALUES
('monitoring', 'router_status_refresh_seconds', '3', 'integer', NOW()),
('isolation', 'isolation_enabled', '0', 'boolean', NOW()),
('isolation', 'isolation_profile_name', 'ISOLIR', 'string', NOW()),
('isolation', 'isolation_group_1_due_day', '10', 'integer', NOW()),
('isolation', 'isolation_group_2_due_day', '25', 'integer', NOW()),
('isolation', 'isolation_grace_days', '5', 'integer', NOW()),
('isolation', 'isolation_cron_token', SHA2(CONCAT(UUID(), UUID(), NOW()), 256), 'string', NOW()),
('branding', 'brand_primary_color', '#0A84FF', 'string', NOW()),
('branding', 'brand_background_color', '#050914', 'string', NOW()),
('branding', 'pwa_description', 'Aplikasi billing dan monitoring internet', 'string', NOW())
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);

INSERT INTO app_settings (setting_group, setting_key, setting_value, setting_type, updated_at)
SELECT 'scheduler', 'cron_token', setting_value, 'string', NOW()
FROM app_settings
WHERE setting_key = 'isolation_cron_token'
  AND NOT EXISTS (SELECT 1 FROM app_settings x WHERE x.setting_key = 'cron_token')
LIMIT 1;

-- Normalisasi email duplikat tanpa membuang akun sebelum unique index dibuat.
UPDATE users u
JOIN (
    SELECT LOWER(TRIM(email)) AS email_key, MIN(id) AS keep_id
    FROM users
    WHERE email IS NOT NULL AND TRIM(email) <> ''
    GROUP BY LOWER(TRIM(email))
    HAVING COUNT(*) > 1
) duplicate_email ON LOWER(TRIM(u.email)) = duplicate_email.email_key
SET u.email = CONCAT(u.username, '+', u.id, '@import.local')
WHERE u.id <> duplicate_email.keep_id;

SET @has_index = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND INDEX_NAME = 'uq_customers_code');
SET @sql = IF(@has_index = 0, 'ALTER TABLE customers ADD UNIQUE KEY uq_customers_code (customer_code)', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @has_index = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'internet_packages' AND INDEX_NAME = 'uq_packages_name');
SET @sql = IF(@has_index = 0, 'ALTER TABLE internet_packages ADD UNIQUE KEY uq_packages_name (package_name)', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @has_index = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'profit_sharing_settings' AND INDEX_NAME = 'uq_profit_effective_month');
SET @sql = IF(@has_index = 0, 'ALTER TABLE profit_sharing_settings ADD UNIQUE KEY uq_profit_effective_month (effective_month)', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @has_index = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roles' AND INDEX_NAME = 'uq_roles_name');
SET @sql = IF(@has_index = 0, 'ALTER TABLE roles ADD UNIQUE KEY uq_roles_name (name)', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @has_index = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'role_permissions' AND INDEX_NAME = 'uq_role_permission');
SET @sql = IF(@has_index = 0, 'ALTER TABLE role_permissions ADD UNIQUE KEY uq_role_permission (role_id, permission_key)', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @has_index = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uq_users_username');
SET @sql = IF(@has_index = 0, 'ALTER TABLE users ADD UNIQUE KEY uq_users_username (username)', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @has_index = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uq_users_global_email');
SET @sql = IF(@has_index = 0, 'ALTER TABLE users ADD UNIQUE KEY uq_users_global_email (email)', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @has_fk = (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND CONSTRAINT_NAME = 'fk_customers_package');
SET @sql = IF(@has_fk = 0, 'ALTER TABLE customers ADD CONSTRAINT fk_customers_package FOREIGN KEY (package_id) REFERENCES internet_packages(id) ON DELETE RESTRICT ON UPDATE CASCADE', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @has_fk = (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'customer_payments' AND CONSTRAINT_NAME = 'fk_payments_customer');
SET @sql = IF(@has_fk = 0, 'ALTER TABLE customer_payments ADD CONSTRAINT fk_payments_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT ON UPDATE CASCADE', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @has_fk = (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'internet_packages' AND CONSTRAINT_NAME = 'fk_packages_router');
SET @sql = IF(@has_fk = 0, 'ALTER TABLE internet_packages ADD CONSTRAINT fk_packages_router FOREIGN KEY (router_id) REFERENCES mikrotik_routers(id) ON DELETE RESTRICT ON UPDATE CASCADE', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @has_fk = (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'role_permissions' AND CONSTRAINT_NAME = 'fk_role_permissions_role');
SET @sql = IF(@has_fk = 0, 'ALTER TABLE role_permissions ADD CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE ON UPDATE CASCADE', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @has_fk = (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND CONSTRAINT_NAME = 'fk_users_role');
SET @sql = IF(@has_fk = 0, 'ALTER TABLE users ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT ON UPDATE CASCADE', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;
