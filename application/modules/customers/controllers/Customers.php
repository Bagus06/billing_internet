<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Customers extends MY_Controller
{
    protected $permission = 'customers';
    public function __construct()
    {
        parent::__construct();
        $this->load->model('customers/customer_model');
        $this->load->model('packages/package_model');
        $this->load->model('routers/router_model');
        $this->load->library('Mikrotik_sync');
        $this->load->library('Mikrotik_query');
    }

    public function index()
    {
        $perPage = max(5, min(100, (int) $this->input->get('per_page') ?: (int) app_setting('default_per_page', 10)));
        $page = max(1, (int) $this->input->get('page') ?: 1);
        $filters = $this->filters();
        $totalRows = $this->customer_model->count_filtered($filters);
        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $this->render('index', [
            'title' => 'Data Pelanggan - ISP BATARA NET',
            'customers' => $this->customer_model->get_paginated($filters, $perPage, $offset),
            'filters' => $filters,
            'page' => $page,
            'per_page' => $perPage,
            'total_rows' => $totalRows,
            'total_pages' => $totalPages,
        ]);
    }

    public function create()
    {
        $this->render('form', [
            'title' => 'Tambah Pelanggan - ISP BATARA NET',
            'mode' => 'create',
            'customer' => $this->blankCustomer(),
            'packages' => $this->package_model->get_all(true),
            'action' => site_url('customers/store'),
        ]);
    }

    public function store()
    {
        try { $payload = $this->payload('LEAD'); }
        catch (InvalidArgumentException $e) { $this->session->set_flashdata('error', $e->getMessage()); redirect('customers/create'); return; }
        $id = $this->customer_model->insert($payload);
        if ($id) {
            $sync = $this->mikrotik_sync->syncCustomer($id);
            $this->setSyncFlash('Pelanggan berhasil ditambahkan.', $sync);
        }
        redirect('customers');
    }

    public function edit($id)
    {
        $customer = $this->customer_model->find($id);

        if (!$customer) {
            show_404();
            return;
        }

        $this->render('form', [
            'title' => 'Edit Pelanggan - ISP BATARA NET',
            'mode' => 'edit',
            'customer' => $customer,
            'packages' => $this->package_model->get_all(true),
            'action' => site_url('customers/update/' . $id),
        ]);
    }

    public function update($id)
    {
        $customer = $this->customer_model->find($id);
        if (!$customer) { show_404(); return; }
        try { $payload = $this->payload($customer['customer_status']); }
        catch (InvalidArgumentException $e) { $this->session->set_flashdata('error', $e->getMessage()); redirect('customers/edit/' . $id); return; }
        $this->customer_model->update($id, $payload);
        $sync = $this->mikrotik_sync->syncCustomer($id);
        $this->setSyncFlash('Pelanggan berhasil diperbarui.', $sync);
        redirect('customers');
    }

    public function delete($id)
    {
        $this->customer_model->delete($id);
        redirect('customers');
    }

    protected function render($view, array $data = [])
    {
        $data['body_class'] = 'monitoring-page';
        parent::render($view, $data);
    }

    private function payload($status)
    {
        $nik = preg_replace('/\D+/', '', (string) $this->input->post('nik', true));
        $name = trim((string) $this->input->post('name', true));
        $name = function_exists('mb_strtoupper') ? mb_strtoupper($name, 'UTF-8') : strtoupper($name);
        $phone = $this->normalizePhone($this->input->post('phone', true));
        $package = $this->package_model->find((int) $this->input->post('package_id'));
        if ($name === '') throw new InvalidArgumentException('Nama pelanggan wajib diisi.');
        if (!preg_match('/^\d{16}$/', $nik)) throw new InvalidArgumentException('NIK wajib terdiri dari tepat 16 digit.');
        if (!preg_match('/^62\d{8,13}$/', $phone)) throw new InvalidArgumentException('Nomor telepon tidak valid. Gunakan nomor Indonesia aktif, contoh 081234567890.');
        if (!$package) throw new InvalidArgumentException('Paket internet wajib dipilih.');
        $ktpPhoto = $this->uploadKtpPhoto();
        $psbDate = $this->input->post('psb_date') ?: null;

        return [
            'customer_code' => $this->generateCustomerCode($nik),
            'name' => $name,
            'phone' => $phone,
            'nik' => $nik,
            'ktp_photo' => $ktpPhoto,
            'address' => trim($this->input->post('address', true)),
            'package_id' => $package ? (int) $package['id'] : null,
            'package_name' => $package ? $package['package_name'] : '',
            'price' => $package ? (float) $package['price'] : 0,
            'psb_date' => $psbDate,
            'group_name' => $this->groupFromPsbDate($psbDate),
            'customer_status' => strtoupper((string) $status),
            'promoter' => trim($this->input->post('promoter', true)),
            'notes' => trim($this->input->post('notes', true)),
        ];
    }

    private function normalizePhone($value)
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        if (strpos($digits, '620') === 0) $digits = '62' . substr($digits, 3);
        elseif (strpos($digits, '0') === 0) $digits = '62' . substr($digits, 1);
        elseif (strpos($digits, '8') === 0) $digits = '62' . $digits;
        return $digits;
    }

    private function normalizePrice($value)
    {
        $value = str_replace(['.', ','], ['', '.'], (string) $value);

        return is_numeric($value) ? (float) $value : 0;
    }

    private function filters()
    {
        $fields = [
            'customer_code',
            'name',
            'phone',
            'package_name',
            'group_name',
            'customer_status',
            'payment_status',
            'promoter',
        ];

        $filters = [];

        foreach ($fields as $field) {
            $filters[$field] = trim($this->input->get($field, true));
        }

        return $filters;
    }

    private function groupFromPsbDate($date)
    {
        if (!$date) {
            return '';
        }

        $day = (int) date('j', strtotime($date));

        return $day >= 16 ? 'Kelompok 2' : 'Kelompok 1';
    }

    private function generateCustomerCode($nik)
    {
        $digits = preg_replace('/\D+/', '', (string) $nik);
        $suffix = substr(str_pad($digits, 6, '0', STR_PAD_LEFT), -6);

        return 'BTN-' . $suffix;
    }

    private function uploadKtpPhoto()
    {
        $existing = trim($this->input->post('existing_ktp_photo', true));

        if (empty($_FILES['ktp_photo']['name'])) {
            return $existing;
        }

        $uploadPath = FCPATH . 'assets/img/ktp/';

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $this->load->library('upload', [
            'upload_path' => $uploadPath,
            'allowed_types' => 'jpg|jpeg|png|webp',
            'max_size' => 4096,
            'encrypt_name' => true,
        ]);

        if (!$this->upload->do_upload('ktp_photo')) {
            return $existing;
        }

        return 'assets/img/ktp/' . $this->upload->data('file_name');
    }

    private function blankCustomer()
    {
        return [
            'customer_code' => '',
            'name' => '',
            'phone' => '',
            'nik' => '',
            'ktp_photo' => '',
            'package_id' => '',
            'address' => '',
            'package_name' => '',
            'price' => 0,
            'psb_date' => date('Y-m-d'),
            'group_name' => $this->groupFromPsbDate(date('Y-m-d')),
            'customer_status' => 'LEAD',
            'payment_status' => 'BELUM BAYAR',
            'promoter' => '',
            'notes' => '',
        ];
    }

    public function toggle_status()
    {
        $id = (int) $this->input->post('customer_id');
        $customer = $this->customer_model->find($id);
        if (!$customer) { $this->json(false, 'Pelanggan tidak ditemukan.'); return; }
        $package = $this->package_model->find((int) $customer['package_id']);
        if (!$package && !empty($customer['package_name'])) $package = $this->package_model->find_by_name($customer['package_name']);
        if (!$package || empty($package['router_id'])) { $this->json(false, 'Paket pelanggan belum memiliki relasi router.'); return; }
        $router = $this->router_model->find((int) $package['router_id']);
        if (!$router || empty($router['is_active'])) { $this->json(false, 'Router pelanggan tidak tersedia atau nonaktif.'); return; }

        $nik = preg_replace('/\D+/', '', (string) $customer['nik']);
        $username = $nik . app_setting('pppoe_username_suffix', '@BATARA.net');
        if ($nik === '') { $this->json(false, 'NIK pelanggan kosong sehingga PPP Secret tidak dapat ditemukan.'); return; }
        $activate = strtoupper((string) $customer['customer_status']) !== 'ACTIVE';

        try {
            $router['ssl'] = !empty($router['use_ssl']);
            $api = $this->mikrotik_query->connect($router);
            $secret = null;
            $secretCreated = false;
            foreach ($api->getPppSecrets() as $row) if (isset($row['name']) && strcasecmp($row['name'], $username) === 0) { $secret = $row; break; }

            if (!$secret || empty($secret['.id'])) {
                if ($activate) {
                    if (empty($package['ppp_profile_name'])) throw new RuntimeException('Profile MikroTik pada paket pelanggan belum diatur.');
                    $password = 'BTN-' . substr($nik, -6);
                    $secretId = $api->createPppSecret(['name' => $username, 'password' => $password, 'service' => 'pppoe', 'profile' => $package['ppp_profile_name'], 'disabled' => 'no']);
                    if (!$secretId) throw new RuntimeException('PPP Secret ' . $username . ' gagal dibuat di MikroTik. Status database tidak diubah.');
                    $secret = ['.id' => $secretId, 'name' => $username]; $secretCreated = true;
                } else {
                if (!$this->customer_model->update($id, ['customer_status' => 'NONACTIVE'])) {
                    throw new RuntimeException('Database pelanggan gagal diperbarui.');
                }
                $this->customer_model->log_status_change($id, (string) $customer['customer_status'], 'NONACTIVE', isset($this->currentUser['id']) ? $this->currentUser['id'] : null);
                $api->close();
                $this->json(true, 'Pelanggan dinonaktifkan di database. PPP Secret tidak ditemukan pada MikroTik.');
                return;
                }
            }

            if ($activate) {
                $success = $secretCreated ? true : $api->setSecretDisabled($secret['.id'], false);
            } else {
                $success = $api->setSecretDisabled($secret['.id'], true);
                if (!$success) throw new RuntimeException('MikroTik gagal menonaktifkan PPP Secret. Status pelanggan di database tidak diubah.');
                foreach ($api->getActiveSessions() as $session) {
                    if (isset($session['name']) && strcasecmp($session['name'], $username) === 0 && !empty($session['.id'])) {
                        $api->disconnectSession($session['.id']);
                    }
                }
            }
            if (!$success) throw new RuntimeException('MikroTik gagal mengaktifkan PPP Secret. Status pelanggan di database tidak diubah.');
            if (!$this->customer_model->update($id, ['customer_status' => $activate ? 'ACTIVE' : 'NONACTIVE'])) {
                $api->setSecretDisabled($secret['.id'], $activate);
                $api->close();
                throw new RuntimeException('Database pelanggan gagal diperbarui; status PPP Secret telah dikembalikan.');
            }
            $this->customer_model->log_status_change($id, (string) $customer['customer_status'], $activate ? 'ACTIVE' : 'NONACTIVE', isset($this->currentUser['id']) ? $this->currentUser['id'] : null);
            $api->close();
            $this->json(true, $activate ? ($secretCreated ? 'Pelanggan diaktifkan. PPP Secret ' . $username . ' berhasil dibuat.' : 'Pelanggan diaktifkan dan PPP Secret telah di-enable.') : 'Pelanggan dinonaktifkan, PPP Secret di-disable, dan sesi aktif diputus.');
        } catch (Throwable $e) { $this->json(false, $e->getMessage()); }
    }

    public function import_spreadsheet()
    {
        if (strtoupper($this->input->method()) !== 'POST') { show_404(); return; }
        try {
            $this->load->library('Customer_spreadsheet_import');
            $r=$this->customer_spreadsheet_import->run();
            $message='Import selesai: '.$r['customers_created'].' pelanggan baru, '.$r['customers_updated'].' pelanggan diperbarui, '.$r['payments_created'].' pembayaran baru, '.$r['payments_skipped'].' pembayaran duplikat dilewati.';
            if ($r['errors']) $message.=' Catatan: '.implode(' | ',array_slice($r['errors'],0,5));
            $this->session->set_flashdata('success',$message);
        } catch (Throwable $e) { $this->session->set_flashdata('error','Import spreadsheet gagal: '.$e->getMessage()); }
        redirect('customers');
    }

    private function setSyncFlash($message, array $sync)
    {
        if ($sync['updated']) $message .= ' PPP Secret mengikuti profile paket.';
        if ($sync['missing']) $message .= ' PPP Secret belum ditemukan di MikroTik.';
        $this->session->set_flashdata('success', $message);
        if (!$sync['success']) $this->session->set_flashdata('error', 'Sinkronisasi MikroTik: ' . implode(' | ', $sync['errors']));
    }

    private function json($success, $message)
    {
        $this->output->set_content_type('application/json')->set_output(json_encode(['success' => (bool) $success, 'message' => $message]));
    }
}
