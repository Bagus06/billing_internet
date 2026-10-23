-- Default konfigurasi Telegram. Pertahankan konfigurasi yang sudah tersimpan.
INSERT INTO app_settings (setting_group, setting_key, setting_value, setting_type, updated_at) VALUES
('notifications', 'telegram_enabled', '0', 'boolean', NOW()),
('notifications', 'telegram_bot_token', '', 'string', NOW()),
('notifications', 'telegram_chat_id', '', 'string', NOW())
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);
