<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Monitoring extends MY_Controller
{
    protected $permission = 'monitoring';
    public function __construct()
    {
        parent::__construct();
        $this->load->model('routers/router_model');
        $this->load->model('customers/customer_model');
        $this->load->library('Olt_snmp');
        $this->load->library('Mikrotik_query');
    }

    public function index($routerId = null)
    {
        $router = $routerId ? $this->router_model->find($routerId) : null;
        $data = [
            'title' => ($router ? $router['name'] . ' - ' : '') . 'Monitoring Mikrotik - ISP BATARA NET',
            'body_class' => 'monitoring-page',
            'router' => $router,
        ];

        $this->render('index', $data);
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
            'offline_sessions' => $data['offline_sessions'],
            'total_secrets' => $data['total_secrets'],
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
            'offline_sessions' => $data['offline_sessions'],
            'total_secrets' => $data['total_secrets'],
            'routers' => $data['routers'],
            'rows' => $data['rows'],
            'errors' => $data['errors'],
        ]);
    }

    public function disconnect()
    {
        $routerId = (int) $this->input->post('router_id');
        $activeId = $this->input->post('active_id', true);
        $secretId = $this->input->post('secret_id', true);
        $router = $this->findRouter($routerId);

        if (!$router || !$secretId) {
            $this->json([
                'success' => false,
                'message' => 'Router atau active session tidak valid.',
            ]);
            return;
        }

        try {
            $api = $this->connectRouter($router);
            $secretDisabled = $api->setSecretDisabled($secretId, true);
            $sessionRemoved = !$activeId || ($secretDisabled && $api->disconnectSession($activeId));
            $api->close();

            $success = $secretDisabled && $sessionRemoved;

            $this->json([
                'success' => $success,
                'message' => $success ? 'Secret dinonaktifkan; sesi aktif diputus jika tersedia.' : 'Gagal menonaktifkan Secret atau memutus koneksi.',
            ]);
        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function connect()
    {
        $routerId = (int) $this->input->post('router_id');
        $secretId = $this->input->post('secret_id', true);
        $router = $this->findRouter($routerId);

        if (!$router || !$secretId) {
            $this->json(['success' => false, 'message' => 'Router atau PPP Secret tidak valid.']);
            return;
        }

        try {
            $api = $this->connectRouter($router);
            $success = $api->setSecretDisabled($secretId, false);
            $api->close();
            $this->json([
                'success' => $success,
                'message' => $success ? 'PPP Secret diaktifkan. Menunggu modem melakukan koneksi.' : 'Gagal mengaktifkan PPP Secret.',
            ]);
        } catch (Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function traffic($routerId = null)
    {
        $rows = [];
        $errors = [];
        foreach ($this->routers($routerId) as $router) {
            try {
                $api = $this->connectRouter($router);
                $interfaces = $api->getInterfaceStats();
                $api->close();
                foreach ($this->trafficByUsername($this->filterRows($interfaces)) as $username => $traffic) {
                    $rows[] = [
                        'router_id' => (int) $router['id'],
                        'username' => $username,
                        'bytes_in' => $traffic['rx'],
                        'bytes_out' => $traffic['tx'],
                    ];
                }
            } catch (Throwable $e) {
                $errors[] = $router['name'] . ': ' . $e->getMessage();
            }
        }
        $this->json(['success' => empty($errors), 'rows' => $rows, 'errors' => $errors]);
    }

    private function collectMonitoringData($includeRows, $routerId = null)
    {
        $rows = [];
        $routers = [];
        $errors = [];
        $routersOnline = 0;
        $activeSessions = 0;
        $offlineSessions = 0;
        $totalSecrets = 0;
        $customersByNik = $this->customersByNik();
        $opticalByCustomer = $this->olt_snmp->opticalByCustomerName();

        foreach ($this->routers($routerId) as $router) {
            try {
                $api = $this->connectRouter($router);
                $resource = $api->getResource();
                $sessions = $api->getActiveSessions();
                $secrets = $api->getPppSecrets();
                $interfaces = $api->getInterfaceStats();
                $api->close();

                $sessionRows = $this->filterRows($sessions);
                $suffix = app_setting('pppoe_username_suffix', '@BATARA.net');
                $secretRows = array_values(array_filter($this->filterRows($secrets), function ($secret) use ($suffix) {
                    return isset($secret['name']) && preg_match('/' . preg_quote($suffix, '/') . '$/i', trim($secret['name']));
                }));
                $activeByName = [];
                $trafficByName = $this->trafficByUsername($this->filterRows($interfaces));
                foreach ($sessionRows as $session) {
                    if (!empty($session['name'])) {
                        $activeByName[strtolower($session['name'])] = $session;
                    }
                }
                $routersOnline++;
                $totalSecrets += count($secretRows);
                $onlineSecretCount = 0;
                foreach ($secretRows as $secret) {
                    if (isset($secret['name'], $activeByName[strtolower($secret['name'])])) {
                        $onlineSecretCount++;
                    }
                }
                $activeSessions += $onlineSecretCount;
                $offlineSessions += max(0, count($secretRows) - $onlineSecretCount);

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
                    foreach ($secretRows as $secret) {
                        $username = isset($secret['name']) ? $secret['name'] : '-';
                        $session = isset($activeByName[strtolower($username)]) ? $activeByName[strtolower($username)] : null;
                        $isOnline = $session !== null;
                        $nik = $this->usernamePrefix($username);
                        $customer = isset($customersByNik[$nik]) ? $customersByNik[$nik] : null;
                        $customerNameKey = $customer ? $this->olt_snmp->normalizeName($customer['name']) : '';
                        $optical = isset($opticalByCustomer[$customerNameKey]) ? $opticalByCustomer[$customerNameKey] : null;
                        $rows[] = [
                            'username' => $username,
                            'address' => $isOnline && isset($session['address']) ? $session['address'] : (isset($secret['remote-address']) ? $secret['remote-address'] : '-'),
                            'local_address' => isset($secret['local-address']) ? $secret['local-address'] : '-',
                            'caller_id' => $isOnline && isset($session['caller-id']) ? $session['caller-id'] : (isset($secret['caller-id']) ? $secret['caller-id'] : '-'),
                            'uptime' => $isOnline && isset($session['uptime']) ? $session['uptime'] : '-',
                            'bytes_in' => $isOnline && isset($trafficByName[strtolower($username)]) ? $trafficByName[strtolower($username)]['rx'] : 0,
                            'bytes_out' => $isOnline && isset($trafficByName[strtolower($username)]) ? $trafficByName[strtolower($username)]['tx'] : 0,
                            'last_off' => isset($secret['last-logged-out']) && $secret['last-logged-out'] !== '' ? $secret['last-logged-out'] : '-',
                            'profile' => isset($secret['profile']) ? $secret['profile'] : '-',
                            'service' => isset($secret['service']) ? $secret['service'] : 'pppoe',
                            'comment' => isset($secret['comment']) ? $secret['comment'] : '-',
                            'customer_nik' => $nik ?: '-',
                            'customer_id' => $customer ? (int) $customer['id'] : null,
                            'customer_code' => $customer ? $customer['customer_code'] : '-',
                            'customer_name' => $customer ? $customer['name'] : '-',
                            'customer_phone' => $customer ? $customer['phone'] : '-',
                            'customer_address' => $customer ? $customer['address'] : '-',
                            'customer_package' => $customer ? $customer['package_name'] : '-',
                            'customer_group' => $customer ? $customer['group_name'] : '-',
                            'customer_status' => $customer ? $customer['customer_status'] : '-',
                            'optical_rx' => $optical && $optical['rx'] !== null ? $optical['rx'] : null,
                            'optical_status' => $optical ? $optical['status'] : 'unknown',
                            'ont_name' => $optical ? $optical['ont_name'] : '-',
                            'disabled' => isset($secret['disabled']) && in_array(strtolower((string) $secret['disabled']), ['true', 'yes', '1'], true),
                            'router' => $router['name'],
                            'router_id' => $router['id'],
                            'active_id' => $isOnline && isset($session['.id']) ? $session['.id'] : '',
                            'secret_id' => isset($secret['.id']) ? $secret['.id'] : '',
                            'status' => $isOnline ? 'ON' : 'OFF',
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
            'offline_sessions' => $offlineSessions,
            'total_secrets' => $totalSecrets,
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
        return $this->mikrotik_query->connect($router);
    }

    private function filterRows(array $rows)
    {
        return array_values(array_filter($rows, function ($row) {
            return !isset($row['!done']);
        }));
    }

    private function customersByNik()
    {
        $indexed = [];
        foreach ($this->customer_model->get_all() as $customer) {
            $nik = preg_replace('/\D+/', '', isset($customer['nik']) ? $customer['nik'] : '');
            if ($nik !== '') {
                $indexed[$nik] = $customer;
            }
        }
        return $indexed;
    }

    private function trafficByUsername(array $interfaces)
    {
        $result = [];
        foreach ($interfaces as $interface) {
            if (empty($interface['name'])) continue;
            $name = strtolower(trim($interface['name'], "<> \t\n\r\0\x0B"));
            if (strpos($name, 'pppoe-') === 0) {
                $name = substr($name, 6);
            }
            $result[$name] = [
                'rx' => isset($interface['rx-byte']) ? (int) $interface['rx-byte'] : 0,
                'tx' => isset($interface['tx-byte']) ? (int) $interface['tx-byte'] : 0,
            ];
        }
        return $result;
    }

    private function usernamePrefix($username)
    {
        $prefix = explode('@', (string) $username, 2)[0];
        return preg_replace('/\D+/', '', $prefix);
    }

    private function json(array $payload)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }
}
