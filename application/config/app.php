<?php

defined('BASEPATH') OR exit('No direct script access allowed');


$config['app_version'] = '0.3.5';
$config['app_name'] = 'Ảnh Cưới';


$config['cloud_base'] = 'https://api.jagame.vn';
$config['cloud_domain'] = 'jagame.vn';

// Nút "♡ Ủng hộ" trong trang quản trị: DONATE_URL nếu có; chế độ nhiều cặp (có BASE_DOMAIN) thì trang ủng hộ của
// nền tảng (platform/index.php, /ung-ho); còn lại là trang của tác giả phần mềm gốc.
$config['donate_url'] = getenv('DONATE_URL') ?: (getenv('BASE_DOMAIN')
? (getenv('SITE_SCHEME') ?: 'https') . '://' . getenv('BASE_DOMAIN') . (getenv('SITE_PORT') ?: '') . '/ung-ho'
: 'https://jagame.vn/donate/');

$config['hosted_domain'] = 'thiep.site';

$config['photo_medium_px'] = 2048;
$config['photo_thumb_px'] = 640;
$config['photo_jpeg_quality'] = 84;
$config['photo_allowed_mime'] = array(
'image/jpeg' => 'jpg',
'image/png' => 'png',
'image/webp' => 'webp',
'image/gif' => 'gif',
);

$config['photo_owner_max_mb'] = 60;

$config['photo_owner_max_mp'] = 120;
$config['photo_guest_max_mp'] = 32; 




// Giới hạn: array(số lần, cửa sổ giây, số lần mỗi IP | NULL, số lần chung mọi người | NULL).
// Khi phần tử thứ 3 là NULL, số đầu là giới hạn mỗi IP. IPv6 được gộp theo dải /64 (xem Ratelimit::ip_bucket).
// Phần tử thứ 4 là trần chung: chặn kẻ đổi IP liên tục (đoán mật khẩu, làm đầy ổ đĩa).
$config['rl_login'] = array(10, 900, NULL, 100);
$config['rl_wish'] = array(10, 3600, 200);
$config['rl_rsvp'] = array(5, 3600, 200);
$config['rl_album_password'] = array(15, 900, 30, 300);
$config['rl_guest_upload'] = array(300, 3600, 1000, 3000);


$config['rl_slug_miss'] = array(30, 600, 300, 2000);

// Ảnh khách: luôn chừa lại ít nhất chừng này MB trống trên ổ, và tối đa chừng này ảnh chờ duyệt.
$config['guest_min_free_mb'] = 2048;
$config['guest_max_pending'] = 2000;

$config['rl_rsvp_invite'] = array(10, 3600, 200);

$config['rl_zip'] = array(6, 3600, 60);