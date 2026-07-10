<?php if (!defined("BASEPATH")) exit("No direct script access allowed");

if (!function_exists('checkPermission')) {
    function checkPermission($link = '', $id = '')
    {
        $check = FALSE;
        $CI = &get_instance();
        $CI->db->select('id, publish_for');
        $getLink = $CI->db->get_where('link', ['link' => $link])->row_array();

        $CI->db->select('permission_id');
        $CI->db->from('user');
        $CI->db->where('user.id', $id);
        $getPermission = $CI->db->get()->row_array();

        $getPermission['group'] = [];
        if (!empty($getPermission["permission_id"])) {
            $usr_pmt = json_decode($getPermission["permission_id"], TRUE);

            if (!is_array($usr_pmt)) {
                $usr_pmt = [$usr_pmt];
            }
            if ((json_last_error() == JSON_ERROR_NONE) && !empty($usr_pmt[0])) {
                foreach ($usr_pmt as $key => $value) {
                    $CI->db->select("group");
                    $pmt_group = $CI->db->get_where("permission", ["id" => $value])->row_array();
                    if (!empty($pmt_group)) {
                        $getPermission['group'] = array_merge($getPermission["group"], json_decode($pmt_group["group"]));
                    }
                }
            } elseif (json_last_error() != JSON_ERROR_NONE) {
                $CI->db->select("group");
                $pmt_group = $CI->db->get_where("permission", ["id" => $getPermission["permission_id"]])->row_array();
                if (!empty($pmt_group)) {
                    $getPermission['group'] =  json_decode($pmt_group["group"], TRUE);
                }
            }
        }

        if (empty($getLink)) {
            $check = FALSE;
        } elseif (!empty($getLink)) {
            if (@$getLink['publish_for'] == 1) {
                $check = TRUE;
            } else {
                if (!empty($getPermission['group'])) {
                    if (in_array(@$getLink['id'], $getPermission['group'])) {
                        $check = TRUE;
                    }
                }
            }
        }

        $CI->db->select('*');
        $CI->db->from('user');
        $CI->db->where('user.id', $id);
        $user = $CI->db->get()->row_array();
        if (@($user['type'] == 1000) && !empty($getLink)) {
            $check = TRUE;
        }
        return $check;
    }
}

if (!function_exists('checkGroupPermission')) {
    function checkGroupPermission($group_name = '', $id = '')
    {
        $ret = FALSE;
        $CI = &get_instance();
        $CI->db->select('permission_id');
        $CI->db->from('user');
        $CI->db->where('user.id', $id);
        $get_user_group_permission = $CI->db->get()->row_array();

        if (!empty($get_user_group_permission)) {
            $CI->db->select('title');
            $CI->db->from('permission');
            $CI->db->where(['row_status' => 1, '']);

            $get_group_permission = [];
            $CI->db->where_in('id', json_decode($get_user_group_permission['permission_id']));
            $get_group_permission = $CI->db->get()->result_array();

            $group_permission_array = [];
            if (!empty($get_group_permission)) {
                foreach ($get_group_permission as $key => $value) {
                    $group_permission_array[] = $value['title'];
                }
            }

            if (!empty($group_name)) {
                if (in_array($group_name, $group_permission_array)) {
                    $ret = TRUE;
                }
            }
        }
        return $ret;
    }

    if (!function_exists('listGroupPermission')) {
        function listGroupPermission($id = '')
        {
            $ret = FALSE;
            $CI = &get_instance();
            $CI->db->select('permission_id');
            $CI->db->from('user');
            $CI->db->where('user.id', $id);
            $get_user_group_permission = $CI->db->get()->row_array();

            if (!empty($get_user_group_permission)) {
                $CI->db->select('title');
                $CI->db->from('permission');
                $CI->db->where(['row_status' => 1, '']);

                $get_group_permission = [];
                $CI->db->where_in('id', json_decode($get_user_group_permission['permission_id']));
                $get_group_permission = $CI->db->get()->result_array();

                $group_permission_array = [];
                if (!empty($get_group_permission)) {
                    foreach ($get_group_permission as $key => $value) {
                        $group_permission_array[] = $value['title'];
                    }
                }

                if (!empty($group_permission_array)) {
                    $ret = $group_permission_array;
                }
            }
            return $ret;
        }
    }

    if (!function_exists('list_user_on_group')) {
        function list_user_on_group($group_name = '')
        {
            $ret = false;
            $CI = &get_instance();

            if (!empty($group_name)) {

                $datas = [
                    "select" => "id",
                    "row_status" => 1,
                    "getreturn" => "data",
                    "order_by" => [
                        "column" => "id",
                        "order" => "ASC"
                    ],
                    "limit" => [
                        "length" => 1,
                        "start" => 0
                    ],
                    "whereclause" => "title = '" . $group_name . "'"
                ];
                $permission_id = $CI->permission_model->permission(0, $datas, "GET");

                if (!empty($permission_id['data'])) {
                    $datas = [
                        "select" => "user_id, profile.name, email",
                        "row_status" => 1,
                        "getreturn" => "data",
                        "order_by" => [
                            "column" => "profile.name",
                            "order" => "ASC"
                        ],
                        "limit" => [
                            "length" => -1,
                            "start" => ""
                        ],
                        "whereclause" => "JSON_CONTAINS(permission_id, '\"" . @$permission_id['data'][0]['id'] . "\"','$')"
                    ];
                    $data_user = $CI->user_model->users(0, $datas, "GET");

                    if (!empty($data_user['data'])) {
                        $ret = $data_user['data'];
                    }
                }
            }

            return $ret;
        }
    }
}
