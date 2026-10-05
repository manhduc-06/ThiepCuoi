<?php

defined('BASEPATH') OR exit('No direct script access allowed');




class Content_model extends CI_Model
{






private $published = NULL; 
private $images = NULL;
public function __construct()
{
parent::__construct();
$this->config->load('content', TRUE);
$this->load->model(array('settings_model', 'photo_model'));
}
public function registry($_vlw16ae)
{
return (array) $this->config->item($_vlw16ae, 'content');
}




public function card_style($_v9nxjru = '')
{
$_v28544j = $this->registry('card_styles');
if ($_v9nxjru !== '' && $this->card_usable($_v9nxjru)) {
return $_v9nxjru;
}
$_v78kyos = (string) $this->settings_model->get('invite_card_style', 'classic');
return $this->card_usable($_v78kyos) ? $_v78kyos : 'classic';
}

public function card_usable($_v1yix2t)
{
$_vsxlqx7 = $this->registry('card_styles');
return isset($_vsxlqx7[$_v1yix2t]) && (empty($_vsxlqx7[$_v1yix2t]['pro']) || pro_enabled());
}

public function card_paths($_vq1q4p1)
{
$_vw0mt9x = $this->registry('card_styles');
$_vfr2zwo = !empty($_vw0mt9x[$_vq1q4p1]['pro']);
return array('view' => ($_vfr2zwo ? 'public/pro/cards/' : 'public/cards/') . $_vq1q4p1, 'css' => ($_vfr2zwo ? 'css/pro/cards/' : 'css/cards/') . $_vq1q4p1 . '.css');
}
public function snapshot_keys()
{
$_vygsg3j = array_map(function ($_v8mxeuj) { return 'pos.' . $_v8mxeuj; }, array_keys($this->registry('content_images')));
return array_merge(
array_keys($this->registry('content_text')),
array_keys($this->registry('content_images')),
$_vygsg3j,
array('events', 'theme', 'fx', 'fx_hearts', 'music', 'wedding_date', 'wedding_time', 'home_album_id', 'hero_photo_id')
);
}

public function use_published()
{
$_v5lffs7 = $this->published_snapshot();
if ($_v5lffs7 === NULL) {
return FALSE;
}
$this->published = $_v5lffs7;
$this->images = NULL;
return TRUE;
}
public function is_published()
{
return $this->published_snapshot() !== NULL;
}
private function published_snapshot()
{
$_ve1fp9c = $this->settings_model->get('published', '');
$_vedv0jx = $_ve1fp9c !== '' ? json_decode($_ve1fp9c, TRUE) : NULL;
return is_array($_vedv0jx) ? $_vedv0jx : NULL;
}

public function get($_vfawu58, $_vp3xgjw = '')
{
if ($this->published !== NULL && in_array($_vfawu58, $this->snapshot_keys(), TRUE)) {
return array_key_exists($_vfawu58, $this->published) ? (string) $this->published[$_vfawu58] : $_vp3xgjw;
}
return (string) $this->settings_model->get($_vfawu58, $_vp3xgjw);
}
private function draft_snapshot()
{
$_vwt3ull = array();
foreach ($this->snapshot_keys() as $_vqr3dt6) {
$_vwt3ull[$_vqr3dt6] = (string) $this->settings_model->get($_vqr3dt6, '');
}
return $_vwt3ull;
}
public function has_unpublished_changes()
{
$_v4e6ze1 = $this->published_snapshot();
return $_v4e6ze1 === NULL || $_v4e6ze1 != $this->draft_snapshot();
}
public function publish()
{
$this->settings_model->set_many(array(
'published' => json_encode($this->draft_snapshot(), JSON_UNESCAPED_UNICODE),
'published_at' => now_str(),
));
$this->gc();
return TRUE;
}

/**
 * Cập nhật một số mục thẳng vào bản đã xuất bản (giữ nguyên các mục nháp khác). Dùng cho thông tin sửa ở
 * Cài đặt như tên cô dâu chú rể: phải có hiệu lực ngay và giống nhau ở mọi nơi (trang cưới, thiệp, tiêu đề,
 * trang gửi ảnh), không đợi bấm Xuất bản. Chưa xuất bản lần nào thì không làm gì.
 */
public function publish_keys(array $values)
{
$snap = $this->published_snapshot();
if ($snap === NULL) {
return FALSE;
}
foreach ($values as $k => $v) {
if (in_array($k, $this->snapshot_keys(), TRUE)) {
$snap[$k] = (string) $v;
}
}
$this->settings_model->set_many(array('published' => json_encode($snap, JSON_UNESCAPED_UNICODE)));
return TRUE;
}

public function gc()
{
$_vm6dqmn = $this->published_snapshot() ?: array();
$_vql8zt2 = $this->draft_snapshot();
$_v1bmh1v = array();
foreach (array_keys($this->registry('content_images')) as $_va06m02) {
foreach (array($_vql8zt2, $_vm6dqmn) as $_vmhhprm) {
if (!empty($_vmhhprm[$_va06m02])) {
$_v1bmh1v[(int) $_vmhhprm[$_va06m02]] = TRUE;
}
}
}
foreach (array($_vql8zt2, $_vm6dqmn) as $_vmhhprm) {
if (!empty($_vmhhprm['hero_photo_id'])) {
$_v1bmh1v[(int) $_vmhhprm['hero_photo_id']] = TRUE;
}
}
foreach ($this->db->select('id')->get_where('photos', array('album_id' => 0))->result_array() as $_vwrcwkf) {
if (!isset($_v1bmh1v[(int) $_vwrcwkf['id']])) {
$this->photo_model->delete($_vwrcwkf['id']);
}
}
$_vox1trk = array_filter(array(isset($_vql8zt2['music']) ? $_vql8zt2['music'] : '', isset($_vm6dqmn['music']) ? $_vm6dqmn['music'] : ''));
$_vox1trk = array_merge($_vox1trk, array_column($this->music_library(), 'file'));
$this->load->library('quota');
foreach ((array) glob(FCPATH . 'uploads/media/*') as $_v4vaqai) {
if (is_file($_v4vaqai) && basename($_v4vaqai) !== 'index.html' && !in_array(basename($_v4vaqai), $_vox1trk, TRUE)) {
$_vkrakur = Quota::files_bytes(array($_v4vaqai));
if (@unlink($_v4vaqai)) {
$this->quota->add(-$_vkrakur);
}
}
}
}
public function couple_title()
{
$_vaj12y9 = trim($this->get('groom_name'));
$_vttmolh = trim($this->get('bride_name'));
return ($_vaj12y9 !== '' && $_vttmolh !== '') ? $_vaj12y9 . ' & ' . $_vttmolh : ($_vaj12y9 . $_vttmolh !== '' ? $_vaj12y9 . $_vttmolh : __c('Đám cưới của chúng mình'));
}

public function monogram()
{

$_v9eryur = function ($_v803hji) {
$_vzxrfxy = array_reverse(preg_split('/\s+/u', trim((string) $_v803hji)));
foreach ($_vzxrfxy as $_v6m347c) {
if (preg_match('/\p{L}/u', $_v6m347c, $_vhu2yww)) {
return mb_strtoupper($_vhu2yww[0]);
}
}
return '';
};
return array($_v9eryur($this->get('groom_name')), $_v9eryur($this->get('bride_name')));
}
public function text($_vtcq2g9)
{
$_v0w2dwo = $this->registry('content_text');
$_vntg4o5 = $this->get($_vtcq2g9, '');


if ($_vntg4o5 !== '' && isset($_v0w2dwo[$_vtcq2g9]) && $_vntg4o5 === $_v0w2dwo[$_vtcq2g9][0]) {
$_vntg4o5 = $this->default_text($_vtcq2g9);
}
if ($_vntg4o5 === '') {
$_vntg4o5 = isset($_v0w2dwo[$_vtcq2g9]) ? $this->default_text($_vtcq2g9) : '';

if ($_vntg4o5 === '' && $_vtcq2g9 === 'c.bride_fullname') {
$_vntg4o5 = $this->get('bride_name');
} elseif ($_vntg4o5 === '' && $_vtcq2g9 === 'c.groom_fullname') {
$_vntg4o5 = $this->get('groom_name');
}
}
return (string) $_vntg4o5;
}




private function default_text($_vkeacaw)
{
$_vp3zsyj = $this->registry('content_text');
$_vvb8u73 = $_vp3zsyj[$_vkeacaw][0];
if ($_vvb8u73 !== '' && lang_content() !== 'vi') {
$_vnmqapt = lang_map('all', lang_content());
if (!empty($_vnmqapt[$_vvb8u73 . '|' . $_vkeacaw])) {
return $_vnmqapt[$_vvb8u73 . '|' . $_vkeacaw];
}
}
return __c($_vvb8u73);
}
const EFFECTS = array('hearts' => 'Tim rơi', 'snow' => 'Tuyết rơi', 'leaves' => 'Lá vàng rơi', 'petals' => 'Cánh hoa rơi',
'blossom' => 'Hoa đào rơi', 'glitter' => 'Kim tuyến vàng', 'stars' => 'Sao lấp lánh', 'none' => 'Không hiệu ứng');

public function fx()
{
$_vw6yo9i = $this->get('fx', '');
if (!array_key_exists($_vw6yo9i, self::EFFECTS)) {
$_vw6yo9i = $this->get('fx_hearts', '1') === '0' ? 'none' : 'hearts';
}
return $_vw6yo9i;
}
public function fx_hearts()
{
return $this->fx() !== 'none';
}
public function is_multiline($_vryscg1)
{
$_vxmsymv = $this->registry('content_text');
return isset($_vxmsymv[$_vryscg1]) && $_vxmsymv[$_vryscg1][2];
}

public function save_text($_vbf1gdp, $_v551903)
{
$_vbk6zhp = $this->registry('content_text');
if (!isset($_vbk6zhp[$_vbf1gdp])) {
return __('Ô chữ không hợp lệ.');
}
$_v551903 = str_replace(array("\r\n", "\r"), "\n", (string) $_v551903);
$_v551903 = preg_replace('/[\x00-\x09\x0B-\x1F\x7F]/u', '', $_v551903);
if (!$_vbk6zhp[$_vbf1gdp][2]) {
$_v551903 = str_replace("\n", ' ', $_v551903);
}
$_v551903 = trim(preg_replace("/\n{3,}/", "\n\n", $_v551903));
if (mb_strlen($_v551903) > $_vbk6zhp[$_vbf1gdp][1]) {
return __('Tối đa {n} ký tự.', array('n' => $_vbk6zhp[$_vbf1gdp][1]));
}
if (in_array($_vbf1gdp, array('groom_name', 'bride_name'), TRUE) && $_v551903 === '') {
return __('Tên không được để trống.');
}
$this->settings_model->set_many(array($_vbf1gdp => $_v551903));
return TRUE;
}

public function image($_vg245vn)
{
if ($this->images === NULL) {
$this->images = array();
$_vx2bp9h = array();
foreach (array_keys($this->registry('content_images')) as $_vck06eb) {
$_vr9s5z9 = (int) $this->get($_vck06eb);
if ($_vck06eb === 'img.hero_main' && !$_vr9s5z9) {
$_vr9s5z9 = (int) $this->get('hero_photo_id'); 
}
if ($_vr9s5z9) {
$_vx2bp9h[$_vck06eb] = $_vr9s5z9;
}
}
if ($_vx2bp9h) {
$_vgxqlix = $this->db->where_in('id', array_values($_vx2bp9h))->get('photos')->result_array();
$_v3y8kus = array_column($_vgxqlix, NULL, 'id');
foreach ($_vx2bp9h as $_vck06eb => $_vr9s5z9) {
if (isset($_v3y8kus[$_vr9s5z9])) {
$this->images[$_vck06eb] = $_v3y8kus[$_vr9s5z9];
}
}
}
}
return isset($this->images[$_vg245vn]) ? $this->images[$_vg245vn] : NULL;
}




public function replace_image($_veq64zq, $_vkzyubv, $_vjqswg0)
{
if (!array_key_exists($_veq64zq, $this->registry('content_images'))) {
return __('Vị trí ảnh không hợp lệ.');
}
$_vg4wpve = $this->photo_model->add_from_file($_vkzyubv, $_vjqswg0, 0, array('source' => 'owner'));
if (!is_array($_vg4wpve)) {
return $_vg4wpve;
}
$this->settings_model->set_many(array($_veq64zq => $_vg4wpve['id'], 'pos.' . $_veq64zq => ''));
if ($_veq64zq === 'img.hero_main') {
$this->settings_model->set_many(array('hero_photo_id' => ''));
}
$this->images = NULL;
$this->gc();
return $_vg4wpve;
}

public function use_photo($_v0i2mss, $_vsxh614)
{
if (!array_key_exists($_v0i2mss, $this->registry('content_images'))) {
return FALSE;
}
$this->settings_model->set_many(array($_v0i2mss => (int) $_vsxh614));
if ($_v0i2mss === 'img.hero_main') {
$this->settings_model->set_many(array('hero_photo_id' => ''));
}
$this->images = NULL;
$this->gc();
return TRUE;
}




public function image_pos($_v60eepv)
{
$_v4fshk1 = explode(' ', trim($this->get('pos.' . $_v60eepv)));
if (count($_v4fshk1) !== 3) {
return array(50, 50, 1);
}
return array(max(0, min(100, (float) $_v4fshk1[0])), max(0, min(100, (float) $_v4fshk1[1])), max(1, min(4, (float) $_v4fshk1[2])));
}
public function save_image_pos($_vgpcrf2, $_vxkjinz, $_vqxbiai, $_vbiyw66)
{
if (!array_key_exists($_vgpcrf2, $this->registry('content_images'))) {
return FALSE;
}
$_vpnxjgv = sprintf('%.1f %.1f %.2f', max(0, min(100, (float) $_vxkjinz)), max(0, min(100, (float) $_vqxbiai)), max(1, min(4, (float) $_vbiyw66)));
$this->settings_model->set_many(array('pos.' . $_vgpcrf2 => $_vpnxjgv));
return TRUE;
}
public function events()
{
$_va0yntl = $this->get('events', '');
$_vx58lpc = $_va0yntl !== '' ? json_decode($_va0yntl, TRUE) : NULL;
if (is_array($_vx58lpc)) {
return $_vx58lpc;
}

return array_map(function ($_vc8zvmf) {
$_vc8zvmf['title'] = __c($_vc8zvmf['title']);
$_vc8zvmf['place'] = __c($_vc8zvmf['place']);
return $_vc8zvmf;
}, $this->registry('content_events'));
}





/**
 * Địa điểm dành cho bên nào: 'groom' | 'bride' | '' (chung). Chủ nhà chọn trong trình chỉnh sửa; chưa chọn thì
 * đoán theo tên/nơi tổ chức ("vu quy", "nhà gái" -> nhà gái; "thành hôn", "tân hôn", "nhà trai" -> nhà trai).
 */
public function event_side(array $ev)
{
$side = isset($ev['side']) ? (string) $ev['side'] : '';
if ($side === 'groom' || $side === 'bride') {
return $side;
}
if ($side === 'both') {
return '';
}
$t = mb_strtolower((isset($ev['title']) ? $ev['title'] : '') . ' ' . (isset($ev['place']) ? $ev['place'] : ''));
if (preg_match('/nhà gái|nha gai|vu quy|bride/u', $t)) {
return 'bride';
}
if (preg_match('/nhà trai|nha trai|thành hôn|thanh hon|tân hôn|tan hon|groom/u', $t)) {
return 'groom';
}
return '';
}

/** Địa điểm khách bên $side nên thấy: của bên đó + chung. Không còn gì (hoặc khách không rõ bên) -> tất cả. */
public function events_for_side(array $events, $side)
{
if ($side !== 'groom' && $side !== 'bride') {
return $events;
}
$own = array_values(array_filter($events, function ($ev) use ($side) {
return in_array($this->event_side($ev), array('', $side), TRUE);
}));
return $own ?: $events;
}

public function normalize_map($_vpat3bx)
{
$_vpat3bx = trim((string) $_vpat3bx);
if ($_vpat3bx === '' || preg_match('~^https?://[^\s]+$~i', $_vpat3bx)) {
return $_vpat3bx;
}
if (preg_match('~^[a-z][a-z0-9+.-]*:~i', $_vpat3bx)) {
return FALSE;
}
if (preg_match('~^(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}(?:[/?#][^\s]*)?$~i', $_vpat3bx)) {
return 'https://' . $_vpat3bx;
}
return FALSE;
}

const MAP_MAX = 2000;

public $events_error_index = NULL;

public function save_events($_vmf69mv)
{
$this->events_error_index = NULL;
if (!is_array($_vmf69mv) || count($_vmf69mv) > 6) {
return __('Tối đa 6 sự kiện.');
}
$_vv2l7so = array();
foreach (array_values($_vmf69mv) as $_vxn6t8k => $_vi2mv4a) {
if (!is_array($_vi2mv4a)) {
return __('Dữ liệu không hợp lệ.');
}
$_vg1z9w5 = array();
foreach (array('title' => 80, 'place' => 120, 'address' => 300, 'time' => 120, 'map' => self::MAP_MAX) as $_vfl73bw => $_vsr4yco) {
$_vk1c7w2 = isset($_vi2mv4a[$_vfl73bw]) ? trim(preg_replace('/[\x00-\x1F\x7F]/u', ' ', (string) $_vi2mv4a[$_vfl73bw])) : '';
if ($_vfl73bw === 'map' && mb_strlen($_vk1c7w2) > $_vsr4yco) {

$this->events_error_index = $_vxn6t8k;
return __('Link quá dài — hãy dùng nút Chia sẻ của Google Maps (maps.app.goo.gl/…).');
}
$_vg1z9w5[$_vfl73bw] = mb_substr($_vk1c7w2, 0, $_vsr4yco);
}
$_vyl03rs = $this->normalize_map($_vg1z9w5['map']);
if ($_vyl03rs === FALSE) {
$this->events_error_index = $_vxn6t8k;
return __('Link bản đồ của "{ten}" không hợp lệ — hãy dán link Google Maps (vd https://maps.app.goo.gl/…).',
array('ten' => $_vg1z9w5['title'] !== '' ? $_vg1z9w5['title'] : __('địa điểm {n}', array('n' => $_vxn6t8k + 1))));
}
$_vg1z9w5['map'] = $_vyl03rs;
// Dành cho: '' = tự nhận theo tên địa điểm, 'both' = chung hai nhà, 'groom' = nhà trai, 'bride' = nhà gái.
$_vside = isset($_vi2mv4a['side']) ? (string) $_vi2mv4a['side'] : '';
$_vg1z9w5['side'] = in_array($_vside, array('both', 'groom', 'bride'), TRUE) ? $_vside : '';
$_vv2l7so[] = $_vg1z9w5;
}
$this->settings_model->set_many(array('events' => json_encode($_vv2l7so, JSON_UNESCAPED_UNICODE)));
return TRUE;
}
public function theme()
{
$_v0tqu2r = $this->get('theme', '');
return $this->theme_allowed($_v0tqu2r) ? $_v0tqu2r : 'serenity';
}




public function theme_allowed($_v4cz7fb)
{
$_v00frjv = $this->registry('themes');
return isset($_v00frjv[$_v4cz7fb]) && (empty($_v00frjv[$_v4cz7fb]['pro']) || pro_enabled());
}





public function music_list()
{
$_ve32j82 = array();
foreach ($this->registry('music_builtin') as $_v60y045 => $_vow49zx) {
$_ve32j82[] = array('id' => 'builtin:' . $_v60y045, 'title' => __($_vow49zx[0]), 'url' => base_url('assets/music/' . $_vow49zx[1]),
'credit' => $_vow49zx[2], 'builtin' => TRUE);
}
foreach ($this->music_library() as $_vow49zx) {
if (is_file(FCPATH . 'uploads/media/' . $_vow49zx['file'])) {
$_ve32j82[] = array('id' => $_vow49zx['file'], 'title' => $_vow49zx['name'], 'url' => base_url('uploads/media/' . $_vow49zx['file']),
'credit' => '', 'builtin' => FALSE);
}
}
return $_ve32j82;
}




public function music_suggestions()
{
$_vzx0frz = array_column($this->music_library(), 'suggest');
$_vmq7bfz = array();
foreach ($this->registry('music_suggestions') as $_vqsfqrs => $_v4arexl) {
if (in_array($_vqsfqrs, $_vzx0frz, TRUE)) {
continue;
}
$_vmq7bfz[] = array('key' => $_vqsfqrs, 'title' => $_v4arexl[0], 'artist' => $_v4arexl[1], 'name' => $_v4arexl[0] . ' — ' . $_v4arexl[1],
'search' => 'https://www.youtube.com/results?search_query=' . rawurlencode($_v4arexl[0] . ' ' . $_v4arexl[1]));
}
return $_vmq7bfz;
}
private function music_library()
{
$_vy4efuk = json_decode((string) $this->settings_model->get('music_library', ''), TRUE);
$_vy4efuk = is_array($_vy4efuk) ? array_values(array_filter($_vy4efuk, function ($_v5crtci) {
return is_array($_v5crtci) && isset($_v5crtci['file']) && preg_match('/^[a-f0-9]{32}\.(mp3|m4a|ogg)$/', $_v5crtci['file']);
})) : array();

$_v28oxzn = (string) $this->settings_model->get('music', '');
if (preg_match('/^[a-f0-9]{32}\.(mp3|m4a|ogg)$/', $_v28oxzn) && !in_array($_v28oxzn, array_column($_vy4efuk, 'file'), TRUE)) {
$_vy4efuk[] = array('file' => $_v28oxzn, 'name' => __('Bài hát đã tải lên'));
}
return $_vy4efuk;
}

public function music()
{
$_vprtc3b = $this->get('music', '');
if ($_vprtc3b === '') {
return NULL;
}
$_vovx722 = $this->registry('music_builtin');
if (strpos($_vprtc3b, 'builtin:') === 0) {
$_ve183pa = substr($_vprtc3b, 8);
return isset($_vovx722[$_ve183pa]) ? array('id' => $_vprtc3b, 'title' => __($_vovx722[$_ve183pa][0]), 'url' => base_url('assets/music/' . $_vovx722[$_ve183pa][1]), 'credit' => $_vovx722[$_ve183pa][2]) : NULL;
}
if (preg_match('/^[a-f0-9]{32}\.(mp3|m4a|ogg)$/', $_vprtc3b) && is_file(FCPATH . 'uploads/media/' . $_vprtc3b)) {
$_v91gpze = __('Nhạc nền');
foreach ($this->music_library() as $_vo2ffwm) {
if ($_vo2ffwm['file'] === $_vprtc3b) {
$_v91gpze = $_vo2ffwm['name'];
}
}
return array('id' => $_vprtc3b, 'title' => $_v91gpze, 'url' => base_url('uploads/media/' . $_vprtc3b), 'credit' => '');
}
return NULL;
}
public function music_url()
{
$_v0x6vak = $this->music();
return $_v0x6vak ? $_v0x6vak['url'] : NULL;
}

public function select_music($_vws1x93)
{
$_vws1x93 = (string) $_vws1x93;
if ($_vws1x93 !== '' && !in_array($_vws1x93, array_column($this->music_list(), 'id'), TRUE)) {
return FALSE;
}
$this->settings_model->set_many(array('music' => $_vws1x93));
$this->gc();
return TRUE;
}




public function save_music($_v18zidi, $_vsf7d00, $_vxs71sz = '')
{
$_v9ca00a = $this->registry('music_suggestions');
$_vxs71sz = (string) $_vxs71sz;
if ($_vxs71sz !== '' && !isset($_v9ca00a[$_vxs71sz])) {
return __('Bài gợi ý không hợp lệ.');
}
$_vsep4hm = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : NULL;
$_vk0lw4t = $_vsep4hm ? finfo_file($_vsep4hm, $_v18zidi) : '';
$_v4hx9m3 = array('audio/mpeg' => 'mp3', 'audio/mp3' => 'mp3', 'audio/mp4' => 'm4a', 'audio/x-m4a' => 'm4a',
'video/mp4' => 'm4a', 'audio/ogg' => 'ogg', 'application/ogg' => 'ogg');
if (!isset($_v4hx9m3[$_vk0lw4t])) {
return __('Chỉ nhận file nhạc MP3, M4A hoặc OGG.');
}
$_vinqpmg = $this->music_library();
if (count($_vinqpmg) >= 20) {
return __('Thư viện đã có 20 bài, hãy xóa bớt.');
}
$this->load->library('quota');
$_vwm0cgl = $this->quota->check((int) @filesize($_v18zidi));
if ($_vwm0cgl !== NULL) {
return $_vwm0cgl;
}
$_vgnsye6 = FCPATH . 'uploads/media';
if (!is_dir($_vgnsye6) && !@mkdir($_vgnsye6, 0755, TRUE)) {
return __('Không tạo được thư mục lưu nhạc.');
}
$_v0obnq3 = random_key(16) . '.' . $_v4hx9m3[$_vk0lw4t];

$_vm0ch60 = $this->quota->begin();
$_vwm0cgl = $_vm0ch60 === NULL ? NULL : $this->quota->over($_vm0ch60, (int) @filesize($_v18zidi));
if ($_vwm0cgl !== NULL) {
$this->quota->end();
return $_vwm0cgl;
}
if (!@move_uploaded_file($_v18zidi, $_vgnsye6 . '/' . $_v0obnq3) && !@copy($_v18zidi, $_vgnsye6 . '/' . $_v0obnq3)) {
$this->quota->end();
return __('Không lưu được file nhạc.');
}
@chmod($_vgnsye6 . '/' . $_v0obnq3, 0644);
$this->quota->add(Quota::files_bytes(array($_vgnsye6 . '/' . $_v0obnq3)));
$this->quota->end();
$_vb5y89s = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', pathinfo((string) $_vsf7d00, PATHINFO_FILENAME)));
$_vbv1wlo = array('file' => $_v0obnq3, 'name' => mb_substr($_vb5y89s !== '' ? $_vb5y89s : __('Bài hát'), 0, 80));
if ($_vxs71sz !== '') {
$_vbv1wlo = array('file' => $_v0obnq3, 'name' => $_v9ca00a[$_vxs71sz][0] . ' — ' . $_v9ca00a[$_vxs71sz][1], 'suggest' => $_vxs71sz);
}
$_vinqpmg[] = $_vbv1wlo;
$this->settings_model->set_many(array('music_library' => json_encode($_vinqpmg, JSON_UNESCAPED_UNICODE), 'music' => $_v0obnq3));
$this->gc();
return $_v0obnq3;
}

public function delete_music($_v9rg1xo)
{
$_v6yk4u6 = array_values(array_filter($this->music_library(), function ($_vzjtrbn) use ($_v9rg1xo) { return $_vzjtrbn['file'] !== $_v9rg1xo; }));
$_vos747q = array('music_library' => json_encode($_v6yk4u6, JSON_UNESCAPED_UNICODE));
if ((string) $this->settings_model->get('music', '') === $_v9rg1xo) {
$_vos747q['music'] = '';
}
$this->settings_model->set_many($_vos747q);
$this->gc();
return TRUE;
}
public function remove_music()
{
return $this->select_music('');
}





public function checklist()
{
$_vd44kto = $this->home_album();
$_vedi3tt = $_vd44kto ? (int) $this->db->where('album_id', (int) $_vd44kto['id'])->where('status', 'approved')->count_all_results('photos') : 0;
$_vtno294 = count(array_filter($this->events(), function ($_v7l9kox) { return trim((string) ($_v7l9kox['address'] ?? '')) !== ''; })) > 0;
$_vmgful9 = array(
array('names', __('Tên cô dâu & chú rể'), trim($this->get('groom_name')) !== '' && trim($this->get('bride_name')) !== '', '#top', 1),
array('date', __('Ngày cưới'), trim($this->get('wedding_date')) !== '', '#top', 1),
array('hero', __('Ảnh chính đầu trang'), $this->image('img.hero_main') !== NULL, '#top', 1),
array('couple', __('Ảnh cô dâu & chú rể'), $this->image('img.bride') !== NULL && $this->image('img.groom') !== NULL, '#couple', 1),
array('address', __('Địa chỉ tổ chức'), $_vtno294, '#location', 1),
array('gallery', __('Album ảnh cưới (không bắt buộc)'), $_vedi3tt > 0, '#gallery', 1),
array('publish', __('Cho khách xem trang'), $this->is_published() && !$this->has_unpublished_changes(), '#top', 3),
);
return array_map(function ($_vso9od1) {
return array('key' => $_vso9od1[0], 'label' => $_vso9od1[1], 'done' => (bool) $_vso9od1[2], 'href' => $_vso9od1[3], 'group' => $_vso9od1[4]);
}, $_vmgful9);
}

public function home_album()
{
$CI =& get_instance();
$CI->load->model('album_model');
$_vncy8zm = (int) $this->get('home_album_id');
$_vo8fsrb = $_vncy8zm ? $CI->album_model->find($_vncy8zm) : NULL;
if (!$_vo8fsrb) {
$_vo8fsrb = $this->db->where('visibility', 'public')->order_by('sort_order, id')->limit(1)->get('albums')->row_array();
}
return $_vo8fsrb ?: NULL;
}
}