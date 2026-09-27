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
        $this->load->library('Customer_isolation');
        $this->load->library('App_storage');
        $this->load->library('Olt_snmp');
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

        $customers = $this->customer_model->get_paginated($filters, $perPage, $offset);
        $this->render('index', [
            'title' => 'Data Pelanggan - ' . app_setting('isp_name', 'ISP Billing'),
            'customers' => $this->withArrears($customers),
            'filters' => $filters,
            'page' => $page,
            'per_page' => $perPage,
            'total_rows' => $totalRows,
            'total_pages' => $totalPages,
            'filter_options' => $this->customer_model->filter_options(),
        ]);
    }

    public function create()
    {
        $ontDevices = $this->olt_snmp->devices();
        $this->render('form', [
            'title' => 'Tambah Pelanggan - ' . app_setting('isp_name', 'ISP Billing'),
            'mode' => 'create',
            'customer' => $this->blankCustomer(),
            'packages' => $this->package_model->get_all(true),
            'action' => site_url('customers/store'),
            'ont_devices' => $ontDevices,
            'ont_error' => $ontDevices ? '' : $this->olt_snmp->lastError(),
            'ont_pairing' => null,
        ]);
    }

    public function store()
    {
        try { $payload = $this->payload('LEAD'); }
        catch (InvalidArgumentException $e) { $this->session->set_flashdata('error', $e->getMessage()); redirect('customers/create'); return; }
        $id = $this->customer_model->insert($payload);
        if ($id) {
            try { $this->saveOntPairing($id); }
            catch (InvalidArgumentException $e) { $this->session->set_flashdata('error', 'Pelanggan tersimpan, tetapi pairing ONT gagal: ' . $e->getMessage()); redirect('customers/edit/' . $id); return; }
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

        $ontDevices = $this->olt_snmp->devices();
        $this->render('form', [
            'title' => 'Edit Pelanggan - ' . app_setting('isp_name', 'ISP Billing'),
            'mode' => 'edit',
            'customer' => $customer,
            'packages' => $this->package_model->get_all(true),
            'action' => site_url('customers/update/' . $id),
            'ont_devices' => $ontDevices,
            'ont_error' => $ontDevices ? '' : $this->olt_snmp->lastError(),
            'ont_pairing' => $this->customer_model->ont_pairing($id),
        ]);
    }

    public function update($id)
    {
        $customer = $this->customer_model->find($id);
        if (!$customer) { show_404(); return; }
        try { $payload = $this->payload($customer['customer_status'], $customer); }
        catch (InvalidArgumentException $e) { $this->session->set_flashdata('error', $e->getMessage()); redirect('customers/edit/' . $id); return; }
        $this->customer_model->update($id, $payload);
        try { $this->saveOntPairing($id); }
        catch (InvalidArgumentException $e) { $this->session->set_flashdata('error', $e->getMessage()); redirect('customers/edit/' . $id); return; }
        $sync = $this->mikrotik_sync->syncCustomer($id);
        $this->setSyncFlash('Pelanggan berhasil diperbarui.', $sync);
        redirect('customers');
    }

    public function delete($id)
    {
        $customer = $this->customer_model->find($id);
        if (!$customer) { show_404(); return; }
        $dependencies = $this->customer_model->deletion_dependencies($id);
        if ($dependencies['total'] > 0) {
            $details = [];
            if ($dependencies['payments']) $details[] = $dependencies['payments'] . ' pembayaran';
            if ($dependencies['status_history']) $details[] = $dependencies['status_history'] . ' histori status';
            if ($dependencies['isolation_logs']) $details[] = $dependencies['isolation_logs'] . ' log isolir';
            $this->session->set_flashdata('error', 'Pelanggan tidak dapat dihapus karena memiliki ' . implode(', ', $details) . '. Nonaktifkan pelanggan untuk mempertahankan histori billing.');
            redirect('customers');
            return;
        }
        if (!$this->customer_model->delete($id)) {
            $this->session->set_flashdata('error', 'Pelanggan gagal dihapus. Data billing tetap aman.');
            redirect('customers');
            return;
        }
        $this->session->set_flashdata('success', 'Pelanggan berhasil dihapus.');
        redirect('customers');
    }

    public function ktp($id)
    {
        $customer = $this->customer_model->find((int) $id);
        if (!$customer || empty($customer['ktp_photo'])) { show_404(); return; }
        try {
            $path = $this->app_storage->resolvePrivate($customer['ktp_photo']);
        } catch (Throwable $e) {
            show_404();
            return;
        }
        $mime = function_exists('mime_content_type') ? mime_content_type($path) : 'application/octet-stream';
        if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) { show_404(); return; }
        $this->output
            ->set_header('Cache-Control: private, no-store, max-age=0')
            ->set_header('Pragma: no-cache')
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_header('Content-Disposition: inline; filename="ktp-' . (int) $customer['id'] . '.' . pathinfo($path, PATHINFO_EXTENSION) . '"')
            ->set_content_type($mime)
            ->set_output(file_get_contents($path));
    }

    protected function render($view, array $data = [])
    {
        $data['body_class'] = 'monitoring-page';
        parent::render($view, $data);
    }

    private function payload($status, array $existingCustomer = [])
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
        $ktpPhoto = $this->uploadKtpPhoto(isset($existingCustomer['ktp_photo']) ? $existingCustomer['ktp_photo'] : '');
        $psbDate = $this->input->post('psb_date') ?: null;
        $latitudeInput = trim((string) $this->input->post('latitude', true));
        $longitudeInput = trim((string) $this->input->post('longitude', true));
        if (($latitudeInput === '') xor ($longitudeInput === '')) throw new InvalidArgumentException('Latitude dan longitude harus dipilih bersamaan dari peta.');
        $latitude = $latitudeInput === '' ? null : filter_var($latitudeInput, FILTER_VALIDATE_FLOAT);
        $longitude = $longitudeInput === '' ? null : filter_var($longitudeInput, FILTER_VALIDATE_FLOAT);
        if ($latitudeInput !== '' && ($latitude === false || $latitude < -90 || $latitude > 90)) throw new InvalidArgumentException('Nilai latitude tidak valid. Pilih ulang titik pada peta.');
        if ($longitudeInput !== '' && ($longitude === false || $longitude < -180 || $longitude > 180)) throw new InvalidArgumentException('Nilai longitude tidak valid. Pilih ulang titik pada peta.');

        return [
            'customer_code' => $this->generateCustomerCode($nik),
            'name' => $name,
            'phone' => $phone,
            'nik' => $nik,
            'ktp_photo' => $ktpPhoto,
            'address' => trim($this->input->post('address', true)),
            'latitude' => $latitude === null ? null : number_format((float) $latitude, 7, '.', ''),
            'longitude' => $longitude === null ? null : number_format((float) $longitude, 7, '.', ''),
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

    private function withArrears(array $customers)
    {
        if (!$customers) return $customers;
        $paid = [];
        foreach ($this->customer_model->payment_periods_for_customers($customers) as $row) {
            $period = sprintf('%04d-%02d', (int) $row['bill_year'], (int) $row['bill_month']);
            if (!empty($row['customer_id'])) $paid['id:' . (int) $row['customer_id']][$period] = true;
            if (!empty($row['customer_code'])) $paid['code:' . $row['customer_code']][$period] = true;
        }
        $today = new DateTimeImmutable('today');
        foreach ($customers as &$customer) {
            $customer['arrears_count'] = 0; $customer['arrears_amount'] = 0; $customer['arrears_periods'] = '';
            if (strtoupper((string) $customer['customer_status']) === 'LEAD') continue;
            $startValue = !empty($customer['psb_date']) ? $customer['psb_date'] : ($customer['created_at'] ?? '');
            try { $start = (new DateTimeImmutable($startValue ?: 'now'))->modify('first day of this month'); }
            catch (Throwable $e) { continue; }
            $deadline = $this->billingDeadline($customer, $today);
            $lastDue = $today >= $deadline ? $today->modify('first day of this month') : $today->modify('first day of previous month');
            if ($start > $lastDue) continue;
            $missing = []; $cursor = $start; $guard = 0;
            while ($cursor <= $lastDue && $guard++ < 240) {
                $period = $cursor->format('Y-m');
                $isPaid = !empty($paid['id:' . (int) $customer['id']][$period]) || !empty($paid['code:' . $customer['customer_code']][$period]);
                if (!$isPaid) $missing[] = $period;
                $cursor = $cursor->modify('+1 month');
            }
            $customer['arrears_count'] = count($missing);
            $customer['arrears_amount'] = count($missing) * (float) $customer['price'];
            $customer['arrears_periods'] = implode(', ', array_map(function ($period) {
                $date = DateTimeImmutable::createFromFormat('!Y-m', $period);
                return $date ? $date->format('m/Y') : $period;
            }, $missing));
        }
        unset($customer);
        return $customers;
    }

    private function billingDeadline(array $customer, DateTimeImmutable $month)
    {
        $groupTwo = preg_match('/(?:^|\D)2(?:\D|$)/', strtolower((string) $customer['group_name'])) === 1;
        $dueDay = $groupTwo ? (int) app_setting('isolation_group_2_due_day', 25) : (int) app_setting('isolation_group_1_due_day', 10);
        $grace = max(0, (int) app_setting('isolation_grace_days', 5));
        $dueDay = max(1, min((int) $month->format('t'), $dueDay));
        return $month->setDate((int) $month->format('Y'), (int) $month->format('n'), $dueDay)->modify('+' . $grace . ' days');
    }

    private function saveOntPairing($customerId)
    {
        $serial = strtoupper(trim((string) $this->input->post('ont_serial_number', true)));
        if ($serial === '') { $this->customer_model->save_ont_pairing($customerId, null); return; }
        foreach ($this->olt_snmp->devices() as $device) {
            if (strtoupper($device['serial_number']) === $serial) { $this->customer_model->save_ont_pairing($customerId, $device); return; }
        }
        throw new InvalidArgumentException('ONT tidak ditemukan pada hasil discovery OLT. Muat ulang form lalu pilih kembali ONT.');
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
            'isolation_status',
            'arrears_status',
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

    private function uploadKtpPhoto($existing = '')
    {
        if (empty($_FILES['ktp_photo']['name'])) {
            return trim((string) $existing);
        }
        return $this->app_storage->storePrivateImage('ktp_photo', 'customers/ktp', 4096);
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
            'latitude' => null,
            'longitude' => null,
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

    public function isolate()
    {
        if (strtoupper($this->input->method()) !== 'POST') { show_404(); return; }
        if (!$this->db->field_exists('is_isolated', 'customers')) { $this->json(false, 'Jalankan file SQL sistem isolir terlebih dahulu.'); return; }
        $result = $this->customer_isolation->isolateCustomer((int) $this->input->post('customer_id'), 'manual');
        $this->json(!empty($result['success']), isset($result['message']) ? $result['message'] : 'Proses isolir selesai.', $result);
    }

    public function isolate_due()
    {
        if (strtoupper($this->input->method()) !== 'POST') { show_404(); return; }
        if (!$this->db->field_exists('is_isolated', 'customers')) { $this->json(false, 'Jalankan migration sistem isolir terlebih dahulu.'); return; }

        $result = $this->customer_isolation->runManual();
        $isolated = isset($result['isolated']) ? (int) $result['isolated'] : 0;
        $skipped = isset($result['skipped']) ? (int) $result['skipped'] : 0;
        $failed = isset($result['failed']) ? (int) $result['failed'] : 0;
        if ($isolated === 0 && $failed === 0) {
            $message = 'Tidak ada pelanggan jatuh tempo yang perlu diisolir saat ini.';
        } else {
            $message = 'Isolir pelanggan selesai. Berhasil: ' . $isolated . ', gagal: ' . $failed . ', dilewati: ' . $skipped . '.';
        }

        $this->json($failed === 0, $message, $result);
    }

    public function restore_isolation()
    {
        if (strtoupper($this->input->method()) !== 'POST') { show_404(); return; }
        if (!$this->db->field_exists('is_isolated', 'customers')) { $this->json(false, 'Jalankan file SQL sistem isolir terlebih dahulu.'); return; }
        $result = $this->customer_isolation->restoreCustomer((int) $this->input->post('customer_id'), 'manual');
        $this->json(!empty($result['success']), isset($result['message']) ? $result['message'] : 'Pemulihan isolir selesai.', $result);
    }

    public function restore_all_isolation()
    {
        if (strtoupper($this->input->method()) !== 'POST') { show_404(); return; }
        if (!$this->db->field_exists('is_isolated', 'customers')) { $this->json(false, 'Jalankan migration sistem isolir terlebih dahulu.'); return; }

        $result = $this->customer_isolation->restoreAllManual();
        $restored = isset($result['restored']) ? (int) $result['restored'] : 0;
        $skipped = isset($result['skipped']) ? (int) $result['skipped'] : 0;
        $failed = isset($result['failed']) ? (int) $result['failed'] : 0;
        if ($restored === 0 && $failed === 0) {
            $message = 'Tidak ada pelanggan berstatus isolir yang perlu dipulihkan.';
        } else {
            $message = 'Pemulihan isolir selesai. Berhasil: ' . $restored . ', gagal: ' . $failed . ', dilewati: ' . $skipped . '.';
        }

        $this->json($failed === 0, $message, $result);
    }

    public function remote_ont()
    {
        if (strtoupper($this->input->method()) !== 'POST') { show_404(); return; }
        $customer = $this->customer_model->find((int) $this->input->post('customer_id'));
        if (!$customer) { $this->json(false, 'Pelanggan tidak ditemukan.'); return; }
        $package = $this->package_model->find((int) $customer['package_id']);
        if (!$package && !empty($customer['package_name'])) $package = $this->package_model->find_by_name($customer['package_name']);
        if (!$package || empty($package['router_id'])) { $this->json(false, 'Paket pelanggan belum memiliki relasi router.'); return; }
        $router = $this->router_model->find((int) $package['router_id']);
        if (!$router || empty($router['is_active'])) { $this->json(false, 'Router pelanggan tidak tersedia atau nonaktif.'); return; }
        $nik = preg_replace('/\D+/', '', (string) $customer['nik']);
        if ($nik === '') { $this->json(false, 'NIK pelanggan kosong sehingga username PPPoE tidak dapat dibentuk.'); return; }
        $username = $nik . app_setting('pppoe_username_suffix', '@BATARA.net');
        $router['ssl'] = !empty($router['use_ssl']);
        try {
            $remote = $this->mikrotik_query->prepareOntRemote($router, $username);
            $this->json(true, 'NAT Forward-ONT diarahkan ke ' . $remote['local_ip'] . '.', ['remote' => $remote]);
        } catch (Throwable $e) {
            $this->json(false, $e->getMessage());
        }
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

    private function json($success, $message, array $extra = [])
    {
        $this->output->set_content_type('application/json')->set_output(json_encode(array_merge(['success' => (bool) $success, 'message' => $message], $extra)));
    }
}
