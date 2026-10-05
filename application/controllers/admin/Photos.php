<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Photos extends Admin_Controller
{
public function __construct()
{
parent::__construct();
$this->require_post();
}

public function upload()
{
$_vbvhdjp = $this->album_model->find($this->input->post('album_id'));
if (!$_vbvhdjp) {
return json_out(array('ok' => FALSE, 'error' => __('Album không tồn tại.')), 404);
}
$_vajpxk3 = isset($_FILES['photo']) ? $_FILES['photo'] : NULL;
if (!$_vajpxk3 || $_vajpxk3['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($_vajpxk3['tmp_name'])) {
$_vy50634 = $_vajpxk3 ? (int) $_vajpxk3['error'] : UPLOAD_ERR_NO_FILE;
$_v3a1q55 = in_array($_vy50634, array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE), TRUE)
? __('Ảnh vượt giới hạn upload của PHP (upload_max_filesize).') : __('Không nhận được file (mã {code}).', array('code' => $_vy50634));
return json_out(array('ok' => FALSE, 'error' => $_v3a1q55), 422);
}
if ($_vajpxk3['size'] > (int) $this->config->item('photo_owner_max_mb') * 1024 * 1024) {
return json_out(array('ok' => FALSE, 'error' => __('Ảnh lớn hơn {mb} MB.', array('mb' => (int) $this->config->item('photo_owner_max_mb')))), 422);
}
@set_time_limit(120);
$_vy3dca8 = $this->photo_model->add_from_file($_vajpxk3['tmp_name'], $_vajpxk3['name'], $_vbvhdjp['id'], array('source' => 'owner'));
if (!is_array($_vy3dca8)) {
return json_out(array('ok' => FALSE, 'error' => $_vy3dca8), 422);
}
json_out(array('ok' => TRUE, 'photo' => array(
'id' => $_vy3dca8['id'], 'thumb' => photo_url($_vy3dca8, 't'), 'medium' => photo_url($_vy3dca8, 'm'),
'width' => $_vy3dca8['width'], 'height' => $_vy3dca8['height'],
)));
}
private function ids()
{
$_vp8gqfx = $this->input->post('ids');
if (!is_array($_vp8gqfx)) {
$_vp8gqfx = explode(',', (string) $_vp8gqfx);
}
return array_values(array_filter(array_map('intval', $_vp8gqfx)));
}
public function delete()
{
$_vqkt5sf = 0;
foreach ($this->ids() as $_v2jvtwl) {
$_vqkt5sf += $this->photo_model->delete($_v2jvtwl) ? 1 : 0;
}
json_out(array('ok' => TRUE, 'deleted' => $_vqkt5sf));
}
public function status()
{
$_vxrmm62 = $this->photo_model->set_status($this->ids(), (string) $this->input->post('status'));
json_out(array('ok' => TRUE, 'updated' => $_vxrmm62));
}
public function move()
{
$_v5kprrs = $this->album_model->find($this->input->post('album_id'));
if (!$_v5kprrs) {
return json_out(array('ok' => FALSE, 'error' => __('Album đích không tồn tại.')), 404);
}
json_out(array('ok' => TRUE, 'moved' => $this->photo_model->move($this->ids(), $_v5kprrs['id'])));
}
public function caption()
{
$this->photo_model->update_caption($this->input->post('id'), $this->input->post('caption'));
json_out(array('ok' => TRUE));
}
public function cover()
{
$_v1ufmbj = $this->photo_model->find($this->input->post('id'));
if (!$_v1ufmbj) {
return json_out(array('ok' => FALSE, 'error' => __('Ảnh không tồn tại.')), 404);
}
$this->album_model->update($_v1ufmbj['album_id'], array('cover_photo_id' => (int) $_v1ufmbj['id']));
json_out(array('ok' => TRUE));
}
public function hero()
{
$_v98rny6 = $this->photo_model->find($this->input->post('id'));
if (!$_v98rny6) {
return json_out(array('ok' => FALSE, 'error' => __('Ảnh không tồn tại.')), 404);
}
$this->load->model('content_model');
$this->content_model->use_photo('img.hero_main', (int) $_v98rny6['id']);
json_out(array('ok' => TRUE));
}
public function reorder()
{
$_vfedz4h = $this->photo_model->reorder((int) $this->input->post('album_id'), $this->ids());
json_out(array('ok' => (bool) $_vfedz4h));
}
}