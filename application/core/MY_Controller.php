<?php

defined('BASEPATH') OR exit('No direct script access allowed');









class MY_Controller extends CI_Controller
{

protected $allow_before_setup = FALSE;
public function __construct()
{
parent::__construct();
$this->load->library('schema');
$this->schema->ensure();
$this->load->model('settings_model');

lang_init('admin', $this->settings_model->get('site_lang', 'vi'), $this->settings_model->get('admin_lang', 'vi'), $this->settings_model->get('site_langs', ''));
$this->output->set_header('X-Content-Type-Options: nosniff');
$this->output->set_header('Referrer-Policy: same-origin');
$this->output->set_header('X-Frame-Options: SAMEORIGIN');
if (!$this->allow_before_setup && $this->settings_model->get('setup_done') !== '1') {
redirect('setup');
}
}

private $admin_ok;




protected function is_admin()
{
if ($this->admin_ok === NULL) {
$id = (int) $this->session->userdata('ac_user_id');
$this->admin_ok = FALSE;
if ($id > 0) {
$row = $this->db->query('SELECT session_version FROM users WHERE id = ?', array($id))->row();
if ($row && (int) $row->session_version === (int) $this->session->userdata('ac_sv')) {
$this->admin_ok = TRUE;
} else {
$this->session->unset_userdata(array('ac_user_id', 'ac_sv'));
}
}
}
return $this->admin_ok;
}

protected function login_as(array $user)
{
$this->session->set_userdata(array('ac_user_id' => (int) $user['id'],
'ac_sv' => isset($user['session_version']) ? (int) $user['session_version'] : 0));
$this->admin_ok = NULL;
}

protected function draft_mode()
{
return $this->is_admin() && $this->input->get('xem') !== 'khach';
}
protected function render($view, array $data = array(), $layout = 'public')
{
$this->load->model('content_model');
if (!$this->draft_mode()) {
$this->content_model->use_published();
} else {
lang_cur(lang_admin()); 
lang_area('admin'); 
}
$themes = $this->content_model->registry('themes');
$data['content'] = $this->content_model;
$data['theme'] = $this->content_model->theme();
$data['theme_accent'] = $themes[$data['theme']]['accent'];
$data['draft'] = $this->draft_mode();
$data['settings'] = $this->settings_model->all();
$data['couple'] = $this->content_model->couple_title();
$data['is_admin'] = $this->is_admin();
$data['flash'] = flash();
$data['content_view'] = $view;
$this->load->view('partials/layout_' . $layout, $data);
}
}
class Public_Controller extends MY_Controller
{

protected $open_methods = array();
public function __construct()
{
parent::__construct();
lang_init('public', $this->settings_model->get('site_lang', 'vi'), $this->settings_model->get('admin_lang', 'vi'), $this->settings_model->get('site_langs', ''));
$open = in_array($this->router->fetch_method(), $this->open_methods, TRUE);
$this->load->model('content_model');

if (!$this->is_admin() && !$this->content_model->is_published()) {
$this->output->set_status_header(503);
$this->render('public/coming_soon', array('title' => 'Trang đang được chuẩn bị'));
$this->output->_display();
exit;
}
if (!$this->site_unlocked() && !$open) {
$this->render('public/site_locked', array('title' => 'Trang riêng tư'));
$this->output->_display();
exit;
}
}
protected function site_unlocked()
{
if ($this->settings_model->get('site_password_hash') === '' || $this->is_admin()) {
return TRUE;
}
return (bool) $this->session->userdata('ac_site_unlocked');
}
protected function album_unlocked(array $album)
{
if ($this->is_admin() || $album['visibility'] === 'public') {
return TRUE;
}
if ($album['visibility'] === 'password') {
$ok = (array) $this->session->userdata('ac_album_unlocked');
return in_array((int) $album['id'], $ok, TRUE);
}
return FALSE;
}
}
class Admin_Controller extends MY_Controller
{
protected $user;
public function __construct()
{
parent::__construct();
if (!$this->is_admin()) {
if ($this->input->is_ajax_request()) {
json_out(array('ok' => FALSE, 'error' => 'Phiên đăng nhập đã hết, hãy đăng nhập lại.'), 401);
$this->output->_display();
exit;
}
redirect('admin/login?next=' . rawurlencode(uri_string()));
}
$this->load->model(array('user_model', 'album_model', 'photo_model', 'wish_model'));
$this->user = $this->user_model->find($this->session->userdata('ac_user_id'));
if (!$this->user) {
$this->session->sess_destroy();
redirect('admin/login');
}

$this->load->library('tunnelrunner');
$this->tunnelrunner->ensure(app_port());
}
protected function render($view, array $data = array(), $layout = 'admin')
{
$data['user'] = $this->user;
$data['pending_photos'] = (int) $this->db->where('status', 'pending')->count_all_results('photos');
$data['pending_wishes'] = $this->wish_model->count_pending();
parent::render($view, $data, $layout);
}

protected function require_post()
{
if ($this->input->method() !== 'post') {
show_error('Phương thức không hợp lệ.', 405);
}
}
}