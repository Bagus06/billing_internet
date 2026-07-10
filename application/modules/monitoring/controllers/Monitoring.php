<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Monitoring extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('routers/router_model');
        require_once APPPATH . 'libraries/Mikrotik_api.php';
    }

    public function index($routerId = null)
    {
        $router = $routerId ? $this->router_model->find($routerId) : null;
        $data = [
            'title' => ($router ? $router['name'] . ' - ' : '') . 'Monitoring Mikrotik - ISP BATARA NET',
            'body_class' => 'monitoring-page',
            'router' => $router,
        ];

        $this->load->view('../../views/layout/header', $data);
        $this->load->view('index');
        $this->load->view('../../views/layout/footer', [
            'module_jsload' => APPPATH . 'modules/monitoring/jsload.php',
        ]);
    }

    public function router($routerId)
    {
        $this->index($routerId);
    }

    public function summary($routerId = null)
    {
        $data = $this->collectMonitoringData(false, $routerId);

        $this->json([
            'success' => $data['success'],
            'routers_online' => $data['routers_online'],
            'active_sessions' => $data['active_sessions'],
            'routers' => $data['routers'],
            'errors' => $data['errors'],
        ]);
    }

    public function sessions($routerId = null)
    {
        $data = $this->collectMonitoringData(true, $routerId);

        $this->json([
            'success' => $data['success'],
            'routers_online' => $data['routers_online'],
            'active_sessions' => $data['active_sessions'],
            'routers' => $data['routers'],
            'rows' => $data['rows'],
            'errors' => $data['errors'],
        ]);
    }

    public function disconnect()
    {
        $routerId = (int) $this->input->post('router_id');
        $activeId = $this->input->post('active_id', true);
        $router = $this->findRouter($routerId);

        if (!$router || !$activeId) {
            $this->json([
                'success' => false,
                'message' => 'Router atau active session tidak valid.',
            ]);
            return;
        }

        try {
            $api = $this->connectRouter($router);
            $success = $api->disconnectSession($activeId);
            $api->close();

            $this->json([
                'success' => $success,
                'message' => $success ? 'User disconnected.' : 'Disconnect gagal.',
            ]);
        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function collectMonitoringData($includeRows, $routerId = null)
    {
        $rows = [];
        $routers = [];
        $errors = [];
        $routersOnline = 0;
        $activeSessions = 0;

        foreach ($this->routers($routerId) as $router) {
            try {
                $api = $this->connectRouter($router);
                $resource = $api->getResource();
                $sessions = $api->getActiveSessions();
                $api->close();

                $sessionRows = $this->filterRows($sessions);
                $routersOnline++;
                $activeSessions += count($sessionRows);

                $routers[] = [
                    'id' => $router['id'],
                    'name' => $router['name'],
                    'host' => $router['host'],
                    'status' => 'online',
                    'uptime' => isset($resource['uptime']) ? $resource['uptime'] : '-',
                    'cpu_load' => isset($resource['cpu-load']) ? $resource['cpu-load'] : '-',
                    'free_memory' => isset($resource['free-memory']) ? $resource['free-memory'] : '-',
                ];

                if ($includeRows) {
                    foreach ($sessionRows as $session) {
                        $rows[] = [
                            'username' => isset($session['name']) ? $session['name'] : '-',
                            'address' => isset($session['address']) ? $session['address'] : '-',
                            'caller_id' => isset($session['caller-id']) ? $session['caller-id'] : '-',
                            'uptime' => isset($session['uptime']) ? $session['uptime'] : '-',
                            'router' => $router['name'],
                            'router_id' => $router['id'],
                            'active_id' => isset($session['.id']) ? $session['.id'] : '',
                            'status' => 'Online',
                        ];
                    }
                }
            } catch (Throwable $e) {
                $errors[] = $router['name'] . ': ' . $e->getMessage();
                $routers[] = [
                    'id' => $router['id'],
                    'name' => $router['name'],
                    'host' => $router['host'],
                    'status' => 'offline',
                    'uptime' => '-',
                    'cpu_load' => '-',
                    'free_memory' => '-',
                ];
            }
        }

        return [
            'success' => empty($errors),
            'routers_online' => $routersOnline,
            'active_sessions' => $activeSessions,
            'routers' => $routers,
            'rows' => $rows,
            'errors' => $errors,
        ];
    }

    private function routers($routerId = null)
    {
        if ($routerId) {
            $router = $this->router_model->find($routerId);
            return $router ? [$this->normalizeRouter($router)] : [];
        }

        return array_map([$this, 'normalizeRouter'], $this->router_model->get_all(true));
    }

    private function findRouter($routerId)
    {
        foreach ($this->routers() as $router) {
            if ((int) $router['id'] === $routerId) {
                return $router;
            }
        }

        return null;
    }

    private function normalizeRouter(array $router)
    {
        $router['ssl'] = !empty($router['use_ssl']);
        $router['timeout'] = isset($router['timeout']) ? (int) $router['timeout'] : 5;

        return $router;
    }

    private function connectRouter(array $router)
    {
        $api = new Mikrotik_api();
        $api->connect($router);

        return $api;
    }

    private function filterRows(array $rows)
    {
        return array_values(array_filter($rows, function ($row) {
            return !isset($row['!done']);
        }));
    }

    private function json(array $payload)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }
}
