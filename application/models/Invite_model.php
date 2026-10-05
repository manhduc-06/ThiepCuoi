<?php

defined('BASEPATH') OR exit('No direct script access allowed');






class Invite_model extends CI_Model
{
const SIDES = array('' => 'Chung', 'groom' => 'Nhà trai', 'bride' => 'Nhà gái');

const SALUTATIONS = array('Ông bà', 'Cô chú', 'Anh chị', 'Gia đình', 'Vợ chồng', 'Anh', 'Chị', 'Em', 'Bạn',
'Cô', 'Chú', 'Bác', 'Ông', 'Bà', 'Cậu', 'Mợ', 'Dì', 'Dượng', 'Thím', 'Thầy', 'Cháu');





const RESERVED = array('admin', 'a', 'moi', 'xac-nhan', 'gui-anh', 'loi-chuc', 'unlock', 'setup', 'health', 'auth',
'home', 'guest', 'errors', 'assets', 'uploads', 'database', 'cloudflared', 'application', 'system', 'index',
'api', 'login', 'logout', 'robots', 'favicon', 'sitemap', 'static', 'public', 'www', 'mail', 'vendor', 'php',
'tools', 'script', 'logs', 'xem', 'thiep', 'album');
const SLUG_RE = '/^[a-z0-9][a-z0-9-]{0,38}[a-z0-9]$/';




public $error_field = '';

private function new_code()
{
$_va7l1sm = 'abcdefghjkmnpqrstuvwxyz23456789';
do {
$_vr6oow4 = '';
for ($_vnyqa1c = 0; $_vnyqa1c < 8; $_vnyqa1c++) {
$_vr6oow4 .= $_va7l1sm[random_int(0, strlen($_va7l1sm) - 1)];
}
} while ($this->find_by_code($_vr6oow4));
return $_vr6oow4;
}
public function find($_vtu0fdq)
{
return $this->db->get_where('invites', array('id' => (int) $_vtu0fdq))->row_array();
}
public function find_by_code($_vc5ef6u)
{
return preg_match('/^[a-z0-9]{8}$/', (string) $_vc5ef6u)
? $this->db->get_where('invites', array('code' => $_vc5ef6u))->row_array() : NULL;
}




public function find_by_slug($_vhbxm6i)
{
$_vhbxm6i = strtolower((string) $_vhbxm6i);
if (!preg_match(self::SLUG_RE, $_vhbxm6i)) {
return NULL;
}
$_vg59d4q = $this->db->get_where('invites', array('slug' => $_vhbxm6i))->row_array();
if (!$_vg59d4q) {
$_vi6l2mg = $this->db->select('invite_id')->get_where('invite_old_slugs', array('slug' => $_vhbxm6i))->row_array();
$_vg59d4q = $_vi6l2mg ? $this->find($_vi6l2mg['invite_id']) : NULL;
}
return $_vg59d4q ?: NULL;
}

public function sample()
{
return $this->db->where('source', 'invite')->order_by('id', 'ASC')->limit(1)->get('invites')->row_array() ?: NULL;
}

public function path($_vxhjqqq)
{
return !empty($_vxhjqqq['slug']) ? $_vxhjqqq['slug'] : 'moi/' . $_vxhjqqq['code'];
}





public function guest_path($_v0j8df2)
{
return $this->site_locked() ? 'moi/' . $_v0j8df2['code'] : $this->path($_v0j8df2);
}

public function site_locked()
{
return (string) setting('site_password_hash', '') !== '';
}

public function display_name($_veq0njo)
{
$_vw117qs = trim((string) $_veq0njo['salutation']);
$_vohepwr = trim((string) $_veq0njo['name']);
return self::join_salutation($_vw117qs, $_vohepwr);
}





public static function join_salutation($_va4eooq, $_vqp3etx)
{
$_va4eooq = trim((string) $_va4eooq);
$_vqp3etx = trim((string) $_vqp3etx);
if ($_va4eooq === '' || $_vqp3etx === '') {
return trim($_va4eooq . ' ' . $_vqp3etx);
}
if (preg_match('/^[\x{4e00}-\x{9fff}\x{3400}-\x{4dbf}·]+$/u', $_va4eooq)) {
return mb_substr($_va4eooq, -1) === '的' ? $_va4eooq . $_vqp3etx : $_vqp3etx . $_va4eooq;
}
if (preg_match('/^[\x{0e00}-\x{0e7f}.]+$/u', $_va4eooq)) {
return $_va4eooq . $_vqp3etx;
}
return $_va4eooq . ' ' . $_vqp3etx;
}

private $reserved_cache = NULL;
public function reserved_words()
{
if ($this->reserved_cache !== NULL) { 
return $this->reserved_cache;
}
$_vyai06v = self::RESERVED;
foreach (array_keys((array) $this->router->routes) as $_vt38f8l) {
$_vr6xhxk = strtolower((string) strtok((string) $_vt38f8l, '/'));
if (preg_match('/^[a-z0-9-]+$/', $_vr6xhxk)) {
$_vyai06v[] = $_vr6xhxk;
}
}
foreach ((array) glob(APPPATH . 'controllers/*') as $_vrkxl3o) {
$_vyai06v[] = strtolower(pathinfo($_vrkxl3o, PATHINFO_FILENAME));
}
foreach ((array) glob(FCPATH . '*') as $_vrkxl3o) {
$_vyai06v[] = strtolower(basename($_vrkxl3o));
}
return $this->reserved_cache = array_values(array_unique($_vyai06v));
}

public function check_slug($_vzasxju, $_vkd3w4v = 0)
{
if (!preg_match(self::SLUG_RE, (string) $_vzasxju)) {
return __('Đường dẫn chỉ gồm chữ thường không dấu, số và dấu gạch ngang (2–40 ký tự, không bắt đầu/kết thúc bằng "-").');
}
if (in_array($_vzasxju, $this->reserved_words(), TRUE)) {
return __('Đường dẫn "{slug}" trùng với trang có sẵn của web, hãy chọn tên khác.', array('slug' => $_vzasxju));
}
$_v9yptxl = $this->db->select('id')->get_where('invites', array('slug' => $_vzasxju))->row_array();
if ($_v9yptxl && (int) $_v9yptxl['id'] !== (int) $_vkd3w4v) {
return __('Đường dẫn "{slug}" đã dùng cho khách khác.', array('slug' => $_vzasxju));
}

$_vvsjz78 = $this->db->select('invite_id')->get_where('invite_old_slugs', array('slug' => $_vzasxju))->row_array();
if ($_vvsjz78 && (int) $_vvsjz78['invite_id'] !== (int) $_vkd3w4v) {
return __('Đường dẫn "{slug}" là link cũ của khách khác (link đó vẫn mở thiệp của họ), hãy chọn tên khác.', array('slug' => $_vzasxju));
}
return TRUE;
}





public function unique_slug($_v8yd9l5, $_vm5nji7 = 0)
{
$_v8yd9l5 = preg_replace_callback('/\+?\d[\d .\-()]*\d/u', function ($_vjjvasj) {
return preg_match_all('/\d/', $_vjjvasj[0]) >= 6 ? ' ' : $_vjjvasj[0];
}, (string) $_v8yd9l5);
$_vuxix5a = trim(preg_replace('/-{2,}/', '-', preg_replace('/\d{6,}/', '', ascii_slug($_v8yd9l5, 34))), '-');
if (strlen($_vuxix5a) < 2) {
$_vuxix5a = 'khach' . ($_vuxix5a !== '' ? '-' . $_vuxix5a : '');
}
$_vl8yw6u = $this->reserved_words();
$_vkfpdv4 = array();
foreach ($this->db->select('id, slug')->like('slug', $_vuxix5a, 'after')->get('invites')->result_array() as $_vfkmw8g) {
if ((int) $_vfkmw8g['id'] !== (int) $_vm5nji7) {
$_vkfpdv4[$_vfkmw8g['slug']] = TRUE;
}
}
foreach ($this->db->select('invite_id, slug')->like('slug', $_vuxix5a, 'after')->get('invite_old_slugs')->result_array() as $_vfkmw8g) {
if ((int) $_vfkmw8g['invite_id'] !== (int) $_vm5nji7) {
$_vkfpdv4[$_vfkmw8g['slug']] = TRUE;
}
}
// Hậu tố ngẫu nhiên 4 ký tự (vd co-lan-x7k2): link vẫn dễ đọc nhưng người lạ không đoán được từ tên khách
// (link thiệp mở được thiệp riêng và trả lời tham dự thay khách). Dài tối đa 34 + 5 = 39 < 40 của route.
do {
$_vhvzyqi = $_vuxix5a . '-' . self::slug_suffix();
} while (isset($_vkfpdv4[$_vhvzyqi]) || in_array($_vhvzyqi, $_vl8yw6u, TRUE));
return $_vhvzyqi;
}

private static function slug_suffix()
{
$abc = 'abcdefghjkmnpqrstuvwxyz23456789'; // bỏ i, l, o, 0, 1 dễ nhầm khi đọc
$out = '';
for ($i = 0; $i < 4; $i++) {
$out .= $abc[random_int(0, strlen($abc) - 1)];
}
return $out;
}

public function ensure_slugs()
{
$_vn5oeq4 = $this->db->where('source', 'invite')->where('slug IS NULL', NULL, FALSE)->get('invites')->result_array();
foreach ($_vn5oeq4 as $_vk8r2j5) {
$this->db->update('invites', array('slug' => $this->unique_slug($this->display_name($_vk8r2j5), $_vk8r2j5['id'])), array('id' => (int) $_vk8r2j5['id']));
}
return count($_vn5oeq4);
}


const SAL_MAX = 40; 
const SAL_LEN = 30; 
const SAL_TPL_LEN = 500;
private $sal_cache = NULL;

public function sal_key($_venh5hi)
{
return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $_venh5hi)));
}

