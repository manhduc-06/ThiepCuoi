<?php

defined('BASEPATH') OR exit('No direct script access allowed');








class Tunnelrunner
{
private function dir()
{
return FCPATH . 'cloudflared';
}
private function log_path()
{
return $this->dir() . DIRECTORY_SEPARATOR . 'tunnel.log';
}





const MARK = 'anhcuoi-tunnel.yml';
public function config_path()
{
$f = $this->dir() . DIRECTORY_SEPARATOR . self::MARK;

if (!is_file($f) || strpos((string) @file_get_contents($f), 'no-autoupdate') === FALSE) {
@file_put_contents($f, "# Ảnh Cưới: cấu hình riêng để cloudflared không đọc ~/.cloudflared/config.yml của chương trình khác.\nno-autoupdate: true\n");
}
return $f;
}
private function is_windows()
{
return strncasecmp(PHP_OS, 'WIN', 3) === 0;
}
public function binary()
{
$exe = $this->is_windows() ? 'cloudflared.exe' : 'cloudflared';
$cands = array(
$this->dir() . '/' . $exe,
dirname(FCPATH) . '/Tools/cloudflared/' . $exe,
'/usr/local/bin/cloudflared', '/opt/homebrew/bin/cloudflared', '/usr/bin/cloudflared',
);
foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $p) {
if ($p !== '') {
$cands[] = rtrim($p, '/\\') . DIRECTORY_SEPARATOR . $exe;
}
}
foreach ($cands as $c) {
if (is_file($c) && ($this->is_windows() || is_executable($c))) {
return $c;
}
}
return NULL;
}
public function can_exec()
{
$disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
return function_exists('shell_exec') && !in_array('shell_exec', $disabled, TRUE);
}




private function process_running($needle)
{


$mine = $this->dir() . DIRECTORY_SEPARATOR . self::MARK;
$match = function ($cmd) use ($needle, $mine) {
return stripos($cmd, 'cloudflared') !== FALSE && strpos($cmd, $needle) !== FALSE && stripos($cmd, $mine) !== FALSE;
};
if ($this->is_windows()) {
if (!$this->can_exec()) {
return FALSE;
}
$out = (string) shell_exec('powershell -NoProfile -NonInteractive -Command "Get-CimInstance Win32_Process -Filter \"Name=\'cloudflared.exe\'\" | ForEach-Object { $_.CommandLine }" 2>NUL');
foreach (preg_split('/\r?\n/', $out) as $line) {
if ($match($line)) {
return TRUE;
}
}
return FALSE;
}
if (is_dir('/proc/self')) {
foreach ((array) glob('/proc/[0-9]*/cmdline') as $f) {
if ($match(str_replace("\0", ' ', (string) @file_get_contents($f)))) {
return TRUE;
}
}
return FALSE;
}
if (!$this->can_exec()) {
return FALSE;
}
foreach (explode("\n", (string) shell_exec('ps -axww -o command 2>/dev/null')) as $line) {
if ($match($line)) {
return TRUE;
}
}
return FALSE;
}

