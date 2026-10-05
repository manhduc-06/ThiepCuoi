<?php

defined('BASEPATH') OR exit('No direct script access allowed');











class Ratelimit
{
const COOKIE = 'ac_dev';
private $CI;

private $device;
public function __construct()
{
$this->CI =& get_instance();
}

private function limit($scope)
{
$l = $this->CI->config->item('rl_' . $scope);
if (!is_array($l) || count($l) < 2) {
return array(30, 900, NULL, NULL);
}
return array((int) $l[0], (int) $l[1], isset($l[2]) ? (int) $l[2] : NULL, isset($l[3]) ? (int) $l[3] : NULL);
}

/** IP dùng để đếm: IPv4 giữ nguyên, IPv6 gộp theo /64 (một người dùng thường có cả dải /64 để xoay vòng). */
public static function ip_bucket($ip)
{
$bin = @inet_pton((string) $ip);
if ($bin === FALSE || strlen($bin) !== 16) {
return (string) $ip;
}
return inet_ntop(substr($bin, 0, 8) . str_repeat("\0", 8)) . '/64';
}

private function ip()
{
return self::ip_bucket(client_ip());
}

public function device()
{
if ($this->device !== NULL) {
return $this->device;
}
$v = isset($_COOKIE[self::COOKIE]) ? (string) $_COOKIE[self::COOKIE] : '';
if (!preg_match('/^[a-f0-9]{32}$/', $v)) {
$v = bin2hex(random_bytes(16));
if (!headers_sent() && !is_cli()) {
setcookie(self::COOKIE, $v, array(
'expires' => time() + 365 * 86400,
'path' => (string) ($this->CI->config->item('cookie_path') ?: '/'),
'secure' => (bool) $this->CI->config->item('cookie_secure'),
'httponly' => TRUE,
'samesite' => 'Lax',
));
}
$_COOKIE[self::COOKIE] = $v;
}
return $this->device = $v;
}
private function count_since($scope, $col, $val, $since)
{
$q = @$this->CI->db->query('SELECT COUNT(*) AS n FROM rate_events WHERE scope = ? AND ' . ($col === 'device' ? 'device' : 'ip') . ' = ? AND created_at > ?',
array($scope, $val, $since));
return $q ? (int) $q->row()->n : 0;
}

public function allowed($scope)
{
list($max, $window, $max_ip, $max_all) = $this->limit($scope);
$since = time() - $window;
if ($max_all !== NULL && $this->count_all_since($scope, $since) >= $max_all) {
return FALSE;
}
if ($max_ip === NULL) {
return $this->count_since($scope, 'ip', $this->ip(), $since) < $max;
}
return $this->count_since($scope, 'device', $this->device(), $since) < $max
&& $this->count_since($scope, 'ip', $this->ip(), $since) < $max_ip;
}

private function count_all_since($scope, $since)
{
$q = @$this->CI->db->query('SELECT COUNT(*) AS n FROM rate_events WHERE scope = ? AND created_at > ?', array($scope, $since));
return $q ? (int) $q->row()->n : 0;
}
public function hit($scope)
{
list(, , $max_ip) = $this->limit($scope);
@$this->CI->db->insert('rate_events', array(
'scope' => $scope,
'ip' => $this->ip(),
'device' => $max_ip === NULL ? '' : $this->device(),
'created_at' => time(),
));

if (mt_rand(1, 100) === 1) {
@$this->CI->db->query('DELETE FROM rate_events WHERE created_at < ?', array(time() - 86400));
}
}

public function clear($scope)
{
list(, , $max_ip) = $this->limit($scope);
$where = $max_ip === NULL ? array('scope' => $scope, 'ip' => $this->ip()) : array('scope' => $scope, 'device' => $this->device());
@$this->CI->db->delete('rate_events', $where);
}
}