<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mikrotik_sync
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('routers/router_model');
        $this->CI->load->model('packages/package_model');
        $this->CI->load->model('customers/customer_model');
        $this->CI->load->library('Mikrotik_query');
    }

    public function syncPackageCustomers($packageId)
    {
        $package = $this->CI->package_model->find($packageId);
        if (!$package || empty($package['router_id']) || empty($package['ppp_profile_name'])) {
            return $this->result(0, 0, ['Paket belum memiliki relasi router dan PPP Profile.'], [], ['Paket — relasi router/profile belum lengkap']);
        }
        return $this->syncCustomers($package, $this->CI->customer_model->get_by_package($packageId));
    }

    public function syncCustomer($customerId)
    {
        $customer = $this->CI->customer_model->find($customerId);
        if (!$customer || empty($customer['package_id'])) return $this->result(0, 0, ['Pelanggan atau paket tidak ditemukan.']);
        $package = $this->CI->package_model->find($customer['package_id']);
        if (!$package || empty($package['router_id']) || empty($package['ppp_profile_name'])) {
            return $this->result(0, 0, ['Paket pelanggan belum memiliki PPP Profile.']);
        }
        return $this->syncCustomers($package, [$customer]);
    }

    private function syncCustomers(array $package, array $customers)
    {
        $router = $this->CI->router_model->find($package['router_id']);
        if (!$router) return $this->result(0, count($customers), ['Router paket tidak ditemukan.'], [], ['Router — data router paket tidak ditemukan']);
        $updated = 0;
        $missing = 0;
        $errors = [];
        $successItems = [];
        $failedItems = [];

        try {
            $api = $this->CI->mikrotik_query->connect($router);
            $secrets = $this->indexSecrets($api->getPppSecrets());

            foreach ($customers as $customer) {
                $nik = preg_replace('/\D+/', '', isset($customer['nik']) ? $customer['nik'] : '');
                $customerName = isset($customer['name']) ? $customer['name'] : 'Pelanggan';
                $expectedSecret = ($nik !== '' ? $nik : 'NIK kosong') . app_setting('pppoe_username_suffix', '@BATARA.net');
                if ($nik === '' || !isset($secrets[$nik])) {
                    $missing++;
                    $failedItems[] = $customerName . ' — Secret ' . $expectedSecret . ' tidak ditemukan di MikroTik';
                    continue;
                }
                $secret = $secrets[$nik];
                $secretName = isset($secret['name']) ? $secret['name'] : $expectedSecret;
                if (isset($secret['profile']) && $secret['profile'] === $package['ppp_profile_name']) { $updated++; $successItems[] = $secretName . ' — sudah memakai ' . $package['ppp_profile_name']; continue; }
                if ($api->setSecretProfile($secret['.id'], $package['ppp_profile_name'])) { $updated++; $successItems[] = $secretName . ' — diubah ke ' . $package['ppp_profile_name']; }
                else { $errors[] = $customerName . ': gagal mengubah profile Secret.'; $failedItems[] = $secretName . ' — MikroTik menolak perubahan ke ' . $package['ppp_profile_name']; }
            }
            $api->close();
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
            $failedItems[] = 'Koneksi/API MikroTik — ' . $e->getMessage();
        }

        return $this->result($updated, $missing, $errors, $successItems, $failedItems);
    }

    private function indexSecrets(array $rows)
    {
        $suffix = app_setting('pppoe_username_suffix', '@BATARA.net');
        $result = [];
        foreach ($rows as $secret) {
            if (isset($secret['!done']) || empty($secret['.id']) || empty($secret['name'])) continue;
            if (!preg_match('/' . preg_quote($suffix, '/') . '$/i', $secret['name'])) continue;
            $prefix = explode('@', $secret['name'], 2)[0];
            $nik = preg_replace('/\D+/', '', $prefix);
            if ($nik !== '') $result[$nik] = $secret;
        }
        return $result;
    }

    private function result($updated, $missing, array $errors, array $successItems = [], array $failedItems = [])
    {
        return ['success' => empty($errors) && empty($failedItems), 'updated' => $updated, 'missing' => $missing, 'errors' => $errors, 'success_items' => $successItems, 'failed_items' => $failedItems];
    }
}
