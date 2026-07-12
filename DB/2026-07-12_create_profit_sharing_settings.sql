CREATE TABLE IF NOT EXISTS profit_sharing_settings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    effective_month DATE NOT NULL,
    party_1_name VARCHAR(150) NOT NULL,
    party_1_percent DECIMAL(5,2) NOT NULL DEFAULT 75.00,
    party_2_name VARCHAR(150) NOT NULL,
    party_2_percent DECIMAL(5,2) NOT NULL DEFAULT 14.00,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_profit_sharing_effective_month (effective_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO profit_sharing_settings
    (effective_month, party_1_name, party_1_percent, party_2_name, party_2_percent, created_at, updated_at)
SELECT '2026-01-01',
       COALESCE((SELECT setting_value FROM app_settings WHERE setting_key = 'profit_party_1_name' LIMIT 1), 'Pihak Pertama'),
       COALESCE((SELECT setting_value FROM app_settings WHERE setting_key = 'profit_party_1_percent' LIMIT 1), '75'),
       COALESCE((SELECT setting_value FROM app_settings WHERE setting_key = 'profit_party_2_name' LIMIT 1), 'Pihak Kedua'),
       COALESCE((SELECT setting_value FROM app_settings WHERE setting_key = 'profit_party_2_percent' LIMIT 1), '14'),
       NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM profit_sharing_settings);
