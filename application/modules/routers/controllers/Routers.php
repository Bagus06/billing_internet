<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Routers extends MY_Controller
{
    protected $permission = 'routers';
    public function __construct()
    {
        parent::__construct();
        $this->load->model('routers/router_model');
        $this->load->model('packages/package_model');
        $this->load->library('Mikrotik_query');
    }

    public function index()
    {
        $this->router_model->encrypt_existing_plain_passwords();

        $this->render('index', [
            'title' => 'Data Mikrotik - ' . app_setting('isp_name', 'ISP Billing'),
            'routers' => $this->router_model->get_all(),
        ]);
    }

    public function create()
    {
        $this->render('form', [
            'title' => 'Tambah Mikrotik - ' . app_setting('isp_name', 'ISP Billing'),
            'mode' => 'create',
            'router' => $this->blankRouter(),
            'action' => site_url('routers/store'),
        ]);
    }

    public function store()
    {
        $this->router_model->insert($this->payload());
        redirect('routers');
    }

    public function edit($id)
    {
        $router = $this->router_model->find($id);

        if (!$router) {
            show_404();
            return;
        }

        $this->render('form', [
            'title' => 'Edit Mikrotik - ' . app_setting('isp_name', 'ISP Billing'),
            'mode' => 'edit',
            'router' => $router,
            'action' => site_url('routers/update/' . $id),
        ]);
    }

    public function update($id)
    {
        $payload = $this->payload();

        if ($payload['password'] === '') {
            unset($payload['password']);
        }

        $this->router_model->update($id, $payload);
        redirect('routers');
    }

    public function delete($id)
    {
        $router = $this->router_model->find($id);
        if (!$router) { show_404(); return; }
        $packageCount = $this->package_model->count_by_router($id);
        if ($packageCount > 0) {
            $this->session->set_flashdata('error', 'Router tidak dapat dihapus karena masih digunakan oleh ' . $packageCount . ' paket internet. Pindahkan paket ke router lain terlebih dahulu.');
            redirect('routers');
            return;
        }
        if (!$this->router_model->delete($id)) {
            $this->session->set_flashdata('error', 'Router gagal dihapus. Konfigurasi jaringan tetap dipertahankan.');
            redirect('routers');
            return;
        }
        $this->session->set_flashdata('success', 'Router berhasil dihapus dari aplikasi. Konfigurasi fisik MikroTik tidak diubah.');
        redirect('routers');
    }

    protected function render($view, array $data = [])
    {
        $data['body_class'] = 'monitoring-page';
        parent::render($view, $data);
    }

    public function status($id)
    {
        $router = $this->router_model->find($id);
        if (!$router) { show_404(); return; }

        $device = ['resource' => [], 'identity' => [], 'routerboard' => [], 'health' => [], 'interfaces' => [], 'files' => [], 'active_sessions' => 0, 'total_secrets' => 0];
        $error = null;
        try {
            $device = $this->mikrotik_query->run($router, function ($api) {
                $resource = $api->getResource();
                $identity = $this->firstApiRow($api->comm('/system/identity/print'));
                $routerboard = $this->firstApiRow($api->comm('/system/routerboard/print'));
                $health = $this->firstApiRow($api->comm('/system/health/print'));
                $interfaces = $this->cleanApiRows($api->getInterfaceStats());
                $files = $this->cleanApiRows($api->comm('/file/print'));
                $sessions = $this->cleanApiRows($api->getActiveSessions());
                $secrets = $this->cleanApiRows($api->getPppSecrets());
                return ['resource' => is_array($resource) ? $resource : [], 'identity' => $identity, 'routerboard' => $routerboard, 'health' => $health, 'interfaces' => $interfaces, 'files' => $files, 'active_sessions' => count($sessions), 'total_secrets' => count($secrets)];
            });
        } catch (Throwable $e) { $error = $e->getMessage(); }

        $this->render('status', ['title' => 'Status Perangkat - ' . $router['name'], 'router' => $router, 'device' => $device, 'connection_error' => $error]);
    }

    public function status_data($id)
    {
        $router = $this->router_model->find($id);
        if (!$router) { $this->statusJson(['success' => false, 'message' => 'Router tidak ditemukan.'], 404); return; }
        try {
            $data = $this->mikrotik_query->run($router, function ($api) {
                $resource = $api->getResource();
                $health = $this->firstApiRow($api->comm('/system/health/print'));
                $interfaces = $this->cleanApiRows($api->getInterfaceStats());
                $sessions = $this->cleanApiRows($api->getActiveSessions());
                $running = 0; $enabled = 0;
                foreach ($interfaces as $interface) { if (($interface['disabled'] ?? 'false') !== 'true') $enabled++; if (($interface['running'] ?? 'false') === 'true') $running++; }
                return ['resource' => is_array($resource) ? $resource : [], 'health' => $health, 'running_interfaces' => $running, 'enabled_interfaces' => $enabled, 'active_sessions' => count($sessions)];
            });
            $this->statusJson(['success' => true, 'data' => $data, 'checked_at' => date('H:i:s')]);
        } catch (Throwable $e) { $this->statusJson(['success' => false, 'message' => $e->getMessage(), 'checked_at' => date('H:i:s')], 503); }
    }

    private function payload()
    {
        return [
            'name' => trim($this->input->post('name', true)),
            'host' => trim($this->input->post('host', true)),
            'port' => (int) $this->input->post('port'),
            'username' => trim($this->input->post('username', true)),
            'password' => (string) $this->input->post('password'),
            'use_ssl' => $this->input->post('use_ssl') ? 1 : 0,
            'timeout' => (int) $this->input->post('timeout'),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
        ];
    }

    private function blankRouter()
    {
        return [
            'name' => '',
            'host' => '',
            'port' => 8728,
            'username' => '',
            'password' => '',
            'use_ssl' => 0,
            'timeout' => 5,
            'is_active' => 1,
        ];
    }

    private function cleanApiRows(array $rows)
    {
        return array_values(array_filter($rows, function ($row) { return is_array($row) && !isset($row['!done']) && !isset($row['!trap']) && !isset($row['!fatal']); }));
    }

    private function firstApiRow(array $rows)
    {
        $rows = $this->cleanApiRows($rows);
        return $rows ? $rows[0] : [];
    }

    private function statusJson(array $payload, $status = 200)
    {
        $this->output->set_status_header($status)->set_content_type('application/json')->set_output(json_encode($payload));
    }
}
