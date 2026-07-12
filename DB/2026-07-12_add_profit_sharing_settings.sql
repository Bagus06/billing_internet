-- Konfigurasi pembagian laba bersih bulanan.
INSERT INTO app_settings (setting_group, setting_key, setting_value, setting_type, updated_at) VALUES
('finance', 'profit_party_1_name', 'Pihak Pertama', 'string', NOW()),
('finance', 'profit_party_1_percent', '75', 'decimal', NOW()),
('finance', 'profit_party_2_name', 'Pihak Kedua', 'string', NOW()),
('finance', 'profit_party_2_percent', '14', 'decimal', NOW())
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);
