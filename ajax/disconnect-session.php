<?php

require '../classes/Mikrotik.php';

$activeId = $_POST['active_id'];
$routerId = $_POST['router_id'];

$router = [
    'id' => $routerId
];

$mikrotik = new Mikrotik($router);

$result = $mikrotik->disconnectSession($activeId);

echo json_encode([

    'success' => $result,
    'message' => $result
        ? 'User disconnected'
        : 'Disconnect failed'

]);
