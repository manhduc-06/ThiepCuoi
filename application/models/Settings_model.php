<?php

defined('BASEPATH') OR exit('No direct script access allowed');
class Settings_model extends CI_Model
{




const SALUTATIONS_JSON = '['
. '{"s":"Ông bà","t":"Cháu kính mời {xung_ho} {ten} đến dự lễ thành hôn của chúng cháu. Sự hiện diện của {xung_ho} là niềm vinh hạnh lớn của gia đình."},'
. '{"s":"Bác","t":"Cháu kính mời {xung_ho} {ten} đến dự lễ thành hôn của {cap_doi}. Sự có mặt của {xung_ho} là niềm vui lớn của gia đình chúng cháu."},'
. '{"s":"Cô chú","t":"Cháu kính mời {xung_ho} {ten} đến chung vui trong ngày trọng đại của chúng cháu. Mong {xung_ho} dành chút thời gian ghé dự ạ."},'
. '{"s":"Ông","t":"Cháu kính mời {xung_ho} {ten} đến dự lễ thành hôn của chúng cháu. Sự hiện diện của {xung_ho} là niềm vinh hạnh lớn của gia đình."},'
. '{"s":"Bà","t":"Cháu kính mời {xung_ho} {ten} đến dự lễ thành hôn của chúng cháu. Sự hiện diện của {xung_ho} là niềm vinh hạnh lớn của gia đình."},'
. '{"s":"Cô","t":"Cháu kính mời {xung_ho} {ten} đến dự lễ cưới của {cap_doi}. Có {xung_ho} đến chung vui, chúng cháu mừng lắm ạ."},'
. '{"s":"Chú","t":"Cháu kính mời {xung_ho} {ten} đến dự lễ cưới của {cap_doi}. Mong {xung_ho} ghé chung vui cùng gia đình ạ."},'
. '{"s":"Dì","t":"Cháu kính mời {xung_ho} {ten} đến chung vui trong ngày cưới của chúng cháu. Có {xung_ho} là chúng cháu thêm ấm lòng ạ."},'
. '{"s":"Cậu mợ","t":"Cháu kính mời {xung_ho} {ten} đến dự lễ thành hôn của chúng cháu. Mong {xung_ho} về chung vui cùng gia đình ạ."},'
. '{"s":"Thầy cô","t":"Em trân trọng kính mời {xung_ho} {ten} đến dự lễ cưới của {cap_doi}. Sự hiện diện của {xung_ho} là niềm vinh dự của chúng em."},'
. '{"s":"Anh chị","t":"Em thân mời {xung_ho} {ten} đến chung vui trong ngày cưới của {cap_doi}. Có {xung_ho} đến là tụi em vui lắm!"},'
. '{"s":"Anh","t":"Em thân mời {xung_ho} {ten} đến dự đám cưới của {cap_doi}. Nhớ ghé chung vui với tụi em nhé {xung_ho}!"},'
. '{"s":"Chị","t":"Em thân mời {xung_ho} {ten} đến dự đám cưới của {cap_doi}. Nhớ ghé chung vui với tụi em nhé {xung_ho}!"},'
. '{"s":"Bạn","t":"Thân mời {ten} đến chung vui cùng tụi mình trong ngày cưới. Có {xung_ho} là ngày vui của tụi mình trọn vẹn hơn!"},'
. '{"s":"Em","t":"Anh chị mời {ten} đến chung vui trong ngày cưới của {cap_doi}. Nhớ đến sớm nhé {xung_ho}!"},'
. '{"s":"Gia đình","t":"Trân trọng kính mời {xung_ho} {ten} đến dự lễ thành hôn của {cap_doi}. Sự hiện diện của {xung_ho} là niềm vinh hạnh của chúng tôi."},'
. '{"s":"Sếp","t":"Em trân trọng kính mời {xung_ho} {ten} đến dự tiệc cưới của {cap_doi}. Sự hiện diện của {xung_ho} là niềm vinh hạnh của chúng em."},'
. '{"s":"Đồng nghiệp","t":"Trân trọng mời {ten} đến dự tiệc cưới của {cap_doi}. Rất mong được chung vui cùng bạn ngoài giờ làm việc!"}'
. ']';

const SALUTATIONS_JSON_V1 = '['
. '{"s":"Ông bà","t":"Cháu kính mời {xung_ho} {ten} đến dự lễ thành hôn của chúng cháu. Sự hiện diện của {xung_ho} là niềm vinh hạnh lớn của gia đình."},'
. '{"s":"Bác","t":"Cháu kính mời {xung_ho} {ten} đến dự lễ thành hôn của {cap_doi}. Sự có mặt của {xung_ho} là niềm vui lớn của gia đình chúng cháu."},'
. '{"s":"Cô chú","t":"Cháu kính mời {xung_ho} {ten} đến chung vui trong ngày trọng đại của chúng cháu. Mong {xung_ho} dành chút thời gian ghé dự ạ."},'
. '{"s":"Cô","t":"Cháu kính mời {xung_ho} {ten} đến dự lễ cưới của {cap_doi}. Có {xung_ho} đến chung vui, chúng cháu mừng lắm ạ."},'
. '{"s":"Chú","t":"Cháu kính mời {xung_ho} {ten} đến dự lễ cưới của {cap_doi}. Mong {xung_ho} ghé chung vui cùng gia đình ạ."},'
. '{"s":"Dì","t":"Cháu kính mời {xung_ho} {ten} đến chung vui trong ngày cưới của chúng cháu. Có {xung_ho} là chúng cháu thêm ấm lòng ạ."},'
. '{"s":"Cậu mợ","t":"Cháu kính mời {xung_ho} {ten} đến dự lễ thành hôn của chúng cháu. Mong {xung_ho} về chung vui cùng gia đình ạ."},'
. '{"s":"Thầy cô","t":"Em trân trọng kính mời {xung_ho} {ten} đến dự lễ cưới của {cap_doi}. Sự hiện diện của {xung_ho} là niềm vinh dự của chúng em."},'
. '{"s":"Anh chị","t":"Em thân mời {xung_ho} {ten} đến chung vui trong ngày cưới của {cap_doi}. Có {xung_ho} đến là tụi em vui lắm!"},'
. '{"s":"Anh","t":"Em thân mời {xung_ho} {ten} đến dự đám cưới của {cap_doi}. Nhớ ghé chung vui với tụi em nhé {xung_ho}!"},'
. '{"s":"Chị","t":"Em thân mời {xung_ho} {ten} đến dự đám cưới của {cap_doi}. Nhớ ghé chung vui với tụi em nhé {xung_ho}!"},'
. '{"s":"Bạn","t":"Thân mời {ten} đến chung vui cùng tụi mình trong ngày cưới. Có {xung_ho} là ngày vui của tụi mình trọn vẹn hơn!"},'
. '{"s":"Em","t":"Anh chị mời {ten} đến chung vui trong ngày cưới của {cap_doi}. Nhớ đến sớm nhé {xung_ho}!"},'
. '{"s":"Gia đình","t":"Trân trọng kính mời {xung_ho} {ten} đến dự lễ thành hôn của {cap_doi}. Sự hiện diện của {xung_ho} là niềm vinh hạnh của chúng tôi."},'
. '{"s":"Sếp","t":"Em trân trọng kính mời {xung_ho} {ten} đến dự tiệc cưới của {cap_doi}. Sự hiện diện của {xung_ho} là niềm vinh hạnh của chúng em."},'
. '{"s":"Đồng nghiệp","t":"Trân trọng mời {ten} đến dự tiệc cưới của {cap_doi}. Rất mong được chung vui cùng bạn ngoài giờ làm việc!"}'
. ']';

const SALUTATIONS_JSON_EN = '['
. '{"s":"Mr. & Mrs.","t":"Together with our families, we joyfully invite you, {xung_ho} {ten}, to celebrate our wedding. Your presence would mean the world to us."},'
. '{"s":"Mr.","t":"We would be honored to have you, {xung_ho} {ten}, join us as we celebrate our wedding."},'
. '{"s":"Mrs.","t":"We would be honored to have you, {xung_ho} {ten}, join us as we celebrate our wedding."},'
. '{"s":"Ms.","t":"We would be honored to have you, {xung_ho} {ten}, join us as we celebrate our wedding."},'
. '{"s":"Miss","t":"We would be honored to have you, {xung_ho} {ten}, join us as we celebrate our wedding."},'
. '{"s":"Dr.","t":"We would be honored to have you, {xung_ho} {ten}, join us as we celebrate our wedding."},'
. '{"s":"Prof.","t":"It would be a true honor to have you, {xung_ho} {ten}, with us as we celebrate our wedding."},'
. '{"s":"Grandma","t":"Our wedding day wouldn\'t be complete without you, {xung_ho} {ten}. We can\'t wait to celebrate with you!"},'
. '{"s":"Grandpa","t":"Our wedding day wouldn\'t be complete without you, {xung_ho} {ten}. We can\'t wait to celebrate with you!"},'
. '{"s":"Aunt","t":"It would mean so much to have you, {xung_ho} {ten}, with us on our wedding day. We can\'t wait to celebrate with you!"},'
. '{"s":"Uncle","t":"It would mean so much to have you, {xung_ho} {ten}, with us on our wedding day. We can\'t wait to celebrate with you!"},'
. '{"s":"Cousin","t":"We\'re getting married, {xung_ho} {ten}, and we\'d love for you to be there. Come celebrate with us!"}'
. ']';

const DEFAULTS = array(
'setup_done' => '0',
'groom_name' => '',
'bride_name' => '',
'wedding_date' => '',
'wedding_time' => '',
'venue' => '',
'venue_map_url' => '',
'intro' => '',
'hero_photo_id' => '',
'site_password_hash' => '',
'guest_upload' => '1',
'guest_upload_approval' => '1',
'guest_upload_max_mb' => '25',
'guest_upload_album_id' => '',
'wishes_enabled' => '1',
'rsvp_enabled' => '1',
'invite_card' => '1',
'site_lang' => 'vi', 
'site_langs' => '', 
'admin_lang' => 'vi', 
'invite_card_style' => 'classic', 
'music_autoplay' => '1',

'gift_enabled' => '0',
'gift_title' => 'Hộp mừng cưới',
'gift_text' => 'Sự hiện diện của bạn là món quà lớn nhất. Nếu không thể đến chung vui, bạn có thể gửi lời chúc mừng qua mã QR dưới đây.',
'gift_note' => '',
'gift_groom_bin' => '', 'gift_groom_acct' => '', 'gift_groom_holder' => '',
'gift_bride_bin' => '', 'gift_bride_acct' => '', 'gift_bride_holder' => '',
'invite_template' => 'Thân mời {xung_ho} {ten} đến chung vui cùng gia đình chúng mình trong ngày trọng đại.',
'salutations' => self::SALUTATIONS_JSON,
'music' => 'builtin:canon-in-d',
'wishes_approval' => '0',




'album_download' => '0',
'accent_color' => '#b4838b',
);
private $cache = NULL;
public function all()
{
if ($this->cache === NULL) {
$this->cache = self::DEFAULTS;
foreach ($this->db->get('settings')->result_array() as $_v8z0451) {
$this->cache[$_v8z0451['key']] = (string) $_v8z0451['value'];
}
}
return $this->cache;
}
public function get($_voh7fr4, $_v8b8qi9 = '')
{
$_vvsi1cg = $this->all();
return array_key_exists($_voh7fr4, $_vvsi1cg) ? $_vvsi1cg[$_voh7fr4] : $_v8b8qi9;
}
public function set_many(array $_v4gpkou)
{
$this->db->trans_start();
foreach ($_v4gpkou as $_vi66s4w => $_ve29jau) {
$this->db->query('INSERT INTO settings (key, value) VALUES (?, ?)
				ON CONFLICT(key) DO UPDATE SET value = excluded.value', array($_vi66s4w, (string) $_ve29jau));
}
$this->db->trans_complete();
$this->cache = NULL;
return $this->db->trans_status();
}
public function couple_title()
{
$_vemhodx = trim($this->get('groom_name'));
$_vcbkgvu = trim($this->get('bride_name'));
if ($_vemhodx !== '' && $_vcbkgvu !== '') {
return $_vemhodx . ' & ' . $_vcbkgvu;
}
return $_vemhodx . $_vcbkgvu !== '' ? $_vemhodx . $_vcbkgvu : __c('Đám cưới của chúng mình');
}




public function localized($_v6zcvlw)
{
$_v3rh91m = (string) $this->get($_v6zcvlw, '');
return (isset(self::DEFAULTS[$_v6zcvlw]) && $_v3rh91m === self::DEFAULTS[$_v6zcvlw]) ? __c($_v3rh91m) : $_v3rh91m;
}
}