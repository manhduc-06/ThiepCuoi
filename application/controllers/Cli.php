<?php
 defined('BASEPATH') OR exit('No direct script access allowed');






class Cli extends CI_Controller
{
public function __construct()
{
parent::__construct();
if (!is_cli()) {
show_404();
}
}

private $commands = array(
'doi_mat_khau' => 'Đặt lại mật khẩu quản trị khi quên (hỏi mật khẩu mới 2 lần).',
'tao_anh_nho' => 'Tạo ngay bản ảnh 1280px cho điện thoại cho mọi ảnh cũ.',
'tao_trang' => '(máy chủ thiep.site) Tạo trang đã cài sẵn: <tên đăng nhập> <bcrypt base64> <chú rể base64> <cô dâu base64> <ngày|->.',
);

public function _remap($_vgxrnrb, $_vkfy4c6 = array())
{
if (isset($this->commands[$_vgxrnrb])) {
return call_user_func_array(array($this, $_vgxrnrb), $_vkfy4c6);
}
if ($_vgxrnrb !== 'index') {
echo 'Không có lệnh "' . $_vgxrnrb . "\".\n\n";
}
echo "Ảnh Cưới — lệnh dòng lệnh. Cách dùng: php index.php cli <lệnh>\n\n";
foreach ($this->commands as $_vczlb46 => $_v6ksibf) {
echo '  ' . str_pad($_vczlb46, 14) . $_v6ksibf . "\n";
}
echo "\nVí dụ: php index.php cli doi_mat_khau\n";
exit(1);
}

public function doi_mat_khau()
{
$_vagkyca = defined('AC_CLI_ARGS') ? (array) json_decode(AC_CLI_ARGS, TRUE) : array();
$_v1tib5v = isset($_vagkyca[0]) ? (string) $_vagkyca[0] : '';
$_vveowwf = isset($_vagkyca[1]) ? (string) $_vagkyca[1] : '';
if ($_v1tib5v === '') {
$_v1tib5v = $this->ask('Mật khẩu mới (ít nhất 8 ký tự): ');
if (mb_strlen($_v1tib5v) >= 8 && $this->ask('Nhập lại mật khẩu mới: ') !== $_v1tib5v) {
echo "Hai lần nhập không khớp. Chưa đổi gì.\n";
exit(1);
}
}
if (mb_strlen($_v1tib5v) < 8) {
echo "Mật khẩu mới phải có ít nhất 8 ký tự. Chạy lại: php index.php cli doi_mat_khau\n";
exit(1);
}
$this->load->library('schema');
$this->schema->ensure();
$this->load->model('user_model');
$_vody80r = $this->db->order_by('id', 'ASC');
if ($_vveowwf !== '') {
$_vody80r->where('username', $_vveowwf);
}
$_vcg3cdk = $_vody80r->get('users', 1)->row_array();
if (!$_vcg3cdk) {
echo $_vveowwf !== '' ? "Không có tài khoản \"$_vveowwf\".\n" : "Chưa có tài khoản nào — mở trang web để cài đặt lần đầu.\n";
exit(1);
}
$this->user_model->set_password($_vcg3cdk['id'], $_v1tib5v);
echo "Đã đặt lại mật khẩu cho tài khoản \"{$_vcg3cdk['username']}\". Mọi nơi đang đăng nhập đã bị đăng xuất — đăng nhập lại ở /admin.\n";
}

public function tao_anh_nho()
{
$this->load->library('schema');
$this->schema->ensure();
$this->load->model('photo_model');
@unlink(FCPATH . 'database/.small_done');
$_v0iu5b1 = count($this->photo_model->missing_small());
if ($_v0iu5b1 === 0) {
$this->photo_model->backfill_small(86400); 
echo "Mọi ảnh đã có bản nhỏ cho điện thoại. Không cần tạo thêm.\n";
return;
}
echo "Còn $_v0iu5b1 ảnh cần tạo bản nhỏ cho điện thoại (mỗi ảnh khoảng 0,1–1 giây)…\n";
$_v4x9iiv = microtime(TRUE);
$this->photo_model->backfill_small(86400, function ($_vklbkse, $_vt3w9ei) {
if ($_vklbkse % 50 === 0 && $_vklbkse < $_vt3w9ei) {
echo "  … $_vklbkse/$_vt3w9ei ảnh\n";
}
});
$_vowskos = $this->photo_model->small_report;
$_vx7up3y = isset($_vowskos['errors']) ? $_vowskos['errors'] : array();
$_v99722i = number_format(microtime(TRUE) - $_v4x9iiv, 1, ',', '.');
echo 'Đã tạo ' . (int) $_vowskos['made'] . " ảnh trong $_v99722i giây. " . count($_vx7up3y) . ' ảnh lỗi'
. ($_vx7up3y ? ': ' . implode(', ', array_keys($_vx7up3y)) : '') . ".\n";
if ($_vx7up3y) {
foreach ($_vx7up3y as $_vfoefxp => $_v063caq) {
echo "  $_vfoefxp: $_v063caq\n";
}
echo "Danh sách ảnh lỗi lưu ở database/.small_errors. Có thể xóa các ảnh này trong trang quản trị rồi tải lại.\n";
exit(1);
}
}





public function tao_trang()
{
$_vx43qgo = defined('AC_CLI_ARGS') ? (array) json_decode(AC_CLI_ARGS, TRUE) : array();
$_vzyem36 = isset($_vx43qgo[0]) ? (string) $_vx43qgo[0] : '';
$_vz46l1l = isset($_vx43qgo[1]) ? (string) base64_decode((string) $_vx43qgo[1], TRUE) : '';
$_vpw7bc9 = isset($_vx43qgo[2]) ? trim((string) base64_decode((string) $_vx43qgo[2], TRUE)) : '';
$_vbes99u = isset($_vx43qgo[3]) ? trim((string) base64_decode((string) $_vx43qgo[3], TRUE)) : '';
$_v5wn580 = isset($_vx43qgo[4]) && $_vx43qgo[4] !== '-' ? (string) $_vx43qgo[4] : '';
if (!preg_match('/^[A-Za-z0-9_.\-]{3,32}$/', $_vzyem36) || !preg_match('/^\$2y\$\d\d\$[.\/A-Za-z0-9]{53}$/', $_vz46l1l)
|| ($_v5wn580 !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_v5wn580))) {
echo json_encode(array('ok' => FALSE, 'error' => 'Tham số không hợp lệ.')), "\n";
exit(1);
}
$this->load->library('schema');
$this->schema->ensure();
$this->load->model(array('user_model', 'settings_model', 'album_model'));
if ($this->user_model->count() > 0) {
echo json_encode(array('ok' => FALSE, 'error' => 'Trang đã có tài khoản.')), "\n";
exit(1);
}
$_vpw7bc9 = mb_substr($_vpw7bc9 !== '' ? $_vpw7bc9 : 'Chú rể', 0, 80);
$_vbes99u = mb_substr($_vbes99u !== '' ? $_vbes99u : 'Cô dâu', 0, 80);
$this->db->insert('users', array('username' => $_vzyem36, 'password_hash' => $_vz46l1l,
'display_name' => $_vpw7bc9 . ' & ' . $_vbes99u, 'created_at' => now_str()));
$this->settings_model->set_many(array('groom_name' => $_vpw7bc9, 'bride_name' => $_vbes99u, 'wedding_date' => $_v5wn580, 'setup_done' => '1'));
$_vva1vht = $this->album_model->create(array('title' => 'Ảnh cưới', 'visibility' => 'public'));
$this->settings_model->set_many(array('home_album_id' => $_vva1vht));
$this->album_model->guest_album_id();
save_tunnel_config('off');
echo json_encode(array('ok' => TRUE)), "\n";
}

private function ask($_vx48ncp)
{
echo $_vx48ncp;
$_vs0k4yi = DIRECTORY_SEPARATOR === '/' && function_exists('stream_isatty') && @stream_isatty(STDIN)
&& function_exists('shell_exec');
if ($_vs0k4yi) {
@shell_exec('stty -echo 2>/dev/null');
}
$_vxev0ua = fgets(STDIN);
if ($_vs0k4yi) {
@shell_exec('stty echo 2>/dev/null');
echo "\n";
}
return $_vxev0ua === FALSE ? '' : rtrim($_vxev0ua, "\r\n");
}
}