public function salutations()
{
if ($this->sal_cache === NULL) {
$_v9unkn6 = json_decode((string) setting('salutations'), TRUE);
if (is_array($_v9unkn6) && ($_v9unkn6 === json_decode(Settings_model::SALUTATIONS_JSON_V1, TRUE)
|| $_v9unkn6 === json_decode(Settings_model::SALUTATIONS_JSON, TRUE))) {

$_v9unkn6 = $this->default_salutations();
}
$_vwegdi3 = is_array($_v9unkn6) ? $this->clean_salutations($_v9unkn6) : NULL;
$this->sal_cache = is_array($_vwegdi3) ? $_vwegdi3 : $this->default_salutations();
}
return $this->sal_cache;
}




public function default_salutations()
{
$_vvpp5uh = lang_content();
if ($_vvpp5uh === 'vi') {
return json_decode(Settings_model::SALUTATIONS_JSON, TRUE);
}
if ($_vvpp5uh !== 'en') {
$_vs1nka5 = APPPATH . 'language/' . $_vvpp5uh . '/salutations.json';
$_vlnm2ub = is_file($_vs1nka5) ? json_decode((string) file_get_contents($_vs1nka5), TRUE) : NULL;
if (is_array($_vlnm2ub) && $_vlnm2ub) {
return $_vlnm2ub;
}
}
return json_decode(Settings_model::SALUTATIONS_JSON_EN, TRUE);
}




