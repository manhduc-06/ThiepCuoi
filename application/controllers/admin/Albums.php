<?php

defined('BASEPATH') OR exit('No direct script access allowed');
class Albums extends Admin_Controller
{
public function index()
{
$this->render('admin/albums', array('title' => 'Album', 'albums' => $this->album_model->list_all()));
}
public function create()
{
$this->form(NULL);
}
public function edit($_v8m5erk = 0)
{
$_vm9b40r = $this->album_model->find($_v8m5erk);
if (!$_vm9b40r) {
show_404();
}
$this->form($_vm9b40r);
}
private function form($_vhy8f6p)
{
$_v971zl0 = array();
$data = $_vhy8f6p ?: array(
'title' => '', 'description' => '', 'event_date' => '', 'visibility' => 'public', 'allow_guest_upload' => 0,
);
if ($this->input->method() === 'post') {
$data['title'] = mb_substr(trim((string) $this->input->post('title')), 0, 120);
$data['description'] = mb_substr(trim((string) $this->input->post('description')), 0, 2000);
$data['event_date'] = trim((string) $this->input->post('event_date'));
$data['visibility'] = (string) $this->input->post('visibility');
$data['allow_guest_upload'] = $this->input->post('allow_guest_upload') ? 1 : 0;
$_vt1kjwf = (string) $this->input->post('password');
if ($data['title'] === '') {
$_v971zl0[] = __('Hãy đặt tên album.');
}
if (!in_array($data['visibility'], Album_model::VISIBILITIES, TRUE)) {
$_v971zl0[] = __('Chế độ hiển thị không hợp lệ.');
}
if ($data['event_date'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['event_date'])) {
$_v971zl0[] = __('Ngày không hợp lệ.');
}
$_vl9h63d = $_vhy8f6p && !empty($_vhy8f6p['password_hash']);
if ($data['visibility'] === 'password' && $_vt1kjwf === '' && !$_vl9h63d) {
$_v971zl0[] = __('Album có mật khẩu thì cần đặt mật khẩu.');
}
if ($_vt1kjwf !== '' && mb_strlen($_vt1kjwf) < 8) {
$_v971zl0[] = __('Mật khẩu album tối thiểu 8 ký tự.');
}
if (!$_v971zl0) {
$_v4mmh64 = array(
'title' => $data['title'],
'description' => $data['description'],
'event_date' => $data['event_date'] !== '' ? $data['event_date'] : NULL,
'visibility' => $data['visibility'],
'allow_guest_upload' => $data['allow_guest_upload'],
);
if ($_vt1kjwf !== '') {
$_v4mmh64['password_hash'] = password_hash($_vt1kjwf, PASSWORD_DEFAULT);
}
if ($_vhy8f6p) {
$this->album_model->update($_vhy8f6p['id'], $_v4mmh64);
$_vqjhue0 = (int) $_vhy8f6p['id'];
flash('success', __('Đã lưu album.'));
} else {
$_vqjhue0 = $this->album_model->create($_v4mmh64);
flash('success', __('Đã tạo album. Giờ hãy thêm ảnh.'));
}
redirect('admin/albums/view/' . $_vqjhue0);
}
}
$this->render('admin/album_form', array(
'title' => $_vhy8f6p ? __('Sửa album') : __('Album mới'), 'album' => $data, 'is_new' => !$_vhy8f6p, 'errors' => $_v971zl0,
'del_info' => $_vhy8f6p ? $this->album_model->delete_info($_vhy8f6p['id']) : NULL,
));
}
public function view($_vetqph5 = 0)
{
$_vgzd91a = $this->album_model->find($_vetqph5);
if (!$_vgzd91a) {
show_404();
}
$this->render('admin/album_detail', array(
'title' => $_vgzd91a['title'],
'album' => $_vgzd91a,
'photos' => $this->photo_model->by_album($_vgzd91a['id'], NULL),
'albums' => $this->album_model->list_all(),
'max_mb' => (int) $this->config->item('photo_owner_max_mb'),
));
}
public function delete($_vqa9byq = 0)
{
$this->require_post();
$_vyviaj2 = $this->album_model->find($_vqa9byq);
if ($_vyviaj2) {
$this->album_model->delete($_vyviaj2['id']);
flash('success', __('Đã xóa album "{title}" và toàn bộ ảnh trong đó.', array('title' => $_vyviaj2['title'])));
}
redirect('admin/albums');
}
public function reorder()
{
$this->require_post();
$_vs8v7un = array_filter(array_map('intval', explode(',', (string) $this->input->post('ids'))));
json_out(array('ok' => (bool) $this->album_model->reorder($_vs8v7un)));
}
}