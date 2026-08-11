-- Menambahkan konfigurasi relay OLT berbasis TCP tanpa menyimpan token rahasia.
INSERT INTO app_settings (setting_group, setting_key, setting_value, setting_type, updated_at) VALUES
('network', 'olt_relay_enabled', '0', 'boolean', NOW()),
('network', 'olt_relay_url', 'http://103.85.52.33:31877', 'string', NOW()),
('network', 'olt_relay_token', '', 'string', NOW())
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);
