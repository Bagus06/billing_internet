<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Olt_snmp
{
    const ONU_NAME_OID = '1.3.6.1.4.1.50224.3.12.2.1.2';
    const ONU_SERIAL_OID = '1.3.6.1.4.1.50224.3.12.2.1.15';
    const ONU_RX_OID = '1.3.6.1.4.1.50224.3.12.3.1.4';
    private static $deviceCache = null;
    private $lastError = '';

    public function devices()
    {
        if (self::$deviceCache !== null) return self::$deviceCache;
        $cacheSeconds = max(30, min(600, (int) app_setting('olt_cache_seconds', 60)));
        $cacheDirectory = FCPATH . 'storage' . DIRECTORY_SEPARATOR . 'cache';
        if (!is_dir($cacheDirectory)) @mkdir($cacheDirectory, 0750, true);
        $cacheFile = $cacheDirectory . DIRECTORY_SEPARATOR . 'olt_devices.json';
        $cached = $this->readCache($cacheFile);
        $relay = $this->relayConnection();
        $connection = $this->connection();
        if (!$relay && !$connection) {
            $this->lastError = 'Relay atau koneksi SNMP OLT belum dikonfigurasi.';
            return [];
        }
        $cacheKey = $relay
            ? hash('sha256', 'relay|' . $relay['url'] . '|' . hash('sha256', $relay['token']))
            : $this->cacheKey($connection[0], $connection[1]);
        if ($cached && isset($cached['cache_key']) && hash_equals((string) $cached['cache_key'], $cacheKey)
            && (time() - (int) $cached['created_at']) < $cacheSeconds) {
            return self::$deviceCache = $cached['devices'];
        }

        $lock = @fopen($cacheFile . '.lock', 'c');
        if ($lock && !@flock($lock, LOCK_EX | LOCK_NB)) {
            @fclose($lock);
            return self::$deviceCache = ($cached && isset($cached['cache_key']) && hash_equals((string) $cached['cache_key'], $cacheKey) ? $cached['devices'] : []);
        }
        $devices = $relay ? $this->relayDevices($relay) : [];
        $relayError = $relay && !$devices ? $this->lastError : '';
        if (!$devices && $connection && $this->snmpAvailable()) {
            $devices = $this->directDevices($connection);
            if (!$devices && $relayError !== '') $this->lastError = $relayError . ' Fallback SNMP langsung juga gagal.';
        }
        if ($devices) @file_put_contents($cacheFile, json_encode(['created_at' => time(), 'cache_key' => $cacheKey, 'devices' => $devices]), LOCK_EX);
        if ($lock) { @flock($lock, LOCK_UN); @fclose($lock); }
        if (!$devices && $this->lastError === '') {
            $this->lastError = $relay
                ? 'Relay OLT aktif tetapi tidak mengembalikan perangkat. Periksa URL dan token relay.'
                : 'OLT merespons, tetapi serial ONT tidak ditemukan pada OID yang dikonfigurasi.';
        }
        $validCached = $cached && isset($cached['cache_key']) && hash_equals((string) $cached['cache_key'], $cacheKey);
        return self::$deviceCache = ($devices ?: ($validCached ? $cached['devices'] : []));
    }

    public function lastError()
    {
        return $this->lastError;
    }

    private function directDevices(array $connection)
    {
        list($peer, $community) = $connection;
        snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
        $names = $this->walk($peer, $community, self::ONU_NAME_OID);
        $serials = $this->walk($peer, $community, self::ONU_SERIAL_OID);
        $powers = $this->walk($peer, $community, self::ONU_RX_OID);
        $devices = [];
        foreach ($names ?: [] as $oid => $value) {
            $index = $this->lastOidPart($oid);
            if ($index !== '') $devices[$index] = ['ont_index' => $index, 'ont_name' => $this->stringValue($value), 'serial_number' => '', 'rx' => null];
        }
        foreach ($serials ?: [] as $oid => $value) {
            $index = $this->lastOidPart($oid);
            if ($index === '') continue;
            if (!isset($devices[$index])) $devices[$index] = ['ont_index' => $index, 'ont_name' => 'ONT ' . $index, 'serial_number' => '', 'rx' => null];
            $devices[$index]['serial_number'] = strtoupper($this->stringValue($value));
        }
        foreach ($powers ?: [] as $oid => $value) {
            if (!preg_match('/\.(\d+)\.0\.0$/', $oid, $matches) || !isset($devices[$matches[1]])) continue;
            $raw = $this->integerValue($value);
            $devices[$matches[1]]['rx'] = $raw <= -4000 ? null : round($raw / 100, 2);
        }
        return $this->normalizeDevices($devices);
    }

    private function relayDevices(array $relay)
    {
        $path = '/api/v1/olt/devices';
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp . "\n" . $path, $relay['token']);
        $headers = ['Accept: application/json', 'X-Relay-Timestamp: ' . $timestamp, 'X-Relay-Signature: ' . $signature];
        $body = false;
        if (function_exists('curl_init')) {
            $handle = curl_init($relay['url'] . $path);
            curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => 12, CURLOPT_HTTPHEADER => $headers]);
            $body = curl_exec($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
            curl_close($handle);
            if ($status !== 200) $body = false;
        } else {
            $context = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 12, 'ignore_errors' => true, 'header' => implode("\r\n", $headers)]]);
            $body = @file_get_contents($relay['url'] . $path, false, $context);
        }
        $payload = is_string($body) ? json_decode($body, true) : null;
        if (!is_array($payload) || empty($payload['success']) || !isset($payload['devices']) || !is_array($payload['devices'])) {
            $this->lastError = 'Relay OLT Debian tidak dapat dihubungi melalui TCP atau menolak autentikasi.';
            return [];
        }
        return $this->normalizeDevices($payload['devices']);
    }

    private function normalizeDevices(array $devices)
    {
        $result = [];
        foreach ($devices as $key => $device) {
            if (!is_array($device)) continue;
            $serial = strtoupper(trim((string) ($device['serial_number'] ?? '')));
            if ($serial === '') continue;
            $rx = isset($device['rx']) && is_numeric($device['rx']) ? round((float) $device['rx'], 2) : null;
            $result[] = [
                'ont_index' => (string) ($device['ont_index'] ?? $key),
                'ont_name' => trim((string) ($device['ont_name'] ?? '')) ?: 'ONT ' . (string) ($device['ont_index'] ?? $key),
                'serial_number' => $serial,
                'rx' => $rx,
                'status' => $this->signalStatus($rx),
            ];
        }
        return $result;
    }

    private function readCache($path)
    {
        if (!is_file($path)) return null;
        $data = json_decode((string) @file_get_contents($path), true);
        return is_array($data) && isset($data['created_at'], $data['devices']) && is_array($data['devices']) ? $data : null;
    }

    public function opticalBySerial()
    {
        $result = [];
        foreach ($this->devices() as $device) $result[strtoupper($device['serial_number'])] = $device;
        return $result;
    }

    public function opticalByCustomerName()
    {
        if (!$this->snmpAvailable()) {
            return [];
        }

        $host = trim((string) app_setting('olt_snmp_host', ''));
        if ($host === '') $host = trim((string) ($_ENV['OLT_SNMP_HOST'] ?? ''));
        $port = (int) app_setting('olt_snmp_port', ($_ENV['OLT_SNMP_PORT'] ?? 161));
        $community = trim((string) app_setting('olt_snmp_community', ''));
        if ($community === '') $community = (string) ($_ENV['OLT_SNMP_COMMUNITY'] ?? '');
        if ($host === '' || $community === '' || $port < 1 || $port > 65535) {
            return [];
        }

        $peer = $this->peerName($host, $port);

        snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
        $names = $this->walk($peer, $community, self::ONU_NAME_OID);
        $powers = $this->walk($peer, $community, self::ONU_RX_OID);
        if (!$names || !$powers) {
            return [];
        }

        $nameByIndex = [];
        foreach ($names as $oid => $value) {
            $index = $this->lastOidPart($oid);
            $name = $this->stringValue($value);
            if ($index !== '' && $name !== '') {
                $nameByIndex[$index] = $name;
            }
        }

        $result = [];
        foreach ($powers as $oid => $value) {
            if (!preg_match('/\.(\d+)\.0\.0$/', $oid, $matches)) {
                continue;
            }
            $index = $matches[1];
            if (!isset($nameByIndex[$index])) {
                continue;
            }
            $raw = $this->integerValue($value);
            $dbm = $raw <= -4000 ? null : round($raw / 100, 2);
            $result[$this->normalizeName($nameByIndex[$index])] = [
                'rx' => $dbm,
                'status' => $this->signalStatus($dbm),
                'ont_name' => $nameByIndex[$index],
                'ont_index' => $index,
            ];
        }

        return $result;
    }

    private function connection()
    {
        $host = trim((string) app_setting('olt_snmp_host', ''));
        if ($host === '') $host = trim((string) ($_ENV['OLT_SNMP_HOST'] ?? ''));
        $port = (int) app_setting('olt_snmp_port', ($_ENV['OLT_SNMP_PORT'] ?? 161));
        $community = trim((string) app_setting('olt_snmp_community', ''));
        if ($community === '') $community = (string) ($_ENV['OLT_SNMP_COMMUNITY'] ?? '');
        return ($host !== '' && $community !== '' && $port > 0 && $port <= 65535) ? [$this->peerName($host, $port), $community] : null;
    }

    private function relayConnection()
    {
        $enabled = (string) app_setting('olt_relay_enabled', ($_ENV['OLT_RELAY_ENABLED'] ?? '0'));
        if (!in_array(strtolower(trim($enabled)), ['1', 'true', 'yes', 'on'], true)) return null;
        $url = trim((string) app_setting('olt_relay_url', ''));
        if ($url === '') $url = trim((string) ($_ENV['OLT_RELAY_URL'] ?? ''));
        $token = trim((string) app_setting('olt_relay_token', ''));
        if ($token === '') $token = trim((string) ($_ENV['OLT_RELAY_TOKEN'] ?? ''));
        if ($url === '' || $token === '' || !preg_match('#^https?://#i', $url)) return null;
        return ['url' => rtrim($url, '/'), 'token' => $token];
    }

    public function normalizeName($name)
    {
        $name = strtoupper(trim((string) $name));
        return preg_replace('/[^A-Z0-9]+/', '', $name);
    }

    private function walk($host, $community, $oid)
    {
        $version = trim((string) app_setting('olt_snmp_version', ''));
        if ($version === '') $version = (string) ($_ENV['OLT_SNMP_VERSION'] ?? '1');
        $version = ltrim(strtolower(trim($version ?: '1')), 'v');
        $function = ($version === '2' || $version === '2c') ? 'snmp2_real_walk' : 'snmprealwalk';
        if (!function_exists($function)) {
            $this->lastError = 'Fungsi PHP ' . $function . ' tidak tersedia pada server hosting.';
            return false;
        }
        foreach ($this->peerCandidates($host) as $peer) {
            $result = @$function($peer, $community, $oid, 4000000, 1);
            if (is_array($result) && $result) return $result;
        }
        $this->lastError = 'Server hosting tidak menerima respons SNMP dari OLT. Periksa ekstensi PHP SNMP dan izin UDP keluar.';
        return false;
    }

    private function peerCandidates($peer)
    {
        $candidates = [$peer];
        if (preg_match('/^([^:]+):(\d+)$/', $peer, $matches)) $candidates[] = 'udp:' . $matches[1] . ':' . $matches[2];
        return array_values(array_unique($candidates));
    }

    private function snmpAvailable()
    {
        return function_exists('snmprealwalk') || function_exists('snmp2_real_walk');
    }

    private function cacheKey($peer, $community)
    {
        $version = trim((string) app_setting('olt_snmp_version', ($_ENV['OLT_SNMP_VERSION'] ?? '1')));
        return hash('sha256', strtolower(trim($peer)) . '|' . strtolower($version) . '|' . hash('sha256', (string) $community));
    }

    private function peerName($host, $port)
    {
        if ((int) $port === 161) {
            return $host;
        }

        return $host . ':' . (int) $port;
    }

    private function stringValue($value)
    {
        $value = preg_replace('/^(STRING|Hex-STRING):\s*/i', '', (string) $value);
        return trim($value, " \t\n\r\0\x0B\"");
    }

    private function integerValue($value)
    {
        preg_match('/-?\d+/', (string) $value, $matches);
        return isset($matches[0]) ? (int) $matches[0] : -4000;
    }

    private function lastOidPart($oid)
    {
        $parts = explode('.', (string) $oid);
        return (string) end($parts);
    }

    private function signalStatus($dbm)
    {
        if ($dbm === null) return 'offline';
        $normalMin = (float) app_setting('signal_normal_min', -25);
        $warningMin = (float) app_setting('signal_warning_min', -28);
        if ($dbm >= $normalMin) return 'normal';
        if ($dbm >= $warningMin) return 'warning';
        return 'critical';
    }
}
