-- Histori perubahan status pelanggan untuk laporan pertumbuhan pelanggan aktif.
CREATE TABLE IF NOT EXISTS customer_status_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id INT UNSIGNED NOT NULL,
    old_status VARCHAR(20) NOT NULL,
    new_status VARCHAR(20) NOT NULL,
    changed_by INT UNSIGNED NULL,
    changed_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_customer_status_history_customer (customer_id),
    KEY idx_customer_status_history_date_status (changed_at, new_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
