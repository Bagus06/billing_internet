<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Request_guard
{
    private $CI;
    public function __construct(){ $this->CI=&get_instance(); }
    public function token()
    {
        $token=(string)$this->CI->session->userdata('app_csrf_token');
        if(strlen($token)!==64){$token=bin2hex(random_bytes(32));$this->CI->session->set_userdata('app_csrf_token',$token);}
        return $token;
    }
    public function verify()
    {
        if(!in_array(strtoupper($this->CI->input->method()),['POST','PUT','PATCH','DELETE'],true))return true;
        $provided=(string)$this->CI->input->get_request_header('X-CSRF-Token',true);
        if($provided==='')$provided=(string)$this->CI->input->post('_app_csrf_token',true);
        $expected=(string)$this->CI->session->userdata('app_csrf_token');
        return $expected!==''&&$provided!==''&&hash_equals($expected,$provided);
    }
    public function enforce()
    {
        if($this->verify())return true;
        show_error('Token keamanan request tidak valid atau sesi telah kedaluwarsa.',403,'Request Ditolak');
        return false;
    }
}