public function clean_salutations(array $_va52j6e)
{
$_vn70ruq = array();
$_vq2j0rr = array();
foreach ($_va52j6e as $_v4sy12l) {
if (!is_array($_v4sy12l)) {
continue;
}
$_v9a9jjv = trim(preg_replace('/\s+/u', ' ', (string) ($_v4sy12l['s'] ?? '')));
$_v0kah5x = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) ($_v4sy12l['t'] ?? '')));
if ($_v9a9jjv === '' && $_v0kah5x === '') {
continue;
}
if ($_v9a9jjv === '') {
return __('Mỗi lời mời mẫu cần có xưng hô đi kèm.');
}
if (mb_strlen($_v9a9jjv) > self::SAL_LEN) {
return __('Xưng hô "{s}…" dài quá {n} ký tự.', array('s' => mb_substr($_v9a9jjv, 0, self::SAL_LEN), 'n' => self::SAL_LEN));
}
if (mb_strlen($_v0kah5x) > self::SAL_TPL_LEN) {
return __('Lời mời mẫu của "{s}" dài quá {n} ký tự.', array('s' => $_v9a9jjv, 'n' => self::SAL_TPL_LEN));
}
$_vcfn73t = $this->sal_key($_v9a9jjv);
if (isset($_vq2j0rr[$_vcfn73t])) {
return __('Xưng hô "{s}" bị trùng — mỗi xưng hô chỉ một dòng.', array('s' => $_v9a9jjv));
}
$_vq2j0rr[$_vcfn73t] = TRUE;
$_vn70ruq[] = array('s' => $_v9a9jjv, 't' => $_v0kah5x);
}
if (count($_vn70ruq) > self::SAL_MAX) {
return __('Tối đa {n} xưng hô.', array('n' => self::SAL_MAX));
}
return $_vn70ruq;
}

public function save_salutations(array $_vw070ky)
{
$_vwgklrf = $this->clean_salutations($_vw070ky);
if (!is_array($_vwgklrf)) {
return $_vwgklrf;
}
$this->settings_model->set_many(array('salutations' => json_encode($_vwgklrf, JSON_UNESCAPED_UNICODE)));
$this->sal_cache = NULL;
return TRUE;
}
public function reset_salutations()
{
$this->settings_model->set_many(array('salutations' => Settings_model::SALUTATIONS_JSON));
$this->sal_cache = NULL;
}

public function find_salutation($_vfvru32)
{
$_vfw5g4i = $this->sal_key($_vfvru32);
if ($_vfw5g4i === '') {
return NULL;
}
foreach ($this->salutations() as $_vdrij8j) {
if ($this->sal_key($_vdrij8j['s']) === $_vfw5g4i) {
return $_vdrij8j;
}
}
return NULL;
}

public function canonical_salutation($_vptooqq)
{
$_v4wzgj7 = $this->find_salutation($_vptooqq);
return $_v4wzgj7 ? $_v4wzgj7['s'] : trim(preg_replace('/\s+/u', ' ', (string) $_vptooqq));
}

private function known_salutations()
{
$_vxwsfqz = array();
foreach ($this->salutations() as $_vqo0f62) {
$_vxwsfqz[$this->sal_key($_vqo0f62['s'])] = $_vqo0f62['s'];
}
foreach (self::SALUTATIONS as $_vb8jk2v) {
$_vxwsfqz += array($this->sal_key($_vb8jk2v) => $_vb8jk2v);
}
$_vnct91u = array_values($_vxwsfqz);
usort($_vnct91u, function ($_v3pl9aq, $_v0lga0l) { return mb_strlen($_v0lga0l) - mb_strlen($_v3pl9aq); });
return $_vnct91u;
}




public function split_salutation($_vsk96ch)
{
$_vsk96ch = trim(preg_replace('/\s+/u', ' ', (string) $_vsk96ch));
$_vzp7ia0 = mb_strtolower($_vsk96ch);
foreach ($this->known_salutations() as $_vhn1xnh) {
$_vs4wuj4 = mb_strlen($_vhn1xnh);
if (mb_substr($_vzp7ia0, 0, $_vs4wuj4) === mb_strtolower($_vhn1xnh) && mb_substr($_vsk96ch, $_vs4wuj4, 1) === ' ' && trim(mb_substr($_vsk96ch, $_vs4wuj4)) !== '') {
return array($_vhn1xnh, trim(mb_substr($_vsk96ch, $_vs4wuj4)));
}
}
return array('', $_vsk96ch);
}




