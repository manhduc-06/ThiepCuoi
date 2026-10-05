<?php

defined('BASEPATH') OR exit('No direct script access allowed');

















if (!function_exists('lang_all')) {

function lang_all()
{
return array(
'vi' => array('Tiếng Việt', 'VI', 'Tiếng Việt'),
'en' => array('English', 'EN', 'Tiếng Anh'),
'zh' => array('中文', '中文', 'Tiếng Trung'),
'lo' => array('ລາວ', 'ລາວ', 'Tiếng Lào'),
'th' => array('ไทย', 'ไทย', 'Tiếng Thái'),
'fr' => array('Français', 'FR', 'Tiếng Pháp'),
);
}
}
if (!function_exists('locale_lang_name_en')) {

function locale_lang_name_en($_v98y565)
{
$_vem7w8p = array('vi' => 'Vietnamese', 'en' => 'English', 'zh' => 'Chinese', 'lo' => 'Lao', 'th' => 'Thai', 'fr' => 'French');
return isset($_vem7w8p[$_v98y565]) ? $_vem7w8p[$_v98y565] : $_v98y565;
}
}
if (!function_exists('lang_admin_all')) {

function lang_admin_all()
{
return array('vi', 'en');
}
}
if (!function_exists('lang_cur')) {

function lang_cur($_v6zp89g = NULL)
{
static $_vskg768 = 'vi';
if ($_v6zp89g !== NULL && isset(lang_all()[$_v6zp89g])) {
$_vskg768 = $_v6zp89g;
}
return $_vskg768;
}
}
if (!function_exists('lang_admin')) {

function lang_admin($_v93096l = NULL)
{
static $_vpk25tt = 'vi';
if ($_v93096l === 'vi' || $_v93096l === 'en') {
$_vpk25tt = $_v93096l;
}
return $_vpk25tt;
}
}
if (!function_exists('lang_area')) {

function lang_area($_vk7jqdf = NULL)
{
static $_vlk9itu = 'admin';
if ($_vk7jqdf === 'public' || $_vk7jqdf === 'admin') {
$_vlk9itu = $_vk7jqdf;
}
return $_vlk9itu;
}
}
if (!function_exists('lang_pick')) {

function lang_pick($_vay8k9q, $_vegltes = NULL)
{
$_vay8k9q = strtolower(trim((string) $_vay8k9q));
if ($_vegltes === NULL) {
$_vegltes = array_keys(lang_all());
}
return in_array($_vay8k9q, $_vegltes, TRUE) ? $_vay8k9q : NULL;
}
}
if (!function_exists('lang_site_list')) {




function lang_site_list($_vvl50zk, $_vqoc33b = '')
{
$_vz1owvm = array_keys(lang_all());
$_vvl50zk = strtolower(trim((string) $_vvl50zk));
$_va1gkiv = in_array($_vvl50zk, $_vz1owvm, TRUE) ? $_vvl50zk : 'vi';
$_vo5tli8 = array();
foreach (explode(',', (string) $_vqoc33b) as $_vxx2mbb) {
$_vxx2mbb = strtolower(trim($_vxx2mbb));
if (in_array($_vxx2mbb, $_vz1owvm, TRUE) && !in_array($_vxx2mbb, $_vo5tli8, TRUE)) {
$_vo5tli8[] = $_vxx2mbb;
}
}
if (!$_vo5tli8) {
$_vo5tli8 = $_vvl50zk === 'both' ? array('vi', 'en') : array($_va1gkiv);
}
if (!in_array($_va1gkiv, $_vo5tli8, TRUE)) {
array_unshift($_vo5tli8, $_va1gkiv); 
}

$_v2g89sm = array($_va1gkiv);
foreach ($_vz1owvm as $_vxx2mbb) {
if ($_vxx2mbb !== $_va1gkiv && in_array($_vxx2mbb, $_vo5tli8, TRUE)) {
$_v2g89sm[] = $_vxx2mbb;
}
}
return $_v2g89sm;
}
}
if (!function_exists('lang_site')) {

function lang_site($_v1kb8u5 = NULL)
{
static $_vqi54ik = array('vi');
if (is_array($_v1kb8u5) && $_v1kb8u5) {
$_vqi54ik = array_values($_v1kb8u5);
}
return $_vqi54ik;
}
}
if (!function_exists('lang_init')) {




function lang_init($_vtoebj8, $_veldvmr, $_voqlm5e, $_v8ttrwv = '')
{
$_vfjia3w = get_instance();
lang_area($_vtoebj8);
$_v9qojuv = lang_site_list($_veldvmr, $_v8ttrwv);
lang_site($_v9qojuv);
if ($_vtoebj8 === 'admin') {
$_vlwqrfa = lang_pick($_vfjia3w->input->get('lang'), lang_admin_all());
if ($_vlwqrfa !== NULL) {
lang_cookie('ac_admin_lang', $_vlwqrfa);
}
$_v91hi5j = $_vlwqrfa !== NULL ? $_vlwqrfa : lang_pick($_vfjia3w->input->cookie('ac_admin_lang'), lang_admin_all());
return lang_cur(lang_admin($_v91hi5j !== NULL ? $_v91hi5j : (lang_pick($_voqlm5e, lang_admin_all()) ?: 'vi')));
}
if (count($_v9qojuv) === 1) {
return lang_cur($_v9qojuv[0]);
}

$_vlwqrfa = lang_pick($_vfjia3w->input->get('lang'), $_v9qojuv);
if ($_vlwqrfa !== NULL) {
lang_cookie('ac_lang', $_vlwqrfa);
}
$_v91hi5j = $_vlwqrfa !== NULL ? $_vlwqrfa : lang_pick($_vfjia3w->input->cookie('ac_lang'), $_v9qojuv);
if ($_v91hi5j === NULL) {
$_v91hi5j = lang_from_accept((string) $_vfjia3w->input->server('HTTP_ACCEPT_LANGUAGE'), $_v9qojuv);
}
return lang_cur($_v91hi5j !== NULL ? $_v91hi5j : $_v9qojuv[0]);
}
}
if (!function_exists('lang_from_accept')) {

function lang_from_accept($_vsxoz4e, array $_vdodkbl)
{
$_vsxoz4e = strtolower(trim($_vsxoz4e));
if ($_vsxoz4e === '') {
return NULL;
}
foreach (explode(',', $_vsxoz4e) as $_v6w43ea) {
$_vsmyhpu = substr(trim(strtok($_v6w43ea, ';')), 0, 2);
if ($_vsmyhpu !== '' && in_array($_vsmyhpu, $_vdodkbl, TRUE)) {
return $_vsmyhpu;
}
}
return NULL;
}
}
if (!function_exists('lang_cookie')) {
function lang_cookie($_v791be7, $_vajjpzq)
{
$_v3oa086 = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
setcookie($_v791be7, $_vajjpzq, array('expires' => time() + 31536000, 'path' => '/', 'secure' => $_v3oa086, 'httponly' => FALSE, 'samesite' => 'Lax'));
}
}
if (!function_exists('lang_map')) {




function lang_map($_v2uk38h = 'all', $_vcndis4 = NULL)
{
static $_v0nj7rr = array();
$_vcndis4 = $_vcndis4 === NULL ? lang_cur() : $_vcndis4;
if ($_vcndis4 === 'vi' || !isset(lang_all()[$_vcndis4])) {
return array();
}
$_vocf5z3 = $_vcndis4 . ':' . $_v2uk38h;
if (!isset($_v0nj7rr[$_vocf5z3])) {
$_vr8e38h = array();
$_v19v548 = APPPATH . 'language/' . $_vcndis4 . '/';
$_vmftsuz = array('js' => 'ui_js_*.php', 'js_public' => 'ui_js_public*.php');
$_v7tcsmr = (array) glob($_v19v548 . (isset($_vmftsuz[$_v2uk38h]) ? $_vmftsuz[$_v2uk38h] : 'ui_*.php'));
foreach ($_v7tcsmr as $_vrifqhp) {
if (is_file($_vrifqhp)) {
$_vihcw08 = include $_vrifqhp;
if (is_array($_vihcw08)) {
$_vr8e38h = array_merge($_vr8e38h, $_vihcw08);
}
}
}
$_v0nj7rr[$_vocf5z3] = $_vr8e38h;
}
return $_v0nj7rr[$_vocf5z3];
}
}
if (!function_exists('__l')) {

function __l($_vxfephi, $_vjgonci, array $_vfar6au = array())
{
$_v7ey7pv = (string) $_vjgonci;
if ($_vxfephi !== 'vi') {
$_vz1apow = lang_map('all', $_vxfephi);
if (isset($_vz1apow[$_v7ey7pv]) && $_vz1apow[$_v7ey7pv] !== '') {
$_v7ey7pv = $_vz1apow[$_v7ey7pv];
}
}
if ($_vfar6au) {
$_voomoc7 = array();
foreach ($_vfar6au as $_v2kejx7 => $_vri1pl4) {
$_voomoc7['{' . $_v2kejx7 . '}'] = (string) $_vri1pl4;
}
$_v7ey7pv = strtr($_v7ey7pv, $_voomoc7);
}
return $_v7ey7pv;
}
}
if (!function_exists('__')) {

function __($_vdszd9u, array $_vjdb9l0 = array())
{
return __l(lang_cur(), $_vdszd9u, $_vjdb9l0);
}
}
if (!function_exists('i18n_script')) {

function i18n_script()
{
$_vf5aroz = lang_cur() === 'vi' ? array() : lang_map(lang_area() === 'public' ? 'js_public' : 'js');
$_vyu943n = json_encode((object) $_vf5aroz, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
return '<script>window.AC_LANG=' . json_encode(lang_cur()) . ';window.AC_I18N=' . $_vyu943n
. ';window.__=function(s,v){var t=window.AC_I18N[s]||s;if(v){for(var k in v){t=t.split("{"+k+"}").join(v[k]);}}return t;};</script>';
}
}