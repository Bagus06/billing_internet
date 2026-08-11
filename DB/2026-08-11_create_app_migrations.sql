-- Registry migration database untuk instalasi single ISP.
-- Tabel ini hanya menyimpan riwayat file SQL yang telah berhasil diterapkan.
CREATE TABLE IF NOT EXISTS app_migrations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    migration_name VARCHAR(190) NOT NULL,
    checksum_sha256 CHAR(64) NOT NULL,
    execution_ms INT UNSIGNED NOT NULL DEFAULT 0,
    applied_by BIGINT UNSIGNED NULL,
    applied_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_app_migrations_name (migration_name),
    KEY idx_app_migrations_applied_at (applied_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
