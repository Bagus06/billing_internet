<?php defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('villages')) {
    function villages($id = '')
    {
        $data = '';
        $CI = &get_instance();
        if (!empty($id)) {
            $CI->db->select('villages.id, villages.pos, villages.desc as villages, districts.desc as districts, regencies.rgcty, regencies.desc as regencies, provinces.desc as provinces');
            $CI->db->from('villages');
            $CI->db->join('districts', 'districts.id=villages.districts_id');
            $CI->db->join('regencies', 'regencies.id=districts.regencies_id');
            $CI->db->join('provinces', 'provinces.id=regencies.province_id');
            $CI->db->where('villages.id', $id);
            $villages = $CI->db->get()->row_array();
            if (!empty($villages)) {
                $data = $villages['villages'] . ', Kec.' . $villages['districts'] . ', ' . $villages['rgcty'] . ' ' . $villages['regencies'] . ', ' . $villages['provinces'] . ' ' . $villages['pos'];
            }
        }
        return $data;
    }
}

if (!function_exists('districts')) {
    function districts($id = '')
    {
        $ret = '';
        $CI = &get_instance();
        if (!empty($id)) {
            $CI->db->select('districts.id, districts.desc as districts, regencies.rgcty, regencies.desc as regencies, provinces.desc as provinces');
            $CI->db->from('districts');
            $CI->db->join('regencies', 'regencies.id=districts.regencies_id');
            $CI->db->join('provinces', 'provinces.id=regencies.province_id');
            $CI->db->where('districts.id', $id);
            $data = $CI->db->get()->row_array();
            if (!empty($data)) {
                $ret = 'Kec.' . @$data['districts'] . ', ' . @$data['rgcty'] . ' ' . @$data['regencies'] . ', ' . @$data['provinces'];
            }
        }
        return $ret;
    }
}

if (!function_exists('regencies')) {
    function regencies($id = '')
    {
        if (!empty($id)) {
            $data = '';
            $CI = &get_instance();
            $CI->db->select('regencies.id, regencies.rgcty, regencies.desc as regencies');
            $CI->db->from('regencies');
            $CI->db->where('regencies.id', $id);
            $regencies = $CI->db->get()->row_array();
            if (!empty($regencies)) {
                $data = $regencies['rgcty'] . ' ' . $regencies['regencies'];
            }
        }
        return $data;
    }
}
