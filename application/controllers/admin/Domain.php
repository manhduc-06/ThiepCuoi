<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Domain extends Admin_Controller
{
public function __construct()
{
parent::__construct();
if (hosted()) { 
show_404();
}
$this->load->library(array('rabitcloud', 'tunnelrunner', 'ratelimit'));
}
public function connect()
{
$this->require_post();
$_v39zlt1 = trim((string) $this->input->post('email'));
$_vttr5ej = (string) $this->input->post('password');
if (!filter_var($_v39zlt1, FILTER_VALIDATE_EMAIL) || $_vttr5ej === '') {
return json_out(array('ok' => FALSE, 'error' => __('Nhập email và mật khẩu tài khoản Rabit Cloud.')), 422);
}
if (!$this->ratelimit->allowed('login')) {
return json_out(array('ok' => FALSE, 'error' => __('Thử quá nhiều lần, đợi 15 phút.')), 429);
}
$this->ratelimit->hit('login');
$_v379phk = strtolower(trim((string) $this->input->post('subdomain')));
if ($_v379phk !== '' && !preg_match('/^[a-z0-9]([a-z0-9-]{1,18}[a-z0-9])?$/', $_v379phk)) {
return json_out(array('ok' => FALSE, 'error' => __('Tên 3–20 ký tự, chỉ chữ thường không dấu, số và dấu gạch ngang.')), 422);
}
$_vqucy81 = $this->rabitcloud->connect($_v39zlt1, $_vttr5ej, (bool) $this->input->post('create'), $this->local_port(), $_v379phk);
if (!$_vqucy81['ok']) {
return json_out($_vqucy81, 502);
}
if ($_vqucy81['token'] === '') {

save_tunnel_config('off', '', $_vqucy81['hostname']);
return json_out(array('ok' => TRUE, 'hostname' => $_vqucy81['hostname'], 'running' => FALSE,
'message' => __('Đã giữ tên miền {host} nhưng Rabit Cloud chưa kích hoạt. Thử lại sau ít phút.', array('host' => $_vqucy81['hostname']))));
}
if (!save_tunnel_config('token', $_vqucy81['token'], $_vqucy81['hostname'])) {
return json_out(array('ok' => FALSE, 'error' => __('Không ghi được cấu hình tunnel (thư mục cloudflared không ghi được).')), 500);
}
$_v8huub3 = $this->tunnelrunner->start($_vqucy81['token']);
json_out(array(
'ok' => TRUE,
'hostname' => $_vqucy81['hostname'],
'url' => 'https://' . $_vqucy81['hostname'] . '/',
'running' => $_v8huub3 === TRUE,
'message' => $_v8huub3 === TRUE ? __('Đang kết nối tới {host}…', array('host' => $_vqucy81['hostname'])) : $_v8huub3,
));
}

public function check()
{
$_v4fkq1c = strtolower(trim((string) $this->input->get('name')));
json_out($this->rabitcloud->check_name($_v4fkq1c));
}




public function request()
{
$this->require_post();
$_vm2pbny = cloud_identity();
if (!$_vm2pbny) {
return json_out(array('ok' => FALSE, 'error' => __('Máy chưa có link .{domain} (đang thử tạo, đợi ít phút).', array('domain' => $this->config->item('cloud_domain')))), 409);
}
$_v1y2bph = strtolower(trim((string) $this->input->post('subdomain')));
if (!preg_match('/^[a-z0-9]([a-z0-9-]{1,18}[a-z0-9])?$/', $_v1y2bph)) {
return json_out(array('ok' => FALSE, 'error' => __('Tên 3–20 ký tự, chỉ chữ thường không dấu, số và dấu gạch ngang.')), 422);
}
$_vj198rh = $this->rabitcloud->request($_vm2pbny['device_secret'], $_v1y2bph, mb_substr(trim((string) $this->input->post('reason')), 0, 300));
if (!$_vj198rh['ok']) {
return json_out($_vj198rh, 422);
}
save_cloud_identity(array('request' => $_vj198rh['data']['request']));
json_out(array('ok' => TRUE, 'request' => $_vj198rh['data']['request'], 'message' => __('Đã gửi yêu cầu, admin jagame.vn sẽ duyệt sớm.')));
}

public function status()
{
$_vn1ir06 = tunnel_config();
$this->tunnelrunner->ensure(app_port());
if ($this->input->get('refresh')) {
$this->tunnelrunner->refresh_status();
}
$_vn1ir06 = tunnel_config();
$_v9zloue = cloud_identity();
$_v030t18 = public_url();
json_out(array('ok' => TRUE, 'mode' => $_vn1ir06['mode'], 'hostname' => $_vn1ir06['hostname'],
'status' => $_vn1ir06['mode'] === 'off' ? 'off' : $this->tunnelrunner->status(),
'public_url' => $_v030t18, 'is_public' => strpos($_v030t18, 'https://') === 0,
'request' => $_v9zloue && isset($_v9zloue['request']) ? $_v9zloue['request'] : NULL,

'last_error' => $this->tunnelrunner->last_claim_error(),
'error' => $_vn1ir06['mode'] === 'off' ? NULL : $this->tunnelrunner->status_error()));
}

public function retry()
{
$this->require_post();
$_v4w5q31 = tunnel_config();
if ($_v4w5q31['mode'] === 'off' && !$_v4w5q31['auto']) {
return json_out(array('ok' => FALSE, 'error' => __('Link Internet đang tắt.')), 409);
}
$this->tunnelrunner->ensure(app_port(), TRUE);
$_vuugizy = tunnel_config();
$_vv77h3r = $this->tunnelrunner->last_claim_error();
json_out(array('ok' => TRUE, 'mode' => $_vuugizy['mode'], 'hostname' => $_vuugizy['hostname'], 'last_error' => $_vv77h3r,
'error' => $this->tunnelrunner->status_error()));
}

public function quick()
{
$this->require_post();
save_tunnel_config('quick', '', '', TRUE); 
$_vnkx7ok = $this->tunnelrunner->ensure(app_port(), TRUE);
json_out(array('ok' => in_array($_vnkx7ok, array('started', 'running'), TRUE), 'error' => is_string($_vnkx7ok) && !in_array($_vnkx7ok, array('started', 'running', 'off'), TRUE) ? $_vnkx7ok : NULL));
}
public function disconnect()
{
$this->require_post();
$this->tunnelrunner->stop();
save_tunnel_config('off');
@unlink(FCPATH . 'database/.public_url');
flash('success', __('Đã tắt link công khai.'));
redirect('admin/share');
}
private function local_port()
{
return app_port();
}
}