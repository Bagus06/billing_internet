-- Bootstrap role Administrator dan akun admin.
-- Idempotent: aman dijalankan kembali pada database yang sudah memiliki akun tersebut.
-- Kredensial awal: username admin. Password disimpan sebagai bcrypt hash.

START TRANSACTION;

INSERT INTO roles (name, description, is_active, created_at, updated_at)
SELECT 'Administrator', 'Akses penuh ke seluruh fitur aplikasi', 1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE name = 'Administrator'
);

UPDATE roles
SET description = 'Akses penuh ke seluruh fitur aplikasi',
    is_active = 1,
    updated_at = NOW()
WHERE name = 'Administrator';

SET @administrator_role_id = (
    SELECT id FROM roles WHERE name = 'Administrator' ORDER BY id ASC LIMIT 1
);

DELETE FROM role_permissions
WHERE role_id = @administrator_role_id;

INSERT INTO role_permissions (role_id, permission_key, created_at) VALUES
(@administrator_role_id, 'home', NOW()),
(@administrator_role_id, 'calculator', NOW()),
(@administrator_role_id, 'monitoring', NOW()),
(@administrator_role_id, 'routers', NOW()),
(@administrator_role_id, 'customers', NOW()),
(@administrator_role_id, 'packages', NOW()),
(@administrator_role_id, 'payments', NOW()),
(@administrator_role_id, 'users', NOW()),
(@administrator_role_id, 'roles', NOW()),
(@administrator_role_id, 'profile', NOW());

INSERT INTO users (
    role_id, name, username, email, password, is_active, created_at, updated_at
)
SELECT
    @administrator_role_id,
    'Administrator',
    'admin',
    '',
    '$2y$10$drC7eiuqUMQ9vij83UcVVee/Jvyp660KqcyuD6Atr5kJVbm5N4sZe',
    1,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE username = 'admin'
);

UPDATE users
SET role_id = @administrator_role_id,
    password = '$2y$10$drC7eiuqUMQ9vij83UcVVee/Jvyp660KqcyuD6Atr5kJVbm5N4sZe',
    is_active = 1,
    updated_at = NOW()
WHERE username = 'admin';

COMMIT;
