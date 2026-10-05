<?php

defined('BASEPATH') OR exit('No direct script access allowed');







class Invitee
{
const COOKIE_TTL = 31536000; 
const WEB_COOKIE = 'ac_rsvp_web';

private static $tiers = array(
'ông' => 'kinh', 'bà' => 'kinh', 'bác' => 'kinh', 'cô' => 'kinh', 'chú' => 'kinh', 'dì' => 'kinh', 'cậu' => 'kinh',
'mợ' => 'kinh', 'thím' => 'kinh', 'thầy' => 'kinh', 'gia' => 'kinh', 'cụ' => 'kinh', 'dượng' => 'kinh', 'hai' => 'kinh',
'quý' => 'kinh', 'nội' => 'kinh', 'ngoại' => 'kinh',
'anh' => 'anhchi', 'chị' => 'anhchi',
'bạn' => 'ngang', 'em' => 'ngang', 'các' => 'ngang', 'cháu' => 'ngang', 'con' => 'ngang',
);
private static $bots = '~facebookexternalhit|facebookcatalog|telegrambot|whatsapp|twitterbot|slackbot|discordbot'
. '|linkedinbot|googlebot|bingbot|skypeuripreview|embedly|pinterestbot|redditbot|applebot|yandex|baiduspider'
. '|crawler|spider~i';




public function voice($salutation)
{
$sal = trim(preg_replace('/\s+/u', ' ', (string) $salutation));

if ($sal === '' || lang_cur() !== 'vi') {
return array(
'tier' => 'neutral',
'ask' => __('Sẽ đến chung vui cùng cô dâu chú rể chứ?'),
'form_yes' => __('Tuyệt quá! Sẽ có mấy người đến?'),
'form_yes1' => __('Tuyệt quá! Hẹn gặp tại tiệc cưới.'),
'form_no' => __('Tiếc quá! Gửi đôi lời chúc nhé?'),
'count' => __('Tổng số người sẽ đến'),
'done_yes' => __('Đã xác nhận tham dự ♡'),
'done_no' => __('Đã báo không thể đến'),
'thanks_yes' => __('Cảm ơn đã báo tin! Hẹn gặp tại tiệc cưới ♡'),
'thanks_no' => __('Cảm ơn đã báo tin. Hẹn gặp dịp khác nhé!'),
'msg_label' => __('Lời chúc gửi cô dâu chú rể'),
'name_label' => __('Tên của bạn'),
'wish_thanks' => __('Cảm ơn lời chúc của bạn!'),
'wish_thanks_pending' => __('Cảm ơn bạn! Lời chúc sẽ hiện sau khi cô dâu chú rể xem.'),
'gift_thanks' => __('Cảm ơn tấm lòng của bạn ♡'),
);
}
$first = mb_strtolower((string) strtok($sal, ' '));
$tier = isset(self::$tiers[$first]) ? self::$tiers[$first] : 'la';
$we = $tier === 'kinh' ? 'chúng cháu' : ($tier === 'anhchi' ? 'chúng em' : 'chúng mình');
$polite = $tier === 'kinh' || $tier === 'anhchi';
$a = $polite ? ' ạ' : '';
$you = mb_strtolower(mb_substr($sal, 0, 1)) . mb_substr($sal, 1); 
$You = mb_strtoupper(mb_substr($sal, 0, 1)) . mb_substr($sal, 1); 
return array(
'tier' => $tier,
'ask' => $You . ' sẽ đến chung vui cùng ' . $we . ' chứ' . $a . '?',
'form_yes' => 'Tuyệt quá! ' . $You . ' đi mấy người' . $a . '?',
'form_yes1' => 'Tuyệt quá! Hẹn gặp ' . $you . ' tại tiệc cưới' . $a . '.',
'form_no' => 'Tiếc quá! ' . $You . ' có lời nhắn gì cho ' . $we . ' không' . $a . '?',
'count' => 'Số người đến (kể cả ' . $you . ')',
'done_yes' => $You . ' sẽ tham dự ♡',
'done_no' => $You . ' đã báo không thể đến',
'thanks_yes' => 'Cảm ơn ' . $you . '! ' . mb_strtoupper(mb_substr($we, 0, 1)) . mb_substr($we, 1)
. ' rất vui được đón ' . $you . $a . ' ♡',
'thanks_no' => 'Cảm ơn ' . $you . ' đã báo cho ' . $we . $a . '. Hẹn gặp ' . $you . ' dịp khác' . ($polite ? ' ạ.' : ' nhé!'),
'msg_label' => 'Lời chúc gửi cô dâu chú rể',

'name_label' => 'Tên',
'wish_thanks' => 'Cảm ơn ' . $you . ' đã gửi lời chúc' . $a . ' ♡',
'wish_thanks_pending' => 'Cảm ơn ' . $you . ' đã gửi lời chúc' . $a . '! Lời chúc sẽ hiện sau khi cô dâu chú rể xem.',

'gift_thanks' => 'Cảm ơn tấm lòng của ' . $you . $a . ' ♡',
);
}

public function salutation_of($invite)
{
return is_array($invite) && ($invite['source'] ?? '') === 'invite' ? (string) ($invite['salutation'] ?? '') : '';
}

private function sign($data)
{
return hash_hmac('sha256', 'anhcuoi-rsvp|' . $data, (string) config_item('encryption_key'));
}





private function sign_seen(array $invite)
{
$msg = trim((string) ($invite['message'] ?? ''));
return substr($this->sign('v3|' . (int) $invite['id'] . '|' . $invite['code'] . '|' . hash('sha256', $msg)), 0, 40);
}
private function set($name, $value)
{
$opts = array('expires' => time() + self::COOKIE_TTL, 'path' => '/', 'secure' => (bool) config_item('cookie_secure'),
'httponly' => TRUE, 'samesite' => 'Lax');
if (!headers_sent()) {
setcookie($name, $value, $opts);
}
$_COOKIE[$name] = $value;
}






public function remember(array $invite, $sent_message = TRUE)
{
if ($sent_message) {
$this->set('ac_rsvp_' . (int) $invite['id'], $this->sign_seen($invite));
}
if (($invite['source'] ?? '') === 'web') {
$this->set(self::WEB_COOKIE, $invite['code'] . '.' . substr($this->sign('web|' . $invite['code']), 0, 40));
}
}

public function knows($invite)
{
if (!is_array($invite) || empty($invite['id']) || trim((string) ($invite['message'] ?? '')) === '') {
return FALSE;
}
$c = isset($_COOKIE['ac_rsvp_' . (int) $invite['id']]) ? (string) $_COOKIE['ac_rsvp_' . (int) $invite['id']] : '';
return $c !== '' && hash_equals($this->sign_seen($invite), $c);
}

public function web_code()
{
$c = isset($_COOKIE[self::WEB_COOKIE]) ? (string) $_COOKIE[self::WEB_COOKIE] : '';
if (!preg_match('/^([a-z0-9]{8})\.([a-f0-9]{40})$/', $c, $m)) {
return '';
}
return hash_equals(substr($this->sign('web|' . $m[1]), 0, 40), $m[2]) ? $m[1] : '';
}

const INV_COOKIE = 'ac_inv';
const INV_TTL = 5184000; 

public function remember_invite(array $invite)
{
$v = $invite['code'] . '.' . substr($this->sign('inv|' . $invite['code']), 0, 40);
if (!headers_sent()) {
setcookie(self::INV_COOKIE, $v, array('expires' => time() + self::INV_TTL, 'path' => '/',
'secure' => (bool) config_item('cookie_secure'), 'httponly' => TRUE, 'samesite' => 'Lax'));
}
$_COOKIE[self::INV_COOKIE] = $v;
}

public function invite_code()
{
if (!isset($_COOKIE[self::INV_COOKIE])) {
return '';
}
$c = (string) $_COOKIE[self::INV_COOKIE];
if (preg_match('/^([a-z0-9]{8})\.([a-f0-9]{40})$/', $c, $m) && hash_equals(substr($this->sign('inv|' . $m[1]), 0, 40), $m[2])) {
return $m[1];
}
$this->forget_invite();
return '';
}

public function forget_invite()
{
if (!headers_sent()) {
setcookie(self::INV_COOKIE, '', array('expires' => time() - 3600, 'path' => '/',
'secure' => (bool) config_item('cookie_secure'), 'httponly' => TRUE, 'samesite' => 'Lax'));
}
unset($_COOKIE[self::INV_COOKIE]);
}

public function is_bot($ua = NULL)
{
$ua = $ua === NULL ? (string) ($_SERVER['HTTP_USER_AGENT'] ?? '') : (string) $ua;
if ($ua === '' || preg_match(self::$bots, $ua)) {
return TRUE;
}

return (bool) preg_match('~zalo|viber|skype~i', $ua) && !preg_match('~mobile|android|iphone|ipad~i', $ua);
}
}