<?php

require_once 'routeros_api.class.php';

class Mikrotik
{
    private $API;
    private $router;

    public function __construct($router)
    {
        $this->router = $router;

        $this->API = new RouterosAPI();

        $this->API->connect(
            $router['host'],
            $router['username'],
            $router['password']
        );
    }

    public function getActiveSessions()
    {
        return $this->API->comm('/ppp/active/print');
    }

    public function disconnectSession($activeId)
    {
        return $this->API->comm(
            '/ppp/active/remove',
            [
                '.id' => $activeId
            ]
        );
    }
}
