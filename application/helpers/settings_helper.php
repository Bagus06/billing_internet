<?php
defined('BASEPATH') or exit('No direct script access allowed');

function app_setting($key, $default = null)
{
    static $settings = null;
    if ($settings === null) {
        $CI =& get_instance();
        $settings = [];
        if (isset($CI->db) && $CI->db->table_exists('app_settings')) {
            foreach ($CI->db->get('app_settings')->result_array() as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }
    return array_key_exists($key, $settings) ? $settings[$key] : $default;
}

function app_language()
{
    $CI =& get_instance();
    $sessionLanguage = isset($CI->session) ? $CI->session->userdata('app_language') : null;
    $authUser = isset($CI->session) ? $CI->session->userdata('auth_user') : null;
    $userLanguage = is_array($authUser) && isset($authUser['preferred_language']) ? $authUser['preferred_language'] : null;
    $language = $sessionLanguage ?: $userLanguage ?: app_setting('default_language', 'id');
    return in_array($language, ['id', 'en'], true) ? $language : 'id';
}
