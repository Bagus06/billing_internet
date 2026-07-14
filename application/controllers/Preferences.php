<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Preferences extends CI_Controller
{
    public function theme()
    {
        if (strtoupper($this->input->method()) !== 'POST') { show_404(); return; }
        $theme = trim((string) $this->input->post('theme', true));
        if (!in_array($theme, ['light', 'dark'], true)) { $this->json(false, 'Tema tidak valid.'); return; }

        $authUser = $this->session->userdata('auth_user');
        if (!$authUser || empty($authUser['id'])) { $this->json(true, 'Preferensi tema disimpan pada perangkat.', ['persisted' => false]); return; }
        if (!$this->db->field_exists('preferred_theme', 'users')) { $this->json(false, 'Kolom preferensi user belum tersedia. Jalankan file ALTER database terbaru.'); return; }

        $saved = $this->db->where('id', (int) $authUser['id'])->update('users', [
            'preferred_theme' => $theme,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if ($saved) {
            $authUser['preferred_theme'] = $theme;
            $this->session->set_userdata('auth_user', $authUser);
        }
        $this->json($saved, $saved ? 'Preferensi tema akun disimpan.' : 'Preferensi tema gagal disimpan.', ['persisted' => (bool) $saved]);
    }

    private function json($success, $message, array $extra = [])
    {
        $this->output->set_content_type('application/json')->set_output(json_encode(array_merge([
            'success' => (bool) $success,
            'message' => (string) $message,
        ], $extra)));
    }
}
