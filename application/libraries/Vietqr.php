<?php
 defined('BASEPATH') OR exit('No direct script access allowed');







class Vietqr
{

const BANKS = array(
'970436' => 'Vietcombank',
'970415' => 'VietinBank',
'970418' => 'BIDV',
'970405' => 'Agribank',
'970407' => 'Techcombank',
'970422' => 'MB Bank',
'970416' => 'ACB',
'970432' => 'VPBank',
'970423' => 'TPBank',
'970403' => 'Sacombank',
'970437' => 'HDBank',
'970441' => 'VIB',
'970443' => 'SHB',
'970431' => 'Eximbank',
'970426' => 'MSB',
'970440' => 'SeABank',
'970448' => 'OCB',
'970449' => 'LPBank (LienVietPostBank)',
'970428' => 'Nam A Bank',
'970409' => 'Bac A Bank',
'970454' => 'Bản Việt (BVBank)',
'970425' => 'ABBANK',
'970412' => 'PVcomBank',
'970452' => 'KienlongBank',
'970438' => 'BaoViet Bank',
'970427' => 'VietABank',
'970419' => 'NCB',
'970430' => 'PGBank',
'970400' => 'Saigonbank',
'970406' => 'DongA Bank',
'970433' => 'Vietbank',
'970424' => 'Shinhan Bank',
'970457' => 'Woori Bank',
'970458' => 'UOB',
'970410' => 'Standard Chartered',
'970434' => 'Indovina Bank',
'546034' => 'CAKE by VPBank',
'546035' => 'Ubank by VPBank',
);
public function banks()
{
return self::BANKS;
}
public function bank_name($bin)
{
return isset(self::BANKS[$bin]) ? self::BANKS[$bin] : '';
}





public function payload($bin, $account, $note = '', $amount = 0)
{
$bin = preg_replace('/\D/', '', (string) $bin);
$account = preg_replace('/[^0-9A-Za-z]/', '', (string) $account);
if (strlen($bin) !== 6 || $account === '' || strlen($account) > 19) {
return '';
}
$is_card = ctype_digit($account) && strlen($account) >= 16 && strpos($account, $bin) === 0;
$beneficiary = $this->tlv('00', $bin) . $this->tlv('01', $account);
$merchant = $this->tlv('00', 'A000000727') . $this->tlv('01', $beneficiary) . $this->tlv('02', $is_card ? 'QRIBFTTC' : 'QRIBFTTA');
$s = $this->tlv('00', '01') . $this->tlv('01', $amount > 0 ? '12' : '11') . $this->tlv('38', $merchant)
. $this->tlv('53', '704');
if ($amount > 0) {
$s .= $this->tlv('54', (string) (int) $amount);
}
$s .= $this->tlv('58', 'VN');
$note = $this->note($note);
if ($note !== '') {
$s .= $this->tlv('62', $this->tlv('08', $note));
}
$s .= '6304';
return $s . $this->crc16($s);
}
private function tlv($id, $value)
{
return $id . str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT) . $value;
}

public function crc16($s)
{
$crc = 0xFFFF;
for ($i = 0, $n = strlen($s); $i < $n; $i++) {
$crc ^= ord($s[$i]) << 8;
for ($b = 0; $b < 8; $b++) {
$crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
$crc &= 0xFFFF;
}
}
return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

const NOTE_MAX = 25;





public function note($s)
{
$s = $this->ascii((string) $s);
if (strlen($s) <= self::NOTE_MAX) {
return $s;
}
$cut = substr($s, 0, self::NOTE_MAX + 1); 
$sp = strrpos($cut, ' ');
$s = $sp ? substr($cut, 0, $sp) : substr($s, 0, self::NOTE_MAX);
return rtrim($s);
}





public function ascii($s)
{
$s = (string) $s;
if ($s !== '' && !preg_match('//u', $s)) {
return ''; 
}
if (class_exists('Normalizer')) {
$d = Normalizer::normalize($s, Normalizer::FORM_D);
if (is_string($d)) {
$s = $d;
}
}

$s = preg_replace('/\p{Mn}+/u', '', $s);
$map = array('à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i','ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o',
'ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o','ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d');
$s = mb_strtolower($s, 'UTF-8');
$s = strtr($s, $map);
return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9 ]+/', ' ', $s)));
}
}