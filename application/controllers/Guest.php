<?php

defined('BASEPATH') OR exit('No direct script access allowed');






class Guest extends Public_Controller
{




protected $open_methods = array('index', 'upload');
public function __construct()
{
parent::__construct();
$this->load->model(array('album_model', 'photo_model', 'wish_model'));
$this->load->library('ratelimit');
}

private function target_album()
{
$_vf7s9mu = (string) ($this->input->get('album') ?: $this->input->post('album'));
if ($_vf7s9mu !== '') {
$_v008etb = $this->album_model->find_by_slug($_vf7s9mu);
if ($_v008etb && (int) $_v008etb['allow_guest_upload'] === 1 && $_v008etb['visibility'] !== 'hidden') {
return $_v008etb;
}
}
return $this->album_model->find($this->album_model->guest_album_id());
}
public function index()
{
if (setting('guest_upload') !== '1') {
$this->render('public/upload_closed', array('title' => __('Gửi ảnh')));
return;
}
$this->render('public/upload', array(
'title' => __('Gửi ảnh cho {cap_doi}', array('cap_doi' => $this->settings_model->couple_title())),
'album' => $this->target_album(),
'max_mb' => max(1, (int) setting('guest_upload_max_mb')),
));
}
public function upload()
{
if ($this->input->method() !== 'post') {
show_404();
}
if (setting('guest_upload') !== '1') {
return json_out(array('ok' => FALSE, 'error' => __('Chủ nhà đã đóng mục gửi ảnh.')), 403);
}
if (!$this->ratelimit->allowed('guest_upload')) {
return json_out(array('ok' => FALSE, 'error' => __('Bạn đã gửi rất nhiều ảnh, hãy nghỉ tay một lát rồi gửi tiếp nhé.')), 429);
}
$_v7rei8f = isset($_FILES['photo']) ? $_FILES['photo'] : NULL;
if (!$_v7rei8f || !is_uploaded_file($_v7rei8f['tmp_name']) || $_v7rei8f['error'] !== UPLOAD_ERR_OK) {
return json_out(array('ok' => FALSE, 'error' => $this->upload_error($_v7rei8f)), 422);
}
$_vscptun = max(1, (int) setting('guest_upload_max_mb')) * 1024 * 1024;
if ($_v7rei8f['size'] > $_vscptun) {
return json_out(array('ok' => FALSE, 'error' => __('Ảnh vượt quá {size}.', array('size' => human_size($_vscptun)))), 422);
}
if (($disk_error = $this->guest_disk_error((int) $_v7rei8f['size'])) !== NULL) {
return json_out(array('ok' => FALSE, 'error' => $disk_error), 503);
}
$_vzwf87g = $this->target_album();
$_vgb7ypp = mb_substr(trim((string) $this->input->post('guest_name')), 0, 60);
$_vpan1os = mb_substr(trim((string) $this->input->post('guest_message')), 0, 300);
// Album có mật khẩu: ảnh khách luôn chờ duyệt, kể cả khi đã tắt duyệt ảnh.
$_ve9g9y3 = setting('guest_upload_approval') === '1' || $_vzwf87g['visibility'] === 'password' ? 'pending' : 'approved';
$this->ratelimit->hit('guest_upload');
$_val1vm7 = $this->photo_model->add_from_file($_v7rei8f['tmp_name'], $_v7rei8f['name'], $_vzwf87g['id'], array(
'source' => 'guest', 'status' => $_ve9g9y3,
'guest_name' => $_vgb7ypp !== '' ? $_vgb7ypp : NULL, 'guest_message' => $_vpan1os !== '' ? $_vpan1os : NULL,
));
if (!is_array($_val1vm7)) {
return json_out(array('ok' => FALSE, 'error' => $_val1vm7), 422);
}
json_out(array('ok' => TRUE, 'pending' => $_ve9g9y3 === 'pending', 'thumb' => photo_url($_val1vm7, 't')));
}

/**
 * Chặn khách làm đầy ổ đĩa: giữ lại ít nhất guest_min_free_mb trống (mỗi ảnh sinh ~4 bản nên tính dư 4 lần cỡ file)
 * và giới hạn số ảnh khách đang chờ duyệt. NULL = cho phép.
 */
private function guest_disk_error($size)
{
$free = @disk_free_space(FCPATH . 'uploads/photos');
$min_free = max(0, (int) $this->config->item('guest_min_free_mb')) * 1024 * 1024;
if ($free !== FALSE && $free - 4 * $size < $min_free) {
log_message('error', 'Guest upload: ổ đĩa sắp đầy (' . (int) ($free / 1048576) . ' MB trống).');
return __('Trang cưới tạm hết chỗ lưu ảnh — bạn báo cô dâu chú rể giúp nhé.');
}
$max_pending = (int) $this->config->item('guest_max_pending');
if ($max_pending > 0 && $this->db->where(array('source' => 'guest', 'status' => 'pending'))->count_all_results('photos') >= $max_pending) {
return __('Đang có quá nhiều ảnh chờ cô dâu chú rể duyệt, bạn gửi lại sau nhé.');
}
return NULL;
}

const BUSY = 'Chưa gửi được, bạn thử lại sau ít phút nhé.';




private function form_error($_vgmp77b, $_vesy4yg, $_vkn1fpy, $_vptp3vx, array $_vj8dptf, $_vv0l9er, $_vsyurr5 = '')
{
if ($_vgmp77b) {
$_vco9915 = array('ok' => FALSE, 'error' => $_vesy4yg);
if ($_vsyurr5 !== '') {
$_vco9915['field'] = $_vsyurr5; 
}
return json_out($_vco9915, $_vkn1fpy);
}
$this->output->set_status_header($_vkn1fpy);
$this->render('public/retry', array('title' => __('Chưa gửi được'), 'error' => $_vesy4yg, 'action' => $_vptp3vx,
'fields' => $_vj8dptf, 'back' => $_vv0l9er, 'error_field' => $_vsyurr5));
}
public function wish()
{
$_v06d91b = $this->input->is_ajax_request();
if ($this->input->method() !== 'post' || setting('wishes_enabled') !== '1') {
return $_v06d91b ? json_out(array('ok' => FALSE, 'error' => __('Mục lời chúc đang tắt.')), 403) : redirect('');
}
$_vxke83x = mb_substr(trim((string) $this->input->post('name')), 0, 60);
$_vdmcdr1 = mb_substr(trim((string) $this->input->post('message')), 0, 1000);

if ((string) $this->input->post('website') !== '') {
return $_v06d91b ? json_out(array('ok' => TRUE, 'pending' => TRUE, 'thanks' => __('Cảm ơn lời chúc của bạn!'))) : redirect('#loi-chuc');
}
$_va1xss7 = array('name' => $_vxke83x, 'message' => $_vdmcdr1);
if ($_vxke83x === '' || $_vdmcdr1 === '') {
return $this->form_error($_v06d91b, __('Hãy nhập tên và lời chúc nhé.'), 422, 'loi-chuc', $_va1xss7, '#loi-chuc');
}
if (!$this->ratelimit->allowed('wish')) {
return $this->form_error($_v06d91b, __(self::BUSY), 429, 'loi-chuc', $_va1xss7, '#loi-chuc');
}
$this->ratelimit->hit('wish');
$_vokdcer = setting('wishes_approval') === '1';
$this->wish_model->add($_vxke83x, $_vdmcdr1, $_vokdcer ? 'pending' : 'approved');
$_vtds4b2 = $this->wish_voice();
$_vftq3dv = $_vokdcer ? $_vtds4b2['wish_thanks_pending'] : $_vtds4b2['wish_thanks'];
if ($_v06d91b) {
return json_out(array('ok' => TRUE, 'pending' => $_vokdcer, 'thanks' => $_vftq3dv,
'wish' => $_vokdcer ? NULL : array('name' => $_vxke83x, 'message' => $_vdmcdr1)));
}
flash('success', $_vftq3dv);
redirect('#loi-chuc');
}





public function rsvp()
{
$_vh89xxf = $this->input->is_ajax_request();
if ($this->input->method() !== 'post' || setting('rsvp_enabled', '1') !== '1') {
return $_vh89xxf ? json_out(array('ok' => FALSE, 'error' => __('Mục xác nhận tham dự đang tắt.')), 403) : redirect('');
}
$this->load->model('invite_model');
$this->load->library('invitee');
$_vub5gtt = (string) $this->input->post('attend');
$_v9hpkw9 = strtolower(trim((string) $this->input->post('code')));

$_voz3oxs = $_v9hpkw9 !== '' && !$this->is_admin();
$_vffya79 = $_voz3oxs && !$this->ratelimit->allowed('slug_miss');
$_v5k66cg = $_v9hpkw9 !== '' && !$_vffya79 ? $this->invite_model->find_by_code($_v9hpkw9) : NULL;


if ($_v9hpkw9 === '' && !$this->is_admin() && ($_v2j3irl = $this->invitee->invite_code()) !== '') {
$_v5k66cg = $this->invite_model->find_by_code($_v2j3irl);
if (!$_v5k66cg || $_v5k66cg['source'] !== 'invite') {
$_v5k66cg = NULL;
$this->invitee->forget_invite();
}
}
$_vmopkgb =($_v5k66cg ? $this->invite_model->path($_v5k66cg) : '') . '#rsvp';
$_vve7mdr = array();
foreach (array('code', 'name', 'attend', 'guests', 'phone', 'message') as $_vokjrn1) {
$_vve7mdr[$_vokjrn1] = mb_substr(trim((string) $this->input->post($_vokjrn1)), 0, 1000);
}
$_vwol8g7 = function ($_vkfip2q, $_vmq9rou = 422, $_vfty0mo = '') use ($_vh89xxf, $_vmopkgb, $_vve7mdr) {
return $this->form_error($_vh89xxf, $_vkfip2q, $_vmq9rou, 'xac-nhan', $_vve7mdr, $_vmopkgb, $_vfty0mo);
};
if ((string) $this->input->post('website') !== '') {
return $_vh89xxf ? json_out(array('ok' => TRUE)) : redirect($_vmopkgb);
}
if ($_vffya79) {
return $_vwol8g7(__(self::BUSY), 429);
}
if ($_v9hpkw9 !== '' && !$_v5k66cg) {
if ($_voz3oxs) {
$this->ratelimit->hit('slug_miss');
}
return $_vwol8g7(__('Thiệp mời không còn tồn tại.'), 404);
}

if ($_v5k66cg && $this->is_admin()) {
return $_vwol8g7(__('Bạn đang xem trước với tư cách chủ nhà — câu trả lời không được ghi. Dùng nút Tham dự/Từ chối ở trang Khách mời để ghi nhận thay khách.'), 403);
}
if (!in_array($_vub5gtt, array('yes', 'no'), TRUE)) {
return $_vwol8g7(__('Hãy chọn "Sẽ tham dự" hoặc "Không thể tham dự".'));
}

$_vo8pobg = trim((string) $this->input->post('phone'));
$_v0x28bt = trim(preg_replace('/\s+/', ' ', preg_replace('/[^0-9+ .()\-]/', '', $_vo8pobg)));
$_vr5dddc = strlen(preg_replace('/\D/', '', $_v0x28bt));
if ($_vo8pobg !== '' && ($_vr5dddc < 8 || $_vr5dddc > 15)) {
return $_vwol8g7(__('Số điện thoại chưa đúng — bạn để trống nếu không muốn để lại số nhé.'), 422, 'phone');
}

$_vnram3r = (bool) $_v5k66cg;
if ($_vnram3r && !$this->ratelimit->allowed('rsvp_invite')) {
return $_vwol8g7(__(self::BUSY), 429);
}
if (!$_v5k66cg) {
$_vrollzn = mb_substr(trim((string) $this->input->post('name')), 0, 80);
if ($_vrollzn === '') {
return $_vwol8g7(__('Hãy nhập tên nhé.'));
}
if (!$this->ratelimit->allowed('rsvp')) {
return $_vwol8g7(__(self::BUSY), 429);
}

$_v5bzdze = ($_vlnecs6 = $this->invitee->web_code()) !== '' ? $this->invite_model->find_by_code($_vlnecs6) : NULL;
if ($_v5bzdze && $_v5bzdze['source'] === 'web') {
if ($_v5bzdze['name'] !== $_vrollzn) {
$this->invite_model->update($_v5bzdze['id'], array('name' => $_vrollzn));
}
$_v5k66cg = $this->invite_model->find($_v5bzdze['id']);
} else {
$this->ratelimit->hit('rsvp');
$_v5k66cg = $this->invite_model->create($_vrollzn, '', '', 'web');
}
if (!is_array($_v5k66cg)) {
return $_vwol8g7(is_string($_v5k66cg) ? $_v5k66cg : __('Chưa lưu được, bạn thử lại nhé.'));
}
}
$_vr4082k = mb_substr(trim((string) $this->input->post('message')), 0, 1000);
$_v9fwpm3 = $this->invite_model->respond($_v5k66cg, $_vub5gtt, (int) $this->input->post('guests'), $_vr4082k, $_vo8pobg === '' ? '' : $_v0x28bt);

$this->invitee->remember($_v9fwpm3, $_vr4082k !== '');
if ($_vnram3r) {
$this->ratelimit->hit('rsvp_invite');
}
$_vamwjyx = $this->invitee->voice($this->invitee->salutation_of($_v9fwpm3));
$_v3b8z2p = $_vub5gtt === 'yes' ? $_vamwjyx['thanks_yes'] : $_vamwjyx['thanks_no'];
if ($_vh89xxf) {
return json_out(array('ok' => TRUE, 'status' => $_v9fwpm3['status'], 'guests' => (int) $_v9fwpm3['guests'],

'message' => $_vr4082k, 'thanks' => $_v3b8z2p,
'title' => $_vub5gtt === 'yes' ? $_vamwjyx['done_yes'] : $_vamwjyx['done_no']));
}
flash('success', $_v3b8z2p);
redirect($this->invite_model->path($_v9fwpm3) . '#rsvp');
}




private function wish_voice()
{
$this->load->library('invitee');
$_vdrv2k8 = strtolower(trim((string) $this->input->post('code')));
$_vtwoo48 = NULL;
if (preg_match('/^[a-z0-9]{4,40}$/', $_vdrv2k8) && ($this->is_admin() || $this->ratelimit->allowed('slug_miss'))) {
$this->load->model('invite_model');
$_vtwoo48 = $this->invite_model->find_by_code($_vdrv2k8);
if (!$_vtwoo48 && !$this->is_admin()) {
$this->ratelimit->hit('slug_miss');
}
}
return $this->invitee->voice($this->invitee->salutation_of($_vtwoo48));
}
private function upload_error($_vahp9b7)
{
$_v20eqwd = $_vahp9b7 ? (int) $_vahp9b7['error'] : UPLOAD_ERR_NO_FILE;
switch ($_v20eqwd) {
case UPLOAD_ERR_INI_SIZE:
case UPLOAD_ERR_FORM_SIZE: return __('Ảnh quá lớn so với giới hạn máy chủ.');
case UPLOAD_ERR_PARTIAL: return __('Mạng chập chờn, ảnh gửi chưa trọn. Hãy thử lại.');
case UPLOAD_ERR_NO_FILE: return __('Chưa chọn ảnh.');
}
return __('Máy chủ không nhận được ảnh (mã {code}).', array('code' => $_v20eqwd));
}
}