private function spawn(array $args, array $env = array())
{
$bin = $this->binary();
if (!$bin) {
return __('Chưa có cloudflared trên máy. Chạy lại Ảnh Cưới bằng run_mac.sh / run_window.bat (tự tải cloudflared).');
}
if (!$this->can_exec()) {
return __('Máy chủ không cho chạy chương trình nền. Khởi động lại Ảnh Cưới để link hoạt động.');
}
$log = $this->log_path();
@file_put_contents($log, '');
if ($this->is_windows()) {
$set = '';
foreach ($env as $k => $v) {
$set .= 'set ' . $k . '=' . $v . '&& ';
}
pclose(popen('start "AnhCuoi Tunnel" /MIN cmd /c "' . $set . '"' . $bin . '" ' . implode(' ', $args) . ' >> "' . $log . '" 2>&1"', 'r'));
return TRUE;
}
$prefix = '';
foreach ($env as $k => $v) {
$prefix .= $k . '=' . escapeshellarg($v) . ' ';
}



$bash = is_executable('/bin/bash') ? '/bin/bash' : '';
$close = '';
for ($fd = 3; $fd <= ($bash ? 31 : 9); $fd++) {
$close .= ' ' . $fd . '>&-';
}
$cmd = $prefix . 'NO_AUTOUPDATE=true nohup ' . escapeshellarg($bin) . ' '
. implode(' ', array_map('escapeshellarg', $args)) . ' >> ' . escapeshellarg($log)
. ' 2>&1 < /dev/null' . $close . ' & echo $!';
$pid = trim((string) shell_exec($bash ? $bash . ' -c ' . escapeshellarg($cmd) : $cmd));
if (ctype_digit($pid)) {
@file_put_contents($this->dir() . '/web_tunnel.pid', $pid);
@chmod($this->dir() . '/web_tunnel.pid', 0600);
}
return TRUE;
}

public function start($token)
{
if (!preg_match('/^[A-Za-z0-9_.=+\/-]{20,}$/', $token)) {
return __('Token tunnel không hợp lệ.');
}
if (getenv('ANHCUOI_TUNNEL_DRYRUN')) { 
@file_put_contents($this->log_path(), "dry-run\nRegistered tunnel connection\n");
return TRUE;
}
$this->stop();

return $this->spawn(array('tunnel', '--no-autoupdate', '--config', $this->config_path(), 'run'), array('TUNNEL_TOKEN' => $token));
}

public function start_quick($port)
{
if (getenv('ANHCUOI_TUNNEL_DRYRUN')) {
@file_put_contents($this->log_path(), "dry-run\n|  https://dry-run-" . (int) $port . ".trycloudflare.com  |\nRegistered tunnel connection\n");
return TRUE;
}
$this->stop();

return $this->spawn(array('tunnel', '--no-autoupdate', '--config', $this->config_path(), '--url', 'http://localhost:' . (int) $port));
}




public function ensure($port, $force = FALSE)
{
if (hosted()) { 
return 'off';
}
$cfg = tunnel_config();
if ($cfg['mode'] === 'off' && !$cfg['auto']) {
return 'off';
}
$stamp = $this->dir() . '/.ensure_at';
if (!$force && is_file($stamp) && time() - filemtime($stamp) < 20) {
return 'running';
}
@touch($stamp);
if ($cfg['auto']) {
$cfg = $this->auto_domain($port, $force, $cfg);
}
if (getenv('ANHCUOI_TUNNEL_DRYRUN')) { 
$log = is_file($this->log_path()) ? (string) @file_get_contents($this->log_path()) : '';
$want = $cfg['mode'] === 'quick' ? 'trycloudflare.com' : 'Registered tunnel connection';
if (strpos($log, $want) === FALSE || ($cfg['mode'] === 'token' && strpos($log, 'trycloudflare.com') !== FALSE)) {
$r = $cfg['mode'] === 'quick' ? $this->start_quick($port) : $this->start($cfg['token']);
return $r === TRUE ? 'started' : $r;
}
return 'running';
}
if ($cfg['mode'] === 'quick') {
if ($this->process_running('--url http://localhost:' . (int) $port) || $this->process_running('--url http://127.0.0.1:' . (int) $port)) {
return 'running';
}
$r = $this->start_quick($port);
return $r === TRUE ? 'started' : $r;
}
if ($cfg['token'] !== '' && !$this->web_pid_alive() && !$this->process_running(' run')) {
$r = $this->start($cfg['token']);
return $r === TRUE ? 'started' : $r;
}
return 'running';
}
private function web_pid_alive()
{
$f = $this->dir() . '/web_tunnel.pid';
$pid = is_file($f) ? (int) trim((string) @file_get_contents($f)) : 0;
if ($pid < 2 || $this->is_windows()) {
return FALSE;
}
return function_exists('posix_kill') ? @posix_kill($pid, 0) : (is_dir('/proc/' . $pid) || trim((string) shell_exec('kill -0 ' . $pid . ' 2>&1; echo $?')) === '0');
}