public function invite_source($_v86hz61)
{
if (trim((string) $_v86hz61['invite_text']) !== '') {
return array('own', '');
}
$_vmhi30s = $this->find_salutation($_v86hz61['salutation']);
return ($_vmhi30s && $_vmhi30s['t'] !== '') ? array('sal', $_vmhi30s['s']) : array('common', '');
}
private function clean_fields(array $_v7afgss)
{
$_vk5ap4q = function ($_vk33wor, $_vprre8r) { return mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) $_vk33wor)), 0, $_vprre8r); };
$_v06arfi = array();
if (array_key_exists('name', $_v7afgss)) {
$_v06arfi['name'] = $_vk5ap4q($_v7afgss['name'], 80);
}
if (array_key_exists('salutation', $_v7afgss)) {
$_v06arfi['salutation'] = $this->canonical_salutation($_vk5ap4q($_v7afgss['salutation'], self::SAL_LEN));
}
if (array_key_exists('side', $_v7afgss)) {
$_v06arfi['side'] = array_key_exists((string) $_v7afgss['side'], self::SIDES) ? (string) $_v7afgss['side'] : '';
}
if (array_key_exists('note', $_v7afgss)) {
$_v06arfi['note'] = $_vk5ap4q($_v7afgss['note'], 200);
}
if (array_key_exists('phone', $_v7afgss)) {
$_vmv6mo8 = $_vk5ap4q($_v7afgss['phone'], 20);
$_v06arfi['phone'] = $_vmv6mo8 !== '' ? $_vmv6mo8 : NULL;
}
if (array_key_exists('invite_text', $_v7afgss)) {
$_v78jevx = mb_substr(trim(str_replace("\r", '', (string) $_v7afgss['invite_text'])), 0, 500);
$_v06arfi['invite_text'] = $_v78jevx !== '' ? $_v78jevx : NULL;
}
if (array_key_exists('max_guests', $_v7afgss)) {
$_v5nbbqj = (int) $_v7afgss['max_guests'];
$_v06arfi['max_guests'] = $_v5nbbqj > 0 ? min(20, $_v5nbbqj) : NULL;
}
return $_v06arfi;
}




public function create($_v50uy90, $_votqint = '', $_vwnwdja = '', $_vm0o9vm = 'invite', array $_vaf0kqi = array())
{
$this->error_field = '';
if (array_key_exists('phone', $_vaf0kqi)) {
$_vvnbb98 = $this->clean_phone($_vaf0kqi['phone']);
if ($_vvnbb98 === FALSE) { 
$this->error_field = 'phone';
return __(self::PHONE_ERROR);
}
$_vaf0kqi['phone'] = $_vvnbb98;
}
$_v62i3go = $this->clean_fields(array('name' => $_v50uy90, 'side' => $_votqint, 'note' => $_vwnwdja) + $_vaf0kqi);
if ($_v62i3go['name'] === '') {
$this->error_field = 'name';
return __('Hãy nhập tên khách.');
}
if ($_vm0o9vm === 'invite') {
$_v8s9xv3 = isset($_vaf0kqi['slug']) ? strtolower(trim((string) $_vaf0kqi['slug'])) : '';
if ($_v8s9xv3 !== '') {
$_vrdk02y = $this->check_slug($_v8s9xv3);
if ($_vrdk02y !== TRUE) {
$this->error_field = 'slug';
return $_vrdk02y;
}
} else {
$_v8s9xv3 = $this->unique_slug($this->display_name($_v62i3go + array('salutation' => '')));
}
$_v62i3go['slug'] = $_v8s9xv3;
}
$_v62i3go += array('code' => $this->new_code(), 'source' => $_vm0o9vm === 'web' ? 'web' : 'invite', 'created_at' => now_str());
$this->db->insert('invites', $_v62i3go);
return $this->find($this->db->insert_id());
}




public function create_many($_v3elgig, $_v8tnw32 = '')
{
return $this->create_rows($this->parse_lines($_v3elgig, $_v8tnw32));
}




public function parse_lines($_vm0znv7, $_vuuinoh = '')
{
$_v6lfdwr = array();
foreach (preg_split('/\R/u', (string) $_vm0znv7) as $_vxj8oz9) {
$_vxj8oz9 = trim($_vxj8oz9);
if ($_vxj8oz9 === '') {
continue;
}
if (strpos($_vxj8oz9, '|') !== FALSE) {
$_v4wemgv = array_map('trim', explode('|', $_vxj8oz9, 3));
$_vna8yax = $_v4wemgv[0];
$_vhevjav = isset($_v4wemgv[1]) ? $_v4wemgv[1] : '';
$_vy4d288 = isset($_v4wemgv[2]) ? $_v4wemgv[2] : '';
if ($_vhevjav === '' && !$this->is_salutation_only($_vna8yax)) { 
list($_vna8yax, $_vhevjav) = $this->split_salutation($_vna8yax);
}
} elseif ($this->is_salutation_only($_vxj8oz9)) {
list($_vna8yax, $_vhevjav, $_vy4d288) = array($_vxj8oz9, '', '');
} else {
list($_vna8yax, $_vhevjav) = $this->split_salutation($_vxj8oz9);
$_vy4d288 = '';
}


$_v6lfdwr[] = array('salutation' => $_vna8yax, 'name' => $_vhevjav, 'side' => $_vuuinoh, 'invite_text' => $_vy4d288);
}
return $_v6lfdwr;
}

private function is_salutation_only($_vmalo0q)
{
$_v37wjmu = $this->sal_key($_vmalo0q);
foreach ($this->known_salutations() as $_va5d0xw) {
if ($this->sal_key($_va5d0xw) === $_v37wjmu) {
return TRUE;
}
}
return FALSE;
}
const BULK_MAX = 500;

public function create_rows(array $_vk42i4i)
{
$_vju9vpj = $this->create_rows_report($_vk42i4i);
return $_vju9vpj['created'];
}







