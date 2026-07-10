<?php defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('subordinates')) {
	function subordinates($user_id = null, $equal_grade = false)
	{
		$ret = [];
		$CI = &get_instance();

		if (!empty($user_id)) {
			$get_grade = grade($user_id);

			if (!empty($get_grade['grade'])) {
				$get_grade = $get_grade['grade'];
				$dep_sec = section(get_profilenl($user_id, 'section_id'), 'id');
				$tjb_company = get_company(["description" => "PT TJB POWER SERVICES"]);

				$query = "SELECT user.id, user.username, profile.name, sim_department.name as department, sim_section.name as section, sim_area.name as area, sim_grade.job_title, sim_grade.grade";
				$query .= " FROM user ";
				$query .= " INNER JOIN profile ON profile.user_id = user.id ";
				$query .= " LEFT JOIN sim_department ON profile.dep_id=sim_department.id ";
				$query .= " LEFT JOIN sim_section ON profile.section_id=sim_section.id ";
				$query .= " LEFT JOIN sim_area ON profile.area_id=sim_area.id ";
				$query .= " LEFT JOIN sim_grade ON profile.grade_id=sim_grade.id ";
				$query .= " WHERE profile.company_id = " . @$tjb_company[0]["id"] . " AND user.status = 1 AND user.verified = 1 AND user.id != 1";

				if (@$get_grade >= 19) { //manager
					if (@$dep_sec['section'] == 40) { //maintenance mechanical
						// SUBORDINATE BY SECTION 37 OR 14 - 18
						$query .= " AND (sim_section.id = 40 OR sim_section.id = 43 OR sim_section.id = 44 OR sim_section.id = 37 OR sim_section.id between 14 AND 18) AND (sim_grade.grade < " . @$get_grade . " OR user.id = $user_id)";
					} else {
						// SUBORDINATE BY DEPARTMENT ID
						if (@$dep_sec['department'] == 2111342) {
							# Subordinate for hrd and management
							$query .= " AND ((sim_department.id = " . @$dep_sec['department'] . ") OR (sim_department.id = '2111347')) AND (sim_grade.grade < " . @$get_grade . " OR user.id = $user_id)";
						} else {
							$query .= " AND sim_department.id = " . @$dep_sec['department'] . " AND (sim_grade.grade < " . @$get_grade . " OR user.id = $user_id)";
						}
					}
				} else if (@$get_grade == 18) { //deputy manager
					if (@$dep_sec['section'] == 40) { //maintenance mechanical {
						// SUBORDINATE BY BY SECTION 37 OR 14 - 18
						$query .= " AND (sim_section.id = 40 OR sim_section.id=37 OR sim_section.id between 14 AND 18) AND (sim_grade.grade < " . @$get_grade . " OR user.id = $user_id)";
					} else {
						// SUBORDINATE BY SECTION ID
						$query .= " AND sim_section.id= " . @$dep_sec['section'] . " AND (sim_grade.grade < " . @$get_grade . " OR user.id = $user_id)";
					}
				} else if (@$get_grade == 17) { //chief 
					if (@$dep_sec['section'] == 22 || @$dep_sec['section'] == 38 || @$dep_sec['section'] == 4 || @$dep_sec['section'] == 1) { // dev, ehs, hrd, f&a
						// SUBORDINATE BY DEPARTMENT ID
						$query .= " AND sim_department.id = " . @$dep_sec['department'] . " AND (sim_grade.grade < " . @$get_grade . " OR user.id = $user_id)";
					} else if (@$dep_sec['section'] == 40) { //maintenance mechanical
						// SUBORDINATE BY SECTION 37 OR 14 - 18
						$query .= " AND (sim_section.id=37 OR sim_section.id=40 OR sim_section.id between 14 AND 18) AND (sim_grade.grade < " . @$get_grade . " OR user.id = $user_id)";
					} else if (in_array(@$dep_sec['section'], [19, 20])) { // Main Plant & WTP/WWTP
						// SUBORDINATE BY Main Plant (19) & WTP/WWTP (20)
						$query .= " AND (sim_section.id=19 OR sim_section.id=20) AND (sim_grade.grade < " . @$get_grade . " OR user.id = $user_id)";
					} else {
						// SUBORDINATE BY SECTION ID
						$query .= " AND sim_section.id= " . @$dep_sec['section'] . " AND (sim_grade.grade < " . @$get_grade . " OR user.id = $user_id)";
					}
				} else {
					// SUBORDINATE BY SECTION ID
					$query .= " AND sim_section.id= " . @$dep_sec['section'];

					if (get_usernl($user_id, 'username') == '80222678') {
						$query .= " AND sim_grade.grade <= " . @$get_grade;
					} else {
						$query .= " AND (sim_grade.grade < " . @$get_grade . " OR user.id = $user_id)";
					}
				}

				if (!empty($query)) {
					$query .= " ORDER BY profile.name ASC";
					$data = $CI->db->query($query)->result_array();
					if (!empty($data)) {
						$ret = $data;
					}
				}
			}
		}

		return $ret;
	}
}

