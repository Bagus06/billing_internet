<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Health extends CI_Controller
{
    public function index()
    {
        $ok = false;
        try {
            $query = $this->db->query('SELECT 1 AS healthy');
            $ok = $query && (int) $query->row()->healthy === 1;
        } catch (Throwable $e) {
            $ok = false;
        }
        $this->output->set_status_header($ok?200:503)->set_header('Cache-Control: no-store')->set_content_type('application/json')
            ->set_output(json_encode(['status'=>$ok?'ok':'not_ready','time'=>gmdate('c')]));
    }
}
