<?php

defined('BASEPATH') OR exit('No direct script access allowed');
class Auth extends MY_Controller
{
public function login()
{
if ($this->is_admin()) {
redirect('admin');
}
$this->load->model('user_model');
$this->load->library('ratelimit');
$_vr6687s = '';
$_vyu9z28 = '';
$_vgl36ja = '';
if ($this->input->get('setup')) {

$_vyu9z28 = __('Trang đã tạo xong, hãy đăng nhập bằng mật khẩu vừa đặt.');
$_vgxnokn = (string) $this->input->get('u');
$_vgl36ja = preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $_vgxnokn) ? $_vgxnokn : '';
}
if ($this->input->method() === 'post') {
$_vgl36ja = trim((string) $this->input->post('username'));
if (!$this->ratelimit->allowed('login')) {
$_vr6687s = __('Bạn đã nhập sai quá nhiều lần. Hãy thử lại sau 15 phút.');
} else {
$_vgxnokn = $this->user_model->verify($_vgl36ja, (string) $this->input->post('password'));
if ($_vgxnokn) {
$this->ratelimit->clear('login');
$this->session->sess_regenerate(TRUE);
$this->login_as($_vgxnokn);
redirect($this->safe_next());
}
$this->ratelimit->hit('login');
$_vr6687s = __('Sai tên đăng nhập hoặc mật khẩu.');
}
}
$this->render('auth/login', array(
'title' => __('Đăng nhập quản trị'), 'error' => $_vr6687s, 'notice' => $_vyu9z28, 'username' => $_vgl36ja,
'next' => (string) $this->input->get('next'),
), 'bare');
}




public function logout()
{
if ($this->input->method() !== 'post') {
if (!$this->is_admin()) {
redirect('');
}
$this->render('auth/logout', array('title' => __('Đăng xuất')), 'bare');
return;
}
$this->session->unset_userdata(array('ac_user_id', 'ac_sv'));
$this->session->sess_regenerate(TRUE);
redirect('');
}

private function safe_next()
{
$_vlqw6mq = (string) ($this->input->post('next') ?: $this->input->get('next'));
return preg_match('~^admin(/[A-Za-z0-9_/-]*)?$~', $_vlqw6mq) ? $_vlqw6mq : 'admin';
}
}