if (!function_exists('subordinate_query')) {
	function subordinate_query($user_grade = '', $department_id = '', $section_id)
	{
		$ret = "";

		if ($user_grade >= 19) { //manager
			if ($section_id == 40) { //maintenance mechanical
				// SUBORDINATE BY SECTION 37 OR 43 OR 44 OR 14 - 18
				$query	= " user.username LIKE '802%' and user.status = 1 and user.id != 1 and user.status=1 and (sim_section.id=37 OR sim_section.id=43 OR sim_section.id=44 OR sim_section.id between 14 and 18) and sim_grade.grade <= $user_grade";
				$ret	= $query;
			} else if ($section_id == 4) { //HR AND MANAGEMENT
				// SUBORDINATE BY SECTION 4
				$query	= " user.username LIKE '802%' and user.status = 1 and user.id != 1 and user.status=1 and (sim_department.id= $department_id OR sim_department.id=2111347) and sim_grade.grade <= $user_grade";
				$ret	= $query;
			} else {
				// SUBORDINATE BY DEPARTMENT ID
				$query	= " user.username LIKE '802%' and user.status = 1 and user.id != 1 and user.status=1 and sim_department.id = $department_id and sim_grade.grade <= $user_grade";
				$ret	= $query;
			}
		} else if ($user_grade == 18) { //deputy manager
			if ($section_id == 40) { //maintenance mechanical {
				// SUBORDINATE BY SECTION 37 OR 43 OR 44 OR 14 - 18
				$query	= " user.username LIKE '802%' and user.status = 1 and user.id != 1 and user.status=1 and (sim_section.id=37 OR sim_section.id=43 OR sim_section.id=44 OR sim_section.id between 14 and 18) and sim_grade.grade <= $user_grade";
				$ret	= $query;
			} else {
				// SUBORDINATE BY SECTION ID
				$query	= " user.username LIKE '802%' and user.status = 1 and user.id != 1 and user.status=1 and sim_section.id= $section_id and sim_grade.grade <= $user_grade";
				$ret	= $query;
			}
		} else if ($user_grade == 17) { //chief 
			if ($section_id == 22 || $section_id == 38 || $section_id == 4 || $section_id == 1) { // dev, ehs, hrd, f&a
				// SUBORDINATE BY DEPARTMENT ID
				$query	= " user.username LIKE '802%' and user.status = 1 and user.id != 1 and user.status=1 and sim_department.id = $department_id and sim_grade.grade <= $user_grade";
				$ret	= $query;
			} else if ($section_id == 40) { //maintenance mechanical
				// SUBORDINATE BY SECTION 37 OR 43 OR 44 OR 14 - 18
				$query	= " user.username LIKE '802%' and user.status = 1 and user.id != 1 and user.status=1 and (sim_section.id=37 OR sim_section.id=43 OR sim_section.id=44 OR sim_section.id between 14 and 18) and sim_grade.grade <= $user_grade";
				$ret	= $query;
			} else if (in_array($section_id, [19, 20])) { //Main Plant & WTP/WWTP
				// SUBORDINATE BY Main Plant (19) & WTP/WWTP (20)
				$query	= " user.username LIKE '802%' and user.status = 1 and user.id != 1 and user.status=1 and (sim_section.id=19 OR sim_section.id=20) and sim_grade.grade <= $user_grade";
				$ret	= $query;
			} else {
				// SUBORDINATE BY SECTION ID
				$query 	= " user.username LIKE '802%' and user.status = 1 and user.id != 1 and user.status=1 and sim_section.id= $section_id and sim_grade.grade <= $user_grade";
				$ret	= $query;
			}
		} else {
			// SUBORDINATE BY SECTION ID
			$query	= " user.username LIKE '802%' and user.status = 1 and user.status=1 and sim_section.id= $section_id and sim_grade.grade <= $user_grade";
			$ret	= $query;
		}

		return $ret;
	}
}

