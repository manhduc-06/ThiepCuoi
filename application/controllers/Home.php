<?php

defined('BASEPATH') OR exit('No direct script access allowed');
class Home extends Public_Controller
{





protected $open_methods = array('unlock_site', 'slug', 'invite', 'index');
public function __construct()
{
parent::__construct();
$this->load->model(array('album_model', 'photo_model', 'wish_model'));
$this->load->library('invitee');
}

public function invite($_vvwxhxq = '')
{
$this->load->model('invite_model');
$_vvwxhxq = strtolower((string) $_vvwxhxq);
$this->open_invite(function () use ($_vvwxhxq) { return $this->invite_model->find_by_code($_vvwxhxq); });
}

public function slug($_vh7l8ki = '')
{
$this->load->model('invite_model');
$_vh7l8ki = strtolower((string) $_vh7l8ki);
$this->open_invite(function () use ($_vh7l8ki) { return $this->invite_model->find_by_slug($_vh7l8ki); }, $_vh7l8ki);
}








private function open_invite($_v20cpsy, $_v9rhuhg = NULL)
{
$this->load->library('ratelimit');
if (!$this->is_admin() && !$this->ratelimit->allowed('slug_miss')) {
return $this->try_later();
}
$_vygkrh2 = $_v20cpsy();
if (!$_vygkrh2) {
if (!$this->is_admin()) {
$this->ratelimit->hit('slug_miss');
}
if (!$this->site_unlocked()) {

return $this->render('public/site_locked', array('title' => __('Trang riêng tư'), 'invite_hint' => $_v9rhuhg !== NULL));
}
return $this->not_found();
}
if (!$this->site_unlocked()) {
if ($_v9rhuhg !== NULL) {

return $this->render('public/site_locked', array('title' => __('Trang riêng tư'), 'invite_hint' => TRUE));
}
$this->session->set_userdata('ac_site_unlocked', TRUE);
}

if ($_v9rhuhg !== NULL && !empty($_vygkrh2['slug']) && $_vygkrh2['slug'] !== $_v9rhuhg) {
redirect($_vygkrh2['slug'], 'location', 301);
}



if ($this->input->method() === 'get' && !$this->is_admin() && !$this->invitee->is_bot()
&& $this->input->get('mau') === NULL) {
$this->invite_model->mark_opened($_vygkrh2);

if ($_vygkrh2['source'] === 'invite') {
$this->invitee->remember_invite($_vygkrh2);
}
}
$this->render_wedding($_vygkrh2);
}




private function device_invite()
{
if ($this->is_admin() || ($_v1h8duy = $this->invitee->invite_code()) === '') {
return NULL;
}
$this->load->model('invite_model');
$_vdzidx2 = $this->invite_model->find_by_code($_v1h8duy);
if (!$_vdzidx2 || $_vdzidx2['source'] !== 'invite') {
$this->invitee->forget_invite();
return NULL;
}
return $_vdzidx2;
}

private function home_path()
{
$_v9tp6dd = $this->device_invite();
return $_v9tp6dd ? $this->invite_model->path($_v9tp6dd) : '';
}




public function index()
{
if ($this->input->get('xem') !== 'khach' && ($_va7ltus = $this->device_invite())) {
$_vu2tlx1 = $this->input->server('QUERY_STRING');

redirect($this->invite_model->guest_path($_va7ltus) . ($_vu2tlx1 ? '?' . $_vu2tlx1 : ''), 'location', 302);
}
if (!$this->site_unlocked()) {
return $this->render('public/site_locked', array('title' => __('Trang riêng tư')));
}
$this->render_wedding(NULL);
}

private function not_found()
{
$this->output->set_status_header(404);
$this->render('public/not_found', array('title' => __('Không tìm thấy trang')));
}

private function try_later()
{
$this->output->set_status_header(429);
$this->output->set_header('Retry-After: 600');
$this->render('public/try_later', array('title' => __('Bạn thử lại sau ít phút nhé')));
}

private $card_view = FALSE;

protected function draft_mode()
{
return !$this->card_view && parent::draft_mode();
}

private function render_wedding($_vfo30mp = NULL)
{
$this->load->model('content_model');
if ($this->is_admin() && $this->card_shown($_vfo30mp)) {
$this->card_view = TRUE;
}
$this->load->helper('lunar');
if (!$this->draft_mode()) {
$this->content_model->use_published();
}
$_vy2fzjn = $this->content_model;
$_v425chx = $_vy2fzjn->home_album();
// Album trang chủ có mật khẩu: chỉ hiện ảnh khi khách đã mở khóa album đó.
$_va8qt6u = $_v425chx && ($_v425chx['visibility'] !== 'password' || $this->album_unlocked($_v425chx))
? $this->photo_model->by_album($_v425chx['id']) : array();

$_v87agm5 = array();
$_v8bz3kr = $_va8qt6u;
foreach (array_keys($_vy2fzjn->registry('content_images')) as $_vhdrhup) {
$_v87agm5[$_vhdrhup] = $_vy2fzjn->image($_vhdrhup);
}
foreach ($_v87agm5 as $_vhdrhup => $_vcfd1t6) {
if (!$_vcfd1t6 && $_v8bz3kr && !in_array($_vhdrhup, array('img.bride', 'img.groom'), TRUE)) {
$_v87agm5[$_vhdrhup] = array_shift($_v8bz3kr) + array('borrowed' => TRUE);
}
}
$_vxryiu9 = $_vy2fzjn->get('wedding_date');
$_vpjy1ne = $_vy2fzjn->get('wedding_time');
$_vubg4h6 = array_values(array_filter($this->album_model->list_all(!$this->is_admin()), function ($_v7tg0wd) use ($_v425chx) {
return !$_v425chx || (int) $_v7tg0wd['id'] !== (int) $_v425chx['id'];
}));
if (is_array($_vfo30mp)) {

$_vfo30mp['phone'] = '';
if (!$this->is_admin() && !$this->invitee->knows($_vfo30mp)) {

$_vfo30mp['message'] = '';
}
}

$_v61i4hn = NULL;
if (!is_array($_vfo30mp) && ($_v90flvn = $this->invitee->web_code()) !== '') {
$this->load->model('invite_model');
$_v61i4hn = $this->invite_model->find_by_code($_v90flvn);
if (!$_v61i4hn || $_v61i4hn['source'] !== 'web') {
$_v61i4hn = NULL;
} else {
$_v61i4hn['phone'] = ''; 
}
}
// Khách mở từ thiệp riêng: chỉ hiện địa điểm của bên mình (nhà trai / nhà gái) và địa điểm chung.
$_vguest_side = is_array($_vfo30mp) ? (string) ($_vfo30mp['side'] ?? '') : '';
$_vok9llc = $this->draft_mode() ? $_vy2fzjn->events() : $_vy2fzjn->events_for_side($_vy2fzjn->events(), $_vguest_side);
$this->render('public/wedding', array(
'guest_side' => $_vguest_side,
'gift' => $this->gift_data(),
'title' => $_vy2fzjn->couple_title(),
'img' => $_v87agm5,
'events' => $_vok9llc,
'ics' => $this->ics_data($_vok9llc, $_vxryiu9, $_vpjy1ne, $_vy2fzjn->couple_title()),
'rsvp_prev' => $_v61i4hn,
'voice' => $this->invitee->voice($this->invitee->salutation_of($_vfo30mp)),
'date' => $_vxryiu9,
'time' => $_vpjy1ne,
'date_text' => $_vxryiu9 ? vn_date($_vxryiu9) . ($_vpjy1ne ? ' · ' . fmt_time($_vpjy1ne) : '') : '',
'lunar_text' => $_vxryiu9 ? vn_lunar_text($_vxryiu9) : '',
'countdown' => $_vxryiu9 ? strtotime($_vxryiu9 . ' ' . ($_vpjy1ne ?: '00:00')) : 0,
'home_album' => $_v425chx,
'gallery' => array_slice($_va8qt6u, 0, 30),
'gallery_total' => count($_va8qt6u),
'albums' => $_vubg4h6,
'music' => $_vy2fzjn->music(),
'monogram' => $_vy2fzjn->monogram(),
'themes' => $_vy2fzjn->registry('themes'),
'unpublished' => $this->is_admin() && $_vy2fzjn->has_unpublished_changes(),
'published_at' => setting('published_at'),
'wishes' => setting('wishes_enabled') === '1' ? $this->wish_model->approved() : array(),

'invite' => is_array($_vfo30mp) ? array('name' => $this->invite_model->display_name($_vfo30mp)) + $_vfo30mp : NULL,
'rsvp_on' => setting('rsvp_enabled', '1') === '1',
) + $this->invite_card_data($_vfo30mp, $_vy2fzjn, $_vxryiu9, $_vpjy1ne), 'wedding');
}




private function card_shown($_vrf4903)
{
return is_array($_vrf4903) && $_vrf4903['source'] === 'invite' && setting('invite_card', '1') === '1';
}
private function invite_card_data($_vy20m7u, $_veeu6sv, $_va83q32, $_v94k0bh)
{
if (!$this->card_shown($_vy20m7u)) {
return array('invite_card' => FALSE);
}
$_vside = (string) ($_vy20m7u['side'] ?? '');
$_vcard_events = $this->card_events($_veeu6sv, $_vside);
$_v6kegze = $_vcard_events ? $_vcard_events[0] : NULL;
$_v7qni4y = $_veeu6sv->couple_title();
$_v52iklj = $_va83q32 ? strtotime($_va83q32) : 0;

$_vd7rmu1 = $_veeu6sv->card_style($this->is_admin() ? (string) $this->input->get('mau') : '');
$_vev1duh = $_veeu6sv->registry('card_styles');
return array(
'invite_card' => TRUE,
'card_style' => $_vd7rmu1,
'card_open_ms' => (int) $_vev1duh[$_vd7rmu1]['open_ms'],
'card_name' => $this->invite_model->display_name($_vy20m7u),
'card_text' => $this->invite_model->invite_text($_vy20m7u, $_v7qni4y, $_va83q32 ? vn_date($_va83q32, FALSE, lang_content()) : ''),
'card_event' => $_v6kegze,
'card_events' => $_vcard_events,
'card_side' => $_vside,

'card_day' => $_v52iklj ? array('weekday' => lang_weekday(date('w', $_v52iklj)), 'd' => date(lang_cur() === 'en' ? 'm' : 'd', $_v52iklj),
'm' => date(lang_cur() === 'en' ? 'd' : 'm', $_v52iklj), 'y' => date('Y', $_v52iklj)) : NULL,
'card_time' => fmt_time($_v94k0bh),
'card_limit' => $this->invite_model->guest_limit($_vy20m7u),
'card_preview' => $this->is_admin(),
);
}

/**
 * Địa điểm in trên thiệp: khách nhà trai/nhà gái -> địa điểm của bên mình trước, rồi địa điểm chung (tối đa 3,
 * chỉ địa điểm đã có nơi/địa chỉ). Khách không rõ bên -> một địa điểm chính như trước (ưu tiên "tiệc").
 */
private function card_events($content, $side)
{
$evs = array_values(array_filter($content->events(), function ($ev) {
return trim($ev['place'] . $ev['address']) !== '';
}));
if ($side === 'groom' || $side === 'bride') {
$own = $common = array();
foreach ($evs as $ev) {
$s = $content->event_side($ev);
if ($s === $side) {
$own[] = $ev;
} elseif ($s === '') {
$common[] = $ev;
}
}
$list = array_slice(array_merge($own, $common), 0, 3);
if ($list) {
return $list;
}
}
$main = $this->main_event($evs);
return $main ? array($main) : array();
}

private function main_event(array $_vxcudwy, $_vrhvjn9 = FALSE)
{
$_v7c8pvm = NULL;
foreach ($_vxcudwy as $_vy59y8j => $_v054qng) {
if (trim($_v054qng['place'] . $_v054qng['address']) === '') {
continue;
}
if (!$_v7c8pvm) {
$_v7c8pvm = array($_vy59y8j, $_v054qng);
}
if (preg_match('/ti[eệ]c|reception|party|dinner/iu', $_v054qng['title'])) {
$_v7c8pvm = array($_vy59y8j, $_v054qng);
break;
}
}
return $_v7c8pvm ? ($_vrhvjn9 ? $_v7c8pvm : $_v7c8pvm[1]) : NULL;
}









private function gift_data()
{
if ((string) setting('gift_enabled') !== '1') {
return NULL;
}
$this->load->library('vietqr');
$_v50lds5 = (string) setting('gift_note');
$_vx4xbmo = array();
foreach (array('groom' => 'Nhà trai', 'bride' => 'Nhà gái') as $_v88h999 => $_vpjhm6v) {
$_vugwnt7 = (string) setting('gift_' . $_v88h999 . '_bin');
$_v91crib = (string) setting('gift_' . $_v88h999 . '_acct');
$_v6p4efd = $this->vietqr->payload($_vugwnt7, $_v91crib, $_v50lds5);
if ($_v6p4efd === '') {
continue;
}
$_vx4xbmo[] = array(
'side' => __($_vpjhm6v),
'key' => $_v88h999 === 'groom' ? 'nha-trai' : 'nha-gai',
'bank' => $this->vietqr->bank_name($_vugwnt7),
'account' => $_v91crib,
'holder' => (string) setting('gift_' . $_v88h999 . '_holder'),
'payload' => $_v6p4efd,
'qr_url' => '',
);
}
if (!$_vx4xbmo) {
return NULL;
}
return array('title' => $this->settings_model->localized('gift_title'), 'text' => $this->settings_model->localized('gift_text'), 'accounts' => $_vx4xbmo);
}
private function ics_data(array $_vwqdjo7, $_vahc76t, $_v7dfoxw, $_ve7l4cr)
{
$_vx0bzei = array('main' => NULL, 'events' => array());
if (!$_vahc76t) {
return $_vx0bzei;
}
$_v0f96hn = function ($_vjfe2h7) {
return trim(implode(', ', array_filter(array(trim($_vjfe2h7['place']), trim($_vjfe2h7['address'])), 'strlen')));
};
foreach ($_vwqdjo7 as $_vy0kra6 => $_vjfe2h7) {
$_vmmy7gi = $_vahc76t;
if (preg_match('~(\d{1,2})\s*[/.-]\s*(\d{1,2})\s*[/.-]\s*(\d{4})~', $_vjfe2h7['time'], $_v2bydcc) && checkdate((int) $_v2bydcc[2], (int) $_v2bydcc[1], (int) $_v2bydcc[3])) {
$_vmmy7gi = sprintf('%04d-%02d-%02d', $_v2bydcc[3], $_v2bydcc[2], $_v2bydcc[1]);
}
$_vvy2wai = '';
$_vclitem = preg_replace('~\d{1,2}\s*[/.-]\s*\d{1,2}\s*[/.-]\s*\d{4}~', ' ', $_vjfe2h7['time']);
if (preg_match('~(?<!\d)(\d{1,2})\s*(?::|h|giờ|g|(?=\s*[ap]\.?m\b))\s*(\d{2})?(?!\d)~iu', $_vclitem, $_v2bydcc) && (int) $_v2bydcc[1] < 24) {
$_vghezt9 = (int) $_v2bydcc[1];
if ($_vghezt9 < 12 && preg_match('~chiều|tối|pm|p\.m\.~iu', $_vclitem)) {
$_vghezt9 += 12;
}
$_vvy2wai = sprintf('%02d:%02d', $_vghezt9, isset($_v2bydcc[2]) && $_v2bydcc[2] !== '' ? min(59, (int) $_v2bydcc[2]) : 0);
}
$_vx0bzei['events'][$_vy0kra6] = $this->ics_attrs($_vmmy7gi, $_vvy2wai, trim($_vjfe2h7['title']) . ' · ' . $_ve7l4cr, $_v0f96hn($_vjfe2h7), $_ve7l4cr . '|' . $_vy0kra6);
}
$_vi1k2mt = $this->main_event($_vwqdjo7, TRUE);
$_vx0bzei['main'] = $this->ics_attrs($_vahc76t, (string) $_v7dfoxw, ($_vi1k2mt ? trim($_vi1k2mt[1]['title']) : __c('Đám cưới')) . ' · ' . $_ve7l4cr,
$_vi1k2mt ? $_v0f96hn($_vi1k2mt[1]) : '', $_ve7l4cr . '|main');
return $_vx0bzei;
}
private function ics_attrs($_v3m3bp7, $_vfgisnf, $_vmkvjd0, $_vode9jb, $_vjlkrrs)
{
$_vdj1kem = new DateTimeZone('Asia/Ho_Chi_Minh');
if (preg_match('/^\d{1,2}:\d{2}$/', $_vfgisnf)) {
$_vqr2zi4 = new DateTime($_v3m3bp7 . ' ' . $_vfgisnf, $_vdj1kem);
$_vznscvv = clone $_vqr2zi4;
$_vznscvv->modify('+4 hours');
$_vuhzxga = new DateTimeZone('UTC');
$_vqr2zi4->setTimezone($_vuhzxga);
$_vznscvv->setTimezone($_vuhzxga);
$_vpjn5nz = $_vqr2zi4->format('Ymd\THis\Z');
$_vawes4q = $_vznscvv->format('Ymd\THis\Z');
$_vmr5ypp = FALSE;
} else {
$_vqr2zi4 = new DateTime($_v3m3bp7, $_vdj1kem);
$_vpjn5nz = $_vqr2zi4->format('Ymd');
$_vawes4q = $_vqr2zi4->modify('+1 day')->format('Ymd');
$_vmr5ypp = TRUE;
}
return array('start' => $_vpjn5nz, 'end' => $_vawes4q, 'allday' => $_vmr5ypp, 'title' => $_vmkvjd0, 'location' => $_vode9jb,
'uid' => substr(md5($_vjlkrrs . '|' . $_v3m3bp7), 0, 16) . '@anhcuoi');
}
/**
 * Bản gốc của ảnh (/anh-goc/<file_key>.<ext>). Chủ nhà luôn tải được; khách chỉ khi bật "cho tải ảnh", ảnh đã
 * duyệt và khách xem được album chứa ảnh (album công khai, album mật khẩu đã mở khóa, hoặc album ẩn đang làm
 * album trang chủ). Trang khóa bằng mật khẩu đã được Public_Controller chặn trước khi tới đây.
 */
public function original($file_key = '')
{
$photo = $this->photo_model->find_by_key($file_key);
$album = $photo ? $this->album_model->find($photo['album_id']) : NULL;
if (!$photo || !$album) {
return $this->not_found();
}
if (!$this->is_admin()) {
$this->load->model('content_model');
$home = $this->content_model->home_album();
$visible = $this->album_unlocked($album)
|| ($album['visibility'] === 'hidden' && $home && (int) $home['id'] === (int) $album['id']);
if (!album_download_allowed() || $photo['status'] !== 'approved' || !$visible) {
return $this->not_found();
}
}
$path = $this->photo_model->original_path($photo);
if (!is_file($path)) {
return $this->not_found();
}
$types = array('jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif');
$ext = strtolower((string) $photo['ext']);
$name = preg_replace('/[^\w\-. ]+/u', '_', pathinfo((string) $photo['orig_name'], PATHINFO_FILENAME));
$name = ($name !== '' ? $name : $photo['file_key']) . '.' . $ext;
if (session_status() === PHP_SESSION_ACTIVE) {
session_write_close();
}
header('Content-Type: ' . (isset($types[$ext]) ? $types[$ext] : 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . str_replace('"', '', $name) . '"');
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
}

public function album($_v8a5lmg = '')
{
$_v2t5i35 = $this->album_model->find_by_slug($_v8a5lmg);
if (!$_v2t5i35 || ($_v2t5i35['visibility'] === 'hidden' && !$this->is_admin())) {
return $this->not_found();
}
if (!$this->album_unlocked($_v2t5i35)) {
$this->render('public/album_locked', array('title' => $_v2t5i35['title'], 'album' => $_v2t5i35, 'error' => '',
'home_path' => $this->home_path()));
return;
}
$this->render('public/album', array(
'title' => $_v2t5i35['title'],
'album' => $_v2t5i35,
'photos' => $this->photo_model->by_album($_v2t5i35['id']),
'home_path' => $this->home_path(), 
));
}
public function unlock_album($_vgqvqp9 = '')
{
$_vpbx0st = $this->album_model->find_by_slug($_vgqvqp9);
if (!$_vpbx0st || $_vpbx0st['visibility'] !== 'password' || $this->input->method() !== 'post') {
redirect('a/' . rawurlencode($_vgqvqp9));
}
$this->load->library('ratelimit');
$_v5ap6hy = '';
if (!$this->ratelimit->allowed('album_password')) {
$_v5ap6hy = __('Thử sai nhiều lần quá. Bạn thử lại sau ít phút nhé.');
$this->output->set_status_header(429);
} elseif (password_verify((string) $this->input->post('password'), (string) $_vpbx0st['password_hash'])) {
$_v2ecd4c = (array) $this->session->userdata('ac_album_unlocked');
$_v2ecd4c[] = (int) $_vpbx0st['id'];
$this->session->set_userdata('ac_album_unlocked', array_values(array_unique($_v2ecd4c)));
redirect('a/' . rawurlencode($_vpbx0st['slug']));
} else {
$this->ratelimit->hit('album_password');
$_v5ap6hy = __('Mật khẩu chưa đúng.');
}
$this->render('public/album_locked', array('title' => $_vpbx0st['title'], 'album' => $_vpbx0st, 'error' => $_v5ap6hy,
'home_path' => $this->home_path()));
}
public function unlock_site()
{
if ($this->input->method() !== 'post') {
redirect('');
}
$this->load->library('ratelimit');
$_vcega4p = '';
if (!$this->ratelimit->allowed('album_password')) {
$_vcega4p = __('Thử sai nhiều lần quá. Bạn thử lại sau ít phút nhé.');
$this->output->set_status_header(429);
} elseif (password_verify((string) $this->input->post('password'), setting('site_password_hash'))) {
$this->session->set_userdata('ac_site_unlocked', TRUE);

$_viv5oqu = trim((string) $this->input->post('back'), '/');
redirect(preg_match('~^[a-z0-9-]+(/[a-z0-9-]+)*$~i', $_viv5oqu) ? $_viv5oqu : '');
} else {
$this->ratelimit->hit('album_password');
$_vcega4p = __('Mật khẩu chưa đúng.');
}
$this->render('public/site_locked', array('title' => __('Trang riêng tư'), 'error' => $_vcega4p));
}

const ZIP_SLOTS = 2;






const ZIP_LONG_SLOTS = 6;
const ZIP_SLOW_AFTER = 15;
const ZIP_SLOW_BPS = 262144;
const ZIP_FAST_MAX = 120;

private function zip_busy($_vuxt1vx, $_vq7wfq7, $_vfonj85, $_vk3aj8r)
{
$this->output->set_status_header($_vuxt1vx);
$this->output->set_header('Retry-After: ' . (int) $_vk3aj8r);
$this->render('public/try_later', array('title' => __('Bạn thử lại sau ít phút nhé'), 'message' => $_vq7wfq7,
'back_url' => base_url('a/' . rawurlencode($_vfonj85['slug'])), 'back_text' => __('← Về album')));
}

private function zip_slot($_vkxeky0, $_vmpr84g = '.zip_slot_', $_v8rqkh3 = self::ZIP_SLOTS)
{
for ($_vbiikfu = 1; $_vbiikfu <= $_v8rqkh3; $_vbiikfu++) {
$_vxkyoja = @fopen($_vkxeky0 . $_vmpr84g . $_vbiikfu, 'c');
if ($_vxkyoja && flock($_vxkyoja, LOCK_EX | LOCK_NB)) {
return $_vxkyoja;
}
if ($_vxkyoja) {
fclose($_vxkyoja);
}
}
return NULL;
}




private function zip_ticker(&$_v1d94ao, $_va8f1r5)
{
$_vmut9c7 = microtime(TRUE);
$_v4bzhse = array('long' => FALSE, 'next' => 0);
return function ($_vh2g94n) use (&$_v1d94ao, &$_v4bzhse, $_va8f1r5, $_vmut9c7) {
if ($_v4bzhse['long']) {
return;
}
$_vvbsee4 = microtime(TRUE) - $_vmut9c7;
if ($_vvbsee4 < self::ZIP_SLOW_AFTER || $_vvbsee4 < $_v4bzhse['next']) {
return;
}
$_v4bzhse['next'] = $_vvbsee4 + 5;
if ($_vvbsee4 < self::ZIP_FAST_MAX && $_vh2g94n / $_vvbsee4 >= self::ZIP_SLOW_BPS) {
return;
}
$_vt14j29 = $this->zip_slot($_va8f1r5, '.zip_long_', self::ZIP_LONG_SLOTS);
if (!$_vt14j29) {
return;
}
@flock($_v1d94ao, LOCK_UN);
@fclose($_v1d94ao);
$_v1d94ao = $_vt14j29;
$_v4bzhse['long'] = TRUE;
};
}

private function zip_entries($_v51ughi)
{
$_viuy3f0 = array();
$_v71cat5 = array();
foreach ($_v51ughi as $_vzfxxl4 => $_vyatvqa) {
$_vcswdj5 = $this->photo_model->original_path($_vyatvqa);
if (!is_file($_vcswdj5)) {
continue;
}
$_vz5kkul = pathinfo((string) $_vyatvqa['orig_name'], PATHINFO_FILENAME);
$_vz5kkul = preg_replace('/[^\w\-. ]+/u', '_', $_vz5kkul) ?: 'anh';
$_vvmp11i = sprintf('%03d_%s.%s', $_vzfxxl4 + 1, mb_substr($_vz5kkul, 0, 60), $_vyatvqa['ext']);
if (isset($_v71cat5[$_vvmp11i])) {
$_vvmp11i = sprintf('%03d_%s_%d.%s', $_vzfxxl4 + 1, mb_substr($_vz5kkul, 0, 60), $_vyatvqa['id'], $_vyatvqa['ext']);
}
$_v71cat5[$_vvmp11i] = TRUE;
$_viuy3f0[] = array($_vcswdj5, $_vvmp11i);
}
return $_viuy3f0;
}

private function zip_headers($_v429z38, $_visdpdr)
{
header('Content-Type: application/zip');
header('Content-Length: ' . $_visdpdr);
header('Content-Disposition: attachment; filename="' . slugify($_v429z38['title']) . '.zip"');
header('X-Content-Type-Options: nosniff');
while (ob_get_level()) {
ob_end_clean();
}
}









public function zip($_viimmm9 = '')
{
$_v1snq3g = $this->album_model->find_by_slug($_viimmm9);
if (!$_v1snq3g || ($_v1snq3g['visibility'] === 'hidden' && !$this->is_admin()) || !$this->album_unlocked($_v1snq3g)) {
return $this->not_found();
}

if (!album_download_allowed() && !$this->is_admin()) {
$_v78baeg = __('Cô dâu chú rể chỉ mở album để xem trên trang, chưa cho tải ảnh về. Bạn vẫn xem thoải mái nhé!');
if ($this->input->is_ajax_request()) {
return json_out(array('ok' => FALSE, 'error' => $_v78baeg), 403);
}
show_error($_v78baeg, 403, __('Album chỉ để xem')); 
}
$_vwj7kep = $this->photo_model->by_album($_v1snq3g['id']);
if (!$_vwj7kep) {
show_error(__('Album chưa có ảnh.'), 404);
}
$_vni3xus = $this->is_admin();
$this->load->library('ratelimit');
if (!$_vni3xus && !$this->ratelimit->allowed('zip')) {
return $this->zip_busy(429, __('Bạn đã tải nhiều lần, thử lại sau ít phút nhé.'), $_v1snq3g, 900);
}
$this->load->library('zipstream');
foreach ($this->zip_entries($_vwj7kep) as $_vax5gcs) {
$this->zipstream->add($_vax5gcs[0], $_vax5gcs[1]);
}
if (!$this->zipstream->count()) {
show_error(__('Album chưa có ảnh.'), 404);
}
$_v29uv5n = FCPATH . 'database/tmp/';
if (!is_dir($_v29uv5n)) {
@mkdir($_v29uv5n, 0700, TRUE);
}

foreach ((array) glob($_v29uv5n . 'acz*') as $_vp619q9) {
if ($_vp619q9 && is_file($_vp619q9) && @filemtime($_vp619q9) < time() - 7200) {
@unlink($_vp619q9);
}
}
$_vn938ii = $this->zipstream->fits();
if (!$_vn938ii) {
if (!class_exists('ZipArchive')) {
show_error(__('Máy chủ thiếu tiện ích ZIP (php-zip). Hãy tải từng ảnh.'), 501);
}
$_vb6zgr9 = @disk_free_space($_v29uv5n);
if ($_vb6zgr9 !== FALSE && $_vb6zgr9 < $this->zipstream->length() + 512 * 1048576) {
return $this->zip_busy(503, __('Máy chủ tạm hết chỗ trống để đóng gói album lớn này — bạn tải từng ảnh hoặc thử lại sau nhé.'), $_v1snq3g, 600);
}
}
$_vhqjg58 = $this->zip_slot($_v29uv5n);
if (!$_vhqjg58) {
return $this->zip_busy(503, __('Đang có nhiều người tải album, bạn thử lại sau ít phút nhé.'), $_v1snq3g, 120);
}
@set_time_limit(0);
$_vkta50i = $this->zip_ticker($_vhqjg58, $_v29uv5n);
if ($_vn938ii) {
register_shutdown_function(function () use (&$_vhqjg58) {
@flock($_vhqjg58, LOCK_UN);
@fclose($_vhqjg58);
});
if (!$_vni3xus) {
$this->ratelimit->hit('zip');
}
$this->zip_headers($_v1snq3g, $this->zipstream->length());
$this->zipstream->send($_vkta50i);
exit; 
}
ignore_user_abort(TRUE);
$_vm8pf92 = @tempnam($_v29uv5n, 'acz');

if (!$_vm8pf92 || realpath(dirname($_vm8pf92)) !== realpath($_v29uv5n)) {
if ($_vm8pf92) {
@unlink($_vm8pf92);
}
flock($_vhqjg58, LOCK_UN);
fclose($_vhqjg58);
show_error(__('Không tạo được file ZIP (thư mục database/tmp không ghi được).'), 500);
}
register_shutdown_function(function () use ($_vm8pf92, &$_vhqjg58) {
@unlink($_vm8pf92);
foreach ((array) glob($_vm8pf92 . '.*') as $_v5x9ha9) { 
if ($_v5x9ha9) {
@unlink($_v5x9ha9);
}
}
@flock($_vhqjg58, LOCK_UN);
@fclose($_vhqjg58);
});
$_vse7t4q = new ZipArchive();
if ($_vse7t4q->open($_vm8pf92, ZipArchive::OVERWRITE) !== TRUE) {
show_error(__('Không tạo được file ZIP.'), 500);
}
foreach ($this->zip_entries($_vwj7kep) as $_vax5gcs) {
$_vse7t4q->addFile($_vax5gcs[0], $_vax5gcs[1]);
$_vse7t4q->setCompressionName($_vax5gcs[1], ZipArchive::CM_STORE);
}
$_vse7t4q->close();
if (!$_vni3xus) {
$this->ratelimit->hit('zip');
}
$this->zip_headers($_v1snq3g, filesize($_vm8pf92));

$_v8r3ww5 = fopen($_vm8pf92, 'rb');
$_vmfzg7u = 0;
while ($_v8r3ww5 && !feof($_v8r3ww5)) {
$_vr558k6 = fread($_v8r3ww5, 65536);
echo $_vr558k6;
$_vmfzg7u += strlen($_vr558k6);
flush();
if (connection_aborted()) {
break;
}
$_vkta50i($_vmfzg7u);
}
if ($_v8r3ww5) {
fclose($_v8r3ww5);
}
exit; 
}
}