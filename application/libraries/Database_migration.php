<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Database_migration
{
    private $CI;
    private $table = 'app_migrations';
    private $directory;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->directory = rtrim(FCPATH . 'DB', DIRECTORY_SEPARATOR);
        $this->ensureRegistry();
        $this->ensureHistoricalBaselineRows();
    }

    public function status()
    {
        $files = $this->migrationFiles();
        $applied = [];
        foreach ($this->CI->db->get($this->table)->result_array() as $row) {
            $applied[$row['migration_name']] = $row;
        }

        $items = [];
        foreach ($files as $name => $path) {
            $checksum = hash_file('sha256', $path);
            $state = !isset($applied[$name]) ? 'pending'
                : (hash_equals((string) $applied[$name]['checksum_sha256'], $checksum) ? 'applied' : 'modified');
            $items[] = [
                'name' => $name,
                'checksum' => $checksum,
                'status' => $state,
                'applied_at' => isset($applied[$name]) ? $applied[$name]['applied_at'] : null,
                'execution_ms' => isset($applied[$name]) ? (int) $applied[$name]['execution_ms'] : null,
            ];
        }

        return [
            'items' => $items,
            'total' => count($items),
            'pending' => count(array_filter($items, function ($item) { return $item['status'] === 'pending'; })),
            'modified' => count(array_filter($items, function ($item) { return $item['status'] === 'modified'; })),
            'latest' => $items ? end($items)['name'] : null,
        ];
    }

    public function migrate($userId = null)
    {
        $status = $this->status();
        if ($status['modified'] > 0) {
            throw new RuntimeException('Migration yang sudah diterapkan telah berubah. Kembalikan file ke checksum semula dan buat file migration baru.');
        }

        if (!$this->acquireLock()) {
            throw new RuntimeException('Proses migration lain masih berjalan. Coba kembali beberapa saat lagi.');
        }

        $results = [];
        try {
            foreach ($status['items'] as $item) {
                if ($item['status'] !== 'pending') continue;
                $path = $this->directory . DIRECTORY_SEPARATOR . $item['name'];
                $sql = (string) file_get_contents($path);
                $this->assertSafe($item['name'], $sql);
                $started = microtime(true);
                $this->executeSql($sql, $item['name']);
                $duration = (int) round((microtime(true) - $started) * 1000);
                $this->CI->db->insert($this->table, [
                    'migration_name' => $item['name'],
                    'checksum_sha256' => $item['checksum'],
                    'execution_ms' => $duration,
                    'applied_by' => $userId ? (int) $userId : null,
                    'applied_at' => date('Y-m-d H:i:s'),
                ]);
                $results[] = ['name' => $item['name'], 'execution_ms' => $duration];
            }
        } finally {
            $this->releaseLock();
        }

        return $results;
    }

    private function ensureRegistry()
    {
        if ($this->CI->db->table_exists($this->table)) return;

        $this->CI->db->query("CREATE TABLE app_migrations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            migration_name VARCHAR(190) NOT NULL,
            checksum_sha256 CHAR(64) NOT NULL,
            execution_ms INT UNSIGNED NOT NULL DEFAULT 0,
            applied_by BIGINT UNSIGNED NULL,
            applied_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_app_migrations_name (migration_name),
            KEY idx_app_migrations_applied_at (applied_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Instalasi lama dijadikan baseline hanya untuk migration historis yang
        // memang sudah menjadi bagian schema sebelum runner diperkenalkan.
        // File baru tidak boleh ikut ter-baseline karena harus tetap pending.
        $historicalBaseline = array_flip($this->historicalBaseline());
        foreach ($this->migrationFiles() as $name => $path) {
            if (!isset($historicalBaseline[$name])) continue;
            $this->CI->db->insert($this->table, [
                'migration_name' => $name,
                'checksum_sha256' => hash_file('sha256', $path),
                'execution_ms' => 0,
                'applied_by' => null,
                'applied_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function historicalBaseline()
    {
        return [
            '2026-07-11_bootstrap_administrator.sql',
            '2026-07-11_create_app_settings.sql',
            '2026-07-12_add_default_language_setting.sql',
            '2026-07-12_add_financial_reports_permission.sql',
            '2026-07-12_add_ppp_profile_parameters_to_packages.sql',
            '2026-07-12_add_ppp_profile_to_internet_packages.sql',
            '2026-07-12_add_profit_sharing_settings.sql',
            '2026-07-12_create_customer_status_history.sql',
            '2026-07-12_create_profit_sharing_settings.sql',
            '2026-07-15_add_customer_isolation_system.sql',
            '2026-07-15_add_user_ui_preferences.sql',
            '2026-07-15_import_google_sheet_customers_payments.sql',
            '2026-07-17_add_customer_coordinates.sql',
            '2026-07-17_add_router_status_polling_setting.sql',
            '2026-07-17_migrate_customer_address_to_coordinates.sql',
            '2026-07-21_add_centralized_cron_scheduler.sql',
            '2026-08-11_create_app_migrations.sql',
            '2026-08-11_remove_multi_tenant_restore_single_isp.sql',
        ];
    }

    private function ensureHistoricalBaselineRows()
    {
        $files = $this->migrationFiles();
        foreach ($this->historicalBaseline() as $name) {
            if (!isset($files[$name])) continue;
            if ($this->CI->db->where('migration_name', $name)->count_all_results($this->table) > 0) continue;
            $this->CI->db->insert($this->table, [
                'migration_name' => $name,
                'checksum_sha256' => hash_file('sha256', $files[$name]),
                'execution_ms' => 0,
                'applied_by' => null,
                'applied_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function migrationFiles()
    {
        if (!is_dir($this->directory)) return [];
        $files = [];
        foreach (glob($this->directory . DIRECTORY_SEPARATOR . '*.sql') ?: [] as $path) {
            $name = basename($path);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}_[A-Za-z0-9][A-Za-z0-9._-]*\.sql$/', $name)) continue;
            $files[$name] = $path;
        }
        ksort($files, SORT_STRING);
        return $files;
    }

    private function assertSafe($name, $sql)
    {
        $forbidden = [
            '/\bDROP\s+(?:DATABASE|SCHEMA|TABLE)\b/i' => 'DROP DATABASE/TABLE',
            '/\bTRUNCATE\s+(?:TABLE\s+)?/i' => 'TRUNCATE',
            '/\bDELETE\s+FROM\b/i' => 'DELETE',
            '/\bALTER\s+TABLE\b[\s\S]*?\bDROP\s+(?:COLUMN|KEY|INDEX|FOREIGN)\b/i' => 'ALTER TABLE DROP',
            '/\bRENAME\s+TABLE\b/i' => 'RENAME TABLE',
            '/^\s*DELIMITER\b/im' => 'DELIMITER/stored procedure',
        ];
        foreach ($forbidden as $pattern => $label) {
            if (preg_match($pattern, $sql)) {
                throw new RuntimeException('Migration ' . $name . ' ditolak karena memuat operasi destruktif: ' . $label . '.');
            }
        }
    }

    private function executeSql($sql, $name)
    {
        $connection = $this->CI->db->conn_id;
        if (!($connection instanceof mysqli)) {
            throw new RuntimeException('Driver database tidak mendukung migration runner.');
        }
        if (!$connection->multi_query($sql)) {
            throw new RuntimeException('Migration ' . $name . ' gagal: ' . $connection->error);
        }
        do {
            if ($result = $connection->store_result()) $result->free();
            if ($connection->errno) throw new RuntimeException('Migration ' . $name . ' gagal: ' . $connection->error);
        } while ($connection->more_results() && $connection->next_result());
        if ($connection->errno) throw new RuntimeException('Migration ' . $name . ' gagal: ' . $connection->error);
    }

    private function acquireLock()
    {
        $query = $this->CI->db->query("SELECT GET_LOCK('billing_database_migration', 0) AS acquired");
        return $query && (int) $query->row()->acquired === 1;
    }

    private function releaseLock()
    {
        $this->CI->db->query("SELECT RELEASE_LOCK('billing_database_migration')");
    }
}
