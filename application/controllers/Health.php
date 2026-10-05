<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Health extends CI_Controller
{

public function __construct()
{
parent::__construct();
$this->output->set_header('X-Content-Type-Options: nosniff');
$this->output->set_header('Referrer-Policy: same-origin');
$this->output->set_header('X-Frame-Options: SAMEORIGIN');
}
public function index()
{
$_vjvt17w = (bool) @$this->db->simple_query('SELECT 1');


if ($_vjvt17w && $this->db->table_exists('settings')) {
$_vokfxri = $this->db->query("SELECT value FROM settings WHERE key = 'setup_done'")->row();
if ($_vokfxri && $_vokfxri->value === '1') {
$this->load->library('tunnelrunner');
$this->tunnelrunner->ensure(app_port());


$_ve9zbvv = in_array((string) $this->input->server('REMOTE_ADDR'), array('127.0.0.1', '::1'), TRUE)
&& !$this->input->server('HTTP_CF_CONNECTING_IP') && !$this->input->server('HTTP_X_FORWARDED_FOR');
if ($this->input->get('beat') === '1' && $_ve9zbvv) {
$this->clean_orphans();
}
if ($this->input->get('beat') === '1' && $_ve9zbvv && !is_file(FCPATH . 'database/.small_done')) {
$_vrtmoa9 = @fopen(FCPATH . 'database/.small_backfill.lock', 'c');
if ($_vrtmoa9 && flock($_vrtmoa9, LOCK_EX | LOCK_NB)) { 
$this->load->model('photo_model');

$this->photo_model->backfill_small(DIRECTORY_SEPARATOR === '\\' ? 1.0 : 3.0);
flock($_vrtmoa9, LOCK_UN);
}
if ($_vrtmoa9) {
fclose($_vrtmoa9);
}
}
}
}
$this->clean_logs();

json_out(array('ok' => $_vjvt17w), $_vjvt17w ? 200 : 503);
}






public function csrf()
{
$this->output->set_header('Cache-Control: no-store, max-age=0');
json_out(array(
'ok' => TRUE,
'name' => $this->security->get_csrf_token_name(),
'hash' => $this->security->get_csrf_hash(),
));
}




private function clean_orphans()
{
$_vqh2bv1 = FCPATH . 'database/.orphans_at';
if (is_file($_vqh2bv1) && time() - filemtime($_vqh2bv1) < 3600) {
return;
}
@touch($_vqh2bv1);
$_vq7i9s7 = @fopen(FCPATH . 'database/.orphans.lock', 'c');
if (!$_vq7i9s7 || !flock($_vq7i9s7, LOCK_EX | LOCK_NB)) {
if ($_vq7i9s7) {
fclose($_vq7i9s7);
}
return;
}
$this->load->model('photo_model');
$_v9w0wwd = $this->photo_model->clean_orphans(3600, 2.0);
flock($_vq7i9s7, LOCK_UN);
fclose($_vq7i9s7);
if ($_v9w0wwd['deleted'] > 0) {
log_message('error', sprintf('Dọn ảnh mồ côi: xóa %d file (%s) không có trong bảng photos%s.', $_v9w0wwd['deleted'],
human_size($_v9w0wwd['bytes']), $_v9w0wwd['left'] ? ' — còn tiếp ở nhịp sau' : ''));
}
}

private function clean_logs()
{
$_vb3pi59 = FCPATH . 'database/.logs_cleaned_at';
if (is_file($_vb3pi59) && time() - filemtime($_vb3pi59) < 86400) {
return;
}
@touch($_vb3pi59);
$_vrhrm95 = rtrim((string) ($this->config->item('log_path') ?: APPPATH . 'logs/'), '/\\');
foreach ((array) glob($_vrhrm95 . '/log-*.php') as $_vmwl5dj) {
if (is_file($_vmwl5dj) && filemtime($_vmwl5dj) < time() - 30 * 86400) {
@unlink($_vmwl5dj);
}
}
}
}