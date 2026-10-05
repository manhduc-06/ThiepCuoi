<?php

defined('BASEPATH') OR exit('No direct script access allowed');
class Dashboard extends Admin_Controller
{
public function index()
{
$_v6qyol5 = @disk_free_space(FCPATH . 'uploads');
$this->load->model(array('invite_model', 'content_model'));
$this->load->library(array('tunnelrunner', 'quota'));
$_v187wzu = tunnel_config();
$this->load->helper('lunar');
$_vp93eak = setting('wedding_date');
$_v6g773t = setting('wedding_time');
$_vif4zxj = $this->invite_model->stats();
$_veezna9 = $this->photo_model->stats();
$_vj7dm2d = $this->wish_model->count_all();
$_vs7d38n = $this->content_model->is_published();
$_vrpc8gs = $this->content_model->has_unpublished_changes();

$_volmzor = $this->content_model->checklist();
$_vakxv9a = array();
foreach ($_volmzor as $_vfew4lx) {
if (!$_vfew4lx['done'] && $_vfew4lx['group'] === 1) {
$_vakxv9a[] = $_vfew4lx['label'];
}
}
$_vych0lz = count(array_filter($_volmzor, function ($_vfew4lx) { return $_vfew4lx['done'] || $_vfew4lx['key'] === 'gallery' || $_vfew4lx['group'] !== 1; })) === count($_volmzor);

$_vgw45u1 = array(
array(__('Hoàn thiện trang cưới'), $_vakxv9a ? __('Còn: {list}', array('list' => implode(', ', $_vakxv9a))) : __('Đã đủ thông tin và ảnh ✓'), $_vych0lz, 'edit'),
array(__('Cho khách xem trang'), $_vs7d38n && $_vrpc8gs ? __('Có thay đổi khách chưa thấy — bấm "Cho khách xem" trên trang sửa') : __('Trước bước này khách chỉ thấy trang "đang chuẩn bị"'), $_volmzor[count($_volmzor) - 1]['done'], 'publish'),
array(__('Thêm khách mời'), __('Mỗi khách có thiệp riêng ghi tên họ (không bắt buộc)'), $_vif4zxj['invited'] > 0, 'guests'),
array(__('Gửi link cho khách'), __('Qua Zalo, Messenger, tin nhắn… hoặc in mã QR'), $_vif4zxj['inv_opened'] > 0 || $_vif4zxj['web'] > 0 || $_vj7dm2d > 0, 'send'),
);
$this->render('admin/dashboard', array(
'next' => $_vgw45u1,
'checklist' => $_volmzor,
'share_text' => share_invite_text($this->settings_model->couple_title(), (string) $_vp93eak),
'has_data' => $_vif4zxj['invited'] > 0 || $_vif4zxj['web'] > 0 || $_vj7dm2d > 0 || $_veezna9['from_guests'] > 0,
'charts' => $this->chart_data(),
'wed_date' => $_vp93eak,
'wed_ts' => $_vp93eak ? strtotime($_vp93eak . ' ' . ($_v6g773t ?: '00:00')) : 0,
'wed_text' => $_vp93eak ? vn_date($_vp93eak) . ($_v6g773t ? ' · ' . $_v6g773t : '') : '',
'wed_lunar' => $_vp93eak ? vn_lunar_text($_vp93eak) : '',
'rsvp' => $_vif4zxj,
'responses' => $this->invite_model->recent_responses(),
'published' => $_vs7d38n,
'changes' => $_vrpc8gs,
'tun_state' => $_v187wzu['mode'] === 'off' ? 'off' : $this->tunnelrunner->status(),
'tun_error' => $_v187wzu['mode'] === 'off' ? NULL : $this->tunnelrunner->status_error(),
'title' => __('Tổng quan'),
'stats' => $_veezna9,
'albums' => $this->album_model->list_all(),
'wishes' => $_vj7dm2d,
'disk_free' => $_v6qyol5 === FALSE ? NULL : $_v6qyol5,
'quota' => $this->quota->summary(), 
'public' => public_url(),
'tunnel' => tunnel_config(),
));
}




private function chart_data()
{
$_v97cfko = array();
for ($_vfzrp3x = 29; $_vfzrp3x >= 0; $_vfzrp3x--) {
$_v97cfko[] = date('Y-m-d', strtotime('-' . $_vfzrp3x . ' days'));
}
$_vjx3h0n = $_v97cfko[0] . ' 00:00:00';
$_vb9ajkb = function ($_vufjr0j, $_va2ofg6) use ($_v97cfko) {
$_vmb2d9n = array();
foreach ($_vufjr0j as $_v519xhe) {
$_vmb2d9n[$_v519xhe['d']] = (int) $_v519xhe[$_va2ofg6];
}
return array_map(function ($_vmzxysy) use ($_vmb2d9n) { return isset($_vmb2d9n[$_vmzxysy]) ? $_vmb2d9n[$_vmzxysy] : 0; }, $_v97cfko);
};
$_vc43xg9 = $this->db->query("SELECT substr(responded_at, 1, 10) AS d,
				COUNT(CASE WHEN status = 'yes' THEN 1 END) AS yes, COUNT(CASE WHEN status = 'no' THEN 1 END) AS no
			FROM invites WHERE responded_at >= ? GROUP BY d", array($_vjx3h0n))->result_array();
$_v9j2zt1 = $this->db->query('SELECT substr(created_at, 1, 10) AS d, COUNT(*) AS n FROM wishes WHERE created_at >= ? GROUP BY d', array($_vjx3h0n))->result_array();
$_v63vefs = $this->db->query("SELECT substr(created_at, 1, 10) AS d, COUNT(*) AS n FROM photos
			WHERE source = 'guest' AND status <> 'rejected' AND created_at >= ? GROUP BY d", array($_vjx3h0n))->result_array();
$_vgh0yeu = $this->invite_model->stats();
return array(
'days' => $_v97cfko,
'rsvp_yes' => $_vb9ajkb($_vc43xg9, 'yes'),
'rsvp_no' => $_vb9ajkb($_vc43xg9, 'no'),
'wishes' => $_vb9ajkb($_v9j2zt1, 'n'),
'photos' => $_vb9ajkb($_v63vefs, 'n'),

'status' => array('yes' => $_vgh0yeu['yes'], 'no' => $_vgh0yeu['no'], 'pending' => $_vgh0yeu['pending']),
'people' => array(
array('label' => __('Nhà trai'), 'value' => $_vgh0yeu['people_groom']),
array('label' => __('Nhà gái'), 'value' => $_vgh0yeu['people_bride']),
array('label' => __('Thiệp chung'), 'value' => max(0, $_vgh0yeu['inv_people'] - $_vgh0yeu['people_groom'] - $_vgh0yeu['people_bride'])),
array('label' => __('Tự xác nhận'), 'value' => $_vgh0yeu['web_people']),
),
);
}
}