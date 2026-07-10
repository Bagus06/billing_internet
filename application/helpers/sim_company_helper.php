<?php defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('get_company')) {
    function get_company($where = [])
    {
        $CI = &get_instance();

        $whereclause = "";
        if (is_array($where)) {
            foreach ($where as $key => $value) {
                $whereclause .= " AND " . $key . " = '" . $value . "'";
            }
        }
        $query = "SELECT * FROM sim_company WHERE row_status = 1" . $whereclause;
        $data = $CI->db->query($query)->result_array();
        return $data;
    }
}

if (!function_exists('get_dep_manager')) {
    function get_dep_manager($dep_id = 0)
    {
        $ret = false;
        $CI = &get_instance();

        if (!empty($dep_id)) {
            $query = "SELECT user_id, profile.name as user_name, sim_department.name as department FROM profile ";
            $query .= "INNER JOIN user ON user.id=profile.user_id ";
            $query .= "INNER JOIN sim_grade ON sim_grade.id = profile.grade_id ";
            $query .= "INNER JOIN sim_section ON sim_section.id=profile.section_id ";
            $query .= "INNER JOIN sim_department ON sim_department.id = profile.dep_id ";
            $query .= "WHERE profile.row_status = 1 AND (grade = 19 AND dep_id = " . $dep_id . " AND sim_section.name=(SELECT name FROM sim_department WHERE sim_department.id = " . $dep_id . ")) AND user.status = 1 AND user.verified = 1";
            $data = $CI->db->query($query)->result_array();

            if (!empty($data[0])) {
                $ret = $data[0];
            } else {
                $query = "SELECT user_id, profile.name as user_name, sim_department.name as department FROM profile ";
                $query .= "INNER JOIN user ON user.id=profile.user_id ";
                $query .= "INNER JOIN sim_grade ON sim_grade.id = profile.grade_id ";
                $query .= "INNER JOIN sim_department ON sim_department.id = profile.dep_id ";
                $query .= "WHERE profile.row_status = 1 AND user.status = 1 AND user.verified = 1 AND grade = '20' AND sim_department.name = 'Management'";

                $data = $CI->db->query($query)->result_array();

                if (!empty($data[0])) {
                    $ret = $data[0];
                }
            }
        }

        return $ret;
    }
}
