<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Network_audit extends CI_Controller
{
    public function olt_devices()
    {
        if (!$this->input->is_cli_request()) { show_404(); return; }
        $this->load->library('Olt_snmp');
        $devices = $this->olt_snmp->devices();
        echo json_encode(['success' => (bool) $devices, 'device_count' => count($devices),
            'error' => $devices ? null : $this->olt_snmp->lastError()], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function remote_ont($nik = '')
    {
        if (!$this->input->is_cli_request()) { show_404(); return; }
        $context = $this->context($nik);
        if (isset($context['error'])) { echo json_encode($context, JSON_PRETTY_PRINT); return; }
        $username = preg_replace('/\D+/', '', (string) $context['customer']['nik']) . app_setting('pppoe_username_suffix', '@BATARA.net');
        try {
            $result = $this->mikrotik_query->prepareOntRemote($context['router'], $username);
            echo json_encode(['success' => true, 'remote' => $result], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }

    public function configure_http_redirect($nik = '')
    {
        if (!$this->input->is_cli_request()) { show_404(); return; }
        $context = $this->context($nik);
        if (isset($context['error'])) { echo json_encode($context, JSON_PRETTY_PRINT); return; }
        $subnet = '10.7.0.0/29';
        $targetIp = '10.5.5.3';
        $targetPort = '80';
        $targetHost = 'isolir.batara.local';

        try {
            $result = $this->mikrotik_query->run($context['router'], function ($api) use ($subnet, $targetIp, $targetPort, $targetHost) {
            $fetch = $api->comm('/tool/fetch', [
                '=url' => 'http://' . $targetIp . '/healthz',
                '=keep-result' => 'no',
                '=duration' => '8s',
            ]);
            if (!$this->done($fetch)) {
                throw new RuntimeException('Server isolir Debian tidak dapat dijangkau dari MikroTik: ' . json_encode($fetch, JSON_UNESCAPED_SLASHES));
            }

            $profiles = $this->clean($api->comm('/ppp/profile/print'));
            $isolationProfile = $this->findBy($profiles, 'name', 'ISOLIR');
            if (!$isolationProfile || empty($isolationProfile['.id'])) {
                throw new RuntimeException('PPP profile ISOLIR tidak ditemukan.');
            }
            if (!$this->done($api->comm('/ppp/profile/set', ['=.id' => $isolationProfile['.id'], '=dns-server' => '10.7.0.1']))) {
                throw new RuntimeException('Gagal menetapkan DNS pada PPP profile ISOLIR.');
            }

            $filters = $this->clean($api->comm('/ip/firewall/filter/print'));
            $drop = $this->findBy($filters, 'comment', 'Billing Internet - blokir pelanggan isolir');
            if (!$drop || empty($drop['.id'])) {
                throw new RuntimeException('Rule blokir isolir tidak ditemukan. Jalankan configure_isolation terlebih dahulu.');
            }

            $rules = [
                ['comment' => 'Billing Internet - DNS UDP pelanggan isolir', 'params' => ['=chain' => 'input', '=src-address' => $subnet, '=protocol' => 'udp', '=dst-port' => '53', '=action' => 'accept']],
                ['comment' => 'Billing Internet - DNS TCP pelanggan isolir', 'params' => ['=chain' => 'input', '=src-address' => $subnet, '=protocol' => 'tcp', '=dst-port' => '53', '=action' => 'accept']],
                ['comment' => 'Billing Internet - akses halaman isolir', 'params' => ['=chain' => 'forward', '=src-address' => $subnet, '=dst-address' => $targetIp, '=protocol' => 'tcp', '=dst-port' => '80', '=action' => 'accept', '=place-before' => $drop['.id']]],
            ];

            foreach ($rules as $rule) {
                $existingRule = $this->findBy($filters, 'comment', $rule['comment']);
                $params = $rule['params'];
                if ($existingRule && !empty($existingRule['.id'])) {
                    unset($params['=place-before']);
                    $params['=.id'] = $existingRule['.id'];
                    $params['=comment'] = $rule['comment'];
                    if (!$this->done($api->comm('/ip/firewall/filter/set', $params))) {
                        throw new RuntimeException('Gagal memperbarui firewall rule: ' . $rule['comment']);
                    }
                    $filters = $this->clean($api->comm('/ip/firewall/filter/print'));
                } else {
                    $params['=comment'] = $rule['comment'];
                    if (!$this->done($api->comm('/ip/firewall/filter/add', $params))) {
                        throw new RuntimeException('Gagal membuat firewall rule: ' . $rule['comment']);
                    }
                    $filters = $this->clean($api->comm('/ip/firewall/filter/print'));
                }
            }

            $dnsEntries = $this->clean($api->comm('/ip/dns/static/print'));
            $dns = $this->findBy($dnsEntries, 'name', $targetHost);
            if ($dns && !empty($dns['.id'])) {
                if (!$this->done($api->comm('/ip/dns/static/set', ['=.id' => $dns['.id'], '=address' => $targetIp, '=disabled' => 'no']))) {
                    throw new RuntimeException('Gagal memperbarui DNS static halaman isolir.');
                }
            } elseif (!$this->done($api->comm('/ip/dns/static/add', ['=name' => $targetHost, '=address' => $targetIp, '=comment' => 'Billing Internet - DNS halaman isolir']))) {
                throw new RuntimeException('Gagal membuat DNS static halaman isolir.');
            }

            $nat = $this->clean($api->comm('/ip/firewall/nat/print'));
            $comment = 'Billing Internet - redirect HTTP pelanggan isolir';
            $natRule = $this->findBy($nat, 'comment', $comment);
            $natParams = [
                '=chain' => 'dstnat', '=src-address' => $subnet, '=dst-address' => '!' . $targetIp,
                '=protocol' => 'tcp', '=dst-port' => '80', '=action' => 'dst-nat',
                '=to-addresses' => $targetIp, '=to-ports' => $targetPort, '=comment' => $comment,
            ];
            if ($natRule && !empty($natRule['.id'])) {
                $natParams['=.id'] = $natRule['.id'];
                if (!$this->done($api->comm('/ip/firewall/nat/set', $natParams))) {
                    throw new RuntimeException('Gagal memperbarui NAT redirect HTTP.');
                }
            } elseif (!$this->done($api->comm('/ip/firewall/nat/add', $natParams))) {
                throw new RuntimeException('Gagal membuat NAT redirect HTTP.');
            }

            return [
                'success' => true,
                'redirector_reachable_from_mikrotik' => true,
                'redirector' => $targetIp . ':' . $targetPort,
                'notice_url' => 'http://' . $targetHost . '/',
                'dns_static' => $targetHost . ' -> ' . $targetIp,
            ];
            });
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }

    public function rollback_redirect($nik = '')
    {
        if (!$this->input->is_cli_request()) { show_404(); return; }
        $context = $this->context($nik);
        if (isset($context['error'])) { echo json_encode($context, JSON_PRETTY_PRINT); return; }
        try {
            $result = $this->mikrotik_query->run($context['router'], function ($api) {
                $removed = [];
                $targets = [
                    ['/ip/firewall/filter/print', '/ip/firewall/filter/remove', 'Billing Internet - izinkan halaman isolir'],
                    ['/ip/firewall/filter/print', '/ip/firewall/filter/remove', 'Billing Internet - izinkan proxy isolir'],
                    ['/ip/firewall/filter/print', '/ip/firewall/filter/remove', 'Billing Internet - lindungi proxy isolir'],
                    ['/ip/firewall/nat/print', '/ip/firewall/nat/remove', 'Billing Internet - tangkap HTTP pelanggan isolir'],
                    ['/ip/proxy/access/print', '/ip/proxy/access/remove', 'Billing Internet - redirect pelanggan isolir'],
                ];
                foreach ($targets as $target) {
                    $row = $this->findBy($this->clean($api->comm($target[0])), 'comment', $target[2]);
                    if ($row && !empty($row['.id']) && $this->done($api->comm($target[1], ['=.id' => $row['.id']]))) $removed[] = $target[2];
                }
                $api->comm('/ip/proxy/set', ['=enabled' => 'no']);
                $proxyRows = $this->clean($api->comm('/ip/proxy/print'));
                return ['success' => !empty($proxyRows) && ($proxyRows[0]['enabled'] ?? 'true') === 'false', 'proxy_enabled' => $proxyRows[0]['enabled'] ?? '', 'removed' => $removed];
            });
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT); }
    }

    public function configure_redirect($nik = '')
    {
        if (!$this->input->is_cli_request()) { show_404(); return; }
        $context = $this->context($nik);
        if (isset($context['error'])) { echo json_encode($context, JSON_PRETTY_PRINT); return; }
        $subnet = '10.7.0.0/29';
        $proxyPort = '8080';
        $pagePath = FCPATH . 'deployment/mikrotik-webproxy/error.html';
        if (!is_file($pagePath)) {
            echo json_encode(['success' => false, 'error' => 'Template Web Proxy lokal tidak ditemukan.'], JSON_PRETTY_PRINT);
            return;
        }
        $page = (string) file_get_contents($pagePath);
        if ($page === '' || strlen($page) > 4096) {
            echo json_encode(['success' => false, 'error' => 'Template Web Proxy harus berukuran 1-4096 byte.'], JSON_PRETTY_PRINT);
            return;
        }
        try {
            $result = $this->mikrotik_query->run($context['router'], function ($api) use ($page, $subnet, $proxyPort) {
                $filters = $this->clean($api->comm('/ip/firewall/filter/print'));
                $dropIsolation = $this->findBy($filters, 'comment', 'Billing Internet - blokir pelanggan isolir');
                if (!$dropIsolation || empty($dropIsolation['.id'])) throw new RuntimeException('Firewall drop isolir tidak ditemukan.');
                if (($dropIsolation['src-address'] ?? '') !== $subnet || ($dropIsolation['chain'] ?? '') !== 'forward' || ($dropIsolation['action'] ?? '') !== 'drop') {
                    throw new RuntimeException('Rule blokir isolir tidak sesuai subnet aman. Konfigurasi dibatalkan.');
                }

                $isolationProfile = $this->findBy($this->clean($api->comm('/ppp/profile/print')), 'name', 'ISOLIR');
                if (!$isolationProfile || empty($isolationProfile['.id'])) throw new RuntimeException('PPP profile ISOLIR tidak ditemukan.');
                if (!$this->done($api->comm('/ppp/profile/set', ['=.id' => $isolationProfile['.id'], '=rate-limit' => '256k/256k']))) {
                    throw new RuntimeException('Gagal menyesuaikan bandwidth halaman isolir.');
                }

                // Stage the self-contained error page before any traffic rule is changed.
                foreach (['webproxy/error.html', 'flash/webproxy/error.html'] as $fileName) {
                    $files = $this->clean($api->comm('/file/print'));
                    $errorFile = $this->findBy($files, 'name', $fileName);
                    if ($errorFile && !empty($errorFile['.id'])) {
                        $api->comm('/file/set', ['=.id' => $errorFile['.id'], '=contents' => $page]);
                    } else {
                        $api->comm('/file/add', ['=name' => $fileName, '=contents' => $page]);
                    }
                }
                $errorFile = $this->findBy($this->clean($api->comm('/file/print')), 'name', 'webproxy/error.html');
                $persistentFile = $this->findBy($this->clean($api->comm('/file/print')), 'name', 'flash/webproxy/error.html');
                if (!$errorFile || (int) ($errorFile['size'] ?? 0) < 1000 || !$persistentFile || (int) ($persistentFile['size'] ?? 0) < 1000) {
                    throw new RuntimeException('Verifikasi file halaman isolir lokal/persisten gagal.');
                }

                $restoreScript = ':local s [/file find where name="flash/webproxy/error.html"]; :if ([:len $s]>0) do={:local t [/file find where name="webproxy/error.html"]; :if ([:len $t]=0) do={/file add name="webproxy/error.html" contents=[/file get $s contents]} else={/file set $t contents=[/file get $s contents]}}';
                $schedulers = $this->clean($api->comm('/system/scheduler/print'));
                $scheduler = $this->findBy($schedulers, 'name', 'billing-restore-isolation-page');
                $schedulerParams = ['=name' => 'billing-restore-isolation-page', '=start-time' => 'startup', '=interval' => '0s', '=on-event' => $restoreScript, '=policy' => 'read,write,test', '=comment' => 'Billing Internet - restore halaman isolir lokal'];
                if ($scheduler && !empty($scheduler['.id'])) {
                    $schedulerParams['=.id'] = $scheduler['.id'];
                    if (!$this->done($api->comm('/system/scheduler/set', $schedulerParams))) throw new RuntimeException('Gagal memperbarui pemulihan halaman isolir saat startup.');
                } elseif (!$this->done($api->comm('/system/scheduler/add', $schedulerParams))) {
                    throw new RuntimeException('Gagal membuat pemulihan halaman isolir saat startup.');
                }

                if (!$this->done($api->comm('/ip/proxy/set', ['=enabled' => 'yes', '=port' => $proxyPort, '=anonymous' => 'no', '=cache-on-disk' => 'no', '=max-cache-size' => 'none']))) {
                    throw new RuntimeException('Gagal mengaktifkan Web Proxy MikroTik.');
                }

                // Permit the proxy only for the isolation pool, before the first input drop rule.
                $filters = $this->clean($api->comm('/ip/firewall/filter/print'));
                $acceptProxy = $this->findBy($filters, 'comment', 'Billing Internet - izinkan proxy isolir');
                $allowParams = ['=chain' => 'input', '=src-address' => $subnet, '=protocol' => 'tcp', '=dst-port' => $proxyPort, '=action' => 'accept', '=comment' => 'Billing Internet - izinkan proxy isolir'];
                if ($acceptProxy && !empty($acceptProxy['.id'])) {
                    $allowParams['=.id'] = $acceptProxy['.id'];
                    if (!$this->done($api->comm('/ip/firewall/filter/set', $allowParams))) throw new RuntimeException('Gagal memperbarui izin Web Proxy isolir.');
                } else {
                    foreach ($filters as $filter) {
                        if (($filter['chain'] ?? '') === 'input' && ($filter['action'] ?? '') === 'drop' && !empty($filter['.id'])) { $allowParams['=place-before'] = $filter['.id']; break; }
                    }
                    if (!$this->done($api->comm('/ip/firewall/filter/add', $allowParams))) throw new RuntimeException('Gagal membuat izin input Web Proxy.');
                }
                $filters = $this->clean($api->comm('/ip/firewall/filter/print'));
                $denyProxy = $this->findBy($filters, 'comment', 'Billing Internet - lindungi proxy isolir');
                if ($denyProxy && !empty($denyProxy['.id'])) $api->comm('/ip/firewall/filter/remove', ['=.id' => $denyProxy['.id']]);
                $filters = $this->clean($api->comm('/ip/firewall/filter/print'));
                $protectParams = ['=chain' => 'input', '=protocol' => 'tcp', '=dst-port' => $proxyPort, '=action' => 'drop', '=comment' => 'Billing Internet - lindungi proxy isolir'];
                foreach ($filters as $filter) {
                    if (($filter['chain'] ?? '') === 'input' && ($filter['action'] ?? '') === 'drop' && !empty($filter['.id'])) { $protectParams['=place-before'] = $filter['.id']; break; }
                }
                if (!$this->done($api->comm('/ip/firewall/filter/add', $protectParams))) throw new RuntimeException('Gagal membuat proteksi Web Proxy.');

                $noticeHost = 'isolir.batara.local';
                $noticeUrl = 'http://' . $noticeHost . '/';
                $dnsRows = $this->clean($api->comm('/ip/dns/static/print'));
                $noticeDns = $this->findBy($dnsRows, 'name', $noticeHost);
                $dnsParams = ['=name' => $noticeHost, '=address' => '10.7.0.1', '=comment' => 'Billing Internet - DNS captive isolir', '=disabled' => 'no'];
                if ($noticeDns && !empty($noticeDns['.id'])) {
                    $dnsParams['=.id'] = $noticeDns['.id'];
                    if (!$this->done($api->comm('/ip/dns/static/set', $dnsParams))) throw new RuntimeException('Gagal memperbarui DNS captive isolir.');
                } elseif (!$this->done($api->comm('/ip/dns/static/add', $dnsParams))) {
                    throw new RuntimeException('Gagal membuat DNS captive isolir.');
                }

                $access = $this->clean($api->comm('/ip/proxy/access/print'));
                foreach (['Billing Internet - halaman lokal isolir', 'Billing Internet - redirect pelanggan isolir'] as $comment) {
                    $oldRule = $this->findBy($access, 'comment', $comment);
                    if ($oldRule && !empty($oldRule['.id'])) $api->comm('/ip/proxy/access/remove', ['=.id' => $oldRule['.id']]);
                }
                if (!$this->done($api->comm('/ip/proxy/access/add', [
                    '=src-address' => $subnet, '=dst-host' => $noticeHost, '=action' => 'deny',
                    '=comment' => 'Billing Internet - halaman lokal isolir',
                ]))) throw new RuntimeException('Gagal membuat pengecualian halaman lokal isolir.');
                $redirectResponse = $api->comm('/ip/proxy/access/add', [
                    '=src-address' => $subnet, '=action' => 'deny', '=redirect-to' => $noticeUrl,
                    '=comment' => 'Billing Internet - redirect pelanggan isolir',
                ]);
                if (!$this->done($redirectResponse)) {
                    $fallback = $api->comm('/ip/proxy/access/add', [
                        '=src-address' => $subnet, '=action' => 'deny',
                        '=comment' => 'Billing Internet - redirect pelanggan isolir',
                    ]);
                    if (!$this->done($fallback)) throw new RuntimeException('Gagal memulihkan blokir HTTP isolir setelah redirect ditolak RouterOS.');
                    throw new RuntimeException('RouterOS ini belum mendukung parameter redirect-to; blokir HTTP aman telah dipulihkan.');
                }

                // Build the new NAT disabled, verify its strict source scope, then switch from Debian.
                $nat = $this->clean($api->comm('/ip/firewall/nat/print'));
                $redirectNat = $this->findBy($nat, 'comment', 'Billing Internet - tangkap HTTP pelanggan isolir');
                $natParams = ['=chain' => 'dstnat', '=src-address' => $subnet, '=protocol' => 'tcp', '=dst-port' => '80', '=action' => 'redirect', '=to-ports' => $proxyPort, '=comment' => 'Billing Internet - tangkap HTTP pelanggan isolir', '=disabled' => 'yes'];
                if ($redirectNat && !empty($redirectNat['.id'])) {
                    $natParams['=.id'] = $redirectNat['.id'];
                    if (!$this->done($api->comm('/ip/firewall/nat/set', $natParams))) throw new RuntimeException('Gagal memperbarui NAT Web Proxy isolir.');
                } elseif (!$this->done($api->comm('/ip/firewall/nat/add', $natParams))) {
                    throw new RuntimeException('Gagal membuat NAT Web Proxy isolir.');
                }
                $nat = $this->clean($api->comm('/ip/firewall/nat/print'));
                $redirectNat = $this->findBy($nat, 'comment', 'Billing Internet - tangkap HTTP pelanggan isolir');
                if (!$redirectNat || ($redirectNat['src-address'] ?? '') !== $subnet || ($redirectNat['dst-port'] ?? '') !== '80' || ($redirectNat['to-ports'] ?? '') !== $proxyPort) {
                    throw new RuntimeException('Verifikasi cakupan NAT Web Proxy gagal. NAT lama tetap dipertahankan.');
                }

                $legacyNat = $this->findBy($nat, 'comment', 'Billing Internet - redirect HTTP pelanggan isolir');
                if ($legacyNat && !empty($legacyNat['.id'])) $api->comm('/ip/firewall/nat/set', ['=.id' => $legacyNat['.id'], '=disabled' => 'yes']);
                if (!$this->done($api->comm('/ip/firewall/nat/set', ['=.id' => $redirectNat['.id'], '=disabled' => 'no']))) {
                    if ($legacyNat && !empty($legacyNat['.id'])) $api->comm('/ip/firewall/nat/set', ['=.id' => $legacyNat['.id'], '=disabled' => 'no']);
                    throw new RuntimeException('Aktivasi NAT Web Proxy gagal; NAT Debian telah dipulihkan.');
                }

                // Debian isolation exceptions are obsolete; WireGuard and OLT rules are intentionally untouched.
                $filters = $this->clean($api->comm('/ip/firewall/filter/print'));
                foreach (['Billing Internet - akses halaman isolir', 'Billing Internet - izinkan halaman isolir'] as $comment) {
                    $old = $this->findBy($filters, 'comment', $comment);
                    if ($old && !empty($old['.id'])) $api->comm('/ip/firewall/filter/remove', ['=.id' => $old['.id']]);
                }
                $dnsRows = $this->clean($api->comm('/ip/dns/static/print'));
                $oldDns = $this->findBy($dnsRows, 'comment', 'Billing Internet - DNS halaman isolir');
                if ($oldDns && !empty($oldDns['.id']) && ($oldDns['name'] ?? '') !== $noticeHost) $api->comm('/ip/dns/static/remove', ['=.id' => $oldDns['.id']]);

                return $this->localProxyState($api, $subnet, $proxyPort);
            });
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT); }
    }

    public function test_server_path($nik = '')
    {
        if (!$this->input->is_cli_request()) { show_404(); return; }
        $context = $this->context($nik);
        if (isset($context['error'])) { echo json_encode($context, JSON_PRETTY_PRINT); return; }
        try {
            $result = $this->mikrotik_query->run($context['router'], function ($api) {
                $tests = [];
                foreach (['', '10.9.9.10', '103.85.52.33'] as $source) {
                    $params = ['=address' => '10.12.11.6', '=count' => '3', '=interval' => '300ms'];
                    if ($source !== '') $params['=src-address'] = $source;
                    $rows = $this->clean($api->comm('/ping', $params));
                    $tests[$source === '' ? 'automatic' : $source] = $rows;
                }
                return $tests;
            });
            echo json_encode(['target' => '10.12.11.6', 'ping' => $result], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) { echo json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT); }
    }

    public function audit_redirect($nik = '', $targetHost = '10.12.11.6')
    {
        if (!$this->input->is_cli_request()) { show_404(); return; }
        $targetHost = filter_var($targetHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $targetHost : '10.12.11.6';
        $context = $this->context($nik);
        if (isset($context['error'])) { echo json_encode($context, JSON_PRETTY_PRINT); return; }
        try {
            $result = $this->mikrotik_query->run($context['router'], function ($api) use ($targetHost) {
                $proxy = $this->clean($api->comm('/ip/proxy/print'));
                $access = $this->clean($api->comm('/ip/proxy/access/print'));
                $filters = $this->clean($api->comm('/ip/firewall/filter/print'));
                $nat = $this->clean($api->comm('/ip/firewall/nat/print'));
                $fetch = $api->comm('/tool/fetch', ['=url' => 'http://' . $targetHost . '/', '=keep-result' => 'no', '=duration' => '8s']);
                $ping = $this->clean($api->comm('/ping', ['=address' => $targetHost, '=count' => '3', '=interval' => '300ms']));
                return ['success' => $this->done($fetch), 'target' => 'http://' . $targetHost . '/', 'proxy' => $proxy, 'proxy_access_count' => count($access), 'existing_managed_filter_rules' => $this->managed($filters, 'Billing Internet -'), 'existing_managed_nat_rules' => $this->managed($nat, 'Billing Internet -'), 'ping' => $ping, 'fetch_response' => $this->clean($fetch)];
            });
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT); }
    }

    public function verify_isolation($nik = '')
    {
        if (!$this->input->is_cli_request()) { show_404(); return; }
        $context = $this->context($nik);
        if (isset($context['error'])) { echo json_encode($context, JSON_PRETTY_PRINT); return; }
        try {
            $result = $this->mikrotik_query->run($context['router'], function ($api) use ($context) {
                $pools = $this->clean($api->comm('/ip/pool/print'));
                $profiles = $this->clean($api->comm('/ppp/profile/print'));
                $filters = $this->clean($api->comm('/ip/firewall/filter/print'));
                $nat = $this->clean($api->comm('/ip/firewall/nat/print'));
                $addresses = $this->clean($api->comm('/ip/address/print'));
                $routes = $this->clean($api->comm('/ip/route/print'));
                $dns = $this->clean($api->comm('/ip/dns/print'));
                $secrets = $this->clean($api->comm('/ppp/secret/print'));
                $pool = $this->findBy($pools, 'name', 'POOL-ISOLIR');
                $profile = $this->findBy($profiles, 'name', 'ISOLIR');
                $firewall = $this->findBy($filters, 'comment', 'Billing Internet - blokir pelanggan isolir');
                $expectedSecret = preg_replace('/\D+/', '', (string) $context['customer']['nik']) . app_setting('pppoe_username_suffix', '@BATARA.net');
                $secret = $this->findBy($secrets, 'name', $expectedSecret);
                $conflicts = [];
                foreach (array_merge($addresses, $routes) as $row) {
                    if (($row['dynamic'] ?? 'false') === 'true' || ($row['disabled'] ?? 'false') === 'true') continue;
                    if (strpos(json_encode($row), '10.7.0.') !== false) $conflicts[] = $row;
                }
                $redirectRules = [];
                foreach ($nat as $row) if (strpos(strtolower(json_encode($row)), 'isolir') !== false || (($row['src-address'] ?? '') === '10.7.0.0/29') || (($row['dst-address'] ?? '') === '10.7.0.0/29')) $redirectRules[] = $row;
                $firewallPosition = null;
                foreach ($filters as $index => $row) if (($row['.id'] ?? '') === ($firewall['.id'] ?? null)) { $firewallPosition = $index + 1; break; }
                $checks = [
                    'pool_exists' => (bool) $pool,
                    'pool_range_valid' => $pool && ($pool['ranges'] ?? '') === '10.7.0.2-10.7.0.6',
                    'profile_exists' => (bool) $profile,
                    'profile_local_valid' => $profile && ($profile['local-address'] ?? '') === '10.7.0.1',
                    'profile_pool_valid' => $profile && ($profile['remote-address'] ?? '') === 'POOL-ISOLIR',
                    'profile_address_list_valid' => $profile && ($profile['address-list'] ?? '') === 'ISOLIR',
                    'profile_ipv6_disabled' => $profile && ($profile['use-ipv6'] ?? '') === 'no',
                    'profile_mpls_disabled' => $profile && ($profile['use-mpls'] ?? '') === 'no',
                    'profile_upnp_disabled' => $profile && ($profile['use-upnp'] ?? '') === 'no',
                    'firewall_exists' => (bool) $firewall,
                    'firewall_source_valid' => $firewall && ($firewall['src-address'] ?? '') === '10.7.0.0/29',
                    'firewall_action_valid' => $firewall && ($firewall['action'] ?? '') === 'drop',
                    'firewall_enabled' => $firewall && ($firewall['disabled'] ?? 'false') === 'false',
                    'subnet_conflict_free' => empty($conflicts),
                    'dns_remote_requests' => !empty($dns) && ($dns[0]['allow-remote-requests'] ?? 'false') === 'true',
                ];
                return [
                    'success' => !in_array(false, $checks, true),
                    'checks' => $checks,
                    'pool' => $pool,
                    'profile' => $profile,
                    'firewall' => $firewall ? ['position' => $firewallPosition, 'total_rules' => count($filters), 'chain' => $firewall['chain'] ?? '', 'src-address' => $firewall['src-address'] ?? '', 'action' => $firewall['action'] ?? '', 'disabled' => $firewall['disabled'] ?? 'false', 'bytes' => $firewall['bytes'] ?? '0', 'packets' => $firewall['packets'] ?? '0'] : null,
                    'nat' => ['total_rules' => count($nat), 'isolation_redirect_rules' => $redirectRules, 'required_for_block_only' => false],
                    'conflicts' => $conflicts,
                    'capacity' => ['usable_addresses' => 5, 'warning' => 'POOL-ISOLIR /29 hanya cukup untuk lima pelanggan aktif bersamaan. Gunakan subnet lebih besar sebelum production massal.'],
                    'test_secret' => $secret ? ['name' => $secret['name'], 'profile' => $secret['profile'] ?? '', 'disabled' => $secret['disabled'] ?? ''] : null,
                ];
            });
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT); }
    }

    public function configure_isolation($nik = '')
    {
        if (!$this->input->is_cli_request()) { show_404(); return; }
        $context = $this->context($nik);
        if (isset($context['error'])) { echo json_encode($context, JSON_PRETTY_PRINT); return; }
        try {
            $result = $this->mikrotik_query->run($context['router'], function ($api) use ($context) {
                $poolName = 'POOL-ISOLIR'; $profileName = 'ISOLIR'; $subnet = '10.7.0.0/29';
                $ranges = '10.7.0.2-10.7.0.6'; $local = '10.7.0.1';
                $pools = $this->clean($api->comm('/ip/pool/print'));
                $profiles = $this->clean($api->comm('/ppp/profile/print'));
                $addresses = $this->clean($api->comm('/ip/address/print'));
                $routes = $this->clean($api->comm('/ip/route/print'));
                foreach (array_merge($pools, $addresses, $routes) as $row) {
                    if (($row['dynamic'] ?? 'false') === 'true' || ($row['disabled'] ?? 'false') === 'true') continue;
                    $serialized = json_encode($row);
                    if (strpos($serialized, '10.7.0.') !== false && (!isset($row['name']) || strcasecmp((string) $row['name'], $poolName) !== 0)) throw new RuntimeException('Subnet 10.7.0.0/29 terdeteksi telah dipakai konfigurasi lain. Proses dibatalkan.');
                }
                $pool = $this->findBy($pools, 'name', $poolName); $poolCreated = false;
                if (!$pool) {
                    $response = $api->comm('/ip/pool/add', ['=name' => $poolName, '=ranges' => $ranges, '=comment' => 'Billing Internet - pool pelanggan terisolir']);
                    if (!$this->done($response)) throw new RuntimeException('Gagal membuat IP pool isolir.');
                    $poolCreated = true; $pools = $this->clean($api->comm('/ip/pool/print')); $pool = $this->findBy($pools, 'name', $poolName);
                } elseif (($pool['ranges'] ?? '') !== $ranges) throw new RuntimeException('POOL-ISOLIR sudah ada tetapi range berbeda. Proses dibatalkan.');

                $profile = $this->findBy($profiles, 'name', $profileName);
                if (!$profile) {
                    $response = $api->comm('/ppp/profile/add', ['=name' => $profileName, '=local-address' => $local, '=remote-address' => $poolName, '=dns-server' => $local, '=only-one' => 'yes', '=change-tcp-mss' => 'yes', '=use-ipv6' => 'no', '=use-mpls' => 'no', '=use-upnp' => 'no', '=address-list' => 'ISOLIR', '=comment' => 'Billing Internet - profile isolir otomatis']);
                    if (!$this->done($response)) {
                        if ($poolCreated && !empty($pool['.id'])) $api->comm('/ip/pool/remove', ['=.id' => $pool['.id']]);
                        throw new RuntimeException('Gagal membuat PPP profile ISOLIR; pool baru dikembalikan.');
                    }
                } else {
                    $valid = ($profile['local-address'] ?? '') === $local && ($profile['remote-address'] ?? '') === $poolName;
                    if (!$valid) throw new RuntimeException('Profile ISOLIR sudah ada tetapi local/remote address berbeda. Proses dibatalkan tanpa mengubahnya.');
                    $response = $api->comm('/ppp/profile/set', ['=.id' => $profile['.id'], '=dns-server' => $local, '=only-one' => 'yes', '=change-tcp-mss' => 'yes', '=use-ipv6' => 'no', '=use-mpls' => 'no', '=use-upnp' => 'no', '=address-list' => 'ISOLIR']);
                    if (!$this->done($response)) throw new RuntimeException('Gagal mengamankan parameter IPv6/MPLS/UPnP pada profile ISOLIR.');
                }

                $filters = $this->clean($api->comm('/ip/firewall/filter/print'));
                $rule = $this->findBy($filters, 'comment', 'Billing Internet - blokir pelanggan isolir');
                if (!$rule) {
                    $response = $api->comm('/ip/firewall/filter/add', ['=chain' => 'forward', '=src-address' => $subnet, '=action' => 'drop', '=comment' => 'Billing Internet - blokir pelanggan isolir']);
                    if (!$this->done($response)) throw new RuntimeException('Profile dan pool dibuat, tetapi firewall isolir gagal dibuat.');
                }

                $pool = $this->findBy($this->clean($api->comm('/ip/pool/print')), 'name', $poolName);
                $profile = $this->findBy($this->clean($api->comm('/ppp/profile/print')), 'name', $profileName);
                $rule = $this->findBy($this->clean($api->comm('/ip/firewall/filter/print')), 'comment', 'Billing Internet - blokir pelanggan isolir');
                $expectedSecret = preg_replace('/\D+/', '', (string) $context['customer']['nik']) . app_setting('pppoe_username_suffix', '@BATARA.net');
                $secret = $this->findBy($this->clean($api->comm('/ppp/secret/print')), 'name', $expectedSecret);
                return ['success' => (bool) ($pool && $profile && $rule), 'pool' => $pool ? ['name' => $pool['name'], 'ranges' => $pool['ranges']] : null, 'profile' => $profile ? ['name' => $profile['name'], 'local-address' => $profile['local-address'] ?? '', 'remote-address' => $profile['remote-address'] ?? '', 'address-list' => $profile['address-list'] ?? ''] : null, 'firewall' => $rule ? ['chain' => $rule['chain'], 'src-address' => $rule['src-address'], 'action' => $rule['action'], 'comment' => $rule['comment']] : null, 'test_secret' => $secret ? ['name' => $secret['name'], 'profile' => $secret['profile'] ?? '', 'disabled' => $secret['disabled'] ?? ''] : null];
            });
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT); }
    }

    public function isolation($nik = '')
    {
        if (!$this->input->is_cli_request()) { show_404(); return; }
        $nik = preg_replace('/\D+/', '', (string) $nik);
        $customer = $this->db->where('nik', $nik)->get('customers')->row_array();
        if (!$customer) { echo json_encode(['error' => 'Pelanggan tidak ditemukan.'], JSON_PRETTY_PRINT); return; }
        $this->load->model('packages/package_model');
        $this->load->model('routers/router_model');
        $this->load->library('Mikrotik_query');
        $package = $this->package_model->find((int) $customer['package_id']);
        $router = $package ? $this->router_model->find((int) $package['router_id']) : null;
        if (!$router) { echo json_encode(['error' => 'Router pelanggan tidak ditemukan.'], JSON_PRETTY_PRINT); return; }
        try {
            $data = $this->mikrotik_query->run($router, function ($api) use ($nik) {
                $suffix = app_setting('pppoe_username_suffix', '@BATARA.net');
                $secretName = $nik . $suffix;
                return [
                    'resource' => $this->clean($api->comm('/system/resource/print')),
                    'secret' => $this->filterName($this->clean($api->comm('/ppp/secret/print')), $secretName),
                    'active' => $this->filterName($this->clean($api->comm('/ppp/active/print')), $secretName),
                    'profiles' => $this->clean($api->comm('/ppp/profile/print')),
                    'pools' => $this->clean($api->comm('/ip/pool/print')),
                    'addresses' => $this->clean($api->comm('/ip/address/print')),
                    'routes' => $this->clean($api->comm('/ip/route/print')),
                    'dns' => $this->clean($api->comm('/ip/dns/print')),
                    'dns_static' => $this->clean($api->comm('/ip/dns/static/print')),
                    'nat' => $this->clean($api->comm('/ip/firewall/nat/print')),
                    'filters' => $this->clean($api->comm('/ip/firewall/filter/print')),
                    'address_lists' => $this->clean($api->comm('/ip/firewall/address-list/print')),
                ];
            });
            echo json_encode(['customer' => ['id' => (int) $customer['id'], 'name' => $customer['name'], 'group' => $customer['group_name']], 'package' => ['id' => (int) $package['id'], 'name' => $package['package_name'], 'profile' => $package['ppp_profile_name']], 'router' => ['id' => (int) $router['id'], 'name' => $router['name'], 'host' => $router['host']], 'config' => $data], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) { echo json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT); }
    }

    public function external_access($nik = '')
    {
        if (!$this->input->is_cli_request()) { show_404(); return; }
        $context = $this->context($nik);
        if (isset($context['error'])) { echo json_encode($context, JSON_PRETTY_PRINT); return; }
        try {
            $result = $this->mikrotik_query->run($context['router'], function ($api) {
                $nat = $this->clean($api->comm('/ip/firewall/nat/print'));
                $filters = $this->clean($api->comm('/ip/firewall/filter/print'));
                return [
                    'addresses' => $this->clean($api->comm('/ip/address/print')),
                    'routes' => $this->clean($api->comm('/ip/route/print', ['?dst-address' => '0.0.0.0/0'])),
                    'interfaces' => $this->clean($api->comm('/interface/print')),
                    'services' => $this->clean($api->comm('/ip/service/print')),
                    'cloud' => $this->clean($api->comm('/ip/cloud/print')),
                    'dstnat' => array_values(array_filter($nat, function ($row) { return ($row['chain'] ?? '') === 'dstnat'; })),
                    'input_filters' => array_values(array_filter($filters, function ($row) { return ($row['chain'] ?? '') === 'input'; })),
                    'forward_filters' => array_values(array_filter($filters, function ($row) { return ($row['chain'] ?? '') === 'forward'; })),
                ];
            });
            echo json_encode(['success' => true, 'router' => ['name' => $context['router']['name'], 'host' => $context['router']['host']], 'audit' => $result], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT); }
    }

    public function configure_olt_snmp_external($nik = '')
    {
        if (!$this->input->is_cli_request()) { show_404(); return; }
        $context = $this->context($nik);
        if (isset($context['error'])) { echo json_encode($context, JSON_PRETTY_PRINT); return; }

        $publicIp = '103.85.52.33';
        $externalPort = '31611';
        $oltIp = '192.168.99.1';
        $oltPort = '161';
        $natComment = 'Billing Internet - OLT SNMP from hosting';
        $filterComment = 'Billing Internet - allow OLT SNMP from hosting';
        $srcnatComment = 'Billing Internet - OLT SNMP return path';

        try {
            $result = $this->mikrotik_query->run($context['router'], function ($api) use ($publicIp, $externalPort, $oltIp, $oltPort, $natComment, $filterComment, $srcnatComment) {
                $natRows = $this->clean($api->comm('/ip/firewall/nat/print'));
                foreach ($natRows as $row) {
                    if (($row['chain'] ?? '') !== 'dstnat' || ($row['protocol'] ?? '') !== 'udp' || ($row['disabled'] ?? 'false') === 'true') continue;
                    if ((string) ($row['dst-port'] ?? '') === $externalPort && ($row['comment'] ?? '') !== $natComment) {
                        throw new RuntimeException('UDP publik ' . $externalPort . ' sudah digunakan rule ' . ($row['comment'] ?? ($row['.id'] ?? 'tanpa nama')) . '.');
                    }
                }

                $dstnat = $this->findBy($natRows, 'comment', $natComment);
                $dstnatParams = [
                    '=chain' => 'dstnat', '=action' => 'dst-nat', '=protocol' => 'udp',
                    '=src-address' => '0.0.0.0/0', '=dst-address' => $publicIp,
                    '=dst-port' => $externalPort, '=to-addresses' => $oltIp, '=to-ports' => $oltPort,
                    '=comment' => $natComment, '=disabled' => 'no',
                ];
                if ($dstnat && !empty($dstnat['.id'])) {
                    $dstnatParams['=.id'] = $dstnat['.id'];
                    $response = $api->comm('/ip/firewall/nat/set', $dstnatParams);
                } else {
                    $response = $api->comm('/ip/firewall/nat/add', $dstnatParams);
                }
                if (!$this->done($response)) throw new RuntimeException('MikroTik menolak DST-NAT SNMP OLT.');

                $natRows = $this->clean($api->comm('/ip/firewall/nat/print'));
                $srcnat = $this->findBy($natRows, 'comment', $srcnatComment);
                $srcnatParams = [
                    '=chain' => 'srcnat', '=action' => 'src-nat', '=to-addresses' => '192.168.99.2', '=protocol' => 'udp',
                    '=src-address' => '0.0.0.0/0', '=dst-address' => $oltIp,
                    '=dst-port' => $oltPort, '=comment' => $srcnatComment, '=disabled' => 'no',
                ];
                if ($srcnat && !empty($srcnat['.id'])) {
                    $srcnatParams['=.id'] = $srcnat['.id'];
                    $response = $api->comm('/ip/firewall/nat/set', $srcnatParams);
                } else {
                    $response = $api->comm('/ip/firewall/nat/add', $srcnatParams);
                }
                if (!$this->done($response)) throw new RuntimeException('MikroTik menolak source NAT jalur balasan OLT.');

                $filters = $this->clean($api->comm('/ip/firewall/filter/print'));
                $filter = $this->findBy($filters, 'comment', $filterComment);
                $filterParams = [
                    '=chain' => 'forward', '=action' => 'accept', '=protocol' => 'udp',
                    '=src-address' => '0.0.0.0/0', '=dst-address' => $oltIp,
                    '=dst-port' => $oltPort, '=connection-nat-state' => 'dstnat',
                    '=comment' => $filterComment, '=disabled' => 'no',
                ];
                if ($filter && !empty($filter['.id'])) {
                    $filterParams['=.id'] = $filter['.id'];
                    $response = $api->comm('/ip/firewall/filter/set', $filterParams);
                } else {
                    $response = $api->comm('/ip/firewall/filter/add', $filterParams);
                }
                if (!$this->done($response)) throw new RuntimeException('MikroTik menolak firewall allow SNMP OLT.');

                $natRows = $this->clean($api->comm('/ip/firewall/nat/print'));
                $filters = $this->clean($api->comm('/ip/firewall/filter/print'));
                return [
                    'success' => true,
                    'endpoint' => 'udp://' . $publicIp . ':' . $externalPort,
                    'allowed_source' => 'any',
                    'target' => $oltIp . ':' . $oltPort . '/udp',
                    'dstnat' => $this->findBy($natRows, 'comment', $natComment),
                    'srcnat' => $this->findBy($natRows, 'comment', $srcnatComment),
                    'filter' => $this->findBy($filters, 'comment', $filterComment),
                ];
            });
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }

    public function configure_debian_external($nik = '')
    {
        if (!$this->input->is_cli_request()) { show_404(); return; }
        $context = $this->context($nik);
        if (isset($context['error'])) { echo json_encode($context, JSON_PRETTY_PRINT); return; }
        $publicIp = '103.85.52.33';
        $serverIp = '10.5.5.3';
        $mappings = [
            ['comment' => 'Billing Internet - Debian SSH public', 'external' => '30003', 'internal' => '30003'],
            ['comment' => 'Billing Internet - Debian Web public', 'external' => '30080', 'internal' => '80'],
        ];
        try {
            $result = $this->mikrotik_query->run($context['router'], function ($api) use ($publicIp, $serverIp, $mappings) {
                $nat = $this->clean($api->comm('/ip/firewall/nat/print'));
                foreach ($mappings as $mapping) {
                    foreach ($nat as $row) {
                        if (($row['chain'] ?? '') !== 'dstnat' || ($row['protocol'] ?? '') !== 'tcp' || ($row['disabled'] ?? 'false') === 'true') continue;
                        if ((string) ($row['dst-port'] ?? '') !== $mapping['external']) continue;
                        if (($row['comment'] ?? '') !== $mapping['comment']) {
                            throw new RuntimeException('Port publik ' . $mapping['external'] . ' sudah dipakai rule ' . ($row['comment'] ?? ($row['.id'] ?? 'tanpa nama')) . '.');
                        }
                    }
                }

                $applied = [];
                foreach ($mappings as $mapping) {
                    $existing = $this->findBy($nat, 'comment', $mapping['comment']);
                    $params = [
                        '=chain' => 'dstnat', '=dst-address' => $publicIp, '=protocol' => 'tcp',
                        '=dst-port' => $mapping['external'], '=action' => 'dst-nat',
                        '=to-addresses' => $serverIp, '=to-ports' => $mapping['internal'],
                        '=comment' => $mapping['comment'], '=disabled' => 'no',
                    ];
                    if ($existing && !empty($existing['.id'])) {
                        $params['=.id'] = $existing['.id'];
                        $response = $api->comm('/ip/firewall/nat/set', $params);
                    } else {
                        $response = $api->comm('/ip/firewall/nat/add', $params);
                    }
                    if (!$this->done($response)) throw new RuntimeException('MikroTik menolak NAT ' . $mapping['comment'] . '.');
                    $applied[] = $publicIp . ':' . $mapping['external'] . ' -> ' . $serverIp . ':' . $mapping['internal'];
                    $nat = $this->clean($api->comm('/ip/firewall/nat/print'));
                }
                return ['success' => true, 'applied' => $applied, 'public_ip' => $publicIp, 'server_ip' => $serverIp];
            });
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT); }
    }

    public function configure_debian_wireguard($nik = '')
    {
        if (!$this->input->is_cli_request()) { show_404(); return; }
        $context = $this->context($nik);
        if (isset($context['error'])) { echo json_encode($context, JSON_PRETTY_PRINT); return; }
        try {
            $result = $this->mikrotik_query->run($context['router'], function ($api) {
                $comment = 'Billing Internet - Debian WireGuard VPN';
                $nat = $this->clean($api->comm('/ip/firewall/nat/print'));
                foreach ($nat as $row) {
                    if (($row['chain'] ?? '') !== 'dstnat' || ($row['protocol'] ?? '') !== 'udp' || ($row['disabled'] ?? 'false') === 'true') continue;
                    if ((string) ($row['dst-port'] ?? '') === '51888' && ($row['comment'] ?? '') !== $comment) {
                        throw new RuntimeException('UDP 51888 sudah digunakan rule ' . ($row['comment'] ?? ($row['.id'] ?? 'tanpa nama')) . '.');
                    }
                }
                $existing = $this->findBy($nat, 'comment', $comment);
                $params = [
                    '=chain' => 'dstnat', '=dst-address' => '103.85.52.33', '=protocol' => 'udp',
                    '=dst-port' => '51888', '=action' => 'dst-nat', '=to-addresses' => '10.5.5.3',
                    '=to-ports' => '51888', '=comment' => $comment, '=disabled' => 'no',
                ];
                if ($existing && !empty($existing['.id'])) {
                    $params['=.id'] = $existing['.id'];
                    $response = $api->comm('/ip/firewall/nat/set', $params);
                } else {
                    $response = $api->comm('/ip/firewall/nat/add', $params);
                }
                if (!$this->done($response)) throw new RuntimeException('MikroTik menolak NAT WireGuard Debian.');
                return ['success' => true, 'endpoint' => '103.85.52.33:51888', 'target' => '10.5.5.3:51888/udp'];
            });
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT); }
    }

    public function configure_olt_relay_external($nik = '')
    {
        if (!$this->input->is_cli_request()) { show_404(); return; }
        if (preg_match('/^router-(\d+)$/', (string) $nik, $matches)) {
            $this->load->model('routers/router_model');
            $this->load->library('Mikrotik_query');
            $router = $this->router_model->find((int) $matches[1]);
            $context = $router ? ['router' => $router] : ['error' => 'Router tidak ditemukan.'];
        } else {
            $context = $this->context($nik);
        }
        if (isset($context['error'])) { echo json_encode($context, JSON_PRETTY_PRINT); return; }
        try {
            $result = $this->mikrotik_query->run($context['router'], function ($api) {
                $publicIp = '103.85.52.33'; $publicPort = '31877'; $serverIp = '10.5.5.3'; $serverPort = '8787';
                $natComment = 'Billing Internet - OLT Relay TCP';
                $srcnatComment = 'Billing Internet - OLT Relay return path';
                $filterComment = 'Billing Internet - allow OLT Relay TCP';
                $nat = $this->clean($api->comm('/ip/firewall/nat/print'));
                foreach ($nat as $row) {
                    if (($row['chain'] ?? '') !== 'dstnat' || ($row['protocol'] ?? '') !== 'tcp' || ($row['disabled'] ?? 'false') === 'true') continue;
                    if ((string) ($row['dst-port'] ?? '') === $publicPort && ($row['comment'] ?? '') !== $natComment) {
                        throw new RuntimeException('TCP publik ' . $publicPort . ' sudah digunakan rule ' . ($row['comment'] ?? ($row['.id'] ?? 'tanpa nama')) . '.');
                    }
                }
                $existing = $this->findBy($nat, 'comment', $natComment);
                $params = ['=chain' => 'dstnat', '=dst-address' => $publicIp, '=protocol' => 'tcp', '=dst-port' => $publicPort,
                    '=action' => 'dst-nat', '=to-addresses' => $serverIp, '=to-ports' => $serverPort, '=comment' => $natComment, '=disabled' => 'no'];
                if ($existing && !empty($existing['.id'])) { $params['=.id'] = $existing['.id']; $response = $api->comm('/ip/firewall/nat/set', $params); }
                else $response = $api->comm('/ip/firewall/nat/add', $params);
                if (!$this->done($response)) throw new RuntimeException('MikroTik menolak DST-NAT relay OLT.');

                $nat = $this->clean($api->comm('/ip/firewall/nat/print'));
                $srcnat = $this->findBy($nat, 'comment', $srcnatComment);
                $srcnatParams = ['=chain' => 'srcnat', '=action' => 'src-nat', '=protocol' => 'tcp',
                    '=dst-address' => $serverIp, '=dst-port' => $serverPort, '=to-addresses' => '10.5.5.2',
                    '=comment' => $srcnatComment, '=disabled' => 'no'];
                if ($srcnat && !empty($srcnat['.id'])) { $srcnatParams['=.id'] = $srcnat['.id']; $response = $api->comm('/ip/firewall/nat/set', $srcnatParams); }
                else $response = $api->comm('/ip/firewall/nat/add', $srcnatParams);
                if (!$this->done($response)) throw new RuntimeException('MikroTik menolak source NAT jalur balik relay OLT.');

                $filters = $this->clean($api->comm('/ip/firewall/filter/print'));
                $filter = $this->findBy($filters, 'comment', $filterComment);
                $filterParams = ['=chain' => 'forward', '=action' => 'accept', '=protocol' => 'tcp', '=dst-address' => $serverIp,
                    '=dst-port' => $serverPort, '=connection-nat-state' => 'dstnat', '=comment' => $filterComment, '=disabled' => 'no'];
                if ($filter && !empty($filter['.id'])) { $filterParams['=.id'] = $filter['.id']; $response = $api->comm('/ip/firewall/filter/set', $filterParams); }
                else $response = $api->comm('/ip/firewall/filter/add', $filterParams);
                if (!$this->done($response)) throw new RuntimeException('MikroTik menolak firewall allow relay OLT.');
                return ['success' => true, 'endpoint' => 'http://' . $publicIp . ':' . $publicPort,
                    'target' => $serverIp . ':' . $serverPort . '/tcp'];
            });
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT); }
    }

    private function clean(array $rows)
    {
        $result = [];
        foreach ($rows as $row) {
            if (isset($row['!done'])) continue;
            foreach (['password', 'secret', 'community', 'private-key', 'shared-secret', 'on-up', 'on-down'] as $key) unset($row[$key]);
            $result[] = $row;
        }
        return $result;
    }

    private function context($nik)
    {
        $nik = preg_replace('/\D+/', '', (string) $nik);
        $customer = $this->db->where('nik', $nik)->get('customers')->row_array();
        if (!$customer) return ['error' => 'Pelanggan tidak ditemukan.'];
        $this->load->model('packages/package_model'); $this->load->model('routers/router_model'); $this->load->library('Mikrotik_query');
        $package = $this->package_model->find((int) $customer['package_id']);
        $router = $package ? $this->router_model->find((int) $package['router_id']) : null;
        return $router ? ['customer' => $customer, 'package' => $package, 'router' => $router] : ['error' => 'Router pelanggan tidak ditemukan.'];
    }

    private function findBy(array $rows, $field, $value)
    {
        foreach ($rows as $row) if (isset($row[$field]) && strcasecmp((string) $row[$field], (string) $value) === 0) return $row;
        return null;
    }

    private function done(array $rows)
    {
        foreach ($rows as $row) if (isset($row['!trap']) || isset($row['!fatal'])) return false;
        foreach ($rows as $row) if (isset($row['!done'])) return true;
        return false;
    }

    private function managed(array $rows, $prefix)
    {
        return array_values(array_filter($rows, function ($row) use ($prefix) { return isset($row['comment']) && strpos((string) $row['comment'], $prefix) === 0; }));
    }

    private function redirectState($api, $targetIp, $targetUrl, $subnet)
    {
        $proxyRows = $this->clean($api->comm('/ip/proxy/print'));
        $proxy = isset($proxyRows[0]) ? $proxyRows[0] : [];
        $filters = $this->clean($api->comm('/ip/firewall/filter/print'));
        $nat = $this->clean($api->comm('/ip/firewall/nat/print'));
        $access = $this->clean($api->comm('/ip/proxy/access/print'));
        $allowPage = $this->findBy($filters, 'comment', 'Billing Internet - izinkan halaman isolir');
        $allowProxy = $this->findBy($filters, 'comment', 'Billing Internet - izinkan proxy isolir');
        $denyProxy = $this->findBy($filters, 'comment', 'Billing Internet - lindungi proxy isolir');
        $drop = $this->findBy($filters, 'comment', 'Billing Internet - blokir pelanggan isolir');
        $natRule = $this->findBy($nat, 'comment', 'Billing Internet - tangkap HTTP pelanggan isolir');
        $proxyRule = $this->findBy($access, 'comment', 'Billing Internet - redirect pelanggan isolir');
        $position = function ($rows, $id) { foreach ($rows as $index => $row) if (($row['.id'] ?? '') === $id) return $index + 1; return null; };
        $checks = [
            'proxy_enabled' => ($proxy['enabled'] ?? 'false') === 'true',
            'proxy_port_8080' => (string) ($proxy['port'] ?? '') === '8080',
            'page_allow_exists' => (bool) $allowPage,
            'page_allow_before_drop' => $allowPage && $drop && $position($filters, $allowPage['.id']) < $position($filters, $drop['.id']),
            'proxy_input_allow_exists' => (bool) $allowProxy,
            'proxy_input_protected' => (bool) $denyProxy,
            'proxy_access_redirect_exists' => $proxyRule && ($proxyRule['redirect-to'] ?? '') === $targetUrl,
            'nat_http_redirect_exists' => $natRule && ($natRule['src-address'] ?? '') === $subnet && ($natRule['to-ports'] ?? '') === '8080',
        ];
        return ['success' => !in_array(false, $checks, true), 'checks' => $checks, 'target' => $targetUrl, 'proxy' => ['enabled' => $proxy['enabled'] ?? '', 'port' => $proxy['port'] ?? ''], 'filter_positions' => ['allow_page' => $allowPage ? $position($filters, $allowPage['.id']) : null, 'drop_isolation' => $drop ? $position($filters, $drop['.id']) : null, 'allow_proxy' => $allowProxy ? $position($filters, $allowProxy['.id']) : null, 'deny_proxy' => $denyProxy ? $position($filters, $denyProxy['.id']) : null], 'nat' => $natRule, 'proxy_access' => $proxyRule];
    }

    private function localProxyState($api, $subnet, $proxyPort)
    {
        $proxyRows = $this->clean($api->comm('/ip/proxy/print'));
        $proxy = $proxyRows[0] ?? [];
        $filters = $this->clean($api->comm('/ip/firewall/filter/print'));
        $nat = $this->clean($api->comm('/ip/firewall/nat/print'));
        $access = $this->clean($api->comm('/ip/proxy/access/print'));
        $files = $this->clean($api->comm('/file/print'));
        $schedulers = $this->clean($api->comm('/system/scheduler/print'));
        $allow = $this->findBy($filters, 'comment', 'Billing Internet - izinkan proxy isolir');
        $protect = $this->findBy($filters, 'comment', 'Billing Internet - lindungi proxy isolir');
        $capture = $this->findBy($nat, 'comment', 'Billing Internet - tangkap HTTP pelanggan isolir');
        $legacy = $this->findBy($nat, 'comment', 'Billing Internet - redirect HTTP pelanggan isolir');
        $deny = $this->findBy($access, 'comment', 'Billing Internet - redirect pelanggan isolir');
        $localPage = $this->findBy($access, 'comment', 'Billing Internet - halaman lokal isolir');
        $noticeDns = $this->findBy($this->clean($api->comm('/ip/dns/static/print')), 'name', 'isolir.batara.local');
        $page = $this->findBy($files, 'name', 'webproxy/error.html');
        $persistentPage = $this->findBy($files, 'name', 'flash/webproxy/error.html');
        $scheduler = $this->findBy($schedulers, 'name', 'billing-restore-isolation-page');
        $checks = [
            'page_stored_on_mikrotik' => $page && (int) ($page['size'] ?? 0) >= 1000,
            'page_persists_after_restart' => $persistentPage && (int) ($persistentPage['size'] ?? 0) >= 1000 && (bool) $scheduler,
            'proxy_enabled' => ($proxy['enabled'] ?? 'false') === 'true',
            'proxy_port' => (string) ($proxy['port'] ?? '') === $proxyPort,
            'proxy_allowed_only_from_isolation_pool' => $allow && ($allow['src-address'] ?? '') === $subnet && ($allow['dst-port'] ?? '') === $proxyPort,
            'proxy_protected_from_other_sources' => (bool) $protect,
            'captive_dns_points_to_router' => $noticeDns && ($noticeDns['address'] ?? '') === '10.7.0.1',
            'local_page_excluded_from_redirect_loop' => $localPage && ($localPage['src-address'] ?? '') === $subnet && ($localPage['dst-host'] ?? '') === 'isolir.batara.local' && empty($localPage['redirect-to']),
            'http_redirects_to_local_captive_page' => $deny && ($deny['src-address'] ?? '') === $subnet && ($deny['action'] ?? '') === 'deny' && ($deny['redirect-to'] ?? '') === 'http://isolir.batara.local/',
            'http_capture_limited_to_isolation_pool' => $capture && ($capture['src-address'] ?? '') === $subnet && ($capture['dst-port'] ?? '') === '80' && ($capture['action'] ?? '') === 'redirect' && ($capture['disabled'] ?? 'false') === 'false' && ($capture['invalid'] ?? 'false') === 'false',
            'debian_http_redirect_disabled' => !$legacy || ($legacy['disabled'] ?? 'false') === 'true',
        ];
        return ['success' => !in_array(false, $checks, true), 'mode' => 'mikrotik-local-web-proxy', 'checks' => $checks, 'page' => $page ? ['name' => $page['name'], 'size' => $page['size'] ?? ''] : null, 'nat' => $capture, 'legacy_debian_nat' => $legacy ? ['disabled' => $legacy['disabled'] ?? 'false'] : null];
    }

    private function filterName(array $rows, $name)
    {
        return array_values(array_filter($rows, function ($row) use ($name) { return isset($row['name']) && strcasecmp((string) $row['name'], (string) $name) === 0; }));
    }
}
