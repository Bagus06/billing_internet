-- Menambahkan pilihan bahasa default aplikasi (id/en).
INSERT INTO app_settings (setting_group, setting_key, setting_value, setting_type, updated_at)
VALUES ('application', 'default_language', 'id', 'string', NOW())
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);
