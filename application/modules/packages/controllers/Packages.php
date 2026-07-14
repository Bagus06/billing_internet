<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Packages extends MY_Controller
{
    protected $permission = 'packages';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('packages/package_model');
        $this->load->model('customers/customer_model');
        $this->load->model('routers/router_model');
        $this->load->library('Mikrotik_query');
        $this->load->library('Mikrotik_sync');
    }

    public function index()
    {
        $profileSync = $this->sync_profiles(false);
        $secretSync = $this->sync_secrets(false);
        $syncErrors = array_merge($profileSync['errors'], $secretSync['errors']);
        $syncFailed = !empty($profileSync['failed_items']) || !empty($secretSync['failed_items']);
        $syncMessage = $profileSync['synced'] . ' profile · ' . $secretSync['updated'] . ' secret';
        if ($profileSync['skipped']) $syncMessage .= ' ' . $profileSync['skipped'] . ' paket dilewati.';
        if ($secretSync['missing']) $syncMessage .= ' ' . $secretSync['missing'] . ' Secret tidak ditemukan.';
        $syncStatus = [
            'success' => !$syncFailed,
            'message' => $syncMessage,
            'detail' => $syncErrors ? implode(' | ', $syncErrors) : 'Sinkronisasi otomatis berhasil',
            'profiles' => ['success' => $profileSync['success_items'], 'failed' => $profileSync['failed_items']],
            'secrets' => ['success' => $secretSync['success_items'], 'failed' => $secretSync['failed_items']],
        ];
        $this->render('index', [
            'title' => 'Paket Internet - ' . app_setting('isp_name', 'ISP BATARA NET'),
            'packages' => $this->package_model->get_all(),
            'topbar_elements' => [[
                'view' => 'topbar_sync',
                'data' => ['sync_status' => $syncStatus],
            ]],
        ]);
    }

    public function create()
    {
        $this->form('create', $this->blankPackage(), site_url('packages/store'));
    }

    public function store()
    {
        $input = $this->validatedInput();
        if (!$input) { redirect('packages/create'); return; }

        try {
            $api = $this->connectRouter($input['router']);
            $createdProfileId = null;
            if ($input['profile_mode'] === 'new') {
                if ($this->profileNameExists($api, $input['profile']['name'])) throw new RuntimeException('Nama PPP Profile sudah tersedia pada router tersebut.');
                $profileId = $api->createPppProfile($input['profile']);
                $createdProfileId = $profileId;
                if (!$profileId) throw new RuntimeException('MikroTik tidak mengembalikan key PPP Profile.');
            } else {
                $selected = $this->findProfileById($api->getPppProfiles(), $input['selected_profile_key']);
                if (!$selected) throw new RuntimeException('PPP Profile yang dipilih tidak ditemukan pada router.');
                $profileId = $selected['.id'];
                $profileChanges = $this->profileChanges($selected, $input['profile']);
                if ($profileChanges) {
                    if ($this->profileNameExists($api, $input['profile']['name'], $profileId)) throw new RuntimeException('Nama PPP Profile sudah dipakai profile lain.');
                    if (!$api->updatePppProfile($profileId, $profileChanges)) throw new RuntimeException('Gagal memperbarui PPP Profile MikroTik.');
                } else {
                    $input['profile'] = $this->routerProfileData($selected);
                }
            }

            $payload = $this->packagePayload($input, $profileId);
            if (!$this->package_model->insert($payload)) {
                if ($createdProfileId) $api->deletePppProfile($createdProfileId);
                throw new RuntimeException('Database gagal menyimpan paket; PPP Profile dibatalkan.');
            }
            $api->close();
            $this->session->set_flashdata('success', $input['profile_mode'] === 'new' ? 'Paket dan PPP Profile baru berhasil dibuat.' : 'Paket berhasil direlasikan ke PPP Profile existing.');
            redirect('packages');
        } catch (Throwable $e) {
            $this->fail($e->getMessage(), 'packages/create');
        }
    }

    public function edit($id)
    {
        $package = $this->package_model->find($id);
        if (!$package) { show_404(); return; }
        $this->form('edit', $package, site_url('packages/update/' . $id));
    }

    public function update($id)
    {
        $existing = $this->package_model->find($id);
        if (!$existing) { show_404(); return; }
        $input = $this->validatedInput($existing);
        if (!$input) { redirect('packages/edit/' . $id); return; }

        try {
            $api = $this->connectRouter($input['router']);
            $newProfileId = null;

            if ($input['profile_mode'] === 'new') {
                if ($this->profileNameExists($api, $input['profile']['name'])) throw new RuntimeException('Nama PPP Profile sudah tersedia pada router tujuan.');
                $profileId = $api->createPppProfile($input['profile']);
                $newProfileId = $profileId;
                if (!$profileId) throw new RuntimeException('Gagal membuat PPP Profile pada router tujuan.');
                $verifiedProfile = $this->findProfileById($api->getPppProfiles(), $profileId);
                if (!$verifiedProfile || $this->profileChanged($verifiedProfile, $input['profile'])) throw new RuntimeException('PPP Profile baru dibuat, tetapi hasil verifikasi MikroTik tidak sesuai dengan form.');
            } else {
                $selected = $this->findProfileById($api->getPppProfiles(), $input['selected_profile_key']);
                if (!$selected) throw new RuntimeException('PPP Profile yang dipilih tidak ditemukan pada router.');
                $profileId = $selected['.id'];
                $profileChanges = $this->profileChanges($selected, $input['profile']);
                if ($profileChanges) {
                    if ($this->profileNameExists($api, $input['profile']['name'], $profileId)) throw new RuntimeException('Nama PPP Profile sudah dipakai profile lain.');
                    if (!$api->updatePppProfile($profileId, $profileChanges)) throw new RuntimeException('Gagal memperbarui PPP Profile existing.');
                    $verifiedProfile = $this->findProfileById($api->getPppProfiles(), $profileId);
                    if (!$verifiedProfile || $this->profileChanged($verifiedProfile, $input['profile'])) throw new RuntimeException('MikroTik merespons sukses, tetapi hasil verifikasi profile masih berbeda. Perubahan dibatalkan pada database.');
                } else {
                    $input['profile'] = $this->routerProfileData($selected);
                }
            }

            $payload = $this->packagePayload($input, $profileId);
            if (!$this->package_model->update($id, $payload)) {
                if ($newProfileId) $api->deletePppProfile($newProfileId);
                throw new RuntimeException('Database gagal memperbarui paket.');
            }
            $verifiedPackage = $this->package_model->find($id);
            if (!$verifiedPackage || !$this->packagePayloadMatches($verifiedPackage, $payload)) throw new RuntimeException('Database merespons sukses, tetapi data paket hasil verifikasi belum berubah.');
            $api->close();
            $sync = $this->mikrotik_sync->syncPackageCustomers($id);
            $message = 'Paket dan PPP Profile berhasil diperbarui. ' . $sync['updated'] . ' Secret pelanggan disinkronkan';
            if ($sync['missing']) $message .= ', ' . $sync['missing'] . ' Secret tidak ditemukan';
            $message .= '.';
            $this->session->set_flashdata('success', $message);
            if (!$sync['success']) $this->session->set_flashdata('error', 'Sinkronisasi Secret: ' . implode(' | ', $sync['errors']));
            redirect('packages');
        } catch (Throwable $e) {
            $this->fail($e->getMessage(), 'packages/edit/' . $id);
        }
    }

    public function delete($id)
    {
        $package = $this->package_model->find($id);
        if (!$package) { show_404(); return; }
        $customerCount = $this->customer_model->count_by_package($id, $package['package_name']);
        if ($customerCount > 0) {
            $this->session->set_flashdata('error', 'Paket tidak dapat dihapus karena masih digunakan oleh ' . $customerCount . ' pelanggan. Pindahkan paket pelanggan terlebih dahulu.');
            redirect('packages');
            return;
        }
        if (!$this->package_model->delete($id)) {
            $this->session->set_flashdata('error', 'Paket gagal dihapus dari database. PPP Profile MikroTik tidak diubah.');
            redirect('packages');
            return;
        }
        $this->session->set_flashdata('success', 'Paket berhasil dihapus. PPP Profile pada MikroTik tetap dipertahankan.');
        redirect('packages');
    }

    public function sync_profiles($redirectAfter = true)
    {
        $synced = 0;
        $skipped = 0;
        $errors = [];
        $successItems = [];
        $failedItems = [];
        $profilesByRouter = [];

        foreach ($this->package_model->get_all() as $package) {
            if (empty($package['router_id']) || (empty($package['ppp_profile_key']) && empty($package['ppp_profile_name']))) {
                $skipped++;
                continue;
            }
            try {
                $routerId = (int) $package['router_id'];
                if (!isset($profilesByRouter[$routerId])) {
                    $router = $this->router_model->find($routerId);
                    if (!$router) throw new RuntimeException('Router tidak ditemukan.');
                    $api = $this->connectRouter($router);
                    $profilesByRouter[$routerId] = $this->indexProfiles($api->getPppProfiles());
                    $api->close();
                }
                $profile = $this->findIndexedProfile($profilesByRouter[$routerId], $package['ppp_profile_key'], $package['ppp_profile_name']);
                if (!$profile) { $errors[] = $package['package_name'] . ': profile tidak ditemukan'; $failedItems[] = $package['package_name'] . ' — profile tidak ditemukan'; continue; }
                $this->package_model->update($package['id'], $this->profileDatabaseFields($profile));
                $synced++;
                $successItems[] = $package['package_name'] . ' — ' . $profile['name'];
            } catch (Throwable $e) {
                $errors[] = $package['package_name'] . ': ' . $e->getMessage();
                $failedItems[] = $package['package_name'] . ' — ' . $e->getMessage();
            }
        }

        $result = ['synced' => $synced, 'skipped' => $skipped, 'errors' => $errors, 'success_items' => $successItems, 'failed_items' => $failedItems];
        if (!$redirectAfter) return $result;
        $message = "{$synced} paket berhasil disinkronkan, {$skipped} dilewati.";
        if ($errors) $message .= ' Error: ' . implode(' | ', $errors);
        $this->session->set_flashdata($errors ? 'error' : 'success', $message);
        redirect('packages');
        return $result;
    }

    public function sync_secrets($redirectAfter = true)
    {
        $updated = 0;
        $missing = 0;
        $errors = [];
        $successItems = [];
        $failedItems = [];
        foreach ($this->package_model->get_all() as $package) {
            if (empty($package['router_id']) || empty($package['ppp_profile_name'])) continue;
            $result = $this->mikrotik_sync->syncPackageCustomers($package['id']);
            $updated += $result['updated'];
            $missing += $result['missing'];
            foreach ($result['success_items'] as $item) $successItems[] = $package['package_name'] . ' / ' . $item;
            foreach ($result['errors'] as $error) $errors[] = $package['package_name'] . ': ' . $error;
            foreach ($result['failed_items'] as $item) $failedItems[] = $package['package_name'] . ' / ' . $item;
        }
        $result = ['updated' => $updated, 'missing' => $missing, 'errors' => $errors, 'success_items' => $successItems, 'failed_items' => $failedItems];
        if (!$redirectAfter) return $result;
        $message = "{$updated} PPP Secret disinkronkan, {$missing} tidak ditemukan.";
        $this->session->set_flashdata($errors ? 'error' : 'success', $errors ? $message . ' Error: ' . implode(' | ', $errors) : $message);
        redirect('packages');
        return $result;
    }

    protected function render($view, array $data = [])
    {
        $data['body_class'] = 'monitoring-page';
        parent::render($view, $data);
    }

    private function form($mode, array $package, $action)
    {
        $options = $this->provisioningOptions();
        $this->render('form', [
            'title' => ($mode === 'create' ? 'Tambah' : 'Edit') . ' Paket - ' . app_setting('isp_name', 'ISP BATARA NET'),
            'mode' => $mode, 'package' => $package, 'action' => $action,
            'routers' => $options['routers'], 'ip_pools' => $options['pools'], 'ppp_profiles' => $options['profiles'], 'router_errors' => $options['errors'],
        ]);
    }

    private function provisioningOptions()
    {
        $routers = $this->router_model->get_all(true);
        $pools = [];
        $profiles = [];
        $errors = [];
        foreach ($routers as $router) {
            try {
                $api = $this->connectRouter($router);
                foreach ($api->getIpPools() as $pool) {
                    if (isset($pool['!done']) || empty($pool['name'])) continue;
                    $pools[] = ['router_id' => (int) $router['id'], 'name' => $pool['name'], 'ranges' => isset($pool['ranges']) ? $pool['ranges'] : '-'];
                }
                foreach ($api->getPppProfiles() as $profile) {
                    if (isset($profile['!done']) || empty($profile['.id']) || empty($profile['name'])) continue;
                    $profiles[] = array_merge($this->routerProfileData($profile), [
                        'router_id' => (int) $router['id'], 'router_name' => $router['name'], 'profile_key' => $profile['.id'],
                    ]);
                }
                $api->close();
            } catch (Throwable $e) { $errors[] = $router['name'] . ': ' . $e->getMessage(); }
        }
        return ['routers' => $routers, 'pools' => $pools, 'profiles' => $profiles, 'errors' => $errors];
    }

    private function validatedInput($existing = null)
    {
        $router = $this->router_model->find((int) $this->input->post('router_id'));
        $packageName = trim($this->input->post('package_name', true));
        $profileName = trim($this->input->post('ppp_profile_name', true));
        $localAddress = trim($this->input->post('ppp_local_address', true));
        $remoteAddress = trim($this->input->post('ppp_remote_address', true));
        $rateLimit = preg_replace('/\s+/', ' ', trim((string) $this->input->post('ppp_rate_limit', true)));
        $dns = trim($this->input->post('ppp_dns_server', true));
        $onlyOne = $this->choice($this->input->post('ppp_only_one'), ['yes', 'no', 'default'], 'yes');
        $tcpMss = $this->choice($this->input->post('ppp_change_tcp_mss'), ['yes', 'no', 'default'], 'yes');
        $profileSelection = (string) $this->input->post('profile_selection');
        $profileMode = $profileSelection === '__new__' ? 'new' : 'existing';
        $validateProfileFields = true;

        $errors = [];
        if (!$router || empty($router['is_active'])) $errors[] = 'Router aktif wajib dipilih.';
        if ($packageName === '') $errors[] = 'Nama paket wajib diisi.';
        if ($profileMode === 'existing' && $profileSelection === '') $errors[] = 'Pilih PPP Profile existing atau Create New.';
        if ($validateProfileFields && !preg_match('/^[A-Za-z0-9_.@ -]{1,64}$/', $profileName)) $errors[] = 'Nama profile hanya boleh berisi huruf, angka, spasi, titik, garis bawah, @, dan tanda minus.';
        if ($validateProfileFields && $localAddress !== '' && !filter_var($localAddress, FILTER_VALIDATE_IP)) $errors[] = 'Local address harus berupa alamat IP yang valid.';
        if ($validateProfileFields && $router && !$this->poolExists($router, $remoteAddress)) $errors[] = 'Remote address harus memilih IP Pool yang tersedia pada router.';
        if ($validateProfileFields && !$this->validRateLimit($rateLimit)) $errors[] = 'Format Rate Limit MikroTik tidak valid. Contoh: 15M/15M atau 35M/35M 40M/40M 26250K/26250K 23/23 8 4375K/4375K.';
        if ($validateProfileFields && $dns !== '') foreach (preg_split('/\s*,\s*/', $dns) as $ip) if (!filter_var($ip, FILTER_VALIDATE_IP)) $errors[] = 'DNS server harus berupa IP valid yang dipisahkan koma.';
        if ($errors) { $this->session->set_flashdata('error', implode(' ', array_unique($errors))); return null; }

        return [
            'router' => $router,
            'package_name' => $packageName,
            'price' => $this->normalizePrice($this->input->post('price', true)),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
            'notes' => trim($this->input->post('notes', true)),
            'profile_mode' => $profileMode, 'selected_profile_key' => $profileMode === 'existing' ? $profileSelection : null,
            'profile' => [
                'name' => $profileName, 'local-address' => $localAddress, 'remote-address' => $remoteAddress,
                'rate-limit' => $rateLimit, 'dns-server' => $dns,
                'only-one' => $onlyOne, 'change-tcp-mss' => $tcpMss,
            ],
        ];
    }

    private function packagePayload(array $input, $profileId)
    {
        return [
            'package_name' => $input['package_name'], 'router_id' => (int) $input['router']['id'],
            'ppp_profile_key' => $profileId, 'ppp_profile_name' => $input['profile']['name'],
            'ppp_local_address' => $input['profile']['local-address'], 'ppp_remote_address' => $input['profile']['remote-address'],
            'ppp_rate_limit' => $input['profile']['rate-limit'], 'ppp_dns_server' => $input['profile']['dns-server'],
            'ppp_only_one' => $input['profile']['only-one'], 'ppp_change_tcp_mss' => $input['profile']['change-tcp-mss'],
            'price' => $input['price'], 'is_active' => $input['is_active'], 'notes' => $input['notes'],
        ];
    }

    private function poolExists(array $router, $name)
    {
        if ($name === '') return false;
        try {
            $api = $this->connectRouter($router);
            foreach ($api->getIpPools() as $pool) if (isset($pool['name']) && $pool['name'] === $name) { $api->close(); return true; }
            $api->close();
        } catch (Throwable $e) { return false; }
        return false;
    }

    private function profileNameExists(Mikrotik_api $api, $name, $ignoreId = null)
    {
        foreach ($api->getPppProfiles() as $profile) {
            if (!isset($profile['name']) || strcasecmp($profile['name'], $name) !== 0) continue;
            if ($ignoreId !== null && isset($profile['.id']) && $profile['.id'] === $ignoreId) continue;
            return true;
        }
        return false;
    }

    private function findProfileById(array $profiles, $profileId)
    {
        foreach ($profiles as $profile) if (isset($profile['.id']) && hash_equals((string) $profile['.id'], (string) $profileId)) return $profile;
        return null;
    }

    private function routerProfileData(array $profile)
    {
        return [
            'name' => $profile['name'],
            'local-address' => isset($profile['local-address']) ? $profile['local-address'] : '',
            'remote-address' => isset($profile['remote-address']) ? $profile['remote-address'] : '',
            'rate-limit' => isset($profile['rate-limit']) ? $profile['rate-limit'] : '',
            'dns-server' => isset($profile['dns-server']) ? $profile['dns-server'] : '',
            'only-one' => isset($profile['only-one']) ? $profile['only-one'] : 'default',
            'change-tcp-mss' => isset($profile['change-tcp-mss']) ? $profile['change-tcp-mss'] : 'default',
        ];
    }

    private function profileChanged(array $current, array $submitted)
    {
        return !empty($this->profileChanges($current, $submitted));
    }

    private function profileChanges(array $current, array $submitted)
    {
        $currentData = $this->routerProfileData($current);
        $changes = [];
        foreach ($submitted as $key => $value) {
            $submittedValue = trim((string) $value);
            $currentValue = trim((string) (isset($currentData[$key]) ? $currentData[$key] : ''));
            if ($submittedValue !== $currentValue) $changes[$key] = $value;
        }
        return $changes;
    }

    private function packagePayloadMatches(array $stored, array $payload)
    {
        foreach ($payload as $key => $value) {
            if (!array_key_exists($key, $stored)) return false;
            if ($key === 'price') {
                if (abs((float) $stored[$key] - (float) $value) > 0.001) return false;
                continue;
            }
            if ((string) $stored[$key] !== (string) $value) return false;
        }
        return true;
    }

    private function validRateLimit($value)
    {
        if ($value === '') return false;
        $parts = explode(' ', $value);
        if (count($parts) > 6) return false;
        $ratePair = '/^\d+[kKmMgG](?:\/\d+[kKmMgG])?$/';
        $timePair = '/^\d+(?:ms|s|m|h|d|w)?(?:\/\d+(?:ms|s|m|h|d|w)?)?$/i';
        if (!preg_match($ratePair, $parts[0])) return false;
        if (isset($parts[1]) && !preg_match($ratePair, $parts[1])) return false;
        if (isset($parts[2]) && !preg_match($ratePair, $parts[2])) return false;
        if (isset($parts[3]) && !preg_match($timePair, $parts[3])) return false;
        if (isset($parts[4]) && (!ctype_digit($parts[4]) || (int) $parts[4] < 1 || (int) $parts[4] > 8)) return false;
        return !isset($parts[5]) || (bool) preg_match($ratePair, $parts[5]);
    }

    private function indexProfiles(array $profiles)
    {
        $indexed = ['id' => [], 'name' => []];
        foreach ($profiles as $profile) {
            if (isset($profile['!done']) || empty($profile['name'])) continue;
            if (!empty($profile['.id'])) $indexed['id'][$profile['.id']] = $profile;
            $indexed['name'][strtolower($profile['name'])] = $profile;
        }
        return $indexed;
    }

    private function findIndexedProfile(array $indexed, $key, $name)
    {
        if ($key && isset($indexed['id'][$key])) return $indexed['id'][$key];
        $normalizedName = strtolower((string) $name);
        return isset($indexed['name'][$normalizedName]) ? $indexed['name'][$normalizedName] : null;
    }

    private function profileDatabaseFields(array $profile)
    {
        return [
            'ppp_profile_key' => isset($profile['.id']) ? $profile['.id'] : null,
            'ppp_profile_name' => $profile['name'],
            'ppp_local_address' => isset($profile['local-address']) ? $profile['local-address'] : '',
            'ppp_remote_address' => isset($profile['remote-address']) ? $profile['remote-address'] : '',
            'ppp_rate_limit' => isset($profile['rate-limit']) ? $profile['rate-limit'] : '',
            'ppp_dns_server' => isset($profile['dns-server']) ? $profile['dns-server'] : '',
            'ppp_only_one' => isset($profile['only-one']) ? $profile['only-one'] : 'default',
            'ppp_change_tcp_mss' => isset($profile['change-tcp-mss']) ? $profile['change-tcp-mss'] : 'default',
        ];
    }

    private function connectRouter(array $router)
    {
        return $this->mikrotik_query->connect($router);
    }

    private function fail($message, $redirect)
    {
        $this->session->set_flashdata('error', $message);
        redirect($redirect);
    }

    private function choice($value, array $allowed, $default) { return in_array($value, $allowed, true) ? $value : $default; }
    private function normalizePrice($value) { $value = str_replace(['.', ','], ['', '.'], (string) $value); return is_numeric($value) ? (float) $value : 0; }

    private function blankPackage()
    {
        return [
            'package_name' => '', 'router_id' => '', 'router_name' => '', 'ppp_profile_key' => '', 'ppp_profile_name' => '',
            'ppp_local_address' => '', 'ppp_remote_address' => '', 'ppp_rate_limit' => '', 'ppp_dns_server' => '8.8.8.8,1.1.1.1',
            'ppp_only_one' => 'yes', 'ppp_change_tcp_mss' => 'yes', 'price' => 0, 'is_active' => 1, 'notes' => '',
        ];
    }
}