public function create_rows_report(array $_vzcftf9)
{
$_vzdmxib = array('received' => 0, 'created' => 0, 'skipped' => 0, 'truncated' => 0, 'dups' => 0, 'over' => 0, 'failed' => array(), 'bad_phone' => 0);
$_vis4pk0 = array();
foreach ($_vzcftf9 as $_vmj7bei) {
if (!is_array($_vmj7bei)) {
continue;
}
$_vmj7bei = array(
'salutation' => is_string($_vmj7bei['salutation'] ?? NULL) ? $_vmj7bei['salutation'] : '',
'name' => is_string($_vmj7bei['name'] ?? NULL) ? trim(preg_replace('/\s+/u', ' ', $_vmj7bei['name'])) : '',
'side' => is_string($_vmj7bei['side'] ?? NULL) ? $_vmj7bei['side'] : '',
'invite_text' => is_string($_vmj7bei['invite_text'] ?? NULL) ? $_vmj7bei['invite_text'] : '',
'phone' => is_string($_vmj7bei['phone'] ?? NULL) ? trim($_vmj7bei['phone']) : '',
'note' => is_string($_vmj7bei['note'] ?? NULL) ? $_vmj7bei['note'] : '', 
'max_guests' => is_scalar($_vmj7bei['max_guests'] ?? NULL) ? (int) $_vmj7bei['max_guests'] : 0, 
);

if ($_vmj7bei['phone'] === '' && ($_v7usydd = $this->split_phone($_vmj7bei['name']))) {
list($_vmj7bei['name'], $_vmj7bei['phone']) = $_v7usydd;
}

$_vb8ssig = $this->clean_phone($_vmj7bei['phone']);
if ($_vb8ssig === FALSE) {
$_vmj7bei['phone'] = '';
$_vmj7bei['_bad_phone'] = TRUE;
} else {
$_vmj7bei['phone'] = $_vb8ssig;
}
if ($_vmj7bei['name'] === '' && trim($_vmj7bei['salutation']) === '' && trim($_vmj7bei['invite_text']) === '') {
continue; 
}
$_vis4pk0[] = $_vmj7bei;
}
$_vzdmxib['received'] = count($_vis4pk0);
if (count($_vis4pk0) > self::BULK_MAX) {
$_vzdmxib['over'] = count($_vis4pk0) - self::BULK_MAX;
$_vis4pk0 = array_slice($_vis4pk0, 0, self::BULK_MAX);
}
$_vs95iq2 = $this->name_keys();
$this->db->trans_start();
foreach ($_vis4pk0 as $_vmj7bei) {
if ($_vmj7bei['name'] === '') {
$_vzdmxib['skipped']++;
continue;
}
if (mb_strlen($_vmj7bei['name']) > 80) {
$_vzdmxib['truncated']++;
}
$_v4ze4zl = $this->create($_vmj7bei['name'], $_vmj7bei['side'], $_vmj7bei['note'], 'invite', array(
'salutation' => $this->canonical_salutation($_vmj7bei['salutation']),
'invite_text' => $_vmj7bei['invite_text'],
'phone' => $_vmj7bei['phone'],
'max_guests' => $_vmj7bei['max_guests'] > 0 ? $_vmj7bei['max_guests'] : '',
));
if (!is_array($_v4ze4zl)) {
$_vzdmxib['failed'][] = mb_strimwidth($_vmj7bei['name'], 0, 40, '…') . ': ' . $_v4ze4zl;
continue;
}
$_vzdmxib['created']++;
if (!empty($_vmj7bei['_bad_phone'])) {
$_vzdmxib['bad_phone']++;
}
$_v109o4m = $this->sal_key($this->display_name($_v4ze4zl));
if (isset($_vs95iq2[$_v109o4m])) {
$_vzdmxib['dups']++;
}
$_vs95iq2[$_v109o4m] = TRUE;
}
$this->db->trans_complete();
return $_vzdmxib;
}




public function split_phone($_vo3vfbd)
{



if (preg_match('/^(.*?)(?:^|[\s,;:|\-–]+)\(?((?:\+?84 ?|0)\d(?:[ .\-]?\d){7,10}|[35789]\d{8})\)?\s*$/u', trim((string) $_vo3vfbd), $_vgjgebl)
&& trim($_vgjgebl[1]) !== '') {
$_vv9a7ki = trim($_vgjgebl[2]);
return array(trim($_vgjgebl[1]), preg_match('/^[35789]\d{8}$/', $_vv9a7ki) ? '0' . $_vv9a7ki : $_vv9a7ki);
}
return NULL;
}





public function clean_phone($_v80s5yq)
{
$_v80s5yq = trim((string) $_v80s5yq);
if ($_v80s5yq === '') {
return '';
}

$_v80s5yq = preg_replace_callback('/\(([^)]*)\)?/', function ($_v31x1y5) {
return preg_match('/\d/', $_v31x1y5[1]) ? ' ' . $_v31x1y5[1] . ' ' : ' ';
}, $_v80s5yq);
$_vgq9epn = trim(preg_replace('/\s+/', ' ', preg_replace('/[^0-9+ .\-]/', '', $_v80s5yq)), ' .-');
if (preg_match('/^[35789]\d{8}$/', $_vgq9epn)) {
$_vgq9epn = '0' . $_vgq9epn; 
}
$_vu4mc0l = strlen(preg_replace('/\D/', '', $_vgq9epn));
return ($_vu4mc0l >= 8 && $_vu4mc0l <= 15) ? mb_substr($_vgq9epn, 0, 20) : FALSE;
}
const PHONE_ERROR = 'Số điện thoại chưa đúng — cần 8–15 chữ số (vd 0912 345 678), hoặc để trống.';

private function name_keys()
{
$_vvevyit = array();
foreach ($this->db->select('salutation, name')->get('invites')->result_array() as $_vrlmwrf) {
$_vvevyit[$this->sal_key($this->display_name($_vrlmwrf))] = TRUE;
}
return $_vvevyit;
}

