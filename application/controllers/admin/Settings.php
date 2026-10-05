<?php

defined('BASEPATH') OR exit('No direct script access allowed');
class Settings extends Admin_Controller
{

public function index()
{
$_v9w8p9v = array();
if ($this->input->method() === 'post') {
$_v2krwp6 = function ($_vajtsiv, $_vsmwyng = 200) {
return mb_substr(trim((string) $this->input->post($_vajtsiv)), 0, $_vsmwyng);
};
$_v8zlpj7 = array(
'groom_name' => $_v2krwp6('groom_name', 80),
'bride_name' => $_v2krwp6('bride_name', 80),
'wedding_date' => $_v2krwp6('wedding_date', 10),
'wedding_time' => $_v2krwp6('wedding_time', 5),

'guest_upload' => $this->input->post('guest_upload') ? '1' : '0',
'guest_upload_approval' => $this->input->post('guest_upload_approval') ? '1' : '0',
'guest_upload_max_mb' => (string) max(1, min(100, (int) $this->input->post('guest_upload_max_mb'))),
'wishes_enabled' => $this->input->post('wishes_enabled') ? '1' : '0',
'wishes_approval' => $this->input->post('wishes_approval') ? '1' : '0',
'rsvp_enabled' => $this->input->post('rsvp_enabled') ? '1' : '0',
'invite_card' => $this->input->post('invite_card') ? '1' : '0',
'music_autoplay' => $this->input->post('music_autoplay') ? '1' : '0',

'album_download' => $this->input->post('album_download') ? '1' : '0',
);

if ($_v8zlpj7['groom_name'] === '') {
$_v9w8p9v['groom_name'] = __('Tên chú rể không được để trống.');
}
if ($_v8zlpj7['bride_name'] === '') {
$_v9w8p9v['bride_name'] = __('Tên cô dâu không được để trống.');
}
if ($_v8zlpj7['wedding_date'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_v8zlpj7['wedding_date'])) {
$_v9w8p9v['wedding_date'] = __('Ngày cưới không hợp lệ.');
}
if ($_v8zlpj7['wedding_time'] !== '' && !preg_match('/^\d{2}:\d{2}$/', $_v8zlpj7['wedding_time'])) {
$_v9w8p9v['wedding_time'] = __('Giờ không hợp lệ.');
}


foreach (array('site_lang' => array_keys(lang_all()), 'admin_lang' => lang_admin_all()) as $_v63jbnu => $_v1jdu3i) {
$_vd9wq3p = $this->input->post($_v63jbnu);
if ($_vd9wq3p === NULL) {
continue;
}
if (in_array((string) $_vd9wq3p, $_v1jdu3i, TRUE)) {
$_v8zlpj7[$_v63jbnu] = (string) $_vd9wq3p;
} else {
$_v9w8p9v[$_v63jbnu] = __('Ngôn ngữ không hợp lệ.');
}
}
$_vt7ytij = $this->input->post('site_langs');
if ($_vt7ytij !== NULL) {
$_ve1ju4z = array();
foreach ((array) $_vt7ytij as $_vd9wq3p) {
if (lang_pick($_vd9wq3p) !== NULL && !in_array(lang_pick($_vd9wq3p), $_ve1ju4z, TRUE)) {
$_ve1ju4z[] = lang_pick($_vd9wq3p);
} elseif (lang_pick($_vd9wq3p) === NULL) {
$_v9w8p9v['site_langs'] = __('Ngôn ngữ không hợp lệ.');
}
}
$_v646co6 = isset($_v8zlpj7['site_lang']) ? $_v8zlpj7['site_lang'] : lang_site_list($this->settings_model->get('site_lang', 'vi'), $this->settings_model->get('site_langs', ''))[0];
if (!in_array($_v646co6, $_ve1ju4z, TRUE)) {
array_unshift($_ve1ju4z, $_v646co6);
}
$_v8zlpj7['site_langs'] = implode(',', $_ve1ju4z);
}
$this->load->model('content_model');
$_v0nljha = (string) $this->input->post('theme');
if ($this->content_model->theme_allowed($_v0nljha)) {
$_v8zlpj7['theme'] = $_v0nljha;
} elseif (array_key_exists($_v0nljha, $this->content_model->registry('themes'))) {
$_v9w8p9v['theme'] = __('Giao diện VIP chỉ dùng được khi tạo trang trên thiep.site — hãy chọn một giao diện khác.');
}
$_v5lbhay = (string) $this->input->post('fx');
if (array_key_exists($_v5lbhay, Content_model::EFFECTS)) {
$_v8zlpj7['fx'] = $_v5lbhay;
}
$_vii67rl = (int) $this->input->post('home_album_id');
if ($_vii67rl && $this->album_model->find($_vii67rl)) {
$_v8zlpj7['home_album_id'] = (string) $_vii67rl;
}
$_v0d80m4 = (int) $this->input->post('guest_upload_album_id');
if ($_v0d80m4 && $this->album_model->find($_v0d80m4)) {
$_v8zlpj7['guest_upload_album_id'] = (string) $_v0d80m4;
}

$this->load->library('vietqr');
$_v8zlpj7['gift_enabled'] = $this->input->post('gift_enabled') ? '1' : '0';
$_v8zlpj7['gift_title'] = $_v2krwp6('gift_title', 60);
$_v8zlpj7['gift_text'] = $_v2krwp6('gift_text', 300);
$_v8zlpj7['gift_note'] = $_v2krwp6('gift_note', 80); 
$_vuarfcf = FALSE;
foreach (array('groom' => __('nhà trai'), 'bride' => __('nhà gái')) as $_v6kl2vi => $_vwvb81o) {
$_v0jhduj = (string) $this->input->post('gift_' . $_v6kl2vi . '_bin');
$_vwzmzzw = preg_replace('/[\s.\-]/', '', $_v2krwp6('gift_' . $_v6kl2vi . '_acct', 40));

$_vr0ucfq = strtoupper($this->vietqr->ascii($_v2krwp6('gift_' . $_v6kl2vi . '_holder', 60)));
$_v8zlpj7['gift_' . $_v6kl2vi . '_bin'] = $this->vietqr->bank_name($_v0jhduj) !== '' ? $_v0jhduj : '';
$_v8zlpj7['gift_' . $_v6kl2vi . '_acct'] = $_vwzmzzw;
$_v8zlpj7['gift_' . $_v6kl2vi . '_holder'] = $_vr0ucfq;
if ($_vwzmzzw === '' && $_vr0ucfq === '' && $_v0jhduj === '') {
continue;
}
if ($_v8zlpj7['gift_' . $_v6kl2vi . '_bin'] === '') {
$_v9w8p9v['gift_' . $_v6kl2vi . '_bin'] = __('Chọn ngân hàng cho tài khoản {side}.', array('side' => $_vwvb81o));
}

if (!preg_match('/^[0-9]{4,19}$/', $_vwzmzzw)) {
$_v9w8p9v['gift_' . $_v6kl2vi . '_acct'] = __('Số tài khoản {side} gồm 4–19 chữ số (không có chữ cái).', array('side' => $_vwvb81o));
}
if ($_vr0ucfq === '') {
$_v9w8p9v['gift_' . $_v6kl2vi . '_holder'] = __('Nhập tên chủ tài khoản {side} (như trên thẻ ngân hàng).', array('side' => $_vwvb81o));
}
$_vuarfcf = TRUE;
}
if ($_v8zlpj7['gift_enabled'] === '1' && !$_vuarfcf) {
$_v9w8p9v['gift_enabled'] = __('Bật "Mừng cưới" cần ít nhất 1 tài khoản (nhà trai hoặc nhà gái).');
}
if ($_v8zlpj7['gift_title'] === '') {
$_v8zlpj7['gift_title'] = 'Hộp mừng cưới';
}
$_vwl2xcm = (string) $this->input->post('site_password_mode');
if ($_vwl2xcm === 'off') {
$_v8zlpj7['site_password_hash'] = '';
} elseif ($_vwl2xcm === 'set' && getenv('ANHCUOI_DEMO')) {
$_v9w8p9v['site_password'] = __('Trang demo không cho đặt mật khẩu xem trang.');
} elseif ($_vwl2xcm === 'set') {
$_v3c5cfe = (string) $this->input->post('site_password');
if (mb_strlen($_v3c5cfe) < 8) {
$_v9w8p9v['site_password'] = __('Mật khẩu xem trang tối thiểu 8 ký tự.');
} else {
$_v8zlpj7['site_password_hash'] = password_hash($_v3c5cfe, PASSWORD_DEFAULT);
}
}
if (!$_v9w8p9v) {
$_v45ivpb = $this->settings_model->all();
$this->settings_model->set_many($_v8zlpj7);
// Tên cô dâu chú rể, ngày giờ cưới sửa ở Cài đặt có hiệu lực ngay trên trang khách (không chờ Xuất bản).
$this->load->model('content_model');
$this->content_model->publish_keys(array_intersect_key($_v8zlpj7, array_flip(array('groom_name', 'bride_name', 'wedding_date', 'wedding_time'))));

if (isset($_v8zlpj7['admin_lang']) && (string) ($_v45ivpb['admin_lang'] ?? 'vi') !== $_v8zlpj7['admin_lang']) {
lang_cookie('ac_admin_lang', $_v8zlpj7['admin_lang']);
lang_cur(lang_admin($_v8zlpj7['admin_lang']));
}
flash('success', $this->saved_message($_v8zlpj7, $_v45ivpb));
redirect('admin/settings');
}
$_vv36ra4 = $_v8zlpj7;
$_vv36ra4['site_password_mode'] = $_vwl2xcm;
$_vv36ra4['site_password'] = (string) $this->input->post('site_password');
}
$this->load->model('content_model');
$this->render('admin/settings', array(
'form_vals' => isset($_vv36ra4) ? $_vv36ra4 : NULL,
'title' => __('Cài đặt'),
'themes' => $this->content_model->registry('themes'),
'cur_theme' => $this->content_model->theme(),
'home_album' => $this->content_model->home_album(),
'music_list' => $this->content_model->music_list(),
'music_sugs' => $this->content_model->music_suggestions(),
'music_cur' => (string) setting('music'),
'errors' => $_v9w8p9v,
'pending' => $this->pending_draft(),
'albums' => $this->album_model->list_all(),
'banks' => $this->load->library('vietqr') ? $this->vietqr->banks() : array(),
));
}




const DRAFT_LABELS = array(
'groom_name' => 'tên chú rể',
'bride_name' => 'tên cô dâu',
'wedding_date' => 'ngày cưới',
'wedding_time' => 'giờ cưới',
'theme' => 'giao diện',
'fx' => 'hiệu ứng',
'home_album_id' => 'album trang chính',
'music' => 'nhạc nền', 
);

private function saved_message(array $_vl6gaeh, array $_vuofeac)
{
$this->load->model('content_model');
$_vjd0p9r = $this->content_model->snapshot_keys();
$_v7r5fyp = $_vvltrax = array();
foreach ($_vl6gaeh as $_vmxmvxu => $_vjvbr23) {
if (array_key_exists($_vmxmvxu, $_vuofeac) && (string) $_vuofeac[$_vmxmvxu] === (string) $_vjvbr23) {
continue;
}
if (in_array($_vmxmvxu, $_vjd0p9r, TRUE)) {
$_vvltrax[] = array_key_exists($_vmxmvxu, self::DRAFT_LABELS) ? __(self::DRAFT_LABELS[$_vmxmvxu]) : $_vmxmvxu;
} else {
$_v7r5fyp[] = $_vmxmvxu;
}
}
$_v6527zj = array();
if ($_v7r5fyp) {
$_v6527zj[] = __('Đã lưu — áp dụng ngay cho khách.');
}
if (in_array('site_password_hash', $_v7r5fyp, TRUE)) {
$_vhmzih3 = (string) ($_vuofeac['site_password_hash'] ?? '') !== '';
$_v6527zj[] = $_vl6gaeh['site_password_hash'] === '' ? __('Đã tắt mật khẩu xem trang.')
: ($_vhmzih3 ? __('Đã đổi mật khẩu xem trang — khách phải nhập mật khẩu mới.') : __('Đã bật mật khẩu xem trang — khách phải nhập mật khẩu.'));
}
if ($_v7r5fyp && $_vl6gaeh['gift_enabled'] === '1') {
$_v6527zj[] = __('Mục Mừng cưới (số tài khoản) đang HIỆN cho khách.');
}
if ($_vvltrax) {
$_vvo3qye = implode(', ', $_vvltrax);
$_v6527zj[] = ($_v7r5fyp ? '' : __('Đã lưu.') . ' ') . __('{list}: khách sẽ thấy sau khi bấm “Cho khách xem” trên trang sửa.', array('list' => mb_strtoupper(mb_substr($_vvo3qye, 0, 1)) . mb_substr($_vvo3qye, 1)));
}
return $_v6527zj ? implode(' ', $_v6527zj) : __('Đã lưu cài đặt.');
}

private function pending_draft()
{
$_vbergw6 = (string) setting('published', '');
$_v6546os = $_vbergw6 !== '' ? json_decode($_vbergw6, TRUE) : NULL;
if (!is_array($_v6546os)) {
return array();
}
$_ven3455 = $this->settings_model->all();
$_v10zq70 = array();
foreach (self::DRAFT_LABELS as $_vjsmgwy => $_vdoxa2e) {
if (array_key_exists($_vjsmgwy, $_v6546os) && (string) $_v6546os[$_vjsmgwy] !== (string) ($_ven3455[$_vjsmgwy] ?? '')) {
$_v10zq70[] = __($_vdoxa2e);
}
}
return $_v10zq70;
}

public function tunnel()
{
$this->require_post();
if (hosted()) { 
show_404();
}
$_v8vi9z8 = (string) $this->input->post('mode');
$_vj3sa5x = tunnel_config();
if ($_v8vi9z8 === 'token') {
$_v29gfxe = trim((string) $this->input->post('token'));
$_v2rrhge = strtolower(trim((string) $this->input->post('hostname')));
$_v2rrhge = preg_replace('~^https?://~', '', rtrim($_v2rrhge, '/'));
if ($_v29gfxe === '' && $_vj3sa5x['mode'] === 'token') {
$_v29gfxe = $_vj3sa5x['token']; 
}
if (!preg_match('/^[A-Za-z0-9_.=+\/-]{40,}$/', $_v29gfxe)) {
flash('error', __('Token tunnel không hợp lệ. Sao chép đúng chuỗi sau "--token" trong Cloudflare Zero Trust.'));
redirect('admin/share');
}
if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $_v2rrhge)) {
flash('error', __('Tên miền không hợp lệ (ví dụ: cuoi.tenban.com).'));
redirect('admin/share');
}
$_vw1mwn9 = save_tunnel_config('token', $_v29gfxe, $_v2rrhge);
} elseif ($_v8vi9z8 === 'auto') {
$_vw1mwn9 = save_tunnel_config('quick', '', '', TRUE);
$this->load->library('tunnelrunner');
$this->tunnelrunner->ensure(app_port(), TRUE);
} elseif (in_array($_v8vi9z8, array('off', 'quick'), TRUE)) {
$_vw1mwn9 = save_tunnel_config($_v8vi9z8);
} else {
$_vw1mwn9 = FALSE;
}
flash($_vw1mwn9 ? 'success' : 'error', $_vw1mwn9
? __('Đã lưu. Hãy KHỞI ĐỘNG LẠI chương trình Ảnh Cưới (đóng cửa sổ rồi mở lại, hoặc khởi động lại container/gói NAS) để áp dụng.')
: __('Không ghi được cấu hình tunnel (thư mục cloudflared không ghi được?).'));
redirect('admin/share');
}

