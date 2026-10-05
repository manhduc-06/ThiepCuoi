<?php

defined('BASEPATH') OR exit('No direct script access allowed');




class Setup extends MY_Controller
{
protected $allow_before_setup = TRUE;
public function __construct()
{
parent::__construct();
$this->load->model('user_model');
if ($this->settings_model->get('setup_done') === '1' && $this->user_model->count() > 0) {
$this->already_done();
}
}




private function already_done()
{
if ($this->is_admin()) {
redirect('');
}
$_vseep8p = trim((string) $this->input->post('username'));
redirect('admin/login?setup=1' . (preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $_vseep8p) ? '&u=' . rawurlencode($_vseep8p) : ''));
}
public function index()
{


if (via_tunnel()) {
$this->output->set_status_header(403);
$this->render('public/setup_local_only', array('title' => __('Cài đặt lần đầu')), 'bare');
return;
}
$_vjts5ly = array();
$_vjzmsj5 = array(
'groom_name' => '', 'bride_name' => '', 'wedding_date' => '', 'username' => 'admin', 'tunnel_mode' => 'auto',
);
if ($this->input->method() === 'post') {
foreach ($_vjzmsj5 as $_vnmht5l => $_v41uw1o) {
$_vjzmsj5[$_vnmht5l] = trim((string) $this->input->post($_vnmht5l));
}
$_vcneguu = (string) $this->input->post('password');
$_v40o70z = (string) $this->input->post('password2');
if ($_vjzmsj5['groom_name'] === '' || $_vjzmsj5['bride_name'] === '') {
$_vjts5ly[] = __('Hãy nhập tên cô dâu và chú rể.');
}
if ($_vjzmsj5['wedding_date'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_vjzmsj5['wedding_date'])) {
$_vjts5ly[] = __('Ngày cưới không hợp lệ.');
}
if (!preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $_vjzmsj5['username'])) {
$_vjts5ly[] = __('Tên đăng nhập 3–32 ký tự, chỉ gồm chữ không dấu, số, dấu . _ -');
}
if (mb_strlen($_vcneguu) < 8) {
$_vjts5ly[] = __('Mật khẩu tối thiểu 8 ký tự.');
} elseif ($_vcneguu !== $_v40o70z) {
$_vjts5ly[] = __('Hai lần nhập mật khẩu không khớp.');
}
if (!in_array($_vjzmsj5['tunnel_mode'], array('off', 'auto'), TRUE)) {
$_vjzmsj5['tunnel_mode'] = 'auto';
}
if (!$_vjts5ly) {

$_vg39aml = @fopen(FCPATH . 'database/.setup.lock', 'c');
if ($_vg39aml) {
flock($_vg39aml, LOCK_EX);
}
if ($this->user_model->count() > 0) {
$this->already_done();
}
$_v42n01q = $this->user_model->create($_vjzmsj5['username'], $_vcneguu, $_vjzmsj5['groom_name'] . ' & ' . $_vjzmsj5['bride_name']);
$this->settings_model->set_many(array(
'groom_name' => mb_substr($_vjzmsj5['groom_name'], 0, 80),
'bride_name' => mb_substr($_vjzmsj5['bride_name'], 0, 80),
'wedding_date' => $_vjzmsj5['wedding_date'],
'setup_done' => '1',

'admin_lang' => lang_cur(),
'site_lang' => lang_cur(),
'site_langs' => lang_cur(),
));
if ($_vg39aml) {
flock($_vg39aml, LOCK_UN);
fclose($_vg39aml);
}
$this->load->model('album_model');
$_v03et0a = $this->album_model->create(array('title' => __('Ảnh cưới{_}', array('_' => '')), 'visibility' => 'public'));
$this->settings_model->set_many(array('home_album_id' => $_v03et0a));
$this->album_model->guest_album_id();
if ($_vjzmsj5['tunnel_mode'] === 'off') {
save_tunnel_config('off');
} elseif (!is_file(FCPATH . 'cloudflared/tunnel.json')) {
save_tunnel_config('quick', '', '', TRUE); 
}

$this->load->library('tunnelrunner');
$this->tunnelrunner->ensure(app_port(), TRUE);
$this->session->sess_regenerate(TRUE);
$this->login_as($this->user_model->find($_v42n01q));
flash('success', __('Đã tạo trang cưới ♡'));
redirect('');
}
}

$this->render('setup/index', array('title' => __('Cài đặt lần đầu'), 'form' => $_vjzmsj5, 'errors' => $_vjts5ly, 'hide_lang' => TRUE), 'bare');
}
}