<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Moderation extends Admin_Controller
{
public function index()
{
$this->render('admin/moderation', array(
'title' => __('Duyệt ảnh khách gửi'),
'photos' => $this->photo_model->pending(),
));
}
public function wishes()
{
$_v6vo0qz = (string) $this->input->get('loc') === 'pending' ? 'pending' : '';
$this->render('admin/wishes', array(
'title' => __('Lời chúc'),
'loc' => $_v6vo0qz,
'wishes' => $this->wish_model->all($_v6vo0qz),
'total' => $this->wish_model->count_all(),
));
}
public function wish_status($_vjuxn9m = 0)
{
$this->require_post();
$this->wish_model->set_status($_vjuxn9m, (string) $this->input->post('status'));

redirect('admin/moderation/wishes' . ($this->input->get('loc') === 'pending' ? '?loc=pending' : '') . '#w' . (int) $_vjuxn9m);
}
public function wish_delete($_vtwykd3 = 0)
{
$this->require_post();
$this->wish_model->delete($_vtwykd3);
flash('success', __('Đã xóa lời chúc.'));
redirect('admin/moderation/wishes' . ($this->input->get('loc') === 'pending' ? '?loc=pending' : ''));
}
}