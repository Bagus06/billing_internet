-- Mengembalikan instalasi menjadi single ISP.
-- Data operasional tenant BATARA (id=1) dipertahankan; tenant percobaan dibuang.
-- Backup sebelum migrasi: storage/backups/before-single-isp-2026-08-11.sql

SET @keep_tenant_id := 1;
SET FOREIGN_KEY_CHECKS = 0;

-- Buang seluruh data tenant selain ISP utama sebelum constraint global dipulihkan.
DELETE FROM cron_task_logs WHERE tenant_id <> @keep_tenant_id;
DELETE FROM cron_runs WHERE tenant_id <> @keep_tenant_id;
DELETE FROM customer_isolation_logs WHERE tenant_id <> @keep_tenant_id;
DELETE FROM customer_payments WHERE tenant_id <> @keep_tenant_id;
DELETE FROM customer_status_history WHERE tenant_id <> @keep_tenant_id;
DELETE FROM customers WHERE tenant_id <> @keep_tenant_id;
DELETE FROM internet_packages WHERE tenant_id <> @keep_tenant_id;
DELETE FROM mikrotik_routers WHERE tenant_id <> @keep_tenant_id;
DELETE FROM profit_sharing_settings WHERE tenant_id <> @keep_tenant_id;
DELETE FROM role_permissions WHERE tenant_id <> @keep_tenant_id;
DELETE FROM users WHERE tenant_id <> @keep_tenant_id;
DELETE FROM roles WHERE tenant_id <> @keep_tenant_id;
DELETE FROM app_settings WHERE tenant_id <> @keep_tenant_id;

-- Migrasi referensi file private/public BATARA ke direktori single ISP.
UPDATE customers
SET ktp_photo = REPLACE(ktp_photo, 'private://tenant-1/', 'private://')
WHERE tenant_id = @keep_tenant_id AND ktp_photo LIKE 'private://tenant-1/%';
UPDATE app_settings
SET setting_value = REPLACE(setting_value, 'assets/img/branding/tenant-1/', 'assets/img/branding/')
WHERE tenant_id = @keep_tenant_id AND setting_key = 'logo_path';

DELIMITER $$
DROP PROCEDURE IF EXISTS drop_all_foreign_keys$$
CREATE PROCEDURE drop_all_foreign_keys()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE table_name_value VARCHAR(64);
    DECLARE constraint_name_value VARCHAR(64);
    DECLARE cur CURSOR FOR
        SELECT TABLE_NAME, CONSTRAINT_NAME
        FROM information_schema.REFERENTIAL_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE();
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO table_name_value, constraint_name_value;
        IF done = 1 THEN LEAVE read_loop; END IF;
        SET @sql = CONCAT('ALTER TABLE `', table_name_value, '` DROP FOREIGN KEY `', constraint_name_value, '`');
        PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;
    END LOOP;
    CLOSE cur;
END$$
CALL drop_all_foreign_keys()$$
DROP PROCEDURE drop_all_foreign_keys$$

DROP PROCEDURE IF EXISTS drop_tenant_indexes$$
CREATE PROCEDURE drop_tenant_indexes()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE table_name_value VARCHAR(64);
    DECLARE index_name_value VARCHAR(64);
    DECLARE cur CURSOR FOR
        SELECT DISTINCT TABLE_NAME, INDEX_NAME
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'tenant_id' AND INDEX_NAME <> 'PRIMARY';
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO table_name_value, index_name_value;
        IF done = 1 THEN LEAVE read_loop; END IF;
        SET @sql = CONCAT('ALTER TABLE `', table_name_value, '` DROP INDEX `', index_name_value, '`');
        PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;
    END LOOP;
    CLOSE cur;
END$$
CALL drop_tenant_indexes()$$
DROP PROCEDURE drop_tenant_indexes$$

DROP PROCEDURE IF EXISTS drop_tenant_columns$$
CREATE PROCEDURE drop_tenant_columns()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE table_name_value VARCHAR(64);
    DECLARE cur CURSOR FOR
        SELECT TABLE_NAME FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'tenant_id'
          AND TABLE_NAME NOT IN ('tenant_domains','tenant_files','tenant_provisioning_runs','tenant_subscriptions','tenant_usage_snapshots','subscription_requests','platform_audit_logs');
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO table_name_value;
        IF done = 1 THEN LEAVE read_loop; END IF;
        SET @sql = CONCAT('ALTER TABLE `', table_name_value, '` DROP COLUMN `tenant_id`');
        PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;
    END LOOP;
    CLOSE cur;
END$$
CALL drop_tenant_columns()$$
DROP PROCEDURE drop_tenant_columns$$
DELIMITER ;

-- Tabel control-plane/SaaS tidak dibutuhkan pada instalasi satu ISP.
DROP TABLE IF EXISTS subscription_requests;
DROP TABLE IF EXISTS tenant_usage_snapshots;
DROP TABLE IF EXISTS tenant_files;
DROP TABLE IF EXISTS tenant_go_live_audit_runs;
DROP TABLE IF EXISTS tenant_provisioning_runs;
DROP TABLE IF EXISTS tenant_subscriptions;
DROP TABLE IF EXISTS tenant_domains;
DROP TABLE IF EXISTS platform_audit_logs;
DROP TABLE IF EXISTS platform_cron_runs;
DROP TABLE IF EXISTS platform_migration_steps;
DROP TABLE IF EXISTS auth_login_attempts;
DROP TABLE IF EXISTS subscription_plans;
DROP TABLE IF EXISTS platform_users;
DROP TABLE IF EXISTS tenants;

-- Pulihkan key global dan relasi inti aplikasi single ISP.
ALTER TABLE app_settings ADD UNIQUE KEY uq_app_settings_key (setting_key);
ALTER TABLE customers ADD UNIQUE KEY uq_customers_code (customer_code);
ALTER TABLE internet_packages ADD UNIQUE KEY uq_packages_name (package_name);
ALTER TABLE profit_sharing_settings ADD UNIQUE KEY uq_profit_effective_month (effective_month);
ALTER TABLE roles ADD UNIQUE KEY uq_roles_name (name);
ALTER TABLE role_permissions ADD UNIQUE KEY uq_role_permission (role_id, permission_key);
ALTER TABLE users ADD UNIQUE KEY uq_users_username (username);

ALTER TABLE cron_task_logs ADD CONSTRAINT fk_cron_task_run FOREIGN KEY (cron_run_id) REFERENCES cron_runs(id) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE customer_isolation_logs ADD CONSTRAINT fk_isolation_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE customer_payments ADD CONSTRAINT fk_payments_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE customer_status_history ADD CONSTRAINT fk_status_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE customers ADD CONSTRAINT fk_customers_package FOREIGN KEY (package_id) REFERENCES internet_packages(id) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE internet_packages ADD CONSTRAINT fk_packages_router FOREIGN KEY (router_id) REFERENCES mikrotik_routers(id) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE role_permissions ADD CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE users ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON UPDATE CASCADE ON DELETE RESTRICT;

SET FOREIGN_KEY_CHECKS = 1;