private function auto_domain($port, $force, array $cfg)
{
$CI =& get_instance();
$CI->load->library('rabitcloud');
$id = cloud_identity();
$dir = $this->dir();
if (!$id || $cfg['mode'] !== 'token' || $cfg['token'] === '') {

$claim_stamp = $dir . '/.claim_at';
$fails = is_file($claim_stamp) ? (int) @file_get_contents($claim_stamp) : 0;
$waits = array(0, 60, 120, 300, 600);
$wait = $waits[min($fails, count($waits) - 1)];
if (!$force && is_file($claim_stamp) && time() - filemtime($claim_stamp) < max($wait, 20)) {
return $cfg;
}
@file_put_contents($claim_stamp, (string) ($fails + 1));
$r = $CI->rabitcloud->claim($port, $id ? $id['device_secret'] : '');
if (!$r['ok'] && $id && preg_match('/không hợp lệ|thu hồi/u', $r['error'])) {

@unlink($dir . '/cloud.json');
$id = NULL;
$r = $CI->rabitcloud->claim($port, '');
}
if ($r['ok']) {
@unlink($claim_stamp);
$this->claim_ok($port);
save_cloud_identity(array('device_secret' => $r['secret'], 'subdomain' => $r['subdomain'],
'hostname' => $r['hostname'], 'request' => $r['request'], 'updated_at' => date('c')));
save_tunnel_config('token', $r['token'], $r['hostname'], TRUE);
$this->stop(); 
@unlink($dir . '/.status_at');
return tunnel_config();
}
log_message('error', 'jagame claim: ' . $r['error']);
$this->claim_failed($r['error']);
if ($cfg['mode'] !== 'quick') {
save_tunnel_config('quick', '', '', TRUE);
}
return tunnel_config();
}
if ($this->claimed_port() !== (int) $port) {
return $this->reclaim_port($port, $force, $id, $cfg);
}
$status_stamp = $dir . '/.status_at';
if ($force || !is_file($status_stamp) || time() - filemtime($status_stamp) >= 600) {
@touch($status_stamp);
$this->refresh_status($id, $cfg);
return tunnel_config();
}
return $cfg;
}




private function reclaim_port($port, $force, array $id, array $cfg)
{
$dir = $this->dir();
$claim_stamp = $dir . '/.claim_at';
$fails = is_file($claim_stamp) ? (int) @file_get_contents($claim_stamp) : 0;
$waits = array(0, 60, 120, 300, 600);
$wait = $waits[min($fails, count($waits) - 1)];
if (!$force && $fails > 0 && time() - filemtime($claim_stamp) < $wait) {
return $cfg;
}
@file_put_contents($claim_stamp, (string) ($fails + 1));
$CI =& get_instance();
$r = $CI->rabitcloud->claim($port, $id['device_secret']);
if (!$r['ok']) {
log_message('error', 'jagame re-claim (port ' . (int) $port . '): ' . $r['error']);
$this->claim_failed($r['error']);
if (preg_match('/không hợp lệ|thu hồi/u', $r['error'])) {

@unlink($dir . '/cloud.json');
save_tunnel_config('quick', '', '', TRUE);
return tunnel_config();
}
return $cfg;
}
@unlink($claim_stamp);
$this->claim_ok($port);
save_cloud_identity(array('subdomain' => $r['subdomain'], 'hostname' => $r['hostname'],
'request' => $r['request'], 'updated_at' => date('c')));
if ($r['token'] !== $cfg['token'] || $r['hostname'] !== $cfg['hostname']) {
save_tunnel_config('token', $r['token'], $r['hostname'], TRUE);
if ($r['token'] !== $cfg['token']) {
$this->stop(); 
}
}
return tunnel_config();
}

