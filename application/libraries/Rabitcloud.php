<?php

defined('BASEPATH') OR exit('No direct script access allowed');










class Rabitcloud
{
public function base()
{
$b = getenv('ANHCUOI_CLOUD_BASE');
if ($b === FALSE || $b === '') {
$CI =& get_instance();
$b = (string) $CI->config->item('cloud_base');
}
return rtrim($b !== '' ? $b : 'https://api.jagame.vn', '/');
}
public function domain()
{
$CI =& get_instance();
return (string) ($CI->config->item('cloud_domain') ?: 'jagame.vn');
}

public function check_name($name)
{
if (!function_exists('curl_init')) {
return array('ok' => FALSE, 'error' => 'PHP thiếu tiện ích curl.');
}
$ch = curl_init($this->base() . '/api/subdomain/check?name=' . rawurlencode($name));
curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 10,
CURLOPT_HTTPHEADER => array('Accept: application/json')));
$j = json_decode((string) curl_exec($ch), TRUE);
curl_close($ch);
if (!is_array($j) || empty($j['success'])) {
return array('ok' => FALSE, 'error' => is_array($j) && !empty($j['message']) ? $j['message'] : 'Không kết nối được máy chủ tên miền.');
}
return array('ok' => TRUE) + $j['data'];
}




public function claim($port, $secret = '')
{
$r = $this->call('/api/wedding/claim', array('pos_port' => (int) $port, 'device_secret' => (string) $secret,
'os' => PHP_OS_FAMILY, 'fingerprint' => substr(hash('sha256', php_uname('n') . '|' . php_uname('m') . '|' . FCPATH), 0, 32)));
if (!$r['ok']) {
return $r;
}
$d = $r['data'];
if (empty($d['hostname']) || empty($d['connector_token'])) {
return array('ok' => FALSE, 'error' => 'Máy chủ tên miền trả dữ liệu thiếu.');
}
return array('ok' => TRUE, 'hostname' => strtolower($d['hostname']), 'subdomain' => $d['subdomain'],
'token' => $d['connector_token'], 'secret' => isset($d['device_secret']) ? $d['device_secret'] : $secret,
'request' => isset($d['request']) ? $d['request'] : NULL);
}

public function status($secret)
{
return $this->call('/api/wedding/status', NULL, NULL, $secret, 'GET');
}

public function request($secret, $name, $reason = '')
{
return $this->call('/api/wedding/request', array('subdomain' => $name, 'reason' => $reason), NULL, $secret);
}



public function connect($email, $password, $create_account, $port, $subdomain = '')
{
if (!function_exists('curl_init')) {
return array('ok' => FALSE, 'error' => 'PHP thiếu tiện ích curl, không kết nối được máy chủ tên miền.');
}
$auth = $this->call('/api/auth/' . ($create_account ? 'register' : 'login'), array(
'email' => $email, 'password' => $password, 'password_confirm' => $password,
));
if (!$auth['ok']) {
return $auth;
}
$access = isset($auth['data']['access_token']) ? (string) $auth['data']['access_token'] : '';
if ($access === '') {
return array('ok' => FALSE, 'error' => 'Máy chủ tên miền không trả phiên đăng nhập.');
}
$prov = $this->call('/api/tenants/provision', array('pos_port' => (int) $port, 'subdomain' => (string) $subdomain), $access);
if (!$prov['ok']) {
return $prov;
}
$d = $prov['data'];
$host = strtolower((string) (isset($d['hostname']) ? $d['hostname'] : ''));
$token = (string) (isset($d['connector_token']) ? $d['connector_token'] : '');
if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $host)) {
return array('ok' => FALSE, 'error' => 'Máy chủ tên miền trả tên miền không hợp lệ.');
}
return array('ok' => TRUE, 'hostname' => $host, 'token' => $token,
'status' => isset($d['status']) ? (string) $d['status'] : '');
}
private function call($path, $body, $bearer = NULL, $device = NULL, $method = 'POST')
{
if (!function_exists('curl_init')) {
return array('ok' => FALSE, 'error' => 'PHP thiếu tiện ích curl.');
}
$headers = array('Content-Type: application/json', 'Accept: application/json');
if ($bearer) {
$headers[] = 'Authorization: Bearer ' . $bearer;
}
if ($device) {
$headers[] = 'X-Device-Secret: ' . $device;
}
$ch = curl_init($this->base() . $path);
$opts = $method === 'GET' ? array() : array(CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => json_encode($body));
curl_setopt_array($ch, $opts + array(
CURLOPT_HTTPHEADER => $headers,
CURLOPT_RETURNTRANSFER => TRUE,
CURLOPT_CONNECTTIMEOUT => 5,
CURLOPT_TIMEOUT => 40,
));
$raw = curl_exec($ch);
$err = curl_error($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($raw === FALSE) {
return array('ok' => FALSE, 'error' => 'Không kết nối được máy chủ tên miền (' . $err . '). Kiểm tra Internet của máy.');
}
$j = json_decode((string) $raw, TRUE);
if (!is_array($j)) {
return array('ok' => FALSE, 'error' => 'Máy chủ tên miền trả lỗi ' . $code . '.');
}
if (empty($j['success'])) {
return array('ok' => FALSE, 'error' => isset($j['message']) && $j['message'] ? (string) $j['message'] : 'Máy chủ tên miền báo lỗi ' . $code . '.');
}
return array('ok' => TRUE, 'data' => isset($j['data']) && is_array($j['data']) ? $j['data'] : array());
}
}