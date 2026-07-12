-- Menambahkan hak akses modul laporan keuangan untuk Administrator.
INSERT INTO role_permissions (role_id, permission_key, created_at)
SELECT roles.id, 'financial_reports', NOW()
FROM roles
WHERE roles.name = 'Administrator'
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions
      WHERE role_permissions.role_id = roles.id
        AND role_permissions.permission_key = 'financial_reports'
  );
