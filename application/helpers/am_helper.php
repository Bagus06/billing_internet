<?php if (!defined("BASEPATH")) exit("No direct script access allowed");

if (!function_exists('am_history')) {
    function am_history($assets_id = '', $inv_id = '', $colum = '', $before_value = '')
    {
        $inputing = FALSE;
        $CI = &get_instance();
        $data = [
            'user_id' => get_user()['id'],
            'assets_id' => @$assets_id,
            'inv_id' => @$inv_id,
            'colum' => $colum,
            'before_value' => $before_value,
        ];

        if ($CI->db->insert('am_history', $data)) {
            $inputing = TRUE;
        }
        return $inputing;
    }
}
