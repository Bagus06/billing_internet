<?php defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('_zoomtoken')) {
    function _zoomtoken()
    {
        $url = "https://zoom.us/oauth/token?grant_type=account_credentials&account_id=" . getenv('ACCOUNT_ID');
        $headers = [
            "Authorization: Basic " . base64_encode(getenv('CLIENT_ID') . ':' . getenv('CLIENT_SECRET'))
        ];
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}

if (!function_exists('zoom_create')) {
    function zoom_create($topic, $date, $time, $duration, $account = 'me', $waiting_room = false, $password = 'tjb@12')
    {
        $output = [];
        $get_token = _zoomtoken();
        if (!empty($get_token['access_token'])) {
            $token = @$get_token['access_token'];

            if ($account === getenv('EMAIL_ZOOM')) {
                $account = 'me';
            }

            $data = [
                "topic" => $topic,
                "type" => 2,
                "start_time" => date('Y-m-d\TH:i:s', strtotime("$date $time")),
                "duration" => (($duration > 30) ? (($duration < 480) ? $duration : 480) : 30),
                "timezone" => "Asia/Jakarta",
                "password" => $password,
                "settings" => [
                    "waiting_room" => (bool) $waiting_room,
                    "join_before_host" => true,
                    "mute_upon_entry" => true,
                    "participant_video" => false,
                    "host_video" => false,
                    "auto_recording" => "none"
                ]
            ];

            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => "https://api.zoom.us/v2/users/$account/meetings",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer $token",
                    "Content-Type: application/json"
                ],
                CURLOPT_POSTFIELDS => json_encode($data)
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            $output = json_decode($response, true);
        } else {
            $output = false;
        }

        return $output;
    }
}

if (!function_exists('zoom_update')) {
    function zoom_update($zoom_meeting_id, $topic, $date, $time, $duration, $waiting_room = false, $password = 'tjb@12')
    {
        $output = [];
        $get_token = _zoomtoken();
        if (!empty($get_token['access_token'])) {
            $token = @$get_token['access_token'];

            $data = [
                "topic" => $topic,
                "type" => 2,
                "start_time" => date('Y-m-d\TH:i:s', strtotime("$date $time")),
                "duration" => (($duration > 30) ? (($duration < 480) ? $duration : 480) : 30),
                "timezone" => "Asia/Jakarta",
                "password" => $password,
                "settings" => [
                    "waiting_room" => (bool) $waiting_room
                ]
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => "https://api.zoom.us/v2/meetings/$zoom_meeting_id",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => "PATCH",
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer $token",
                    "Content-Type: application/json"
                ],
                CURLOPT_POSTFIELDS => json_encode($data)
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            $output = [
                'success' => $httpCode == 204,
                'http_code' => $httpCode,
                'response' => $response
            ];
        }

        return $output;
    }
}

if (!function_exists('zoom_delete')) {
    function zoom_delete($zoom_meeting_id)
    {
        $output = [];
        $get_token = _zoomtoken();
        if (!empty($get_token['access_token'])) {
            $token = @$get_token['access_token'];

            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => "https://api.zoom.us/v2/meetings/$zoom_meeting_id",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => "DELETE",
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer " . $token,
                    "Content-Type: application/json"
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            $output = [
                'success' => $httpCode == 204,
                'http_code' => $httpCode,
                'response' => $response
            ];
        }

        return $output;
    }
}
