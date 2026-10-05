<?php

defined('BASEPATH') OR exit('No direct script access allowed');




if (!function_exists('ed_text')) {





function ed_text($_vvbfffo, $_vgdqaf1, $_vl8t46h = 'span', $_v5xmt8w = '', $_vjlgd3x = '')
{
$_v9ie5lv = $_vvbfffo->text($_vgdqaf1);
$_v3p7g3u = $_vvbfffo->is_multiline($_vgdqaf1);
$_vyksgk0 = $_vvbfffo->registry('content_text');
$_vo2m6y4 = isset($_vyksgk0[$_vgdqaf1]) ? (int) $_vyksgk0[$_vgdqaf1][1] : 0;
return '<' . $_vl8t46h . ($_v5xmt8w !== '' ? ' class="' . e($_v5xmt8w) . '"' : '') . ' data-edit="' . e($_vgdqaf1) . '"'
. ($_vjlgd3x !== '' ? ' data-ph="' . e($_vjlgd3x) . '"' : '')
. ($_vo2m6y4 ? ' data-max="' . $_vo2m6y4 . '"' : '')
. ($_v3p7g3u ? ' data-multiline' : '') . '>' . ($_v3p7g3u ? str_replace("\n", '<br>', e($_v9ie5lv)) : e($_v9ie5lv)) . '</' . $_vl8t46h . '>';
}
}
if (!function_exists('ed_pos_style')) {

function ed_pos_style($_vin43zw, $_vthardq, $_vukfmdz)
{
$_v0rg1f1 = 'object-position:' . $_vin43zw . '% ' . $_vthardq . '%';
if ($_vukfmdz > 1.001) {
$_v0rg1f1 .= ';transform:scale(' . $_vukfmdz . ');transform-origin:' . $_vin43zw . '% ' . $_vthardq . '%';
}
return $_v0rg1f1;
}
}
if (!function_exists('ed_img')) {




function ed_img($_vcu7vmo, $_vjua9eu, $_v0rqu8e = '', $_v1ud7n4 = 'm', $_vk0v1jf = '')
{
$CI =& get_instance();
list($_vkl8lho, $_v761c0n, $_vo6u0h6) = $CI->content_model->image_pos($_vjua9eu);
$_vx7o81a = '<figure class="slot ' . e($_v0rqu8e) . ($_vcu7vmo ? '' : ' slot-empty') . '" data-edit-img="' . e($_vjua9eu) . '"'
. ' data-pos="' . e($_vkl8lho . ' ' . $_v761c0n . ' ' . $_vo6u0h6) . '"'
. ($_vk0v1jf !== '' ? ' data-label="' . e($_vk0v1jf) . '"' : '') . '>';
if ($_vcu7vmo) {

$_vb43lns = $_vjua9eu === 'img.hero_main';
$_vx7o81a .= '<img src="' . photo_url($_vcu7vmo, $_v1ud7n4) . '" srcset="' . e(photo_srcset($_vcu7vmo, $_v1ud7n4)) . '"'
. ' sizes="' . e(ed_img_sizes($_vjua9eu, $_vo6u0h6)) . '" alt=""'
. ($_vb43lns ? ' fetchpriority="high"' : ' loading="lazy"') . ' decoding="async"'
. ' style="' . e(ed_pos_style($_vkl8lho, $_v761c0n, $_vo6u0h6)) . '">';
} else {
$_vx7o81a .= '<span class="slot-ph" aria-hidden="true">♡</span>';
}
return $_vx7o81a . '</figure>';
}
}
if (!function_exists('ed_img_sizes')) {




function ed_img_sizes($_vh3m1ly, $_vhuzkie = 1, $_vvev38s = NULL)
{
$_vvxblg9 = max(1, min(3, (float) $_vhuzkie));


if ($_vh3m1ly === 'img.hero_main') {
$CI =& get_instance();
if ($_vvev38s === NULL) {
$_vvev38s = (string) $CI->load->get_var('theme');
if ($_vvev38s === '' && isset($CI->content_model)) {
$_vvev38s = (string) $CI->content_model->get('theme');
}
}
$_v1lh18u = (array) $CI->config->item('themes', 'content') ?: (array) $CI->config->item('themes');
if (isset($_v1lh18u[$_vvev38s]['hero_sizes'])) {
$_vvqw9o3 = (string) $_v1lh18u[$_vvev38s]['hero_sizes'];
return $_vvxblg9 > 1.001 ? preg_replace_callback('/(\d+(?:\.\d+)?)(vw|px)/', function ($_vip0k7y) use ($_vvxblg9) {
return (int) round((float) $_vip0k7y[1] * $_vvxblg9) . $_vip0k7y[2];
}, $_vvqw9o3) : $_vvqw9o3;
}
}
$_v70l81u = array(
'img.hero_main' => array(100, 50),
'img.hero_left' => array(50, 25),
'img.hero_right' => array(50, 25),
'img.bride' => array(75, 30),
'img.groom' => array(75, 30),
'img.event' => array(100, 45),
'img.quote' => array(100, 100),
);
list($_vdt0aex, $_vmtxfm2) = isset($_v70l81u[$_vh3m1ly]) ? $_v70l81u[$_vh3m1ly] : array(100, 50);
return '(max-width: 760px) ' . min(300, (int) round($_vdt0aex * $_vvxblg9)) . 'vw, ' . min(200, (int) round($_vmtxfm2 * $_vvxblg9)) . 'vw';
}
}