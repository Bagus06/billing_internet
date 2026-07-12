<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Language extends CI_Controller
{
    public function switch_to($language)
    {
        if (in_array($language, ['id', 'en'], true)) $this->session->set_userdata('app_language', $language);
        $target = $this->input->get('return', true);
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        if (!$target || strpos($target, base_url()) !== 0) $target = strpos($referrer, base_url()) === 0 ? $referrer : base_url();
        redirect($target);
    }
}
