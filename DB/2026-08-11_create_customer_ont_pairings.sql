-- Relasi pelanggan ke identitas fisik ONT; tidak bergantung pada nama pelanggan.
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
