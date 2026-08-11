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
        if (!$this->snmpAvailable()) {
            $this->lastError = 'Ekstensi PHP SNMP belum aktif pada server hosting.';
            return [];
        }
        $cacheSeconds = max(30, min(600, (int) app_setting('olt_cache_seconds', 60)));
        $cacheDirectory = FCPATH . 'storage' . DIRECTORY_SEPARATOR . 'cache';
        if (!is_dir($cacheDirectory)) @mkdir($cacheDirectory, 0750, true);
        $cacheFile = $cacheDirectory . DIRECTORY_SEPARATOR . 'olt_devices.json';
        $cached = $this->readCache($cacheFile);
        $connection = $this->connection();
        if (!$connection) {
            $this->lastError = 'Host atau community SNMP OLT belum dikonfigurasi.';
            return [];
        }
        $cacheKey = $this->cacheKey($connection[0], $connection[1]);
        if ($cached && isset($cached['cache_key']) && hash_equals((string) $cached['cache_key'], $cacheKey)
            && (time() - (int) $cached['created_at']) < $cacheSeconds) {
            return self::$deviceCache = $cached['devices'];
        }

        $lock = @fopen($cacheFile . '.lock', 'c');
        if ($lock && !@flock($lock, LOCK_EX | LOCK_NB)) {
            @fclose($lock);
            return self::$deviceCache = ($cached && isset($cached['cache_key']) && hash_equals((string) $cached['cache_key'], $cacheKey) ? $cached['devices'] : []);
        }
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
        foreach ($devices as &$device) $device['status'] = $this->signalStatus($device['rx']);
        unset($device);
        $devices = array_values(array_filter($devices, function ($device) { return $device['serial_number'] !== ''; }));
        if ($devices) @file_put_contents($cacheFile, json_encode(['created_at' => time(), 'cache_key' => $cacheKey, 'devices' => $devices]), LOCK_EX);
        if ($lock) { @flock($lock, LOCK_UN); @fclose($lock); }
        if (!$devices && $this->lastError === '') $this->lastError = 'OLT merespons, tetapi serial ONT tidak ditemukan pada OID yang dikonfigurasi.';
        $validCached = $cached && isset($cached['cache_key']) && hash_equals((string) $cached['cache_key'], $cacheKey);
        return self::$deviceCache = ($devices ?: ($validCached ? $cached['devices'] : []));
    }

    public function lastError()
    {
        return $this->lastError;
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
