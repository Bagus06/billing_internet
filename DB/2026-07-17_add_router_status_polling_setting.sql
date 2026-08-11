INSERT INTO app_settings (setting_group, setting_key, setting_value, setting_type, updated_at)
VALUES ('monitoring', 'router_status_refresh_seconds', '3', 'integer', NOW())
ON DUPLICATE KEY UPDATE setting_group = VALUES(setting_group), setting_type = VALUES(setting_type), updated_at = NOW();