public function claimed_port()
{
$f = $this->dir() . '/.claim_port';
return is_file($f) ? (int) trim((string) @file_get_contents($f)) : 0;
}
private function claim_ok($port)
{
@file_put_contents($this->dir() . '/.claim_port', (string) (int) $port, LOCK_EX);
@unlink($this->dir() . '/.claim_error');
}
private function claim_failed($message)
{
@file_put_contents($this->dir() . '/.claim_error', json_encode(array('at' => date('c'), 'message' => (string) $message),
JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
}




public function last_claim_error()
{
$f = $this->dir() . '/.claim_error';
$j = is_file($f) ? json_decode((string) @file_get_contents($f), TRUE) : NULL;
return (is_array($j) && isset($j['message'])) ? array('at' => (string) $j['at'], 'message' => (string) $j['message']) : NULL;
}

public function port_mismatch($port = NULL)
{
$cfg = tunnel_config();
$claimed = $this->claimed_port();
return $cfg['auto'] && $cfg['mode'] === 'token' && $claimed > 0 && $claimed !== (int) ($port === NULL ? app_port() : $port);
}



public function status_error()
{
$err = $this->last_claim_error();
if ($this->port_mismatch()) {
return __('Link {host} đang trỏ vào cổng cũ {old}, chưa báo được cổng mới {new} cho máy chủ jagame.vn{err}. Máy sẽ tự thử lại.', array(
'host' => tunnel_config()['hostname'], 'old' => $this->claimed_port(), 'new' => app_port(), 'err' => $err ? ' (' . $err['message'] . ')' : ''));
}
return $err ? $err['message'] : NULL;
}

public function refresh_status($id = NULL, $cfg = NULL)
{
$id = $id ?: cloud_identity();
$cfg = $cfg ?: tunnel_config();
if (!$id) {
return NULL;
}
$CI =& get_instance();
$CI->load->library('rabitcloud');
$r = $CI->rabitcloud->status($id['device_secret']);
if (!$r['ok']) {
if (preg_match('/không hợp lệ|thu hồi/u', $r['error'])) {

@unlink($this->dir() . '/cloud.json');
save_tunnel_config('quick', '', '', TRUE);
}
return NULL;
}
$d = $r['data'];
save_cloud_identity(array('subdomain' => $d['subdomain'], 'hostname' => $d['hostname'], 'request' => $d['request'], 'updated_at' => date('c')));
if ($cfg['mode'] === 'token' && $cfg['hostname'] !== $d['hostname']) {
save_tunnel_config('token', $cfg['token'], $d['hostname'], $cfg['auto']);
}
return $d;
}
public function stop()
{
$f = $this->dir() . '/web_tunnel.pid';
if (is_file($f)) {
$pid = (int) trim((string) @file_get_contents($f));
if ($pid > 1 && $this->can_exec() && !$this->is_windows()) {
shell_exec('kill ' . $pid . ' 2>/dev/null');
}
@unlink($f);
}
if ($this->is_windows() && $this->can_exec()) {
shell_exec('TASKKILL /F /T /FI "WINDOWTITLE eq AnhCuoi Tunnel*" >nul 2>&1');
}
}

public function quick_url()
{
$log = $this->log_path();
if (!is_file($log)) {
return NULL;
}
$tail = (string) @file_get_contents($log, FALSE, NULL, max(0, filesize($log) - 50000));
if (preg_match_all('~https://([a-z0-9-]+)\.trycloudflare\.com~', $tail, $m)) {
for ($i = count($m[0]) - 1; $i >= 0; $i--) {
if ($m[1][$i] !== 'api') {
return $m[0][$i];
}
}
}
return NULL;
}




public function status()
{
if ($this->port_mismatch()) {
return 'error';
}
$log = $this->log_path();
if (!is_file($log)) {
return 'off';
}
$tail = (string) @file_get_contents($log, FALSE, NULL, max(0, filesize($log) - 20000));
if (stripos($tail, 'Registered tunnel connection') !== FALSE) {
return 'connected';
}
return (time() - filemtime($log) < 120) ? 'connecting' : 'off';
}
}