if (!function_exists('subordinate_query_appr')) {
	function subordinate_query_appr($user_grade = '', $department_id = '', $section_id)
	{
		$ret = "";

		if ($user_grade >= 19) { //manager
			if ($section_id == 40) { //maintenance mechanical
				// SUBORDINATE BY SECTION 37 OR 43 OR 44 OR 14 - 18
				$query	= " user.username LIKE '802%' and user.status = 1 and user.id != 1 and user.status=1 and (sim_section.id=37 OR sim_section.id=43 OR sim_section.id=44 OR sim_section.id between 14 and 18) and sim_grade.grade <= $user_grade";
				$ret	= $query;
			} else if ($section_id == 4) { //HR AND MANAGEMENT
				// SUBORDINATE BY SECTION 4
				$query	= " user.username LIKE '802%' and user.status = 1 and user.id != 1 and user.status=1 and (sim_department.id= $department_id OR sim_department.id=2111347) and sim_grade.grade <= $user_grade";
				$ret	= $query;
			} else {
				// SUBORDINATE BY DEPARTMENT ID
				$query	= " user.username LIKE '802%' and user.status = 1 and user.id != 1 and user.status=1 and sim_department.id = $department_id and sim_grade.grade <= $user_grade";
				$ret	= $query;
			}
		} else if ($user_grade == 18) { //deputy manager
			if ($section_id == 40) { //maintenance mechanical {
				// SUBORDINATE BY BY SECTION 37 OR 43 OR 44 OR 14 - 18
				$query	= " user.username LIKE '802%' and user.status = 1 and user.id != 1 and user.status=1 and (sim_section.id=37 OR sim_section.id=43 OR sim_section.id=44 OR sim_section.id between 14 and 18) and sim_grade.grade <= $user_grade";
				$ret	= $query;
			} else {
				// SUBORDINATE BY SECTION ID
				$query	= " user.username LIKE '802%' and user.status = 1 and user.id != 1 and user.status=1 and sim_section.id= $section_id and sim_grade.grade <= $user_grade";
				$ret	= $query;
			}
		} else if ($user_grade == 17) { //chief 
			if ($section_id == 22 || $section_id == 38 || $section_id == 4 || $section_id == 1) { // dev, ehs, hrd, f&a
				// SUBORDINATE BY DEPARTMENT ID
				$query	= " user.username LIKE '802%' and user.status = 1 and user.id != 1 and user.status=1 and sim_department.id = $department_id and sim_grade.grade <= $user_grade";
				$ret	= $query;
			} else if ($section_id == 40) { //maintenance mechanical
				// SUBORDINATE BY BY SECTION 37 OR 43 OR 44 OR 14 - 18
				$query	= " user.username LIKE '802%' and user.status = 1 and user.id != 1 and user.status=1 and (sim_section.id=37 OR sim_section.id=43 OR sim_section.id=44 OR sim_section.id between 14 and 18) and sim_grade.grade <= $user_grade";
				$ret	= $query;
			} else if (in_array($section_id, [19, 20])) { //Main Plant & WTP/WWTP
				// SUBORDINATE BY Main Plant (19) & WTP/WWTP (20)
				$query	= " user.username LIKE '802%' and user.status = 1 and user.id != 1 and user.status=1 and (sim_section.id=19 OR sim_section.id=20) and sim_grade.grade <= $user_grade";
				$ret	= $query;
			} else {
				// SUBORDINATE BY SECTION ID
				$query 	= " user.username LIKE '802%' and user.status = 1 and user.id != 1 and user.status=1 and sim_section.id= $section_id and sim_grade.grade <= $user_grade";
				$ret	= $query;
			}
		} else {
			// SUBORDINATE BY SECTION ID
			$query	= " user.username LIKE '802%' and user.status = 1 and user.status=1 and sim_section.id= $section_id and sim_grade.grade <= $user_grade";
			$ret	= $query;
		}

		return $ret;
	}
}

