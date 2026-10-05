<?php

defined('BASEPATH') OR exit('No direct script access allowed');
class User_model extends CI_Model
{
public function count()
{
return (int) $this->db->count_all('users');
}
public function find($_v9bs9l6)
{
return $this->db->get_where('users', array('id' => (int) $_v9bs9l6))->row_array();
}
public function find_by_username($_v6mgqh2)
{
return $this->db->query('SELECT * FROM users WHERE username = ? COLLATE NOCASE', array((string) $_v6mgqh2))->row_array();
}
public function create($_vbxvzzi, $_vukiqvh, $_vjssnl5 = '')
{
$this->db->insert('users', array(
'username' => $_vbxvzzi,
'password_hash' => password_hash($_vukiqvh, PASSWORD_DEFAULT),
'display_name' => $_vjssnl5,
'created_at' => now_str(),
));
return (int) $this->db->insert_id();
}

public function verify($_v9b44f8, $_v9iuybk)
{
$_vkdi6xb = $this->find_by_username($_v9b44f8);
if (!$_vkdi6xb) {
// Vẫn tốn thời gian băm như khi có tài khoản, để thời gian phản hồi không lộ tên đăng nhập có tồn tại hay không.
password_verify((string) $_v9iuybk, password_hash('x', PASSWORD_DEFAULT));
return NULL;
}
if (!password_verify((string) $_v9iuybk, $_vkdi6xb['password_hash'])) {
return NULL;
}
$_v1rzjay = array('last_login_at' => now_str());
if (password_needs_rehash($_vkdi6xb['password_hash'], PASSWORD_DEFAULT)) {
$_v1rzjay['password_hash'] = password_hash($_v9iuybk, PASSWORD_DEFAULT);
}
$this->db->update('users', $_v1rzjay, array('id' => (int) $_vkdi6xb['id']));
return $_vkdi6xb;
}




public function set_password($_v1kvine, $_vibfaaw)
{
$_v1kvine = (int) $_v1kvine;
$this->db->set('password_hash', password_hash($_vibfaaw, PASSWORD_DEFAULT))
->set('session_version', 'session_version + 1', FALSE)
->where('id', $_v1kvine);
$_v1tvm54 = $this->db->update('users');
$CI =& get_instance();
if ($_v1tvm54 && !is_cli() && isset($CI->session) && (int) $CI->session->userdata('ac_user_id') === $_v1kvine) {
$_vbhj36s = $this->db->query('SELECT session_version FROM users WHERE id = ?', array($_v1kvine))->row();
$CI->session->set_userdata('ac_sv', $_vbhj36s ? (int) $_vbhj36s->session_version : 0);
}
return $_v1tvm54;
}
}