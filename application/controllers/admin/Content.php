<?php

defined('BASEPATH') OR exit('No direct script access allowed');



class Content extends Admin_Controller
{
public function __construct()
{
parent::__construct();
$this->require_post();
$this->load->model('content_model');
}
public function text()
{
$_vyh43d1 = (string) $this->input->post('key');
$_vuh3mso = $this->content_model->save_text($_vyh43d1, (string) $this->input->post('value'));
if ($_vuh3mso !== TRUE) {
return json_out(array('ok' => FALSE, 'error' => $_vuh3mso), 422);
}
json_out(array('ok' => TRUE, 'value' => $this->content_model->text($_vyh43d1)));
}
public function image()
{
$_vu6i13z = $this->uploaded('photo');
if (is_string($_vu6i13z)) {
return json_out(array('ok' => FALSE, 'error' => $_vu6i13z), 422);
}
@set_time_limit(120);
$_vtxdkat = $this->content_model->replace_image((string) $this->input->post('key'), $_vu6i13z['tmp_name'], $_vu6i13z['name']);
if (!is_array($_vtxdkat)) {
return json_out(array('ok' => FALSE, 'error' => $_vtxdkat), 422);
}

$this->load->helper('edit');
json_out(array('ok' => TRUE, 'medium' => photo_url($_vtxdkat, 'm'), 'thumb' => photo_url($_vtxdkat, 't'),
'srcset' => photo_srcset($_vtxdkat, 'm'), 'sizes' => ed_img_sizes((string) $this->input->post('key'), 1)));
}

public function position()
{
$_v53s48c = $this->content_model->save_image_pos((string) $this->input->post('key'),
$this->input->post('x'), $this->input->post('y'), $this->input->post('z'));
json_out(array('ok' => $_v53s48c, 'error' => $_v53s48c ? NULL : __('Vị trí ảnh không hợp lệ.')), $_v53s48c ? 200 : 422);
}

public function replace_photo()
{
$_v6t5utc = $this->photo_model->find($this->input->post('id'));
if (!$_v6t5utc || (int) $_v6t5utc['album_id'] === 0) {
return json_out(array('ok' => FALSE, 'error' => __('Ảnh không tồn tại.')), 404);
}
$_v0uhlmq = $this->uploaded('photo');
if (is_string($_v0uhlmq)) {
return json_out(array('ok' => FALSE, 'error' => $_v0uhlmq), 422);
}
@set_time_limit(120);
$_v6zmozo = $this->photo_model->add_from_file($_v0uhlmq['tmp_name'], $_v0uhlmq['name'], $_v6t5utc['album_id'], array('source' => 'owner'));
if (!is_array($_v6zmozo)) {
return json_out(array('ok' => FALSE, 'error' => $_v6zmozo), 422);
}
$this->db->update('photos', array('sort_order' => $_v6t5utc['sort_order'], 'caption' => $_v6t5utc['caption']), array('id' => $_v6zmozo['id']));
$this->db->update('albums', array('cover_photo_id' => $_v6zmozo['id']), array('cover_photo_id' => $_v6t5utc['id']));
foreach (array_keys($this->content_model->registry('content_images')) as $_vyybu5d) {
if ((int) setting($_vyybu5d) === (int) $_v6t5utc['id']) {
$this->settings_model->set_many(array($_vyybu5d => $_v6zmozo['id']));
}
}
$this->photo_model->delete($_v6t5utc['id']);
json_out(array('ok' => TRUE, 'id' => $_v6zmozo['id'], 'medium' => photo_url($_v6zmozo, 'm'), 'thumb' => photo_url($_v6zmozo, 't'),
'full' => photo_url($_v6zmozo, 'o'), 'srcset' => photo_srcset($_v6zmozo, 'm'), 'srcset_s' => photo_srcset($_v6zmozo, 's')));
}
public function events()
{
$_vb0g419 = json_decode((string) $this->input->post('events'), TRUE);
$_vr1uemf = $this->content_model->save_events($_vb0g419);
if ($_vr1uemf !== TRUE) {
return json_out(array('ok' => FALSE, 'error' => $_vr1uemf, 'index' => $this->content_model->events_error_index,
'field' => $this->content_model->events_error_index !== NULL ? 'map' : NULL), 422);
}

json_out(array('ok' => TRUE, 'events' => $this->content_model->events(), 'checklist' => $this->content_model->checklist()));
}
public function date()
{
$_v47229h = trim((string) $this->input->post('date'));
$_v0hqcpb = trim((string) $this->input->post('time'));
if (($_v47229h !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_v47229h)) || ($_v0hqcpb !== '' && !preg_match('/^\d{2}:\d{2}$/', $_v0hqcpb))) {
return json_out(array('ok' => FALSE, 'error' => __('Ngày giờ không hợp lệ.')), 422);
}
$this->settings_model->set_many(array('wedding_date' => $_v47229h, 'wedding_time' => $_v0hqcpb));
json_out(array('ok' => TRUE, 'text' => $_v47229h ? vn_date($_v47229h) . ($_v0hqcpb ? ' · ' . $_v0hqcpb : '') : '',
'ts' => $_v47229h ? strtotime($_v47229h . ' ' . ($_v0hqcpb ?: '00:00')) : 0));
}
public function theme()
{
$_vztrh5n = (string) $this->input->post('theme');
$_vifri1e = $this->content_model->registry('themes');
if (!array_key_exists($_vztrh5n, $_vifri1e)) {
return json_out(array('ok' => FALSE, 'error' => __('Giao diện không tồn tại.')), 422);
}
if (!$this->content_model->theme_allowed($_vztrh5n)) { 
return json_out(array('ok' => FALSE, 'vip' => TRUE,
'error' => __('Giao diện VIP "{name}" chỉ dùng được khi tạo trang trên thiep.site.', array('name' =>
(lang_cur() === 'en' && !empty($_vifri1e[$_vztrh5n]['name_en'])) ? $_vifri1e[$_vztrh5n]['name_en'] : $_vifri1e[$_vztrh5n]['name']))), 403);
}
$this->settings_model->set_many(array('theme' => $_vztrh5n));
json_out(array('ok' => TRUE));
}

