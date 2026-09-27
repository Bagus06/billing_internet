<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Customer_isolation
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('customers/customer_model');
        $this->CI->load->model('packages/package_model');
        $this->CI->load->model('routers/router_model');
        $this->CI->load->library('Mikrotik_query');
    }

    public function run($source = 'cron', $date = null)
    {
        if (!$this->enabled()) return $this->result(0, 0, 0, ['Sistem isolir belum diaktifkan.']);
        return $this->runDueCustomers($source, $date);
    }

    public function runManual($date = null)
    {
        return $this->runDueCustomers('manual_bulk', $date);
    }

    private function runDueCustomers($source, $date = null)
    {
        $today = $date ? new DateTimeImmutable($date) : new DateTimeImmutable('today');
        $customers = $this->CI->customer_model->isolation_candidates((int) $today->format('n'), (int) $today->format('Y'));
        $isolated = 0; $skipped = 0; $failed = 0; $messages = [];
        foreach ($customers as $customer) {
            if (!$this->isIsolationDue($customer, $today)) { $skipped++; continue; }
            if (!empty($customer['is_isolated'])) { $skipped++; continue; }
            $outcome = $this->isolate($customer, $source);
            if ($outcome['success']) $isolated++; else $failed++;
            $messages[] = $outcome['message'];
        }
        return $this->result($isolated, $skipped, $failed, $messages);
    }

    public function restoreCustomer($customerId, $source = 'payment')
    {
        return $this->withCustomerLock((int) $customerId, function () use ($customerId, $source) {
            return $this->restoreUnlocked((int) $customerId, $source);
        });
    }

    public function restoreAllManual()
    {
        $customers = $this->CI->customer_model->isolation_restore_candidates();
        $restored = 0; $skipped = 0; $failed = 0; $messages = [];

        foreach ($customers as $customer) {
            $outcome = $this->restoreCustomer((int) $customer['id'], 'manual_bulk');
            if (!empty($outcome['success']) && !empty($outcome['changed'])) {
                $restored++;
            } elseif (!empty($outcome['success'])) {
                $skipped++;
            } else {
                $failed++;
            }
            if (isset($outcome['message'])) $messages[] = $outcome['message'];
        }

        return [
            'success' => $failed === 0,
            'restored' => $restored,
            'skipped' => $skipped,
            'failed' => $failed,
            'messages' => $messages,
        ];
    }

    public function preview($date = null)
    {
        $today = $date ? new DateTimeImmutable($date) : new DateTimeImmutable('today');
        $customers = $this->CI->customer_model->isolation_candidates((int) $today->format('n'), (int) $today->format('Y'));
        $eligible = [];
        foreach ($customers as $customer) {
            $isolationDate = $this->isolationDate($customer, $today);
            if ($today < $isolationDate) continue;
            $eligible[] = [
                'id' => (int) $customer['id'],
                'customer_code' => (string) $customer['customer_code'],
                'name' => (string) $customer['name'],
                'group_name' => (string) $customer['group_name'],
                'isolation_date' => $isolationDate->format('Y-m-d'),
            ];
        }
        return [
            'success' => true,
            'date' => $today->format('Y-m-d'),
            'candidate_count' => count($customers),
            'eligible_count' => count($eligible),
            'eligible' => $eligible,
        ];
    }

    private function restoreUnlocked($customerId, $source)
    {
        $customer = $this->CI->customer_model->find((int) $customerId);
        if (!$customer) return ['success' => false, 'message' => 'Pelanggan tidak ditemukan.'];
        if (empty($customer['is_isolated'])) return ['success' => true, 'changed' => false, 'message' => 'Pelanggan tidak sedang diisolir.'];
        $context = $this->context($customer);
        if (!$context['success']) return $this->failure($customer, 'RESTORE', '', '', $context['message'], $source);
        $targetProfile = (string) $context['package']['ppp_profile_name'];
        try {
            return $this->CI->mikrotik_query->run($context['router'], function ($api) use ($customer, $targetProfile, $source) {
                if (!$this->profileExists($api->getPppProfiles(), $targetProfile)) {
                    return $this->failure($customer, 'RESTORE', '', $targetProfile, 'Profile paket ' . $targetProfile . ' tidak ditemukan pada MikroTik.', $source);
                }
                $secret = $this->findSecret($api->getPppSecrets(), $customer);
                if (!$secret) return $this->failure($customer, 'RESTORE', '', $targetProfile, 'PPP Secret pelanggan tidak ditemukan.', $source);
                $from = isset($secret['profile']) ? (string) $secret['profile'] : '';
                if (!$api->setSecretProfile($secret['.id'], $targetProfile)) return $this->failure($customer, 'RESTORE', $from, $targetProfile, 'MikroTik menolak pemulihan profile.', $source, $secret['name']);
                $disconnected = $this->disconnect($api, $secret['name']);
                if (!$this->CI->customer_model->update_isolation($customer['id'], false, null, null, null)) {
                    $rolledBack = $from !== '' && $api->setSecretProfile($secret['.id'], $from);
                    if ($rolledBack) $this->disconnect($api, $secret['name']);
                    $detail = $rolledBack ? 'Profile MikroTik berhasil dikembalikan ke ' . $from . '.' : 'Rollback profile MikroTik gagal; perlu pemeriksaan manual.';
                    return $this->failure($customer, 'RESTORE', $from, $targetProfile, 'Database gagal diperbarui. ' . $detail, $source, $secret['name']);
                }
                $message = 'PPP Secret ' . $secret['name'] . ' dipulihkan ke profile ' . $targetProfile . '.' . ($disconnected ? ' Sesi aktif diputus.' : ' Tidak ada sesi aktif yang perlu diputus.');
                $this->log($customer['id'], 'RESTORE', 'SUCCESS', $secret['name'], $from, $targetProfile, $message, $source);
                return ['success' => true, 'changed' => true, 'disconnected' => $disconnected, 'message' => $message];
            });
        } catch (Throwable $e) { return $this->failure($customer, 'RESTORE', '', $targetProfile, $e->getMessage(), $source); }
    }

    public function isolateCustomer($customerId, $source = 'manual')
    {
        $customer = $this->CI->customer_model->find((int) $customerId);
        if (!$customer) return ['success' => false, 'message' => 'Pelanggan tidak ditemukan.'];
        if (strtoupper((string) $customer['customer_status']) !== 'ACTIVE') return ['success' => false, 'message' => 'Hanya pelanggan ACTIVE yang dapat diisolir.'];
        if (!empty($customer['is_isolated'])) return ['success' => true, 'changed' => false, 'message' => 'Pelanggan sudah berada pada status ISOLIR.'];
        $result = $this->isolate($customer, $source);
        $result['changed'] = !empty($result['success']);
        return $result;
    }

    private function isolate(array $customer, $source)
    {
        return $this->withCustomerLock((int) $customer['id'], function () use ($customer, $source) {
            return $this->isolateUnlocked($customer, $source);
        });
    }

    private function isolateUnlocked(array $customer, $source)
    {
        if (!empty($customer['is_isolated'])) return ['success' => true, 'message' => $customer['name'] . ' sudah diisolir.'];
        $context = $this->context($customer);
        if (!$context['success']) return $this->failure($customer, 'ISOLATE', '', $this->profile(), $context['message'], $source);
        $target = $this->profile();
        $normalProfile = (string) $context['package']['ppp_profile_name'];
        try {
            return $this->CI->mikrotik_query->run($context['router'], function ($api) use ($customer, $target, $normalProfile, $source) {
                $profiles = $api->getPppProfiles();
                if (!$this->profileExists($profiles, $target)) return $this->failure($customer, 'ISOLATE', '', $target, 'Profile ' . $target . ' belum tersedia pada MikroTik.', $source);
                $secret = $this->findSecret($api->getPppSecrets(), $customer);
                if (!$secret) return $this->failure($customer, 'ISOLATE', '', $target, 'PPP Secret pelanggan tidak ditemukan.', $source);
                $from = isset($secret['profile']) ? (string) $secret['profile'] : '';
                $originalProfile = strcasecmp($from, $target) === 0 ? $normalProfile : $from;
                if (!$api->setSecretProfile($secret['.id'], $target)) return $this->failure($customer, 'ISOLATE', $from, $target, 'MikroTik menolak perubahan ke profile isolir.', $source, $secret['name']);
                $disconnected = $this->disconnect($api, $secret['name']);
                if (!$this->CI->customer_model->update_isolation($customer['id'], true, date('Y-m-d H:i:s'), $originalProfile, null)) {
                    $rolledBack = $from !== '' && $api->setSecretProfile($secret['.id'], $from);
                    if ($rolledBack) $this->disconnect($api, $secret['name']);
                    $detail = $rolledBack ? 'Profile MikroTik berhasil dikembalikan ke ' . $from . '.' : 'Rollback profile MikroTik gagal; perlu pemeriksaan manual.';
                    return $this->failure($customer, 'ISOLATE', $from, $target, 'Database gagal diperbarui. ' . $detail, $source, $secret['name']);
                }
                $message = $customer['name'] . ' berhasil diisolir menggunakan profile ' . $target . '.' . ($disconnected ? ' Sesi aktif diputus.' : ' Tidak ada sesi aktif yang perlu diputus.');
                $this->log($customer['id'], 'ISOLATE', 'SUCCESS', $secret['name'], $originalProfile, $target, $message, $source);
                return ['success' => true, 'disconnected' => $disconnected, 'message' => $message];
            });
        } catch (Throwable $e) { return $this->failure($customer, 'ISOLATE', '', $target, $e->getMessage(), $source); }
    }

    private function context(array $customer)
    {
        $package = $this->CI->package_model->find((int) $customer['package_id']);
        if (!$package || empty($package['router_id']) || empty($package['ppp_profile_name'])) return ['success' => false, 'message' => 'Relasi paket, router, atau PPP Profile belum lengkap.'];
        $router = $this->CI->router_model->find((int) $package['router_id']);
        if (!$router || empty($router['is_active'])) return ['success' => false, 'message' => 'Router pelanggan tidak tersedia atau nonaktif.'];
        return ['success' => true, 'package' => $package, 'router' => $router];
    }

    private function findSecret(array $rows, array $customer)
    {
        $nik = preg_replace('/\D+/', '', (string) $customer['nik']);
        $expected = $nik . app_setting('pppoe_username_suffix', '@BATARA.net');
        foreach ($rows as $row) if (!empty($row['.id']) && isset($row['name']) && strcasecmp($row['name'], $expected) === 0) return $row;
        return null;
    }

    private function profileExists(array $profiles, $name)
    {
        foreach ($profiles as $profile) {
            if (isset($profile['name']) && strcasecmp((string) $profile['name'], (string) $name) === 0) return true;
        }
        return false;
    }

    private function disconnect($api, $name)
    {
        $disconnected = false;
        foreach ($api->getActiveSessions() as $session) {
            if (!empty($session['.id']) && isset($session['name']) && strcasecmp($session['name'], $name) === 0) {
                if ($api->disconnectSession($session['.id'])) $disconnected = true;
            }
        }
        return $disconnected;
    }

    private function isIsolationDue(array $customer, DateTimeImmutable $today)
    {
        return $today >= $this->isolationDate($customer, $today);
    }

    private function isolationDate(array $customer, DateTimeImmutable $today)
    {
        $group = strtolower(trim((string) $customer['group_name']));
        $isGroupTwo = preg_match('/(?:^|\D)2(?:\D|$)/', $group) === 1;
        $dueDay = $isGroupTwo ? (int) app_setting('isolation_group_2_due_day', 25) : (int) app_setting('isolation_group_1_due_day', 10);
        $grace = max(0, (int) app_setting('isolation_grace_days', 5));
        $lastDay = (int) $today->format('t');
        $dueDay = max(1, min($lastDay, $dueDay));
        return $today->setDate((int) $today->format('Y'), (int) $today->format('n'), $dueDay)->modify('+' . $grace . ' days');
    }

    private function withCustomerLock($customerId, callable $callback)
    {
        $lockName = 'billing_isolation_customer_' . (int) $customerId;
        $query = $this->CI->db->query('SELECT GET_LOCK(?, 0) AS acquired', [$lockName]);
        if (!$query || (int) $query->row()->acquired !== 1) {
            return ['success' => false, 'changed' => false, 'message' => 'Proses pelanggan sedang dijalankan oleh request lain. Silakan coba kembali.'];
        }
        try {
            return $callback();
        } finally {
            $this->CI->db->query('SELECT RELEASE_LOCK(?)', [$lockName]);
        }
    }

    private function failure(array $customer, $action, $from, $to, $message, $source, $secret = '')
    {
        $this->CI->customer_model->set_isolation_error($customer['id'], $message);
        $this->log($customer['id'], $action, 'FAILED', $secret, $from, $to, $message, $source);
        return ['success' => false, 'message' => $customer['name'] . ': ' . $message];
    }

    private function log($customerId, $action, $status, $secret, $from, $to, $message, $source)
    {
        if (!$this->CI->db->table_exists('customer_isolation_logs')) {
            log_message('error', 'Tabel customer_isolation_logs tidak tersedia. ' . $action . '/' . $status . ': ' . $message);
            return false;
        }
        return $this->CI->db->insert('customer_isolation_logs', ['customer_id' => (int) $customerId, 'action' => $action, 'status' => $status, 'secret_name' => $secret ?: null, 'from_profile' => $from ?: null, 'to_profile' => $to ?: null, 'message' => $message, 'source' => $source, 'created_at' => date('Y-m-d H:i:s')]);
    }

    private function enabled() { return in_array(strtolower((string) app_setting('isolation_enabled', '0')), ['1', 'true', 'yes', 'on'], true); }
    private function profile() { return trim((string) app_setting('isolation_profile_name', 'ISOLIR')) ?: 'ISOLIR'; }
    private function result($isolated, $skipped, $failed, array $messages) { return ['success' => $failed === 0, 'isolated' => $isolated, 'skipped' => $skipped, 'failed' => $failed, 'messages' => $messages]; }
}