if (!function_exists('department_manager')) {
	function department_manager($section_id = '', $wf_lv = '')
	{
		$CI = &get_instance();
		$output = [
			'success' => FALSE,
			'message' => '',
			'data' => []
		];

		# Get department detailed by user section_id
		$depsec_detailed_query = "SELECT department_id, sim_department.name as department_name, sim_section.name as section_name FROM sim_section INNER JOIN sim_department ON sim_department.id = department_id WHERE sim_section.id = $section_id";
		$depsec_detailed = $CI->db->query($depsec_detailed_query);
		if (!empty($depsec_detailed)) {
			$depsec_detailed = $depsec_detailed->row_array();
		} else {
			$depsec_detailed = FALSE;
		}

		$query = "SELECT profile.user_id, profile.name FROM profile";
		$query .= " INNER JOIN user ON user.id=profile.user_id";
		$query .= " INNER JOIN sim_grade ON sim_grade.id=profile.grade_id";
		$query .= " INNER JOIN sim_section ON sim_section.id=profile.section_id";
		$query .= " INNER JOIN sim_department ON sim_department.id = profile.dep_id";
		$query .= " WHERE ";

		if (strtolower(@$depsec_detailed['department_name']) == 'management') { // Get where condition department management

			$query .= "(user.id = '" . @list_user_on_group($wf_lv)[0]['user_id'] . "')";
		} else { // Other condition
			$query .= "(";

			# Get where user grade 19/manager and user section id is same
			$query .= "(grade = 19 AND dep_id = '" . @$depsec_detailed['department_id'] . "' AND section_id = '" . $section_id . "')";

			# Get where user grade 19/manager, user section id is same and section name same with department name
			$query .= " OR (grade = 19 AND dep_id = '" . @$depsec_detailed['department_id'] . "' AND sim_section.name = '" . @$depsec_detailed['department_name'] . "')";

			$query .= ") ";
		}

		$query .= "AND user.status = 1 AND user.verified = 1";
		$excuted = $CI->db->query($query);

		if (!empty($excuted)) {
			$output['success'] = TRUE;
			$output['data'] = $excuted->row_array();
		} else {
			$output['message'] = $CI->db->error()["message"];
		}

		return $output;
	}
}

if (!function_exists('get_wf_group')) {
	function get_wf_group($wf_lv = '')
	{
		$whereclause = "";
		$CI = &get_instance();

		$datas = [
			"select" => "id",
			"row_status" => 1,
			"getreturn" => "data",
			"order_by" => [
				"column" => "id",
				"order" => "ASC"
			],
			"limit" => [
				"length" => -1,
				"start" => ""
			],
			"whereclause" => "title IN "  . str_replace("[", "(", str_replace("]", ")",  json_encode([$wf_lv])))
		];
		$pmt_items = $CI->permission_model->permission(0, $datas, "GET");

		$loop = 0;
		foreach ($pmt_items["data"] as $key => $value) {
			if ($loop === 0) {
				$whereclause .= "JSON_CONTAINS(permission_id, '\"" . $value["id"] . "\"','$')";
			} else {
				$whereclause .= " OR JSON_CONTAINS(permission_id, '\"" . $value["id"] . "\"','$')";
			}
			$loop++;
		}

		return $whereclause;
	}
}

if (!function_exists('get_subordinate_by_wflv')) {
	function get_subordinate_by_user_wflv($user_id = '')
	{
		$CI = &get_instance();
		$wflv = "";
		$user_list = false;
		$user_detailed = $CI->user_model->detailed(encrypt_url($user_id));

		$getting_datas = [
			"select" => "permission.id, permission.title",
			"row_status" => 1,
			"getreturn" => "data",
			"order_by" => [
				"column" => "id",
				"order" => "ASC"
			],
			"limit" => [
				"length" => -1,
				"start" => ""
			],
			"whereclause" => "permission.id IN "  . str_replace("[", "(", str_replace("]", ")",  @$user_detailed['permission_id'])) . " AND (title LIKE 'L1 %' OR title LIKE 'L2 %')"
		];
		$permission_group = $CI->permission_model->permission(0, $getting_datas, "GET");

		if (!empty($permission_group['data'])) {
			$loop = 0;
			foreach ($permission_group["data"] as $key => $value) {
				if ($loop === 0) {
					$wflv .= "((wf_lv1 = '" . $value['title'] . "') OR (wf_lv2 = '" . $value['title'] . "')) ";
				} else {
					$wflv .= "OR ((wf_lv1 = '" . $value['title'] . "') OR (wf_lv2 = '" . $value['title'] . "')) ";
				}
				$loop++;
			}

			$getting_datas = [
				"select" => "user_id, profile.name",
				"row_status" => 1,
				"getreturn" => "data",
				"order_by" => [
					"column" => "user_id",
					"order" => "ASC"
				],
				"limit" => [
					"length" => -1,
					"start" => ""
				],
				"whereclause" => $wflv
			];
			$user_list = $CI->user_model->users(0, $getting_datas, "GET");
		}


		return $user_list;
	}
}
