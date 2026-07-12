<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Olt_snmp
{
    const ONU_NAME_OID = '1.3.6.1.4.1.50224.3.12.2.1.2';
    const ONU_RX_OID = '1.3.6.1.4.1.50224.3.12.3.1.4';

    public function opticalByCustomerName()
    {
        if (!extension_loaded('snmp')) {
            return [];
        }

        $host = trim(isset($_ENV['OLT_SNMP_HOST']) ? $_ENV['OLT_SNMP_HOST'] : '');
        $port = (int) (isset($_ENV['OLT_SNMP_PORT']) ? $_ENV['OLT_SNMP_PORT'] : 161);
        $community = isset($_ENV['OLT_SNMP_COMMUNITY']) ? $_ENV['OLT_SNMP_COMMUNITY'] : '';
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

    public function normalizeName($name)
    {
        $name = strtoupper(trim((string) $name));
        return preg_replace('/[^A-Z0-9]+/', '', $name);
    }

    private function walk($host, $community, $oid)
    {
        $version = ltrim(strtolower(trim(isset($_ENV['OLT_SNMP_VERSION']) ? $_ENV['OLT_SNMP_VERSION'] : '1')), 'v');
        if ($version === '2' || $version === '2c') {
            return @snmp2_real_walk($host, $community, $oid, 3000000, 1);
        }
        return @snmprealwalk($host, $community, $oid, 3000000, 1);
    }

    private function peerName($host, $port)
    {
        if ((int) $port === 161) {
            return $host;
        }

        return 'udp:' . $host . ':' . (int) $port;
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