public function publish()
{
$this->content_model->publish();
json_out(array('ok' => TRUE, 'published_at' => setting('published_at'), 'checklist' => $this->content_model->checklist()));
}

public function checklist()
{
json_out(array('ok' => TRUE, 'checklist' => $this->content_model->checklist()));
}




public function fx()
{
$_vw3rwrc = (string) $this->input->post('fx');
if (!array_key_exists($_vw3rwrc, Content_model::EFFECTS)) {
return json_out(array('ok' => FALSE, 'error' => __('Hiệu ứng không hợp lệ.')), 422);
}
$this->settings_model->set_many(array('fx' => $_vw3rwrc, 'fx_auto' => $this->input->post('auto') === '1' ? '1' : '0'));
json_out(array('ok' => TRUE, 'fx' => $_vw3rwrc));
}




public function music()
{
$_vthu856 = (string) $this->input->post('action');
$_vu0f4lc = (string) $this->input->post('id');
if ($_vthu856 === 'select' && !$this->content_model->select_music($_vu0f4lc)) {
return json_out(array('ok' => FALSE, 'error' => __('Bài hát không có trong thư viện.')), 422);
}
if ($_vthu856 === 'delete') {
$this->content_model->delete_music($_vu0f4lc);
}
if ($_vthu856 === 'upload' || isset($_FILES['music'])) {
$_vdm389f = $this->uploaded('music', 20);
if (is_string($_vdm389f)) {
return json_out(array('ok' => FALSE, 'error' => $_vdm389f), 422);
}
$_vd50hgx = $this->content_model->save_music($_vdm389f['tmp_name'], $_vdm389f['name'], (string) $this->input->post('suggest'));
if (!preg_match('/^[a-f0-9]{32}\./', $_vd50hgx)) {
return json_out(array('ok' => FALSE, 'error' => $_vd50hgx), 422);
}
}
$_vf7o60e = $this->content_model->music();
json_out(array('ok' => TRUE, 'list' => $this->content_model->music_list(), 'current' => $_vf7o60e ? $_vf7o60e['id'] : '',
'url' => $_vf7o60e ? $_vf7o60e['url'] : NULL, 'suggestions' => $this->content_model->music_suggestions()));
}

private function uploaded($_vugdb77, $_vkp814x = NULL)
{
$_vih8gps = isset($_FILES[$_vugdb77]) ? $_FILES[$_vugdb77] : NULL;
if (!$_vih8gps || $_vih8gps['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($_vih8gps['tmp_name'])) {
$_vaag0oc = $_vih8gps ? (int) $_vih8gps['error'] : UPLOAD_ERR_NO_FILE;
return in_array($_vaag0oc, array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE), TRUE)
? __('File vượt giới hạn tải lên của máy chủ.') : __('Không nhận được file (mã {code}).', array('code' => $_vaag0oc));
}
$_vcziwlr = ($_vkp814x ?: (int) $this->config->item('photo_owner_max_mb')) * 1024 * 1024;
if ($_vih8gps['size'] > $_vcziwlr) {
return __('File lớn hơn {size}.', array('size' => human_size($_vcziwlr)));
}
return $_vih8gps;
}
}