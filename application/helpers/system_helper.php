<?php defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('application')) {
	function application()
	{
		$CI = &get_instance();
		$app = $CI->db->get_where('config', ['title' => 'application'])->row_array();
		$app = json_decode($app['value']);
		$data = [];
		$data['by'] = @$app->by;
		$data['mode'] = @$app->mode;
		$data['name'] = @$app->title;
		$data['version'] = @$app->version;
		$data['dsc'] = $app->desc;
		$data['logo'] = 'assets/images/logo/' . $app->logo;
		$data['background'] = 'assets/images/background/' . $app->background;
		$data['loader'] = 'assets/images/logo/' . $app->loading;
		return $data;
	}
}

if (!function_exists('dd')) {
	function dd($data)
	{
		echo "<pre>";
		print_r($data);
		echo "</pre>";
		die;
	}
}

if (!function_exists('notif_v1')) {
	function notif_v1($for = [], $multiple_users_id = [], $user_id = 0, $title = '', $message = '', $link = '')
	{
		$CI = &get_instance();
		$data = [
			'notif_for' => (empty($user_id)) ? json_encode($for) : json_encode([]),
			'multiple_users_id' => json_encode($multiple_users_id),
			'user_id' => $user_id,
			'title' => $title,
			'message' => $message,
			'link' => $link,
			'status' => 2,
			'row_status' => 1
		];
		if ($CI->db->insert('notification', $data)) {
			return TRUE;
		}
		return FALSE;
	}
}

if (!function_exists('read_notif_v1')) {
	function read_notif_v1($where = "")
	{
		$CI = &get_instance();
		if (!empty($where)) {
			$query = "UPDATE notification SET status = '1' WHERE " . $where;
			$execute = $CI->db->query($query);
			if (!empty($execute)) {
				return TRUE;
			}
		}
		return FALSE;
	}
}

if (!function_exists('notif')) {
	function notif($for = '', $user_id = '', $dep_id = '', $section_id = '', $title = '', $message = '', $link = '')
	{
		$CI = &get_instance();
		$data = [
			'notif_for' => $for,
			'user_id' => $user_id,
			'sim_dep_id' => $dep_id,
			'sim_section_id' => $section_id,
			'title' => $title,
			'message' => $message,
			'link' => $link,
			'status' => 2,
			'row_status' => 1
		];
		if ($CI->db->insert('notification', $data)) {
			return TRUE;
		}
		return FALSE;
	}
}

if (!function_exists('history')) {
	function history($action = '', $value = '')
	{
		$inputing = FALSE;
		$CI = &get_instance();
		$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$data = [
			'user_id' => @get_user()['id'],
			'url' => @$url,
			'action' => @$action,
			'value' => @json_encode($value)
		];

		if ($CI->db->insert('history', $data)) {
			$inputing = TRUE;
		}
		return $inputing;
	}
}

if (!function_exists('time_date')) {
	function time_date($timestamp = '')
	{
		$data = '';
		if (!empty($timestamp)) {
			$data = date("d F Y - H.i", strtotime($timestamp));
		}
		return $data;
	}
}

if (!function_exists('excel_to_php_date')) {
	function excel_to_php_date($exdate)
	{
		$date = ($exdate - 25569) * 86400;
		return $date;
	}
}

if (!function_exists('excel_to_php_time')) {
	function excel_to_php_time($extime)
	{
		$total = $extime * 24; //multiply by the 24 hours
		$hours = floor($total); //Gets the natural number part
		$minute_calculate = $total - $hours; //Now has only the decimal part
		$minute_calculate = $minute_calculate * 60; //Get the number of minutes
		$minutes = floor($minute_calculate);
		$seconds_calculate = $minute_calculate - $minutes;
		$seconds_calculate = $seconds_calculate * 60;
		$seconds = floor(@$seconds_calculate);

		if (!empty($format)) {
			$format .= str_replace("H", $hours, $format);
			$format .= str_replace("i", $minutes, $format);
			$format .= str_replace("s", $seconds, $format);
		} else {
			$format = $hours . ":" . $minutes . ":" . $seconds;
		}

		return $format;
	}
}

if (!function_exists('dateformat')) {
	function dateformat($timestamp = '')
	{
		$data = '';
		$data = date("d M Y", strtotime($timestamp));
		return $data;
	}
}

if (!function_exists('weekOfYear')) {
	function weekOfYear($date)
	{
		$date = strtotime($date);
		$weekOfYear = intval(date("W", $date));
		if (date('n', $date) == "1" && $weekOfYear > 51) {
			// It's the last week of the previos year.
			return 0;
		} else if (date('n', $date) == "12" && $weekOfYear == 1) {
			// It's the first week of the next year.
			return 53;
		} else {
			// It's a "normal" week.
			return $weekOfYear;
		}
	}
}

