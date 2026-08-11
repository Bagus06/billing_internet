-- Menambahkan koordinat lokasi pelanggan untuk picker dan tampilan peta.
SET @has_latitude = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'latitude');
SET @sql = IF(@has_latitude = 0, 'ALTER TABLE customers ADD COLUMN latitude DECIMAL(10,7) NULL AFTER address', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @has_longitude = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'longitude');
SET @sql = IF(@has_longitude = 0, 'ALTER TABLE customers ADD COLUMN longitude DECIMAL(10,7) NULL AFTER latitude', 'SELECT 1');
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;
