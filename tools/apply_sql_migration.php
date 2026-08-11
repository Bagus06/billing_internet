<?php
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
$requested = isset($argv[1]) ? basename((string) $argv[1]) : '';
if ($requested === '' || !preg_match('/^\d{4}-\d{2}-\d{2}_[a-z0-9_]+\.sql$/', $requested)) {
    fwrite(STDERR, "Gunakan nama file migration SQL yang valid dari folder DB.\n");
    exit(1);
}

$migration = realpath($root . '/DB/' . $requested);
$dbRoot = realpath($root . '/DB');
if (!$migration || !$dbRoot || strpos($migration, $dbRoot . DIRECTORY_SEPARATOR) !== 0) {
    fwrite(STDERR, "Migration tidak ditemukan pada folder DB.\n");
    exit(1);
}

require $root . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable($root)->safeLoad();
foreach (['DB_HOST', 'DB_USER', 'DB_NAME'] as $key) {
    if (!isset($_ENV[$key]) || $_ENV[$key] === '') {
        fwrite(STDERR, "Environment database belum lengkap: {$key}.\n");
        exit(1);
    }
}
if (!array_key_exists('DB_PASS', $_ENV)) $_ENV['DB_PASS'] = '';

$db = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['DB_NAME']);
if ($db->connect_errno) {
    fwrite(STDERR, "Koneksi database gagal.\n");
    exit(1);
}
$db->set_charset('utf8mb4');
$sql = file_get_contents($migration);
if ($sql === false || trim($sql) === '') {
    fwrite(STDERR, "Migration kosong atau tidak dapat dibaca.\n");
    exit(1);
}

if (!$db->multi_query($sql)) {
    fwrite(STDERR, "Migration gagal: {$db->error}\n");
    exit(1);
}
do {
    if ($result = $db->store_result()) $result->free();
    if (!$db->more_results()) break;
    if (!$db->next_result()) {
        fwrite(STDERR, "Migration gagal: {$db->error}\n");
        exit(1);
    }
} while (true);

$db->close();
echo json_encode(['success' => true, 'migration' => $requested], JSON_UNESCAPED_SLASHES) . PHP_EOL;
