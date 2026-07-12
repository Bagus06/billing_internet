<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mikrotik_profiles extends MY_Controller
{
    protected $permission = 'routers';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('routers/router_model');
        $this->load->library('Mikrotik_api');
    }

    public function index()
    {
        $routers = $this->router_model->get_all(true);
        $selectedId = (int) $this->input->get('router_id');
        if (!$selectedId && $routers) $selectedId = (int) $routers[0]['id'];
        $profiles = []; $error = null;
        if ($selectedId) {
            try {
                $router = $this->router($selectedId); $api = $this->connect($router);
                $usageMap = [];
                foreach ($api->getPppSecrets() as $secret) {
                    if (isset($secret['!done']) || empty($secret['profile'])) continue;
                    $key = strtolower((string) $secret['profile']);
                    if (!isset($usageMap[$key])) $usageMap[$key] = [];
                    $usageMap[$key][] = $secret['name'] ?? ($secret['.id'] ?? 'Secret');
                }
                foreach ($api->getPppProfiles() as $profile) {
                    if (isset($profile['!done']) || empty($profile['.id'])) continue;
                    $users = $usageMap[strtolower((string) ($profile['name'] ?? ''))] ?? [];
                    $profile['_usage_count'] = count($users);
                    $profile['_usage_examples'] = array_slice($users, 0, 5);
                    $profiles[] = $profile;
                }
                $api->close();
            } catch (Throwable $e) { $error = $e->getMessage(); }
        }
        $this->render('index', ['title' => 'PPP Profile MikroTik', 'routers' => $routers, 'selected_router_id' => $selectedId, 'profiles' => $profiles, 'api_error' => $error]);
    }

    public function create()
    {
        $routerId = (int) $this->input->get('router_id');
        $this->profileForm('create', $routerId, null);
    }

    public function store()
    {
        $routerId = (int) $this->input->post('router_id');
        $data = $this->profileInput();
        if (!$data) { redirect('mikrotik-profiles/create?router_id=' . $routerId); return; }
        try {
            $api = $this->connect($this->router($routerId));
            foreach ($api->getPppProfiles() as $row) if (isset($row['name']) && strcasecmp($row['name'], $data['name']) === 0) throw new RuntimeException('Nama PPP Profile sudah tersedia pada router.');
            $id = $api->createPppProfile($data);
            if (!$id) throw new RuntimeException('MikroTik gagal membuat PPP Profile.');
            $profile = $this->find($api->getPppProfiles(), $id);
            $api->close();
            if (!$profile) throw new RuntimeException('Profile dibuat tetapi gagal diverifikasi dari MikroTik.');
            $this->session->set_flashdata('success', 'PPP Profile berhasil dibuat langsung di MikroTik.');
            redirect('mikrotik-profiles?router_id=' . $routerId);
        } catch (Throwable $e) { $this->fail($e->getMessage(), 'mikrotik-profiles/create?router_id=' . $routerId); }
    }

    public function edit()
    {
        $routerId = (int) $this->input->get('router_id'); $profileId = (string) $this->input->get('profile_id');
        try {
            $api = $this->connect($this->router($routerId)); $profile = $this->find($api->getPppProfiles(), $profileId);
            if (!$profile) { show_404(); return; }
            $usage = $this->profileUsage($api, $profile['name'] ?? '');
            $profile['_usage_count'] = $usage['count']; $profile['_usage_examples'] = $usage['examples'];
            $api->close();
            $this->profileForm('edit', $routerId, $profile);
        } catch (Throwable $e) { $this->fail($e->getMessage(), 'mikrotik-profiles?router_id=' . $routerId); }
    }

    public function update()
    {
        $routerId = (int) $this->input->post('router_id'); $profileId = (string) $this->input->post('profile_id');
        $data = $this->profileInput();
        if (!$data) { redirect('mikrotik-profiles/edit?router_id=' . $routerId . '&profile_id=' . rawurlencode($profileId)); return; }
        try {
            $api = $this->connect($this->router($routerId)); $current = $this->find($api->getPppProfiles(), $profileId);
            if (!$current) throw new RuntimeException('PPP Profile tidak ditemukan pada MikroTik.');
            $currentName = isset($current['name']) ? (string) $current['name'] : '';
            if ($currentName !== $data['name']) {
                $usage = $this->profileUsage($api, $currentName);
                if ($usage['count'] > 0) throw new RuntimeException('Nama profile tidak dapat diubah karena masih digunakan oleh ' . $usage['count'] . ' PPP Secret: ' . implode(', ', $usage['examples']) . '. Pindahkan Secret ke profile lain terlebih dahulu.');
            }
            foreach ($api->getPppProfiles() as $row) if (($row['.id'] ?? '') !== $profileId && isset($row['name']) && strcasecmp($row['name'], $data['name']) === 0) throw new RuntimeException('Nama PPP Profile sudah digunakan profile lain.');
            $changes = $this->changes($current, $data);
            if ($changes && !$api->updatePppProfile($profileId, $changes)) throw new RuntimeException('MikroTik menolak perubahan PPP Profile.');
            $verified = $this->find($api->getPppProfiles(), $profileId); $api->close();
            if (!$verified || $this->changes($verified, $data)) throw new RuntimeException('Hasil verifikasi MikroTik masih berbeda dari form.');
            $this->session->set_flashdata('success', $changes ? 'PPP Profile berhasil diperbarui langsung di MikroTik.' : 'Tidak ada perubahan pada PPP Profile.');
            redirect('mikrotik-profiles?router_id=' . $routerId);
        } catch (Throwable $e) { $this->fail($e->getMessage(), 'mikrotik-profiles/edit?router_id=' . $routerId . '&profile_id=' . rawurlencode($profileId)); }
    }

    public function delete()
    {
        $routerId = (int) $this->input->post('router_id'); $profileId = (string) $this->input->post('profile_id');
        try {
            $api = $this->connect($this->router($routerId)); $profile = $this->find($api->getPppProfiles(), $profileId);
            if (!$profile) throw new RuntimeException('PPP Profile tidak ditemukan.');
            $usage = $this->profileUsage($api, isset($profile['name']) ? $profile['name'] : '');
            if ($usage['count'] > 0) throw new RuntimeException('Profile ' . $profile['name'] . ' tidak dapat dihapus karena masih digunakan oleh ' . $usage['count'] . ' PPP Secret: ' . implode(', ', $usage['examples']) . '. Pindahkan Secret ke profile lain terlebih dahulu.');
            if (!$api->deletePppProfile($profileId)) throw new RuntimeException('MikroTik menolak penghapusan profile. Profile mungkin merupakan default atau sedang digunakan.');
            $api->close(); $this->session->set_flashdata('success', 'PPP Profile ' . $profile['name'] . ' berhasil dihapus dari MikroTik.');
        } catch (Throwable $e) { $this->session->set_flashdata('error', $e->getMessage()); }
        redirect('mikrotik-profiles?router_id=' . $routerId);
    }

    private function profileForm($mode, $routerId, $profile)
    {
        $routers = $this->router_model->get_all(true); if (!$routerId && $routers) $routerId = (int) $routers[0]['id'];
        $pools = []; $error = null;
        if ($routerId) try { $api = $this->connect($this->router($routerId)); $pools = $api->getIpPools(); $api->close(); } catch (Throwable $e) { $error = $e->getMessage(); }
        $this->render('form', ['title' => ($mode === 'create' ? 'Tambah' : 'Edit') . ' PPP Profile MikroTik', 'mode' => $mode, 'routers' => $routers, 'router_id' => $routerId, 'profile' => $profile ?: $this->blank(), 'pools' => $pools, 'api_error' => $error, 'action' => site_url('mikrotik-profiles/' . ($mode === 'create' ? 'store' : 'update'))]);
    }

    private function profileInput()
    {
        $data = ['name' => trim($this->input->post('name', true)), 'local-address' => trim($this->input->post('local_address', true)), 'remote-address' => trim($this->input->post('remote_address', true)), 'rate-limit' => preg_replace('/\s+/', ' ', trim($this->input->post('rate_limit', true))), 'dns-server' => trim($this->input->post('dns_server', true)), 'only-one' => $this->choice($this->input->post('only_one')), 'change-tcp-mss' => $this->choice($this->input->post('change_tcp_mss'))];
        $errors = [];
        if (!preg_match('/^[A-Za-z0-9_.@ -]{1,64}$/', $data['name'])) $errors[] = 'Nama profile tidak valid.';
        if ($data['local-address'] !== '' && !filter_var($data['local-address'], FILTER_VALIDATE_IP)) $errors[] = 'Local address tidak valid.';
        if (!$this->validRate($data['rate-limit'])) $errors[] = 'Format rate-limit tidak valid.';
        if ($data['dns-server'] !== '') foreach (preg_split('/\s*,\s*/', $data['dns-server']) as $ip) if (!filter_var($ip, FILTER_VALIDATE_IP)) $errors[] = 'DNS server tidak valid.';
        if ($errors) { $this->session->set_flashdata('error', implode(' ', array_unique($errors))); return null; }
        return $data;
    }

    private function validRate($value)
    {
        if ($value === '') return true; $parts = explode(' ', $value); if (count($parts) > 6) return false;
        $rate = '/^\d+[kKmMgG](?:\/\d+[kKmMgG])?$/'; $time = '/^\d+(?:ms|s|m|h|d|w)?(?:\/\d+(?:ms|s|m|h|d|w)?)?$/i';
        if (!preg_match($rate, $parts[0])) return false;
        if (isset($parts[1]) && !preg_match($rate, $parts[1])) return false; if (isset($parts[2]) && !preg_match($rate, $parts[2])) return false;
        if (isset($parts[3]) && !preg_match($time, $parts[3])) return false;
        if (isset($parts[4]) && (!ctype_digit($parts[4]) || (int) $parts[4] < 1 || (int) $parts[4] > 8)) return false;
        return !isset($parts[5]) || (bool) preg_match($rate, $parts[5]);
    }

    private function router($id) { $router = $this->router_model->find($id); if (!$router || empty($router['is_active'])) throw new RuntimeException('Router aktif tidak ditemukan.'); return $router; }
    private function connect(array $router) { $router['ssl'] = !empty($router['use_ssl']); $api = new Mikrotik_api(); $api->connect($router); return $api; }
    private function find(array $rows, $id) { foreach ($rows as $row) if (($row['.id'] ?? '') === $id) return $row; return null; }
    private function profileUsage(Mikrotik_api $api, $profileName)
    {
        $names = [];
        foreach ($api->getPppSecrets() as $secret) {
            if (isset($secret['!done']) || !isset($secret['profile']) || strcasecmp((string) $secret['profile'], (string) $profileName) !== 0) continue;
            $names[] = isset($secret['name']) && $secret['name'] !== '' ? $secret['name'] : (isset($secret['.id']) ? $secret['.id'] : 'Secret tanpa nama');
        }
        return ['count' => count($names), 'examples' => array_slice($names, 0, 5)];
    }
    private function normalized(array $row) { return ['name' => $row['name'] ?? '', 'local-address' => $row['local-address'] ?? '', 'remote-address' => $row['remote-address'] ?? '', 'rate-limit' => $row['rate-limit'] ?? '', 'dns-server' => $row['dns-server'] ?? '', 'only-one' => $row['only-one'] ?? 'default', 'change-tcp-mss' => $row['change-tcp-mss'] ?? 'default']; }
    private function changes(array $current, array $data) { $current = $this->normalized($current); $changes = []; foreach ($data as $key => $value) if (trim((string) $current[$key]) !== trim((string) $value)) $changes[$key] = $value; return $changes; }
    private function choice($value) { return in_array($value, ['yes', 'no', 'default'], true) ? $value : 'default'; }
    private function blank() { return ['.id' => '', 'name' => '', 'local-address' => '', 'remote-address' => '', 'rate-limit' => '', 'dns-server' => '8.8.8.8,1.1.1.1', 'only-one' => 'yes', 'change-tcp-mss' => 'yes']; }
    private function fail($message, $url) { $this->session->set_flashdata('error', $message); redirect($url); }
    protected function render($view, array $data = [], $moduleJsload = null) { $data['body_class'] = 'monitoring-page'; parent::render($view, $data, $moduleJsload); }
}