public function music()
{
$this->require_post();
$this->load->model('content_model');
$_vlkl78x = (string) $this->input->post('delete');
if ($_vlkl78x !== '') {
$this->content_model->delete_music($_vlkl78x);
flash('success', __('Đã xóa bài hát.'));
} elseif (!empty($_FILES['music']['name'])) {
$_vx87hia = $_FILES['music'];
if ($_vx87hia['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($_vx87hia['tmp_name']) || $_vx87hia['size'] > 20 * 1024 * 1024) {
flash('error', __('Không nhận được file nhạc (tối đa 20 MB).'));
} else {
$_vns83aj = $this->content_model->save_music($_vx87hia['tmp_name'], $_vx87hia['name'], (string) $this->input->post('suggest'));
flash(preg_match('/^[a-f0-9]{32}\./', $_vns83aj) ? 'success' : 'error',
preg_match('/^[a-f0-9]{32}\./', $_vns83aj) ? __('Đã thêm và chọn bài hát. Bấm “Cho khách xem” trên trang sửa để khách nghe.') : $_vns83aj);
}
} else {
$_vpjb12i = $this->content_model->select_music((string) $this->input->post('music'));
flash($_vpjb12i ? 'success' : 'error', $_vpjb12i ? ((string) $this->input->post('music') === ''
? __('Đã tắt nhạc nền. Bấm “Cho khách xem” trên trang sửa để áp dụng cho khách.')
: __('Đã chọn nhạc nền. Bấm “Cho khách xem” trên trang sửa để khách nghe.')) : __('Bài hát không hợp lệ.'));
}
redirect('admin/settings#nhac');
}
public function password()
{
if (getenv('ANHCUOI_DEMO')) { 
flash('error', __('Trang demo không cho đổi mật khẩu quản trị — dữ liệu demo tự khôi phục mỗi giờ.'));
redirect('admin/settings');
}
$_vj635ak = array();
if ($this->input->method() === 'post') {
$_vlb2p3x = (string) $this->input->post('current');
$_vyr2qsw = (string) $this->input->post('new');
if (!password_verify($_vlb2p3x, $this->user['password_hash'])) {
$_vj635ak[] = __('Mật khẩu hiện tại chưa đúng.');
} elseif (mb_strlen($_vyr2qsw) < 8) {
$_vj635ak[] = __('Mật khẩu mới tối thiểu 8 ký tự.');
} elseif ($_vyr2qsw !== (string) $this->input->post('new2')) {
$_vj635ak[] = __('Hai lần nhập mật khẩu mới không khớp.');
}
if (!$_vj635ak) {
$this->user_model->set_password($this->user['id'], $_vyr2qsw);
$this->session->sess_regenerate(TRUE);
flash('success', __('Đã đổi mật khẩu.'));
redirect('admin/settings/password');
}
}
$this->render('admin/password', array('title' => __('Đổi mật khẩu'), 'errors' => $_vj635ak));
}





public function backup_full()
{
if (getenv('ANHCUOI_DEMO')) {
flash('error', __('Trang demo không cho tải bản sao dữ liệu — dữ liệu demo tự khôi phục mỗi giờ.'));
redirect('admin/settings');
}
$this->load->library('zipstream');
$_v42m5tb = tempnam(sys_get_temp_dir(), 'acb');
$_vpdx24t = @$this->db->conn_id->exec("VACUUM INTO '" . SQLite3::escapeString($_v42m5tb . '.db') . "'");
@unlink($_v42m5tb);
$_v42m5tb .= '.db';
$_vgor6vd = ($_vpdx24t && is_file($_v42m5tb)) ? $_v42m5tb : $this->db->database;
$this->zipstream->add($_vgor6vd, 'database/anhcuoi.db');
$_vaju5oy = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads';
$_vhzcolh = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($_vaju5oy, FilesystemIterator::SKIP_DOTS));
foreach ($_vhzcolh as $_v8au5iw) {
$_vkns5h7 = str_replace('\\', '/', substr($_v8au5iw->getPathname(), strlen($_vaju5oy) + 1));
if ($_v8au5iw->isFile() && basename($_vkns5h7) !== 'index.html' && substr(basename($_vkns5h7), 0, 1) !== '.') {
$this->zipstream->add($_v8au5iw->getPathname(), 'uploads/' . $_vkns5h7);
}
}
if (!$this->zipstream->fits()) {
if ($_vgor6vd === $_v42m5tb) {
@unlink($_v42m5tb);
}
flash('error', __('Dữ liệu quá lớn để tải một file .zip (trên 4 GB hoặc quá nhiều file). Hãy chép tay thư mục database/ và uploads/.'));
redirect('admin/settings');
}
set_time_limit(0);
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="anhcuoi-full-' . date('Ymd-His') . '.zip"');
header('Content-Length: ' . $this->zipstream->length());
header('Cache-Control: no-store');
while (ob_get_level()) {
ob_end_clean();
}
$this->zipstream->send();
if ($_vgor6vd === $_v42m5tb) {
@unlink($_v42m5tb);
}
exit;
}

public function backup()
{
if (getenv('ANHCUOI_DEMO')) { 
flash('error', __('Trang demo không cho tải bản sao dữ liệu — dữ liệu demo tự khôi phục mỗi giờ.'));
redirect('admin/settings');
}
$_vg9lbn9 = $this->db->database;
$_v4sxyo6 = tempnam(sys_get_temp_dir(), 'acb');

$_vg3ovt8 = @$this->db->conn_id->exec("VACUUM INTO '" . SQLite3::escapeString($_v4sxyo6 . '.db') . "'");
@unlink($_v4sxyo6);
$_v4sxyo6 .= '.db';
if (!$_vg3ovt8 || !is_file($_v4sxyo6)) {
$_v4sxyo6 = $_vg9lbn9;
}
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="anhcuoi-' . date('Ymd-His') . '.db"');
header('Content-Length: ' . filesize($_v4sxyo6));
while (ob_get_level()) {
ob_end_clean();
}
readfile($_v4sxyo6);
if ($_v4sxyo6 !== $_vg9lbn9) {
@unlink($_v4sxyo6);
}
exit;
}
}