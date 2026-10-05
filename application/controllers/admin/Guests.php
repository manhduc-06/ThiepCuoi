<?php

defined('BASEPATH') OR exit('No direct script access allowed');




class Guests extends Admin_Controller
{

const PER_PAGE = 50;
public function __construct()
{
parent::__construct();
$this->load->model(array('invite_model', 'content_model'));
}
public function index()
{
$this->invite_model->ensure_slugs();
$_vokp7o8 = (string) $this->input->get('loc');
$_vokp7o8 = in_array($_vokp7o8, Invite_model::FILTERS, TRUE) ? $_vokp7o8 : '';
$_vb10zph = (string) $this->input->get('ben');
$_vb10zph = in_array($_vb10zph, array('groom', 'bride', 'none'), TRUE) ? $_vb10zph : '';
$_vptafjm = mb_substr(trim((string) $this->input->get('q')), 0, 60);
$_vce56tv = $this->invite_model->count_all($_vokp7o8, $_vb10zph, $_vptafjm);
$_v8poet8 = max(1, (int) ceil($_vce56tv / self::PER_PAGE));
$_v9wkpu5 = min($_v8poet8, max(1, (int) $this->input->get('trang')));
list($_vhbwoe5, $_v69p2nn, $_vpmumbd) = $this->guest_facing();

$_vpceqg3 = $this->invite_model->all($_vokp7o8, $_vb10zph, $_vptafjm, self::PER_PAGE, ($_v9wkpu5 - 1) * self::PER_PAGE);
$_v092np5 = $this->invite_model->duplicate_ids();

$_vl0yhe2 = $this->session->flashdata('guest_form');
$_vbfya5t = $this->session->flashdata('guest_back');


$this->session->unmark_flash(array('guest_form', 'guest_back'));
$this->session->unset_userdata(array('guest_form', 'guest_back'));
foreach ($_vpceqg3 as &$_v92vozy) {
$_v92vozy['_src'] = $this->invite_model->invite_source($_v92vozy);
$_v92vozy['_text'] = $_v92vozy['source'] === 'invite' ? $this->invite_model->invite_text($_v92vozy, $_vhbwoe5, $_v69p2nn) : '';
$_v92vozy['_dup'] = isset($_v092np5[(int) $_v92vozy['id']]);

$_v92vozy['_old_slugs'] = (is_array($_vl0yhe2) && (int) $_vl0yhe2['id'] === (int) $_v92vozy['id'])
? $this->invite_model->old_slugs($_v92vozy['id']) : array();
}
unset($_v92vozy);
$_vvi46qb = $this->invite_model->sample(); 
$this->render('admin/guests', array(
'title' => __('Khách mời & thiệp mời'),
'filter' => $_vokp7o8,
'side' => $_vb10zph,
'q' => $_vptafjm,
'invites' => $_vpceqg3,
'total' => $_vce56tv,
'page' => $_v9wkpu5,
'pages' => $_v8poet8,
'per_page' => self::PER_PAGE,
'stats' => $this->invite_model->stats(),
'base' => rtrim(public_url(), '/') . '/',
'sides' => Invite_model::SIDES,
'sal_list' => $this->invite_model->salutations(),
'card_on' => setting('invite_card', '1') === '1',
'card_style' => $this->content_model->card_style(),
'card_styles' => $this->content_model->registry('card_styles'),

'card_chosen' => $this->db->where('key', 'invite_card_style')->count_all_results('settings') > 0,


'card_sample' => $_vvi46qb ? base_url($this->invite_model->path($_vvi46qb)) : '',
'template' => $this->settings_model->localized('invite_template'),
'couple_short' => $_vhbwoe5,
'pub_changed' => $_vpmumbd,
'date_short' => $_v69p2nn,
'form_back' => is_array($_vl0yhe2) ? $_vl0yhe2 : NULL,
'back_link' => is_array($_vbfya5t) ? $_vbfya5t : NULL,
'bulk_max' => Invite_model::BULK_MAX,
));
}




private function keep_form($_vkavqtm, array $_vslwa9q, $_van1blp)
{
$_v43e1of = $this->invite_model->error_field; 
$this->session->set_flashdata('guest_form', array('id' => (int) $_vkavqtm, 'f' => $_vslwa9q, 'error' => $_van1blp, 'field' => $_v43e1of));
}




private function back($_vq0xmn5 = 0)
{
$_vujz7bi = array(
'loc' => (string) $this->input->get('loc'),
'ben' => (string) $this->input->get('ben'),
'q' => (string) $this->input->get('q'),
'trang' => (string) max(1, (int) $this->input->get('trang')),
);
$_vmoj8xe = $_vujz7bi;
if ($_vq0xmn5) {
$_v0qddmj = $this->invite_model->position($_vq0xmn5, in_array($_vujz7bi['loc'], Invite_model::FILTERS, TRUE) ? $_vujz7bi['loc'] : '',
in_array($_vujz7bi['ben'], array('groom', 'bride', 'none'), TRUE) ? $_vujz7bi['ben'] : '', mb_substr(trim($_vujz7bi['q']), 0, 60));
if ($_v0qddmj >= 0) {
$_vujz7bi['trang'] = (string) (intdiv($_v0qddmj, self::PER_PAGE) + 1);
}
}
$_v0inkq9 = function (array $_vujz7bi) {
if ($_vujz7bi['trang'] === '1') {
$_vujz7bi['trang'] = '';
}
$_v5cd936 = array_filter($_vujz7bi, 'strlen');
return 'admin/guests' . ($_v5cd936 ? '?' . http_build_query($_v5cd936) : '');
};

if ($_vq0xmn5 && $_vmoj8xe['trang'] !== $_vujz7bi['trang']) {
$this->session->set_flashdata('guest_back', array('id' => (int) $_vq0xmn5, 'page' => (int) $_vmoj8xe['trang'], 'url' => $_v0inkq9($_vmoj8xe)));
}
return $_v0inkq9($_vujz7bi);
}





private function guest_facing()
{
$_vzq22im = $this->content_model;
$_vukw502 = array($_vzq22im->couple_title(), $_vzq22im->get('wedding_date'));
$_vbfpvrn = $_vzq22im->use_published() && $_vukw502 !== array($_vzq22im->couple_title(), $_vzq22im->get('wedding_date'));
$_vsngj0l = $_vzq22im->get('wedding_date');
return array($_vzq22im->couple_title(), $_vsngj0l ? vn_date($_vsngj0l, FALSE, lang_content()) : '', $_vbfpvrn);
}




public function form($_v1ypc2j = 0)
{
$_v4ypkvt = $this->invite_model->find($_v1ypc2j);
if (!$_v4ypkvt) {
show_404();
}
list($_vog3snx, $_vc1id9j) = $this->guest_facing();
$_v4ypkvt['_text'] = $_v4ypkvt['source'] === 'invite' ? $this->invite_model->invite_text($_v4ypkvt, $_vog3snx, $_vc1id9j) : '';
$_v4ypkvt['_old_slugs'] = $this->invite_model->old_slugs($_v4ypkvt['id']);
$_v22xfew = $this->invite_model->find_salutation($_v4ypkvt['salutation']);
$_vnr68et = array_filter(array(
'loc' => (string) $this->input->get('loc'),
'ben' => (string) $this->input->get('ben'),
'q' => (string) $this->input->get('q'),
'trang' => (int) $this->input->get('trang') > 1 ? (string) (int) $this->input->get('trang') : '',
), 'strlen');
$this->output->set_header('Cache-Control: no-store');
$this->load->view('admin/_guest_edit', array(
'g' => $_v4ypkvt,
'eb' => NULL,
'keep' => $_vnr68et ? '?' . http_build_query($_vnr68et) : '',
'host' => preg_replace('~^https?://~', '', rtrim(public_url(), '/') . '/'),
'side_labels' => array('' => __('Khách chung'), 'groom' => __('Nhà trai'), 'bride' => __('Nhà gái')),
'own_placeholder' => ($_v22xfew && $_v22xfew['t'] !== '') ? $_v22xfew['t'] : $this->settings_model->localized('invite_template'),
));
}

private function form_fields()
{
return array(
'salutation' => (string) $this->input->post('salutation'),
'name' => (string) $this->input->post('name'),
'side' => (string) $this->input->post('side'),
'note' => (string) $this->input->post('note'),
'invite_text' => (string) $this->input->post('invite_text'),
'max_guests' => (string) $this->input->post('max_guests'),
'slug' => (string) $this->input->post('slug'),
);
}







public function add()
{
$this->require_post();
$_v7hi4z5 = (string) $this->input->post('rows_json');
$_v76e9l0 = (array) $this->input->post('rows_name');
$_vw46x4h = $this->input->is_ajax_request(); 
if ($_v7hi4z5 !== '') {
$_vpn7uga = json_decode($_v7hi4z5, TRUE);
if (!is_array($_vpn7uga) && $_vw46x4h) {
return json_out(array('ok' => FALSE, 'error' => __('Không đọc được bảng khách vừa gửi.')), 422);
}
if (!is_array($_vpn7uga)) {
flash('error', __('Không đọc được bảng khách vừa gửi, bạn thử lại nhé.'));
redirect('admin/guests#nhanh');
}
} elseif ($_v76e9l0) {
$_v4efk5t = function ($_v1hsyi6, $_vn73juh) {
$_v5lq5db = $this->input->post($_v1hsyi6);
return is_array($_v5lq5db) && isset($_v5lq5db[$_vn73juh]) && is_string($_v5lq5db[$_vn73juh]) ? $_v5lq5db[$_vn73juh] : '';
};
$_vpn7uga = array();
foreach (array_keys($_v76e9l0) as $_vn73juh) {
$_vpn7uga[] = array('salutation' => $_v4efk5t('rows_sal', $_vn73juh), 'name' => is_string($_v76e9l0[$_vn73juh]) ? $_v76e9l0[$_vn73juh] : '',
'side' => $_v4efk5t('rows_side', $_vn73juh), 'invite_text' => $_v4efk5t('rows_text', $_vn73juh));
}
} else {
$_vpn7uga = $this->invite_model->parse_lines((string) $this->input->post('names'), (string) $this->input->post('side'));
}
$_vsxaneu = $this->invite_model->create_rows_report($_vpn7uga);
if ($_vw46x4h) {

$_vn67c0y = $_vsxaneu;
$_vn67c0y['created'] += max(0, min(100000, (int) $this->input->post('prev_created')));
$_vn67c0y['received'] += max(0, min(100000, (int) $this->input->post('prev_received')));
$_vlew62u = $_vn67c0y['received'] ? $this->bulk_message($_vn67c0y) : __('Nhập ít nhất 1 tên (mỗi dòng 1 người).');
flash($_vn67c0y['created'] === $_vn67c0y['received'] && $_vn67c0y['received'] ? 'success' : 'error', $_vlew62u);
return json_out(array('ok' => TRUE, 'created' => $_vsxaneu['created'], 'received' => $_vsxaneu['received'], 'message' => $_vlew62u));
}
if (!$_vsxaneu['received']) {
flash('error', __('Nhập ít nhất 1 tên (mỗi dòng 1 người).'));
} else {
flash($_vsxaneu['created'] === $_vsxaneu['received'] ? 'success' : 'error', $this->bulk_message($_vsxaneu));
}
redirect('admin/guests');
}

private function bulk_message(array $_vz1u5hk)
{
$_vlrv4tz = __('Đã tạo {n} thiệp mời ({n}/{m} dòng).', array('n' => $_vz1u5hk['created'], 'm' => $_vz1u5hk['received']));
$_vq176b5 = $_vz1u5hk['received'] - $_vz1u5hk['created'];
if ($_vq176b5 > 0) {
$_veww6bo = array();
if ($_vz1u5hk['skipped']) {
$_veww6bo[] = __('bỏ qua {n} dòng thiếu tên', array('n' => $_vz1u5hk['skipped']));
}
if ($_vz1u5hk['over']) {
$_veww6bo[] = __('{n} dòng cuối vượt quá {max} khách mỗi lần', array('n' => $_vz1u5hk['over'], 'max' => Invite_model::BULK_MAX));
}
foreach (array_slice($_vz1u5hk['failed'], 0, 5) as $_vuamk0b) {
$_veww6bo[] = $_vuamk0b;
}
if (count($_vz1u5hk['failed']) > 5) {
$_veww6bo[] = '…';
}
$_vlrv4tz .= ' ' . __('{n} dòng không tạo được: {why}.', array('n' => $_vq176b5, 'why' => implode('; ', $_veww6bo)));
}
if ($_vz1u5hk['truncated']) {
$_vlrv4tz .= ' ' . __('Đã rút gọn {n} tên dài quá 80 ký tự.', array('n' => $_vz1u5hk['truncated']));
}
if (!empty($_vz1u5hk['bad_phone'])) { 
$_vlrv4tz .= ' ' . __('{n} dòng có SĐT không hợp lệ, đã bỏ (vẫn tạo thiệp).', array('n' => $_vz1u5hk['bad_phone']));
}
if ($_vz1u5hk['dups']) {
$_vlrv4tz .= ' ' . __('{n} tên trùng với khách đã có (xem nhãn "trùng tên" trong danh sách).', array('n' => $_vz1u5hk['dups']));
}
if ($_vz1u5hk['created']) {
$_vlrv4tz .= ' ' . __('Bấm "Gửi thiệp" ở từng khách để gửi nhé.');
}
return $_vlrv4tz;
}




public function salutations()
{
$this->require_post();
if ($this->input->post('reset') === '1') {
$this->invite_model->reset_salutations();
$_vrva0v7 = TRUE;
$_v79qw5i = __('Đã khôi phục danh sách xưng hô mặc định.');
} else {
$_vssdvfi = (array) $this->input->post('sal');
$_vlp794m = (array) $this->input->post('tpl');
$_v1kxulk = array();
foreach (array_keys($_vssdvfi) as $_vw8btz1) {
$_v1kxulk[] = array('s' => is_string($_vssdvfi[$_vw8btz1]) ? $_vssdvfi[$_vw8btz1] : '', 't' => isset($_vlp794m[$_vw8btz1]) && is_string($_vlp794m[$_vw8btz1]) ? $_vlp794m[$_vw8btz1] : '');
}
$_vrva0v7 = $this->invite_model->save_salutations($_v1kxulk);
$_v79qw5i = __('Đã lưu danh sách xưng hô & lời mời mẫu.');
}
if ($this->input->is_ajax_request()) {
return json_out($_vrva0v7 === TRUE ? array('ok' => TRUE, 'salutations' => $this->invite_model->salutations())
: array('ok' => FALSE, 'error' => $_vrva0v7), $_vrva0v7 === TRUE ? 200 : 422);
}
flash($_vrva0v7 === TRUE ? 'success' : 'error', $_vrva0v7 === TRUE ? $_v79qw5i : $_vrva0v7);
redirect('admin/guests#xung-ho');
}

public function create()
{
$this->require_post();
$_vv15e00 = $this->form_fields();
$_vv15e00['phone'] = trim((string) $this->input->post('phone'));

$_vlin6c9 = '';
if ($_vzwxvs7 = $this->invite_model->split_phone($_vv15e00['name'])) {
if ($_vv15e00['phone'] !== '') {
$this->invite_model->error_field = 'name';
$_vszas3x = __('Tên có số điện thoại — xóa số khỏi ô Tên (số điện thoại đã có ở ô bên dưới).');
flash('error', $_vszas3x);
$this->keep_form(0, $_vv15e00, $_vszas3x);
redirect('admin/guests#them-khach');
}
list($_vv15e00['name'], $_vv15e00['phone']) = $_vzwxvs7;
$_vlin6c9 = ' ' . __('(đã tách số điện thoại {phone} khỏi tên)', array('phone' => $_vzwxvs7[1]));
}
$_vizxytq = $this->invite_model->create($_vv15e00['name'], $_vv15e00['side'], $_vv15e00['note'], 'invite', $_vv15e00);
if (is_array($_vizxytq)) {
flash('success', __('Đã tạo thiệp mời cho {name}{split}: {url}', array('name' => $this->invite_model->display_name($_vizxytq), 'split' => $_vlin6c9,
'url' => rtrim(public_url(), '/') . '/' . $this->invite_model->guest_path($_vizxytq))));
} else {
flash('error', $_vizxytq);
$this->keep_form(0, $_vv15e00, $_vizxytq);
}
redirect('admin/guests');
}
public function edit($_vw0l1md = 0)
{
$this->require_post();
$_vpd6ydj = $this->form_fields();
if ($this->input->post('phone') !== NULL) { 
$_vpd6ydj['phone'] = trim((string) $this->input->post('phone'));
}
$_vi5h9yp = $this->invite_model->update($_vw0l1md, $_vpd6ydj);
flash($_vi5h9yp === TRUE ? 'success' : 'error', $_vi5h9yp === TRUE ? __('Đã lưu.') : $_vi5h9yp);
if ($_vi5h9yp !== TRUE) {
$this->keep_form($_vw0l1md, $_vpd6ydj, $_vi5h9yp);
}
redirect($this->back($_vw0l1md) . '#g' . (int) $_vw0l1md);
}

public function check_slug()
{
$_vf5v9zw = strtolower(trim((string) $this->input->get('slug')));
$_vb065le = $this->invite_model->check_slug($_vf5v9zw, (int) $this->input->get('id'));
json_out(array('ok' => TRUE, 'available' => $_vb065le === TRUE, 'message' => $_vb065le === TRUE ? '' : $_vb065le,
'field' => $_vb065le === TRUE ? '' : 'slug',
'url' => rtrim(public_url(), '/') . '/' . $_vf5v9zw));
}

public function card()
{
$this->require_post();
$_vnbgn4q = array();
if ($this->input->post('invite_card') !== NULL) {
$_vnbgn4q['invite_card'] = $this->input->post('invite_card') === '1' ? '1' : '0';
}
$_v0dbher = $this->input->post('invite_card_style');
if ($_v0dbher !== NULL) {

if (!$this->content_model->card_usable((string) $_v0dbher)) {

$_v2q098c = array_key_exists((string) $_v0dbher, $this->content_model->registry('card_styles'))
? __('Mẫu thiệp VIP chỉ dùng được trên trang cưới thiep.site.') : __('Mẫu thiệp không hợp lệ.');
if ($this->input->is_ajax_request()) {
return json_out(array('ok' => FALSE, 'error' => $_v2q098c, 'vip' => array_key_exists((string) $_v0dbher, $this->content_model->registry('card_styles'))));
}
flash('error', $_v2q098c);
return redirect('admin/guests');
}
$_vnbgn4q['invite_card_style'] = (string) $_v0dbher;
}
if ($this->input->post('invite_template') !== NULL) {
$_voa068u = mb_substr(trim(str_replace("\r", '', (string) $this->input->post('invite_template'))), 0, 500);
$_vnbgn4q['invite_template'] = $_voa068u !== '' ? $_voa068u : Settings_model::DEFAULTS['invite_template'];
}
if ($_vnbgn4q) {
$this->settings_model->set_many($_vnbgn4q);
}
if ($this->input->is_ajax_request()) {
return json_out(array('ok' => TRUE, 'invite_card' => setting('invite_card', '1'),
'invite_card_style' => $this->content_model->card_style()));
}
if (isset($_vnbgn4q['invite_card_style'])) {
$_v02e1wb = $this->content_model->registry('card_styles');
$_vlzluku = $_v02e1wb[$_vnbgn4q['invite_card_style']];
$_vasgjd2 = (lang_cur() === 'en' && !empty($_vlzluku['name_en'])) ? $_vlzluku['name_en'] : $_vlzluku['name'];
flash('success', __('Đã chọn mẫu thiệp "{name}". Khách mở link riêng sẽ thấy mẫu này.', array('name' => $_vasgjd2)));
} else {
flash('success', isset($_vnbgn4q['invite_card'])
? ($_vnbgn4q['invite_card'] === '1' ? __('Đã bật thiệp mời: khách mở link riêng sẽ thấy thiệp trước.') : __('Đã tắt thiệp mời: link riêng vào thẳng trang cưới.'))
: __('Đã lưu mẫu lời mời.'));
}
redirect('admin/guests');
}

public function mark($_voppxof = 0)
{
$this->require_post();
$_v0x3epv = $this->invite_model->find($_voppxof);
$_vwcpum7 = (string) $this->input->post('status');
if ($_v0x3epv && in_array($_vwcpum7, array('yes', 'no'), TRUE)) {
$_vcqv7p3 = $this->invite_model->respond($_v0x3epv, $_vwcpum7, max(1, (int) $this->input->post('guests') ?: (int) $_v0x3epv['guests']), (string) $_v0x3epv['message']);
flash('success', $_vwcpum7 === 'yes'
? __('Đã ghi nhận {name} tham dự · {n} người.', array('name' => $this->invite_model->display_name($_vcqv7p3), 'n' => (int) $_vcqv7p3['guests']))
: __('Đã ghi nhận {name} không tham dự.', array('name' => $this->invite_model->display_name($_vcqv7p3))));
} elseif ($_v0x3epv && $_vwcpum7 === 'pending') {
$this->db->update('invites', array('status' => 'pending', 'guests' => 1, 'responded_at' => NULL), array('id' => (int) $_voppxof));
flash('success', __('Đã chuyển {name} về "chưa trả lời".', array('name' => $this->invite_model->display_name($_v0x3epv))));
} else {
flash('error', __('Không tìm thấy khách này.'));
}
redirect($this->back($_voppxof) . '#g' . (int) $_voppxof);
}
public function delete($_vrpant2 = 0)
{
$this->require_post();
$this->invite_model->delete($_vrpant2);
flash('success', __('Đã xóa thiệp mời.'));
redirect($this->back());
}

public function export()
{
$this->invite_model->ensure_slugs();
$_v72sajw = array('pending' => __('Chưa trả lời'), 'yes' => __('Tham dự'), 'no' => __('Không tham dự'));
$_v6xsta8 = rtrim(public_url(), '/') . '/';
list($_vebl3f9, $_vt95phd) = $this->guest_facing(); 
$_vrcej89 = fopen('php://temp', 'w+');
fwrite($_vrcej89, "\xEF\xBB\xBF");
fputcsv($_vrcej89, array(__('Xưng hô'), __('Tên'), __('Bên'), __('Nguồn'), __('Trạng thái'), __('Số người'), __('Tối đa'), __('Đã mở thiệp lúc'), __('Điện thoại'),
__('Lời nhắn'), __('Lời mời riêng'), __('Lời mời trên thiệp'), __('Ghi chú'), __('Link thiệp mời'), __('Trả lời lúc')));

$_vybfdy2 = function ($_v6nxd4y) { $_v6nxd4y = (string) $_v6nxd4y; return preg_match('/^[=+\-@\t\r]/', $_v6nxd4y) ? "'" . $_v6nxd4y : $_v6nxd4y; };
foreach ($this->invite_model->all() as $_vbfdf35) {
$_vcycol8 = $_vbfdf35['source'] === 'invite' ? $_v6xsta8 . $this->invite_model->guest_path($_vbfdf35) : ''; 
$_vevtvf0 = array($_vbfdf35['salutation'], $_vbfdf35['name'], __(Invite_model::SIDES[(string) $_vbfdf35['side']] ?? ''),
$_vbfdf35['source'] === 'web' ? __('Tự xác nhận trên web') : __('Thiệp mời'), $_v72sajw[$_vbfdf35['status']] ?? $_vbfdf35['status'],
$_vbfdf35['status'] === 'yes' ? $_vbfdf35['guests'] : '', $_vbfdf35['max_guests'], $_vbfdf35['opened_at'], $_vbfdf35['phone'], $_vbfdf35['message'],
$_vbfdf35['invite_text'], $_vbfdf35['source'] === 'invite' ? $this->invite_model->invite_text($_vbfdf35, $_vebl3f9, $_vt95phd) : '', $_vbfdf35['note'], $_vcycol8, $_vbfdf35['responded_at']);
$_vevtvf0 = array_map($_vybfdy2, $_vevtvf0);

if (preg_match('/^\+?[0-9][0-9 .\-]{6,22}$/', (string) $_vbfdf35['phone'])) {
$_vevtvf0[8] = '="' . $_vbfdf35['phone'] . '"';
}
fputcsv($_vrcej89, $_vevtvf0);
}
rewind($_vrcej89);
$this->output->set_content_type('text/csv', 'utf-8')
->set_header('Content-Disposition: attachment; filename="' . (lang_cur() === 'en' ? 'guests-' : 'khach-moi-') . date('Ymd') . '.csv"')
->set_output(stream_get_contents($_vrcej89));
}
}