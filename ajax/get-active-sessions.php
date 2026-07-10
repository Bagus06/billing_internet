<?php

require '../classes/Mikrotik.php';

$routers = [

    [
        'id' => 1,
        'name' => 'CORE-01',
        'ip' => '10.10.10.1'
    ],

    [
        'id' => 2,
        'name' => 'POP-01',
        'ip' => '10.10.20.1'
    ]

];

$data = [];

foreach ($routers as $router) {

    $mikrotik = new Mikrotik($router);

    $sessions = $mikrotik->getActiveSessions();

    foreach ($sessions as $row) {

        $data[] = [

            'username' => $row['name'],
            'address' => $row['address'] ?? '-',
            'caller_id' => $row['caller-id'] ?? '-',
            'uptime' => $row['uptime'] ?? '-',
            'router' => $router['name'],

            'status' =>
            '<span class="badge badge-success">
                    Online
                </span>',

            'action' => '

                <button
                    class="btn btn-danger btn-sm btn-disconnect"
                    data-id="' . $row['.id'] . '"
                    data-router="' . $router['id'] . '"
                >
                    Disconnect
                </button>

            '
        ];
    }
}

echo json_encode([
    'data' => $data
]);
