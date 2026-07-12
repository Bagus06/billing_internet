-- Konfigurasi aplikasi terpusat.

CREATE TABLE IF NOT EXISTS app_settings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_group VARCHAR(50) NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NULL,
    setting_type VARCHAR(20) NOT NULL DEFAULT 'string',
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_app_settings_key (setting_key),
    KEY idx_app_settings_group (setting_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO app_settings (setting_group, setting_key, setting_value, setting_type, updated_at) VALUES
('branding', 'isp_name', 'ISP BATARA NET', 'string', NOW()),
('branding', 'app_subtitle', 'Mikrotik Network Tools', 'string', NOW()),
('branding', 'logo_path', 'assets/img/logo.jpeg', 'string', NOW()),
('branding', 'footer_text', 'ISP BATARA NET', 'string', NOW()),
('monitoring', 'monitoring_refresh_seconds', '30', 'integer', NOW()),
('monitoring', 'traffic_refresh_seconds', '3', 'integer', NOW()),
('monitoring', 'pppoe_username_suffix', '@BATARA.net', 'string', NOW()),
('monitoring', 'signal_normal_min', '-25', 'decimal', NOW()),
('monitoring', 'signal_warning_min', '-28', 'decimal', NOW()),
('application', 'default_per_page', '10', 'integer', NOW()),
('application', 'timezone', 'Asia/Jakarta', 'string', NOW()),
('application', 'default_language', 'id', 'string', NOW())
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);

INSERT INTO role_permissions (role_id, permission_key, created_at)
SELECT roles.id, 'settings', NOW()
FROM roles
WHERE roles.name = 'Administrator'
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions
      WHERE role_permissions.role_id = roles.id
        AND role_permissions.permission_key = 'settings'
  );