if (!function_exists('weekOfMonth')) {
	function weekOfMonth($date)
	{
		//Get the first day of the month.
		$firstOfMonth = date("Y-m-01", strtotime($date));

		//Apply above formula.
		return weekOfYear($date) - weekOfYear($firstOfMonth) + 1;
	}
}

if (!function_exists('time_24format')) {
	function time_24format($timestamp = '')
	{
		$data = '';
		$data = date("H.i", strtotime($timestamp));
		return $data;
	}
}

if (!function_exists('excel_datetime')) {
	function excel_datetime($timestamp = '', $formated = '')
	{
		$data = '';
		$data = date($formated, ($timestamp - 25569) * 86400);
		return $data;
	}
}

if (!function_exists('idr')) {
	function idr($angka)
	{

		$hasil_rupiah = number_format($angka, 0, ',', '.');
		if (empty($hasil_rupiah)) {
			$hasil_rupiah = '0';
		}
		return $hasil_rupiah;
	}
}

function encrypt($string = '')
{
	$key = '';
	if (!empty($string)) {
		$key = password_hash($string, PASSWORD_DEFAULT);
	}
	return $key;
}

function decrypt($string = '', $current_key = '')
{
	$key = '';
	if (!empty($string) && !empty($current_key)) {
		$key = password_verify($string, $current_key);
	}
	return $key;
}

function alphabet()
{
	$data = [];
	foreach (range('A', 'Z') as $char) {
		$data[] = $char;
	}
	return $data;
}
function output_json($array)
{
	$output = '{}';
	if (!empty($array)) {
		if (is_object($array)) {
			$array = (array)$array;
		}
		if (!is_array($array)) {
			$output = $array;
		} else {
			if (defined('JSON_PRETTY_PRINT')) {
				$output = json_encode($array, JSON_PRETTY_PRINT);
			} else {
				$output = json_encode($array);
			}
		}
	}
	header('content-type: application/json; charset: UTF-8');
	header('cache-control: must-revalidate');
	header('expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
	echo $output;
	exit();
}

function am($parent, $child, $ext, $type)
{
	$ci = &get_instance();
	$data[] = 0;
	if (!empty($parent)) {
		if ($ci->uri->rsegments[1] == $parent) {
			$data['parent'] = 'active';
		}
	}
	if (!empty($child) && !empty($parent)) {
		if (($ci->uri->rsegments[1] == $parent) && ($ci->uri->rsegments[2] == $child)) {
			$data['child'] = 'active';
		}
	}
	if (!empty($ext)) {
		if ($ci->uri->rsegments[2] == $ext) {
			$data['ext'] = 'active';
		}
	}
	if (!empty($type)) {
		if (!empty($type) && !empty($parent)) {
			if ($ci->uri->rsegments[1] == $parent) {
				$data['open'] = 'menu-open';
			}
		}
	}
	return $data;
}

function capital_letters($string)
{
	$str = $string;
	$str = ucfirst($str);
	$str = str_replace("_", " ", $str);
	return $str;
}

if (!function_exists('notif_msg')) {
	function notif_msg($notif_num)
	{
		$str = file_get_contents('notifications.json');
		$json = json_decode($str, true);

		if (!empty($json[$notif_num])) {
			return $json[$notif_num];
		}
		return false;
	}
}

if (!function_exists('_sendMail')) {
	function _sendMail($from, $to, $cc = "", $subject, $body, $email_account = '', $email_password = '')
	{
		$CI = &get_instance();
		$config = [
			'protocol' => 'smtp',
			'smtp_host' => 'mail.tjbservices.com',
			'smtp_user' => ((!empty($email_account)) ? getenv(strtoupper($email_account)) : getenv('EMAIL_ADDRESS_OTHER')),
			'smtp_pass' => ((!empty($email_password)) ? getenv(strtoupper($email_password)) : getenv('EMAIL_PASSWORD_OTHER')),
			'smtp_port' => 25,
			'smtp_timeout'    => '30',
			'mailtype' => 'html',
			'charset' => 'utf-8',
			'newline' => "\r\n",
			'wordwrap' => TRUE
		];

		$CI->load->library('email', $config);
		$CI->email->initialize($config);

		$CI->email->from(((!empty($email_account)) ? getenv(strtoupper($email_account)) : getenv('EMAIL_ADDRESS_OTHER')), $from);
		$CI->email->to($to);
		if (!empty($cc)) {
			$CI->email->cc($cc);
		}

		// $CI->email->bcc('support@tjbservices.com');
		$CI->email->subject($subject);
		$CI->email->message($body);

		// return true;
		if ($CI->email->send()) {
			return true;
		} else {
			return false;
		}

		$CI->email->clear();
	}
}