public function duplicate_ids()
{
$_v8w1g2e = array();
foreach ($this->db->select('id, salutation, name')->get('invites')->result_array() as $_vlhpjww) {
$_v8w1g2e[$this->sal_key($this->display_name($_vlhpjww))][] = (int) $_vlhpjww['id'];
}
$_v0ub7eg = array();
foreach ($_v8w1g2e as $_vh6udec) {
if (count($_vh6udec) > 1) {
$_v0ub7eg += array_fill_keys($_vh6udec, TRUE);
}
}
return $_v0ub7eg;
}

public function update($_vnx7dmq, array $_v25qrck)
{
$this->error_field = '';
$_v4yzrk1 = $this->find($_vnx7dmq);
if (!$_v4yzrk1) {
return __('Không tìm thấy lời mời.');
}
if (array_key_exists('phone', $_v25qrck)) {
$_v61qfn0 = $this->clean_phone($_v25qrck['phone']);
if ($_v61qfn0 === FALSE) {
$this->error_field = 'phone';
return __(self::PHONE_ERROR);
}
$_v25qrck['phone'] = $_v61qfn0;
}
$_v81hecw = $this->clean_fields($_v25qrck);
if (isset($_v81hecw['name']) && $_v81hecw['name'] === '') {
$this->error_field = 'name';
return __('Tên khách không được để trống.');
}
if ($_v4yzrk1['source'] === 'invite' && array_key_exists('slug', $_v25qrck)) {
$_vyz0a7n = strtolower(trim((string) $_v25qrck['slug']));
if ($_vyz0a7n === '') {
$_vyz0a7n = $this->unique_slug($this->display_name($_v81hecw + $_v4yzrk1), $_v4yzrk1['id']);
}
$_vjprm5k = $this->check_slug($_vyz0a7n, $_v4yzrk1['id']);
if ($_vjprm5k !== TRUE) {
$this->error_field = 'slug';
return $_vjprm5k;
}
$_v81hecw['slug'] = $_vyz0a7n;
}
$this->db->trans_start();
if (isset($_v81hecw['slug']) && !empty($_v4yzrk1['slug']) && $_v81hecw['slug'] !== $_v4yzrk1['slug']) {

$this->db->query('INSERT INTO invite_old_slugs (slug, invite_id, created_at) VALUES (?, ?, ?)
				ON CONFLICT(slug) DO UPDATE SET invite_id = excluded.invite_id', array($_v4yzrk1['slug'], (int) $_vnx7dmq, now_str()));
$this->db->delete('invite_old_slugs', array('slug' => $_v81hecw['slug'], 'invite_id' => (int) $_vnx7dmq));
}
if ($_v81hecw) {
$this->db->update('invites', $_v81hecw, array('id' => (int) $_vnx7dmq));
}
$this->db->trans_complete();
return TRUE;
}

public function old_slugs($_vq5o1pb)
{
return array_column($this->db->select('slug')->order_by('created_at')->get_where('invite_old_slugs',
array('invite_id' => (int) $_vq5o1pb))->result_array(), 'slug');
}
public function delete($_vqguj8m)
{
$this->db->delete('invite_old_slugs', array('invite_id' => (int) $_vqguj8m));
return $this->db->delete('invites', array('id' => (int) $_vqguj8m));
}

public function mark_opened($_vd7ccxn)
{
if (empty($_vd7ccxn['opened_at'])) {
$this->db->update('invites', array('opened_at' => now_str()), array('id' => (int) $_vd7ccxn['id']));
}
}

public function guest_limit($_v46gcck)
{
$_vyvt8v8 = (int) (isset($_v46gcck['max_guests']) ? $_v46gcck['max_guests'] : 0);
return $_vyvt8v8 > 0 ? min(20, $_vyvt8v8) : 20;
}



public function respond($_vfiqudr, $_vli9u6t, $_vkcsfkz, $_v2l3bzq, $_vnwg5jk = '')
{
$this->load->model('wish_model');
$_v2l3bzq = mb_substr(trim((string) $_v2l3bzq), 0, 1000);
$_vuk3qub = (int) $_vfiqudr['wish_id'];
$_v4dljzo = $this->display_name($_vfiqudr);
if ($_v2l3bzq !== '') {
$_vq9yi8s = setting('wishes_approval') === '1';
$_vq12302 = $_vuk3qub ? $this->db->get_where('wishes', array('id' => $_vuk3qub))->row_array() : NULL;
if ($_vq12302) {
$_vzlyipn = array('name' => $_v4dljzo);
if ((string) $_vq12302['message'] !== $_v2l3bzq) {


$_vzlyipn['message'] = $_v2l3bzq;
$_vzlyipn['status'] = $_vq9yi8s ? 'pending' : ($_vq12302['status'] === 'hidden' ? 'hidden' : 'approved');
$_vzlyipn['updated_at'] = now_str();
}
$this->db->update('wishes', $_vzlyipn, array('id' => $_vuk3qub));
} else {
$_vuk3qub = $this->wish_model->add($_v4dljzo, $_v2l3bzq, $_vq9yi8s ? 'pending' : 'approved');
}
} else {

$_v2l3bzq = trim((string) $_vfiqudr['message']);
}
$this->db->update('invites', array(
'status' => $_vli9u6t,
'guests' => $_vli9u6t === 'yes' ? max(1, min($this->guest_limit($_vfiqudr), (int) $_vkcsfkz)) : 0,
'message' => $_v2l3bzq !== '' ? $_v2l3bzq : NULL,
'phone' => $_vnwg5jk !== '' ? mb_substr($_vnwg5jk, 0, 20) : $_vfiqudr['phone'],
'wish_id' => $_vuk3qub ?: NULL,
'responded_at' => now_str(),
), array('id' => (int) $_vfiqudr['id']));
return $this->find($_vfiqudr['id']);
}




public function invite_text($_v9moxo2, $_v6gpqln = '', $_v1izag4 = '')
{
$_vxlpp3b = $this->invite_source($_v9moxo2);
if ($_vxlpp3b[0] === 'own') {
$_vgabgfz = trim((string) $_v9moxo2['invite_text']);
} elseif ($_vxlpp3b[0] === 'sal') {
$_vgabgfz = $this->find_salutation($_v9moxo2['salutation'])['t'];
} else {
$_vgabgfz = trim($this->settings_model->localized('invite_template'));
}
return $this->fill_template($_vgabgfz, $_v9moxo2['salutation'], $_v9moxo2['name'], $_v6gpqln, $_v1izag4);
}

public function fill_template($_vqv4htf, $_vk6mfvs, $_vt5c07r, $_vveirzn = '', $_v25ajcv = '')
{
$_vk6mfvs = trim((string) $_vk6mfvs);

$_vlzf0s4 = $_vk6mfvs !== '' && lang_content() === 'vi' ? mb_strtolower(mb_substr($_vk6mfvs, 0, 1)) . mb_substr($_vk6mfvs, 1) : $_vk6mfvs;
$_vqv4htf = (string) $_vqv4htf;


if ($_vk6mfvs !== '') {
$_vhig5t6 = self::join_salutation($_vlzf0s4, (string) $_vt5c07r);
$_vqv4htf = str_replace(array('{ten}{xung_ho}', '{xung_ho} {ten}', '{xung_ho}{ten}'), '{xung_ho_ten}', $_vqv4htf);
} else {
$_vhig5t6 = trim((string) $_vt5c07r);
$_vqv4htf = str_replace(array('{ten}{xung_ho}', '{xung_ho} {ten}', '{xung_ho}{ten}'), '{xung_ho_ten}', $_vqv4htf);
}
$_vcnov3z = strtr($_vqv4htf, array('{xung_ho_ten}' => $_vhig5t6, '{xung_ho}' => $_vlzf0s4, '{ten}' => trim((string) $_vt5c07r), '{cap_doi}' => $_vveirzn, '{ngay}' => $_v25ajcv));

$_vcnov3z = preg_replace('/([\x{0e00}-\x{0eff}\x{4e00}-\x{9fff}])(\p{Latin})/u', '$1 $2', $_vcnov3z);
$_vcnov3z = preg_replace('/(\p{Latin})([\x{0e00}-\x{0eff}])/u', '$1 $2', $_vcnov3z);
$_vcnov3z = trim(preg_replace('/[ \t]{2,}/u', ' ', $_vcnov3z));
$_vcnov3z = preg_replace('/ +([,.!?])/u', '$1', $_vcnov3z); 
return $_vcnov3z !== '' ? mb_strtoupper(mb_substr($_vcnov3z, 0, 1)) . mb_substr($_vcnov3z, 1) : '';
}

const FILTERS = array('unopened', 'opened', 'opened_all', 'invited', 'yes', 'no', 'pending', 'web');






public function all($_v4ugxlw = '', $_v9lvu7p = '', $_vf50h1r = '', $_vaw7h4t = 0, $_v5ggx2w = 0)
{
$this->filter_where($_v4ugxlw, $_v9lvu7p, $_vf50h1r);
$this->db->order_by('COALESCE(responded_at, created_at) DESC, id DESC');
if ((int) $_vaw7h4t > 0) { 
$this->db->limit((int) $_vaw7h4t, max(0, (int) $_v5ggx2w));
}
return $this->db->get('invites')->result_array();
}

public function count_all($_v98jlwe = '', $_vv5vu0j = '', $_v9u1ge1 = '')
{
$this->filter_where($_v98jlwe, $_vv5vu0j, $_v9u1ge1);
return (int) $this->db->count_all_results('invites');
}




public function position($_v4ii65n, $_vhlwkxy = '', $_v4apsri = '', $_vogkktb = '')
{
$_vkjgwoj = $this->find($_v4ii65n);
if (!$_vkjgwoj) {
return -1;
}
$this->filter_where($_vhlwkxy, $_v4apsri, $_vogkktb);
if (!$this->db->where('id', (int) $_v4ii65n)->count_all_results('invites')) {
return -1;
}
$_vb3xu39 = $this->db->escape((string) ($_vkjgwoj['responded_at'] ?: $_vkjgwoj['created_at']));
$this->filter_where($_vhlwkxy, $_v4apsri, $_vogkktb);
$this->db->where('(COALESCE(responded_at, created_at) > ' . $_vb3xu39 . ' OR (COALESCE(responded_at, created_at) = ' . $_vb3xu39
. ' AND id > ' . (int) $_v4ii65n . '))', NULL, FALSE);
return (int) $this->db->count_all_results('invites');
}

private function filter_where($_v9qqkmh, $_v68bvcy, $_vhkke3u)
{
switch ($_v9qqkmh) {
case 'unopened':
$this->db->where('source', 'invite')->where('status', 'pending')->where('opened_at IS NULL', NULL, FALSE);
break;
case 'opened':
$this->db->where('source', 'invite')->where('status', 'pending')->where('opened_at IS NOT NULL', NULL, FALSE);
break;
case 'opened_all':
$this->db->where('source', 'invite')->where('opened_at IS NOT NULL', NULL, FALSE);
break;
case 'invited':
$this->db->where('source', 'invite');
break;
case 'yes':
case 'no':
case 'pending':
$this->db->where('status', $_v9qqkmh);
break;
case 'web':
$this->db->where('source', 'web');
break;
}
if ($_v68bvcy === 'none') {
$this->db->group_start()->where('side', '')->or_where('side IS NULL', NULL, FALSE)->group_end();
} elseif (in_array($_v68bvcy, array('groom', 'bride'), TRUE)) {
$this->db->where('side', $_v68bvcy);
}
$_vhkke3u = trim((string) $_vhkke3u);
if ($_vhkke3u !== '') {
$this->db->group_start()->like('name', $_vhkke3u)->or_like('salutation', $_vhkke3u)->or_like('slug', ascii_slug($_vhkke3u, 40) ?: $_vhkke3u)
->or_like('phone', $_vhkke3u)->or_like('note', $_vhkke3u)->group_end();
}
}




public function stats()
{
$_v6ppysm = $this->db->query("SELECT
				COUNT(*) AS total,
				COUNT(CASE WHEN source = 'invite' THEN 1 END) AS invited,
				COUNT(CASE WHEN status = 'yes' THEN 1 END) AS yes,
				COUNT(CASE WHEN status = 'no' THEN 1 END) AS no,
				COUNT(CASE WHEN status = 'pending' THEN 1 END) AS pending,
				COUNT(CASE WHEN status = 'pending' AND opened_at IS NOT NULL THEN 1 END) AS opened,
				COALESCE(SUM(CASE WHEN status = 'yes' THEN guests END), 0) AS people,
				COALESCE(SUM(CASE WHEN status = 'yes' AND side = 'groom' THEN guests END), 0) AS people_groom,
				COALESCE(SUM(CASE WHEN status = 'yes' AND side = 'bride' THEN guests END), 0) AS people_bride,
				COUNT(CASE WHEN source = 'invite' AND opened_at IS NOT NULL THEN 1 END) AS inv_opened,
				COUNT(CASE WHEN source = 'invite' AND status = 'yes' THEN 1 END) AS inv_yes,
				COUNT(CASE WHEN source = 'invite' AND status = 'no' THEN 1 END) AS inv_no,
				COUNT(CASE WHEN source = 'invite' AND status = 'pending' THEN 1 END) AS inv_pending,
				COUNT(CASE WHEN source = 'invite' AND status = 'pending' AND opened_at IS NULL THEN 1 END) AS inv_unopened,
				COALESCE(SUM(CASE WHEN source = 'invite' AND status = 'yes' THEN guests END), 0) AS inv_people,
				COUNT(CASE WHEN source = 'invite' AND side = 'groom' THEN 1 END) AS inv_groom,
				COUNT(CASE WHEN source = 'invite' AND side = 'bride' THEN 1 END) AS inv_bride,
				COUNT(CASE WHEN source = 'invite' AND side = 'groom' AND status = 'yes' THEN 1 END) AS inv_groom_yes,
				COUNT(CASE WHEN source = 'invite' AND side = 'bride' AND status = 'yes' THEN 1 END) AS inv_bride_yes,
				COUNT(CASE WHEN source = 'invite' AND side = 'groom' AND status <> 'pending' THEN 1 END) AS inv_groom_resp,
				COUNT(CASE WHEN source = 'invite' AND side = 'bride' AND status <> 'pending' THEN 1 END) AS inv_bride_resp,
				COUNT(CASE WHEN source = 'web' THEN 1 END) AS web,
				COUNT(CASE WHEN source = 'web' AND status = 'yes' THEN 1 END) AS web_yes,
				COUNT(CASE WHEN source = 'web' AND status = 'no' THEN 1 END) AS web_no,
				COALESCE(SUM(CASE WHEN source = 'web' AND status = 'yes' THEN guests END), 0) AS web_people,
				COUNT(CASE WHEN source = 'invite' AND COALESCE(side, '') NOT IN ('groom', 'bride') THEN 1 END) AS inv_common,
				COUNT(CASE WHEN source = 'invite' AND COALESCE(side, '') NOT IN ('groom', 'bride') AND status <> 'pending' THEN 1 END) AS inv_common_resp,
				COALESCE(SUM(CASE WHEN source = 'invite' AND COALESCE(side, '') NOT IN ('groom', 'bride') AND status = 'yes' THEN guests END), 0) AS people_common,
				COALESCE(SUM(CASE WHEN source = 'web' AND COALESCE(side, '') NOT IN ('groom', 'bride') AND status = 'yes' THEN guests END), 0) AS web_people_common
			FROM invites")->row_array();
$_v6ppysm = array_map('intval', $_v6ppysm);
$_vkzdq6l = function ($_vzjknen, $_vfkauwu) { return $_vfkauwu > 0 ? (int) round($_vzjknen * 100 / $_vfkauwu) : 0; };
$_v6ppysm['inv_responded'] = $_v6ppysm['inv_yes'] + $_v6ppysm['inv_no'];
$_v6ppysm['response_rate'] = $_vkzdq6l($_v6ppysm['inv_responded'], $_v6ppysm['invited']);
$_v6ppysm['open_rate'] = $_vkzdq6l($_v6ppysm['inv_opened'], $_v6ppysm['invited']);
return $_v6ppysm;
}
public function recent_responses($_vegf8np = 8)
{
return $this->db->where('status <>', 'pending')->order_by('responded_at DESC, id DESC')->limit((int) $_vegf8np)->get('invites')->result_array();
}
}