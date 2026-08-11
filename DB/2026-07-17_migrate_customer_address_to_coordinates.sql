-- Memindahkan pasangan koordinat dari customers.address ke latitude/longitude.
-- Nilai awal disimpan agar migrasi dapat dipulihkan bila diperlukan.

CREATE TABLE IF NOT EXISTS customer_address_coordinate_backup_20260717 (
    customer_id INT NOT NULL,
    address TEXT NOT NULL,
    backed_up_at DATETIME NOT NULL,
    PRIMARY KEY (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO customer_address_coordinate_backup_20260717 (customer_id, address, backed_up_at)
SELECT id, address, NOW()
FROM customers
WHERE address IS NOT NULL
  AND TRIM(address) <> ''
  AND REGEXP_LIKE(TRIM(address), '^-?[0-9]+([.][0-9]+)?[[:space:]]*,[[:space:]]*-?[0-9]+([.][0-9]+)?$')
ON DUPLICATE KEY UPDATE address = VALUES(address);

UPDATE customers c
SET
    c.latitude = CAST(TRIM(SUBSTRING_INDEX(c.address, ',', 1)) AS DECIMAL(10,7)),
    c.longitude = CAST(TRIM(SUBSTRING_INDEX(c.address, ',', -1)) AS DECIMAL(10,7)),
    c.updated_at = NOW()
WHERE c.address IS NOT NULL
  AND TRIM(c.address) <> ''
  AND REGEXP_LIKE(TRIM(c.address), '^-?[0-9]+([.][0-9]+)?[[:space:]]*,[[:space:]]*-?[0-9]+([.][0-9]+)?$');

UPDATE customers c
JOIN customer_address_coordinate_backup_20260717 backup ON backup.customer_id = c.id
SET c.address = ''
WHERE c.latitude IS NOT NULL
  AND c.longitude IS NOT NULL
  AND c.latitude BETWEEN -90 AND 90
  AND c.longitude BETWEEN -180 AND 180
  AND TRIM(c.address) = TRIM(backup.address);
