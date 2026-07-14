<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Language extends CI_Controller
{
    public function switch_to($language)
    {
        if (in_array($language, ['id', 'en'], true)) {
            $this->session->set_userdata('app_language', $language);
            $authUser = $this->session->userdata('auth_user');
            if ($authUser && !empty($authUser['id']) && $this->db->field_exists('preferred_language', 'users')) {
                $saved = $this->db->where('id', (int) $authUser['id'])->update('users', [
                    'preferred_language' => $language,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                if ($saved) {
                    $authUser['preferred_language'] = $language;
                    $this->session->set_userdata('auth_user', $authUser);
                }
            }
        }
        $target = $this->input->get('return', true);
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        if (!$target || strpos($target, base_url()) !== 0) $target = strpos($referrer, base_url()) === 0 ? $referrer : base_url();
        redirect($target);
    }
}
