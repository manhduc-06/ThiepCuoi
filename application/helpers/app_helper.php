<?php

defined('BASEPATH') OR exit('No direct script access allowed');



if (!function_exists('e')) {
function e($_vr9h056)
{
return htmlspecialchars((string) $_vr9h056, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
}
if (!function_exists('setting')) {

function setting($_v4dbm74, $_v41pd8h = '')
{
$CI =& get_instance();
if (!isset($CI->settings_model)) {
return $_v41pd8h;
}
return $CI->settings_model->get($_v4dbm74, $_v41pd8h);
}
}
if (!function_exists('asset_url')) {

function asset_url($_vq5hix4)
{
$_vq5hix4 = ltrim($_vq5hix4, '/');
$_v8w2dqs = FCPATH . 'assets/' . $_vq5hix4;
$_v48ati4 = is_file($_v8w2dqs) ? filemtime($_v8w2dqs) : 0;
return base_url('assets/' . $_vq5hix4) . '?v=' . $_v48ati4;
}
}
if (!function_exists('pro_enabled')) {




function pro_enabled()
{
static $_vdqfmel = NULL;
if ($_vdqfmel === NULL) {
$_vdqfmel = getenv('ANHCUOI_PRO') === '1' && is_dir(FCPATH . 'assets/css/pro');
}
return $_vdqfmel;
}
}
if (!function_exists('photo_rel_path')) {

function photo_rel_path($_vstk5yr, $_vzcwdq0, $_vy8fuov = 'jpg')
{
$_v58tj3i = substr($_vstk5yr, 0, 2);
return 'uploads/photos/' . $_v58tj3i . '/' . $_vstk5yr . '_' . $_vzcwdq0 . '.' . ($_vzcwdq0 === 'o' ? $_vy8fuov : 'jpg');
}
}
if (!function_exists('photo_url')) {
function photo_url($_vo3ykgw, $_vgq5dyr = 't')
{
$_vo3ykgw = (array) $_vo3ykgw;
if ($_vgq5dyr === 'o') {
// Bản gốc không phục vụ tĩnh (router/Caddy chặn *_o.*): đi qua Home::original để kiểm quyền tải.
return base_url('anh-goc/' . $_vo3ykgw['file_key'] . '.' . $_vo3ykgw['ext']);
}
return base_url(photo_rel_path($_vo3ykgw['file_key'], $_vgq5dyr, $_vo3ykgw['ext']));
}
}
if (!function_exists('album_download_allowed')) {




function album_download_allowed()
{
return (string) setting('album_download', '0') === '1';
}
}
if (!function_exists('photo_srcset')) {




function photo_srcset($_vnkjxn4, $_vrsl1mg = 'm')
{
$_vnkjxn4 = (array) $_vnkjxn4;
$_vvbktnq = (int) (isset($_vnkjxn4['width']) ? $_vnkjxn4['width'] : 0);
$_vv9grsv = (int) (isset($_vnkjxn4['height']) ? $_vnkjxn4['height'] : 0);
$CI =& get_instance();
$_vf0dqvk = array('t' => (int) $CI->config->item('photo_thumb_px') ?: 640, 's' => 1280, 'm' => (int) $CI->config->item('photo_medium_px') ?: 2048);
$_v8razwp = array();
foreach ($_vf0dqvk as $_vj5naki => $_vwv2gri) {
if ($_vj5naki === 's' && !is_file(FCPATH . photo_rel_path($_vnkjxn4['file_key'], 's'))) {
continue;
}

$_vnnyojj = ($_vvbktnq > 0 && $_vv9grsv > 0) ? (int) round($_vvbktnq * min(1, $_vwv2gri / max($_vvbktnq, $_vv9grsv))) : $_vwv2gri;
$_v8razwp[$_vnnyojj] = photo_url($_vnkjxn4, $_vj5naki) . ' ' . $_vnnyojj . 'w';
if ($_vj5naki === $_vrsl1mg) {
break;
}
}
ksort($_v8razwp);
return implode(', ', array_unique($_v8razwp));
}
}
if (!function_exists('random_key')) {
function random_key($_vtvqs6v = 16)
{
return bin2hex(random_bytes($_vtvqs6v));
}
}
if (!function_exists('client_ip')) {





function client_ip()
{
// Sau Caddy (deploy/caddy/routes.caddy): Caddy đã xác định IP thật của khách và truyền qua biến FastCGI CLIENT_IP.
if (!empty($_SERVER['CLIENT_IP']) && filter_var($_SERVER['CLIENT_IP'], FILTER_VALIDATE_IP)) {
return (string) $_SERVER['CLIENT_IP'];
}
$_vq24j76 = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
$_v4zqc0a = in_array($_vq24j76, array('127.0.0.1', '::1'), TRUE);
if ($_v4zqc0a && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])
&& filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
return (string) $_SERVER['HTTP_CF_CONNECTING_IP'];
}
return $_vq24j76;
}
}
if (!function_exists('json_out')) {
function json_out($data, $_vdr9w0h = 200)
{
$CI =& get_instance();
$CI->output
->set_status_header($_vdr9w0h)
->set_content_type('application/json', 'utf-8')
->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
}
if (!function_exists('now_str')) {
function now_str()
{
return date('Y-m-d H:i:s');
}
}
if (!function_exists('human_size')) {
function human_size($_vpfd2kh)
{
$_vpfd2kh = (float) $_vpfd2kh;
$_vlymlgs = array('B', 'KB', 'MB', 'GB', 'TB');
$_v8102nb = 0;
while ($_vpfd2kh >= 1024 && $_v8102nb < count($_vlymlgs) - 1) {
$_vpfd2kh /= 1024;
$_v8102nb++;
}
$_v5dvtum = function_exists('dec_point') ? dec_point() : ','; 
return ($_v8102nb === 0 ? (int) $_vpfd2kh : number_format($_vpfd2kh, 1, $_v5dvtum, $_v5dvtum === ',' ? '.' : ',')) . ' ' . $_vlymlgs[$_v8102nb];
}
}
if (!function_exists('dec_point')) {

function dec_point($_vb1bhe7 = NULL)
{
$_vbq9cis = $_vb1bhe7 ?: (function_exists('lang_cur') ? lang_cur() : 'vi');
return ($_vbq9cis === 'vi' || $_vbq9cis === 'fr') ? ',' : '.';
}
}
if (!function_exists('lang_weekday')) {

function lang_weekday($_v3mcka8, $_voeymu8 = FALSE, $_ve52upy = NULL)
{
$_ve52upy = $_ve52upy ?: lang_cur();
$_v3mcka8 = ((int) $_v3mcka8 % 7 + 7) % 7;
if ($_ve52upy === 'en') {
return $_voeymu8 ? array('Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa')[$_v3mcka8]
: array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')[$_v3mcka8];
}
$_v6e8e4z = $_voeymu8 ? array('CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7')
: array('Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy');
return __l($_ve52upy, $_v6e8e4z[$_v3mcka8]);
}
}
if (!function_exists('lang_month')) {

function lang_month($_vtp6sqf, $_vmk5on1 = NULL)
{
$_vmk5on1 = $_vmk5on1 ?: lang_cur();
$_vtp6sqf = max(1, min(12, (int) $_vtp6sqf));
if ($_vmk5on1 === 'en') {
return date('F', mktime(0, 0, 0, $_vtp6sqf, 1, 2026));
}
return __l($_vmk5on1, 'Tháng ' . $_vtp6sqf);
}
}
if (!function_exists('vn_date')) {







function vn_date($_vzmsukl, $_vxqwa0h = TRUE, $_veeuve3 = NULL)
{
$_vafc3ea = strtotime((string) $_vzmsukl);
if (!$_vafc3ea) {
return '';
}
$_veeuve3 = $_veeuve3 ?: lang_cur();
if ($_veeuve3 === 'en') {
return date($_vxqwa0h ? 'l, F j, Y' : 'F j, Y', $_vafc3ea);
}
$_vt8icqj = $_veeuve3 === 'vi'; 
$_vbb9qsk = array('thu' => lang_weekday(date('w', $_vafc3ea), FALSE, $_veeuve3), 'd' => date($_vt8icqj ? 'd' : 'j', $_vafc3ea), 'm' => date($_vt8icqj ? 'm' : 'n', $_vafc3ea),
'thang' => lang_month(date('n', $_vafc3ea), $_veeuve3), 'y' => date('Y', $_vafc3ea), 'y_be' => (int) date('Y', $_vafc3ea) + 543);
return __l($_veeuve3, $_vxqwa0h ? '{thu}, {d}/{m}/{y}' : '{d}/{m}/{y}', $_vbb9qsk);
}
}
if (!function_exists('fmt_time')) {

function fmt_time($_v5ubq3o, $_vq9817n = NULL)
{
$_v5ubq3o = trim((string) $_v5ubq3o);
if (($_vq9817n ?: lang_cur()) !== 'en' || !preg_match('/^(\d{1,2}):(\d{2})$/', $_v5ubq3o, $_vrrwwsy) || (int) $_vrrwwsy[1] > 23) {
return $_v5ubq3o;
}
$_vh246w0 = (int) $_vrrwwsy[1];
return (($_vh246w0 % 12) ?: 12) . ':' . $_vrrwwsy[2] . ($_vh246w0 < 12 ? ' AM' : ' PM');
}
}
if (!function_exists('fmt_dmy')) {

function fmt_dmy($_vfiqssj, $_vj9qlys = '.')
{
$_vtkuvvu = strtotime((string) $_vfiqssj);
if (!$_vtkuvvu) {
return '';
}
$_v6nan1c = lang_cur();
$_vhk796z = $_v6nan1c === 'en' ? array('m', 'd', 'Y') : ($_v6nan1c === 'zh' ? array('Y', 'm', 'd') : array('d', 'm', 'Y'));
return date(implode($_vj9qlys, $_vhk796z), $_vtkuvvu);
}
}
if (!function_exists('lang_content')) {





function lang_content()
{
$_vg23oec = lang_site();
if (lang_area() === 'public' && in_array(lang_cur(), $_vg23oec, TRUE)) {
return lang_cur();
}
return $_vg23oec[0];
}
}
if (!function_exists('__c')) {

function __c($_v1a39jm, array $_v50564s = array())
{
return __l(lang_content(), $_v1a39jm, $_v50564s);
}
}
if (!function_exists('__n')) {




function __n($_v0abow2, $_vary89r, array $_v8r2z3q = array())
{
$_v8r2z3q['n'] = $_vary89r;
if (lang_cur() !== 'vi' && (int) $_vary89r === 1) {
$_v98spe5 = lang_map();
if (!empty($_v98spe5[$_v0abow2 . '|1'])) {
return __($_v98spe5[$_v0abow2 . '|1'], $_v8r2z3q);
}
}
return __($_v0abow2, $_v8r2z3q);
}
}
if (!function_exists('lang_switch_url')) {

function lang_switch_url($_vyqu7v4)
{
$_vb3sds4 = $_GET;
unset($_vb3sds4['lang']);
$_vb3sds4['lang'] = $_vyqu7v4;
return base_url(uri_string()) . '?' . http_build_query($_vb3sds4);
}
}
if (!function_exists('lang_switch_html')) {





function lang_switch_html($_vzedm3x = FALSE)
{
$_vvlsvhj = lang_site();
if ($_vzedm3x || count($_vvlsvhj) < 2) {
return '';
}
$_v9rlpsy = lang_all();
$_vhoilfr = lang_cur();
$_vzyp12m = '';
foreach ($_vvlsvhj as $_vzjemtz) {
$_vzyp12m .= '<a href="' . e(lang_switch_url($_vzjemtz)) . '" hreflang="' . $_vzjemtz . '" lang="' . $_vzjemtz . '" title="' . e($_v9rlpsy[$_vzjemtz][0]) . '"'
. ($_vzjemtz === $_vhoilfr ? ' aria-current="true"' : '') . '>' . e(count($_vvlsvhj) > 2 ? $_v9rlpsy[$_vzjemtz][0] : $_v9rlpsy[$_vzjemtz][1]) . '</a>';
}
if (count($_vvlsvhj) > 2) {
return '<details class="lang-sw lang-many"><summary aria-label="' . e(__('Ngôn ngữ')) . '" title="' . e($_v9rlpsy[$_vhoilfr][0]) . '">'
. '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8">'
. '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>'
. '<span lang="' . $_vhoilfr . '">' . e($_v9rlpsy[$_vhoilfr][1]) . '</span></summary><nav aria-label="' . e(__('Ngôn ngữ')) . '">' . $_vzyp12m . '</nav></details>';
}
return '<nav class="lang-sw" aria-label="' . e(__('Ngôn ngữ')) . '">' . $_vzyp12m . '</nav>';
}
}
if (!function_exists('ascii_slug')) {

function ascii_slug($_vfxcawy, $_veec3qi = 60)
{
$_v4wm21a = array(
'a' => 'àáạảãâầấậẩẫăằắặẳẵ', 'e' => 'èéẹẻẽêềếệểễ', 'i' => 'ìíịỉĩ',
'o' => 'òóọỏõôồốộổỗơờớợởỡ', 'u' => 'ùúụủũưừứựửữ', 'y' => 'ỳýỵỷỹ', 'd' => 'đ',
);
$_vfxcawy = mb_strtolower((string) $_vfxcawy, 'UTF-8');
foreach ($_v4wm21a as $_vywiliu => $_vs8xdij) {
$_vfxcawy = preg_replace('/[' . $_vs8xdij . ']/u', $_vywiliu, $_vfxcawy);
}
$_vfxcawy = trim(preg_replace('/[^a-z0-9]+/', '-', $_vfxcawy), '-');
return trim(substr($_vfxcawy, 0, $_veec3qi), '-');
}
}
if (!function_exists('slugify')) {
function slugify($_v6dg7tu)
{
$_v6dg7tu = ascii_slug($_v6dg7tu, 60);
return $_v6dg7tu !== '' ? $_v6dg7tu : 'album';
}
}
if (!function_exists('flash')) {

function flash($_vzc6gts = NULL, $_vc0orrw = NULL)
{
$CI =& get_instance();
if ($_vzc6gts !== NULL) {
$CI->session->set_flashdata('ac_flash', array('type' => $_vzc6gts, 'message' => $_vc0orrw));
return NULL;
}
$_vf6g6du = $CI->session->flashdata('ac_flash');


if ($_vf6g6du !== NULL) {
$CI->session->unset_userdata('ac_flash');
}
return is_array($_vf6g6du) ? $_vf6g6du : NULL;
}
}
if (!function_exists('share_invite_text')) {

function share_invite_text($_v3el5ur, $_vp8u1a4 = '')
{
$_vnk643c = $_vp8u1a4 !== '' && strtotime($_vp8u1a4) ? vn_date($_vp8u1a4, FALSE, lang_content()) : '';
return $_vnk643c !== '' ? __c('Trân trọng mời bạn đến chung vui trong ngày cưới của {cap_doi} ({ngay}). Thiệp cưới của chúng mình:', array('cap_doi' => $_v3el5ur, 'ngay' => $_vnk643c))
: __c('Trân trọng mời bạn đến chung vui trong ngày cưới của {cap_doi}. Thiệp cưới của chúng mình:', array('cap_doi' => $_v3el5ur));
}
}
if (!function_exists('csrf_field')) {
function csrf_field()
{
$CI =& get_instance();
return '<input type="hidden" name="' . e($CI->security->get_csrf_token_name())
. '" value="' . e($CI->security->get_csrf_hash()) . '">';
}
}
if (!function_exists('hosted')) {





function hosted()
{
static $_vzoq9xc = NULL;
if ($_vzoq9xc === NULL) {
$_vzoq9xc = getenv('ANHCUOI_HOSTED') === '1';
}
return $_vzoq9xc;
}
}
if (!function_exists('vip_labels')) {






function vip_labels()
{
static $_vp4rjt1 = NULL;
if ($_vp4rjt1 === NULL) {


$_vp4rjt1 = TRUE;
if (hosted()) {
$_v5mrcp7 = is_file(FCPATH . '.platform') ? json_decode((string) @file_get_contents(FCPATH . '.platform'), TRUE) : NULL;
$_vp4rjt1 = is_array($_v5mrcp7) && isset($_v5mrcp7['monetize']) && $_v5mrcp7['monetize'] === TRUE;
}
}
return $_vp4rjt1;
}
}
if (!function_exists('hosted_url')) {

function hosted_url()
{
$CI =& get_instance();
$_vgm9jpu = strtolower((string) $CI->config->item('hosted_domain'));
$_v9jx6k9 = strtolower(preg_replace('~:\d+$~', '', isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : ''));
if ($_vgm9jpu !== '' && preg_match('~^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.' . preg_quote($_vgm9jpu, '~') . '$~', $_v9jx6k9)) {
return 'https://' . $_v9jx6k9 . '/';
}
return base_url();
}
}
if (!function_exists('public_url')) {




function public_url()
{
if (hosted()) {
return hosted_url();
}
$_vtumq85 = tunnel_config();
if ($_vtumq85['mode'] === 'token' && $_vtumq85['hostname'] !== '') {
return 'https://' . $_vtumq85['hostname'] . '/';
}
if ($_vtumq85['mode'] === 'quick') {
$CI =& get_instance();
$CI->load->library('tunnelrunner');
$_vnybpvl = $CI->tunnelrunner->quick_url();
if (!$_vnybpvl) { 
$_vt88j51 = FCPATH . 'database/.public_url';
$_vnybpvl = is_file($_vt88j51) ? trim((string) @file_get_contents($_vt88j51)) : '';
}
if (preg_match('~^https://[a-z0-9-]+\.trycloudflare\.com$~', (string) $_vnybpvl)) {
return $_vnybpvl . '/';
}
}
return base_url();
}
}
if (!function_exists('app_port')) {

function app_port()
{
$_vjuruvh = (int) @file_get_contents(FCPATH . 'database/.app_port');
if ($_vjuruvh > 0 && $_vjuruvh < 65536) {
return $_vjuruvh;
}
$_vhgd461 = isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 0;
return ($_vhgd461 > 0 && !in_array($_vhgd461, array(80, 443), TRUE)) ? $_vhgd461 : 8686;
}
}
if (!function_exists('via_tunnel')) {

function via_tunnel()
{
return !empty($_SERVER['HTTP_CF_CONNECTING_IP']) || !empty($_SERVER['HTTP_CF_RAY']);
}
}
if (!function_exists('tunnel_config')) {




function tunnel_config()
{


$_vvf0h7l = array('mode' => 'quick', 'token' => '', 'hostname' => '', 'auto' => TRUE);
$_vdjbghq = FCPATH . 'cloudflared/tunnel.json';
if (!is_file($_vdjbghq)) {
return $_vvf0h7l;
}
$_vvf0h7l['mode'] = 'off';
$_vvf0h7l['auto'] = FALSE;
$_v1cauj4 = json_decode((string) @file_get_contents($_vdjbghq), TRUE);
if (!is_array($_v1cauj4)) {
return $_vvf0h7l;
}
if (isset($_v1cauj4['mode']) && in_array($_v1cauj4['mode'], array('off', 'quick', 'token'), TRUE)) {
$_vvf0h7l['mode'] = $_v1cauj4['mode'];
}
$_vvf0h7l['token'] = isset($_v1cauj4['token']) ? (string) $_v1cauj4['token'] : '';
$_vvf0h7l['hostname'] = isset($_v1cauj4['hostname']) ? (string) $_v1cauj4['hostname'] : '';

$_vvf0h7l['auto'] = array_key_exists('auto', $_v1cauj4) ? !empty($_v1cauj4['auto']) : $_vvf0h7l['mode'] === 'quick';
return $_vvf0h7l;
}
}
if (!function_exists('cloud_identity')) {

function cloud_identity()
{
$_vyvikg9 = FCPATH . 'cloudflared/cloud.json';
$_vsldj4u = is_file($_vyvikg9) ? json_decode((string) @file_get_contents($_vyvikg9), TRUE) : NULL;
return (is_array($_vsldj4u) && !empty($_vsldj4u['device_secret'])) ? $_vsldj4u : NULL;
}
function save_cloud_identity(array $data)
{
$_vjwpujv = FCPATH . 'cloudflared/cloud.json';
$_vs6bzsk = cloud_identity() ?: array();
$_vnzg9zw = @file_put_contents($_vjwpujv, json_encode($data + $_vs6bzsk, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX) !== FALSE;
if ($_vnzg9zw) {
@chmod($_vjwpujv, 0600);
}
return $_vnzg9zw;
}
}
if (!function_exists('save_tunnel_config')) {
function save_tunnel_config($_vw4pb2e, $_v1tvtip = '', $_vnwwak8 = '', $_vm7ulr0 = FALSE)
{
$_v48lzdr = FCPATH . 'cloudflared';
if (!is_dir($_v48lzdr)) {
@mkdir($_v48lzdr, 0700, TRUE);
}
$_vmuovz1 = $_v48lzdr . '/tunnel.json';
$_vs49g9x = @file_put_contents($_vmuovz1, json_encode(array(
'mode' => $_vw4pb2e, 'token' => $_v1tvtip, 'hostname' => $_vnwwak8, 'auto' => (bool) $_vm7ulr0,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) !== FALSE;
if ($_vs49g9x) {
@chmod($_vmuovz1, 0600);
}
return $_vs49g9x;
}
}





if (!function_exists('ac_vi_case_map')) {

function ac_vi_case_map()
{
static $_vb53mxi = NULL;
if ($_vb53mxi === NULL) {
$_vgnhqpj = preg_split('//u', 'àáảãạăằắẳẵặâầấẩẫậèéẻẽẹêềếểễệìíỉĩịòóỏõọôồốổỗộơờớởỡợùúủũụưừứửữựỳýỷỹỵđ', -1, PREG_SPLIT_NO_EMPTY);
$_vs4u2yw = preg_split('//u', 'ÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬÈÉẺẼẸÊỀẾỂỄỆÌÍỈĨỊÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢÙÚỦŨỤƯỪỨỬỮỰỲÝỶỸỴĐ', -1, PREG_SPLIT_NO_EMPTY);
$_vb53mxi = array(array_combine($_vs4u2yw, $_vgnhqpj), array_combine($_vgnhqpj, $_vs4u2yw));
}
return $_vb53mxi;
}
}
if (!function_exists('mb_strtolower')) {
function mb_strtolower($_vsku375, $_vrs3jq8 = NULL)
{
$_v94yy71 = ac_vi_case_map();
return strtr(strtolower((string) $_vsku375), $_v94yy71[0]);
}
}
if (!function_exists('mb_strtoupper')) {
function mb_strtoupper($_vqh36xu, $_vlxga7j = NULL)
{
$_vlnza9q = ac_vi_case_map();
return strtr(strtoupper((string) $_vqh36xu), $_vlnza9q[1]);
}
}
if (!function_exists('mb_strimwidth')) {

function mb_strimwidth($_veqe8e7, $_v2217wr, $_vtj0lzv, $_vom67ia = '', $_vshcq60 = NULL)
{
$_v21enpm = preg_split('//u', (string) $_veqe8e7, -1, PREG_SPLIT_NO_EMPTY);
$_v21enpm = array_slice(is_array($_v21enpm) ? $_v21enpm : array(), (int) $_v2217wr);
if (count($_v21enpm) <= $_vtj0lzv) {
return implode('', $_v21enpm);
}
$_vngtla0 = preg_split('//u', (string) $_vom67ia, -1, PREG_SPLIT_NO_EMPTY);
$_vd37lk5 = max(0, (int) $_vtj0lzv - count(is_array($_vngtla0) ? $_vngtla0 : array()));
return implode('', array_slice($_v21enpm, 0, $_vd37lk5)) . $_vom67ia;
}
}