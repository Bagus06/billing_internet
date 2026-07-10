<?php defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('get_usernl')) {
	function get_usernl($id = '', $colum = '')
	{
		$CI = &get_instance();
		$CI->db->select($colum);
		$profile = $CI->db->get_where('user', ['id' => $id])->row_array();
		return $profile[$colum];
	}
}

if (!function_exists('user_ag')) {
	function user_ag()
	{
		$CI = &get_instance();
		$CI->load->library('user_agent');
		$ret = '';

		if ($CI->agent->is_mobile()) {
			$ret = 'mobile';
		} else {
			$ret = 'desktop';
		}

		return $ret;
	}
}

if (!function_exists('get_user')) {
	function get_user()
	{
		$link = str_replace('/', '_', base_url());
		$user = [];
		if (!empty($_SESSION[$link . '_logged_in'])) {
			$user = $_SESSION[$link . '_logged_in'];
		}
		return $user;
	}
}

if (!function_exists('section')) {
	function section($section_id = 0, $get = '')
	{
		$data = '';
		$CI = &get_instance();
		$section = $CI->db->get_where('sim_section', ['id' => $section_id])->row_array();
		$department = $CI->db->get_where('sim_department', ['id' => @$section['department_id']])->row_array();
		if (!empty($section)) {
			if ((empty($get)) || ($get == 'name')) {
				$data = [
					'department' => $department['name'],
					'section' => $section['name']
				];
			} elseif ($get == 'id') {
				$data = [
					'department' => $department['id'],
					'section' => $section['id']
				];
			}
		}
		return $data;
	}
}

if (!function_exists('grade')) {
	function grade($id = 0)
	{
		$data = false;
		$CI = &get_instance();

		if (empty($id)) {
			$link = str_replace('/', '_', base_url());
			$user = @$_SESSION[$link . '_logged_in'];
			$id = $user['id'];
		}

		$CI->db->select('grade_id');
		$profile = $CI->db->get_where('profile', ['user_id' => $id])->row_array();

		$grade = $CI->db->get_where('sim_grade', ['id' => $profile['grade_id']])->row_array();
		if (!empty($grade)) {
			$data = $grade;
		}

		return $data;
	}
}

if (!function_exists('get_profilenl')) {
	function get_profilenl($id = '', $colum = '')
	{
		$CI = &get_instance();
		$CI->db->select($colum);
		$profile = $CI->db->get_where('profile', ['user_id' => $id])->row_array();
		return $profile[$colum];
	}
}

if (!function_exists('get_profile')) {
	function get_profile($colum = '')
	{
		$CI = &get_instance();
		$link = str_replace('/', '_', base_url());
		$user = @$_SESSION[$link . '_logged_in'];

		$CI->db->select($colum);
		$profile = $CI->db->get_where('profile', ['user_id' => $user['id']])->row_array();
		return $profile[$colum];
	}
}

function get_name_by_id($id)
{
	if (!empty($id)) {
		$ci = &get_instance();
		return $ci->db->query("SELECT * FROM profile WHERE profile.user_id = $id")->row_array();
	}
}

function get_room_by_id($id)
{
	if (!empty($id)) {
		$ci = &get_instance();
		return $ci->db->query("SELECT room FROM meeting_room WHERE id = $id")->row_array();
	}
}

if (!function_exists('get_hours')) {
	function get_hours($overtime_user, $overtime_date = '')
	{
		$CI = &get_instance();
		$result = array();
		if ($overtime_user == "") {
			$overtime_user = [];
		}

		foreach ($overtime_user as $key => $value) {
			$query = "SELECT TIMEDIFF(hr_overtime_user.target_finish, hr_overtime_user.target_start) AS amount_time";
			$query .= " FROM hr_overtime_user";
			$query .= " LEFT JOIN hr_overtime ON hr_overtime_user.form_id=hr_overtime.form_num";
			$query .= " WHERE hr_overtime_user.user_id = " . $value["user_id"] . " and hr_overtime_user.reject_status = 0 and hr_overtime_user.row_status = 1 and MONTH(hr_overtime.overtime_date) = MONTH('$overtime_date') and YEAR(hr_overtime.overtime_date) = YEAR('$overtime_date') and (hr_overtime.status='OTAPPR' OR hr_overtime.status='OTREQ') and (hr_overtime_user.actual_start = '' OR hr_overtime_user.actual_finish = '') ORDER BY hr_overtime_user.user_id DESC";
			$time_target = $CI->db->query($query)->result_array();

			$query = "SELECT TIMEDIFF(hr_overtime_user.actual_finish, hr_overtime_user.actual_start) AS amount_time";
			$query .= " FROM hr_overtime_user";
			$query .= " LEFT JOIN hr_overtime ON hr_overtime_user.form_id=hr_overtime.form_num";
			$query .= " WHERE hr_overtime_user.user_id = " . $value["user_id"] . " and hr_overtime_user.reject_status = 0 and hr_overtime_user.row_status = 1 and MONTH(hr_overtime.overtime_date) = MONTH('$overtime_date') and YEAR(hr_overtime.overtime_date) = YEAR('$overtime_date') and (hr_overtime.status='OTAPPR' OR hr_overtime.status='PAYREQ' OR hr_overtime.status='PAYAPPR') ORDER BY hr_overtime_user.user_id DESC";
			$time_actual = $CI->db->query($query)->result_array();


			$total = 0;
			$total2 = 0;

			foreach ($time_target as $target) {
				$ot = intval($target["amount_time"]);
				if ($ot < 0) {
					$ot += 24;
				} else if ($ot >= 9) {
					//dikurangi 1 jam istirahat
					$ot -= 1;
				}
				$total = $total + $ot;
			}

			foreach ($time_actual as $actual) {
				$ot2 = intval($actual["amount_time"]);
				if ($ot2 < 0) {
					$ot2 += 24;
				} else if ($ot2 >= 9) {
					//dikurangi 1 jam istirahat
					$ot2 -= 1;
				}
				$total2 = $total2 + $ot2;
			}

			$data = array(
				"user_id" => $value["user_id"],
				"ot_req" => $total,
				"ot_appr" => $total2
			);

			$result[] = $data;
		}
		return $result;
	}
}
