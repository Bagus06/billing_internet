-- Preferensi UI per user agar theme dan bahasa konsisten di semua perangkat.
-- Nilai NULL berarti mengikuti default aplikasi dari app_settings.

START TRANSACTION;

SET @has_preferred_theme = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'preferred_theme'
);
SET @sql = IF(@has_preferred_theme = 0,
    'ALTER TABLE users ADD COLUMN preferred_theme VARCHAR(10) NULL AFTER email',
    'SELECT 1'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @has_preferred_language = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'preferred_language'
);
SET @sql = IF(@has_preferred_language = 0,
    'ALTER TABLE users ADD COLUMN preferred_language VARCHAR(5) NULL AFTER preferred_theme',
    'SELECT 1'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

INSERT INTO app_settings (setting_group, setting_key, setting_value, setting_type, updated_at)
VALUES ('application', 'default_theme', 'dark', 'string', NOW())
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);

COMMIT;
