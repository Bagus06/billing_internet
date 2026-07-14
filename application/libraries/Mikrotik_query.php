<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mikrotik_query
{
    private $CI;
    private $config;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('Mikrotik_api');
        $this->CI->load->helper('mikrotik');
        $this->CI->config->load('mikrotik', true);
        $this->config = (array) $this->CI->config->item('mikrotik');
    }

    public function connect(array $router)
    {
        $api = new Mikrotik_api();
        $api->connect(mikrotik_normalize_router($router));
        return $api;
    }

    public function run(array $router, callable $callback)
    {
        $api = $this->connect($router);
        try { return $callback($api); }
        finally { $api->close(); }
    }

    public function resource(array $router) { return $this->run($router, function ($api) { return $api->getResource(); }); }
    public function activeSessions(array $router) { return $this->run($router, function ($api) { return mikrotik_clean_rows($api->getActiveSessions()); }); }
    public function interfaces(array $router) { return $this->run($router, function ($api) { return mikrotik_clean_rows($api->getInterfaceStats()); }); }
    public function secrets(array $router) { return $this->run($router, function ($api) { return mikrotik_clean_rows($api->getPppSecrets()); }); }
    public function profiles(array $router) { return $this->run($router, function ($api) { return mikrotik_clean_rows($api->getPppProfiles()); }); }
    public function ipPools(array $router) { return $this->run($router, function ($api) { return mikrotik_clean_rows($api->getIpPools()); }); }

    public function findProfile(array $router, $value, $field = '.id')
    {
        return mikrotik_find_row($this->profiles($router), $value, $field);
    }

    public function findSecret(array $router, $value, $field = '.id')
    {
        return mikrotik_find_row($this->secrets($router), $value, $field);
    }

    public function profileUsageFromSecrets(array $secrets, $profileName)
    {
        $names = [];
        foreach ($secrets as $secret) {
            if (!isset($secret['profile']) || strcasecmp((string) $secret['profile'], (string) $profileName) !== 0) continue;
            $names[] = !empty($secret['name']) ? $secret['name'] : ($secret['.id'] ?? 'Secret tanpa nama');
        }
        return ['count' => count($names), 'examples' => array_slice($names, 0, 5)];
    }

    public function profilesWithUsage(array $router)
    {
        return $this->run($router, function ($api) {
            $secrets = mikrotik_clean_rows($api->getPppSecrets());
            $profiles = mikrotik_clean_rows($api->getPppProfiles());
            foreach ($profiles as &$profile) {
                $usage = $this->profileUsageFromSecrets($secrets, $profile['name'] ?? '');
                $profile['_usage_count'] = $usage['count'];
                $profile['_usage_examples'] = $usage['examples'];
            }
            unset($profile);
            return $profiles;
        });
    }

    public function issueDeleteConfirmation($scope, $routerId, $objectId, $objectName)
    {
        $token = bin2hex(random_bytes(32));
        $key = (string) ($this->config['mikrotik_delete_session_key'] ?? 'mikrotik_delete_confirmations');
        $items = (array) $this->CI->session->userdata($key);
        $now = time();
        foreach ($items as $hash => $item) if (($item['expires_at'] ?? 0) < $now) unset($items[$hash]);
        $items[hash('sha256', $token)] = ['scope' => (string) $scope, 'router_id' => (int) $routerId,
            'object_id' => (string) $objectId, 'object_name' => (string) $objectName,
            'expires_at' => $now + (int) ($this->config['mikrotik_delete_confirmation_ttl'] ?? 300)];
        $this->CI->session->set_userdata($key, $items);
        return $token;
    }

    public function confirmDelete($token, $phrase, $scope, $routerId, $objectId)
    {
        $expectedPhrase = (string) ($this->config['mikrotik_delete_confirmation_phrase'] ?? 'HAPUS');
        if (!hash_equals($expectedPhrase, strtoupper(trim((string) $phrase)))) return false;
        $key = (string) ($this->config['mikrotik_delete_session_key'] ?? 'mikrotik_delete_confirmations');
        $items = (array) $this->CI->session->userdata($key);
        $hash = hash('sha256', (string) $token);
        $item = $items[$hash] ?? null;
        unset($items[$hash]);
        $this->CI->session->set_userdata($key, $items);
        return $item && ($item['expires_at'] ?? 0) >= time()
            && hash_equals((string) $item['scope'], (string) $scope)
            && (int) $item['router_id'] === (int) $routerId
            && hash_equals((string) $item['object_id'], (string) $objectId);
    }

    public function deletePppProfile(array $router, $profileId, $token, $phrase)
    {
        $routerId = (int) ($router['id'] ?? 0);
        if (!$this->confirmDelete($token, $phrase, 'ppp_profile', $routerId, $profileId)) {
            throw new RuntimeException('Konfirmasi penghapusan tidak valid atau sudah kedaluwarsa. Muat ulang halaman dan ulangi konfirmasi.');
        }
        return $this->run($router, function ($api) use ($profileId) {
            $profile = mikrotik_find_row(mikrotik_clean_rows($api->getPppProfiles()), $profileId);
            if (!$profile) throw new RuntimeException('PPP Profile tidak ditemukan pada MikroTik.');
            $usage = $this->profileUsageFromSecrets(mikrotik_clean_rows($api->getPppSecrets()), $profile['name'] ?? '');
            if ($usage['count'] > 0) throw new RuntimeException('Profile masih digunakan oleh ' . $usage['count'] . ' PPP Secret: ' . implode(', ', $usage['examples']) . '.');
            if (!$api->deletePppProfile($profileId)) throw new RuntimeException('MikroTik menolak penghapusan PPP Profile.');
            if (mikrotik_find_row(mikrotik_clean_rows($api->getPppProfiles()), $profileId)) throw new RuntimeException('Verifikasi gagal: PPP Profile masih ditemukan setelah perintah delete.');
            return $profile;
        });
    }
}
