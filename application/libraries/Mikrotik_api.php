<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mikrotik_api
{
    private $socket;
    private $connected = false;

    public function connect(array $router)
    {
        $host = $router['host'];
        $port = isset($router['port']) ? (int) $router['port'] : 8728;
        $timeout = isset($router['timeout']) ? (int) $router['timeout'] : 5;
        $transport = !empty($router['ssl']) ? 'ssl://' : '';

        $this->socket = @fsockopen($transport . $host, $port, $errno, $errstr, $timeout);

        if (!$this->socket) {
            throw new RuntimeException('Tidak bisa konek ke router: ' . $errstr);
        }

        stream_set_timeout($this->socket, $timeout);
        $this->connected = true;

        $login = $this->comm('/login', [
            '=name' => $router['username'],
            '=password' => $router['password'],
        ]);

        if (!$this->isDone($login)) {
            throw new RuntimeException('Login API Mikrotik gagal.');
        }

        return true;
    }

    public function getResource()
    {
        return $this->firstRow($this->comm('/system/resource/print'));
    }

    public function getActiveSessions()
    {
        return $this->comm('/ppp/active/print');
    }

    public function disconnectSession($activeId)
    {
        return $this->isDone($this->comm('/ppp/active/remove', [
            '=.id' => $activeId,
        ]));
    }

    public function comm($command, array $params = [])
    {
        $this->writeSentence(array_merge([$command], $params));

        return $this->readResponse();
    }

    public function close()
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }

        $this->connected = false;
    }

    private function writeSentence(array $words)
    {
        foreach ($words as $key => $value) {
            $word = is_int($key) ? $value : $key . '=' . $value;
            $this->writeWord($word);
        }

        $this->writeWord('');
    }

    private function writeWord($word)
    {
        fwrite($this->socket, $this->encodeLength(strlen($word)) . $word);
    }

    private function readResponse()
    {
        $response = [];
        $row = [];

        while ($this->connected) {
            $word = $this->readWord();

            if ($word === false) {
                break;
            }

            if ($word === '') {
                if (!empty($row)) {
                    $response[] = $row;

                    if (isset($row['!done'])) {
                        break;
                    }

                    $row = [];
                }

                continue;
            }

            if ($word[0] === '!') {
                $row[$word] = true;
                continue;
            }

            if ($word[0] === '=') {
                $parts = explode('=', substr($word, 1), 2);
                $row[$parts[0]] = isset($parts[1]) ? $parts[1] : '';
            }
        }

        return $response;
    }

    private function readWord()
    {
        $length = $this->decodeLength();

        if ($length === false) {
            return false;
        }

        if ($length === 0) {
            return '';
        }

        $word = '';

        while (strlen($word) < $length) {
            $part = fread($this->socket, $length - strlen($word));

            if ($part === false || $part === '') {
                $meta = stream_get_meta_data($this->socket);
                return !empty($meta['timed_out']) ? false : $word;
            }

            $word .= $part;
        }

        return $word;
    }

    private function encodeLength($length)
    {
        if ($length < 0x80) {
            return chr($length);
        }

        if ($length < 0x4000) {
            return chr(($length >> 8) | 0x80) . chr($length & 0xFF);
        }

        if ($length < 0x200000) {
            return chr(($length >> 16) | 0xC0) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }

        if ($length < 0x10000000) {
            return chr(($length >> 24) | 0xE0) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }

        return chr(0xF0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
    }

    private function decodeLength()
    {
        $char = fread($this->socket, 1);

        if ($char === false || $char === '') {
            $meta = stream_get_meta_data($this->socket);
            return !empty($meta['timed_out']) ? false : 0;
        }

        $length = ord($char);

        if (($length & 0x80) === 0x00) {
            return $length;
        }

        if (($length & 0xC0) === 0x80) {
            return (($length & ~0xC0) << 8) + ord(fread($this->socket, 1));
        }

        if (($length & 0xE0) === 0xC0) {
            return (($length & ~0xE0) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        }

        if (($length & 0xF0) === 0xE0) {
            return (($length & ~0xF0) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
        }

        return (ord(fread($this->socket, 1)) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
    }

    private function isDone(array $response)
    {
        foreach ($response as $row) {
            if (isset($row['!done'])) {
                return true;
            }
        }

        return false;
    }

    private function firstRow(array $response)
    {
        foreach ($response as $row) {
            if (!isset($row['!done'])) {
                return $row;
            }
        }

        return [];
    }
}
