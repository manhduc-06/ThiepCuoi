<?php

defined('BASEPATH') OR exit('No direct script access allowed');










$config['content_text'] = array(
'groom_name' => array('', 80, FALSE),
'bride_name' => array('', 80, FALSE),
'c.hero_eyebrow' => array('Chúng mình về chung một nhà', 80, FALSE),
'c.save_title' => array('Trân trọng kính mời', 80, FALSE),
'c.save_text' => array('Sự hiện diện của bạn là niềm vinh hạnh cho gia đình chúng mình.', 400, TRUE),
'c.couple_title' => array('Cô dâu & Chú rể', 60, FALSE),
'c.bride_fullname' => array('', 80, FALSE),
'c.bride_info' => array('', 300, TRUE),
'c.groom_fullname' => array('', 80, FALSE),
'c.groom_info' => array('', 300, TRUE),
'c.event_title' => array('Lễ thành hôn', 60, FALSE),
// Tiêu đề lễ trên thiệp gửi khách nhà gái (thiệp nhà trai / khách chung dùng c.event_title).
'c.event_title_bride' => array('Lễ vu quy', 60, FALSE),
'c.event_text' => array('Cùng đếm ngược tới ngày vui của chúng mình', 200, TRUE),
'c.location_title' => array('Địa điểm tổ chức', 60, FALSE),
'c.quote' => array('Yêu nhau không phải là nhìn nhau, mà là cùng nhau nhìn về một hướng.', 200, TRUE),
'c.album_title' => array('Album ảnh cưới', 60, FALSE),
'c.upload_title' => array('Bạn có ảnh đẹp của chúng mình?', 80, FALSE),
'c.upload_text' => array('Gửi tặng cô dâu chú rể những khoảnh khắc bạn đã chụp — không cần cài ứng dụng, không cần đăng nhập.', 300, TRUE),
'c.rsvp_title' => array('Xác nhận tham dự', 60, FALSE),
'c.rsvp_text' => array('Hãy cho chúng mình biết bạn có đến chung vui được không nhé!', 300, TRUE),
'c.invite_greeting' => array('Trân trọng kính mời', 60, FALSE),
'c.wishes_title' => array('Lời chúc', 60, FALSE),
'c.footer' => array('Cảm ơn bạn đã đến chung vui cùng chúng mình ♡', 200, TRUE),
);
$config['content_images'] = array(
'img.hero_main' => 'Ảnh chính đầu trang',
'img.hero_left' => 'Ảnh nghiêng bên trái',
'img.hero_right' => 'Ảnh nghiêng bên phải',
'img.bride' => 'Ảnh cô dâu',
'img.groom' => 'Ảnh chú rể',
'img.event' => 'Ảnh mục lễ cưới',
'img.quote' => 'Ảnh nền câu trích dẫn',
);

$config['content_events'] = array(
array('title' => 'Lễ vu quy', 'place' => 'Tư gia nhà gái', 'address' => '', 'time' => '', 'map' => ''),
array('title' => 'Tiệc cưới', 'place' => 'Nhà hàng', 'address' => '', 'time' => '', 'map' => ''),
);


$config['music_builtin'] = array(
'canon-in-d' => array('Canon in D — Pachelbel', 'canon-in-d.mp3', 'Kevin MacLeod (incompetech.com) · CC BY 3.0'),
'bridal-chorus' => array('Bridal Chorus (Here Comes the Bride) — Wagner', 'bridal-chorus.mp3', 'Kevin MacLeod (incompetech.com) · CC BY 3.0'),
'wedding-march' => array('Wedding March — Mendelssohn', 'wedding-march.mp3', ''),
'air-on-g-string' => array('Air on the G String — Bach', 'air-on-g-string.mp3', ''),
'ave-maria' => array('Ave Maria — Schubert / Gounod', 'ave-maria.mp3', ''),

'reawakening' => array('Reawakening — piano & cello', 'reawakening.mp3', 'Kevin MacLeod (incompetech.com) · CC BY 4.0'),
'there-is-romance' => array('There is Romance — piano', 'there-is-romance.mp3', 'Kevin MacLeod (incompetech.com) · CC BY 4.0'),
'gymnopedie-no-1' => array('Gymnopédie No. 1 — Satie', 'gymnopedie-no-1.mp3', 'Kevin MacLeod (incompetech.com) · CC BY 4.0'),
'prelude-in-c' => array('Prelude in C — Bach', 'prelude-in-c.mp3', 'Kevin MacLeod (incompetech.com) · CC BY 4.0'),
'canon-in-d-harps' => array('Canon in D (hai đàn hạc) — Pachelbel', 'canon-in-d-harps.mp3', 'Kevin MacLeod (incompetech.com) · CC BY 4.0'),
'crinoline-dreams' => array('Crinoline Dreams — piano & dây', 'crinoline-dreams.mp3', 'Kevin MacLeod (incompetech.com) · CC BY 4.0'),
'procession-of-the-king' => array('Procession of the King — dàn dây', 'procession-of-the-king.mp3', 'Kevin MacLeod (incompetech.com) · CC BY 4.0'),
);



$config['music_suggestions'] = array(
'beautiful-in-white' => array('Beautiful In White', 'Shane Filan'),
'cham-em-mot-doi' => array('Chăm Em Một Đời', 'Đức Phúc'),
'du-cho-tan-the' => array('Dù Cho Tận Thế', 'Erik'),
'em-dong-y' => array('Em Đồng Ý (I Do)', 'Đức Phúc x 911'),
'i-do' => array('I Do', '911'),
'is-it-you' => array('Is It You (I Have Loved)', 'Dana Winner'),
'ngay-nay-nguoi-con-gai-nay' => array('Ngày Này, Người Con Gái Này', 'Vũ Cát Tường'),
'tonight-i-celebrate' => array('Tonight I Celebrate My Love', 'Peabo Bryson & Roberta Flack'),
'when-you-tell-me' => array('When You Tell Me That You Love Me', 'Westlife ft. Diana Ross'),
'hon-ca-yeu' => array('Hơn Cả Yêu', 'Đức Phúc'),
'cuoi-nhau-di' => array('Cưới Nhau Đi (Yes I Do)', 'Bùi Anh Tuấn & Hiền Hồ'),
'ngay-dau-tien' => array('Ngày Đầu Tiên', 'Đức Phúc'),
'noi-nay-co-anh' => array('Nơi Này Có Anh', 'Sơn Tùng M-TP'),
'mot-nha' => array('Một Nhà', 'Da LAB'),
'sugar' => array('Sugar', 'Maroon 5'),
'marry-you' => array('Marry You', 'Bruno Mars'),
'thuyen-hoa' => array('Thuyền Hoa', 'Quang Linh'),
'dam-cuoi-tren-duong-que' => array('Đám Cưới Trên Đường Quê', 'Hoàng Thi Thơ'),
);



$config['card_styles'] = array(
'classic' => array('name' => 'Phong bì cổ điển', 'name_en' => 'Classic Envelope', 'desc' => 'Nắp phong bì lật mở, thiệp nhô lên', 'desc_en' => 'The flap lifts and the card rises out', 'open_ms' => 650),
'gatefold' => array('name' => 'Cổng hai cánh', 'name_en' => 'Double Gate', 'desc' => 'Tháo đai, hai cánh cửa vòm mở ra hai bên', 'desc_en' => 'Untie the band and two arched doors swing open', 'open_ms' => 1250),
'book' => array('name' => 'Thiệp gấp đôi', 'name_en' => 'Folded Card', 'desc' => 'Bìa cứng lật mở như một cuốn sách', 'desc_en' => 'A hardcover that opens like a book', 'open_ms' => 1150),
'popup' => array('name' => 'Pop-up 3D', 'name_en' => '3D Pop-up', 'desc' => 'Mở thiệp, cổng hoa và bảng tên dựng đứng', 'desc_en' => 'Open it and a floral arch with a name plaque stands up', 'open_ms' => 800),
'scroll' => array('name' => 'Cuộn thư', 'name_en' => 'Scroll', 'desc' => 'Tháo nơ, cuộn giấy trải dài xuống', 'desc_en' => 'Untie the ribbon and the scroll unrolls', 'open_ms' => 700),
'wax' => array('name' => 'Sáp niêm phong', 'name_en' => 'Wax Seal', 'desc' => 'Lật phong bì, bẻ dấu sáp, thiệp trượt ra', 'desc_en' => 'Turn the envelope, break the seal, the card slides out', 'open_ms' => 1850),
'foil-flip' => array('name' => 'Ép kim xoay', 'name_en' => 'Foil Flip', 'desc' => 'Thiệp dày ép kim nhũ, xoay 180° ra mặt sau', 'desc_en' => 'A thick foil-pressed card that flips 180°', 'open_ms' => 520),
'lasercut' => array('name' => 'Ren cắt laser', 'name_en' => 'Laser-cut Lace', 'desc' => 'Lớp ren cắt laser nhấc lên, nhiều lớp chiều sâu', 'desc_en' => 'A laser-cut lace layer lifts, layered depth', 'open_ms' => 950),
'songhy-tri' => array('name' => 'Song hỷ ba tấm', 'name_en' => 'Double Happiness Trifold', 'desc' => 'Thiệp đỏ truyền thống, hai tấm bên lần lượt mở', 'desc_en' => 'Traditional red card, side panels open one by one', 'open_ms' => 1500),
'watercolor' => array('name' => 'Màu nước & ảnh', 'name_en' => 'Watercolour & Photo', 'desc' => 'Hoa lá màu nước, ảnh polaroid nhấc lên', 'desc_en' => 'Watercolour florals with a polaroid that lifts', 'open_ms' => 1000),



);



$config['themes'] = array(
'serenity' => array('name' => 'Bạc hà', 'name_en' => 'Mint', 'desc' => 'Xanh bạc hà dịu, thoáng và nhẹ nhàng', 'desc_en' => 'Soft mint green, airy and gentle', 'accent' => '#6fae95', 'hero_sizes' => '(max-width: 760px) 88vw, 680px'),
'lavender' => array('name' => 'Oải hương', 'name_en' => 'Lavender', 'desc' => 'Tím oải hương lãng mạn, nét chữ bay', 'desc_en' => 'Romantic lavender with flowing type', 'accent' => '#c77aab', 'hero_sizes' => '(max-width: 760px) 88vw, 680px'),
'summer' => array('name' => 'Hoàng hôn', 'name_en' => 'Sunset', 'desc' => 'Cam hoàng hôn ấm áp, tươi vui', 'desc_en' => 'Warm sunset orange, bright and cheerful', 'accent' => '#ec7a63', 'hero_sizes' => '(max-width: 760px) 88vw, 680px'),
'thiep' => array('name' => 'Thiệp cưới', 'name_en' => 'Wedding Card', 'desc' => 'Ảnh tràn màn hình như tấm thiệp in', 'desc_en' => 'Full-screen photo like a printed card', 'accent' => '#e7746f', 'hero_sizes' => '100vw'),
'hoangkim' => array('name' => 'Hoàng kim', 'name_en' => 'Golden', 'desc' => 'Vàng kim sang trọng trên nền kem', 'desc_en' => 'Luxurious gold on a cream background', 'accent' => '#8f6d33', 'hero_sizes' => '(max-width: 760px) 62vw, 450px'),
'songhy' => array('name' => 'Song hỷ', 'name_en' => 'Double Happiness', 'desc' => 'Đỏ truyền thống, chữ Song Hỷ', 'desc_en' => 'Traditional red with the Double Happiness sign', 'accent' => '#b3261e', 'hero_sizes' => '(max-width: 760px) 70vw, 320px'),
'tapchi' => array('name' => 'Tạp chí', 'name_en' => 'Magazine', 'desc' => 'Đen trắng tối giản kiểu tạp chí', 'desc_en' => 'Minimal black-and-white magazine style', 'accent' => '#1c1c1c', 'hero_sizes' => '(max-width: 760px) 100vw, 52vw'),
'vuonhoa' => array('name' => 'Vườn hoa', 'name_en' => 'Garden', 'desc' => 'Xanh lá vườn hoa, mộc mạc tự nhiên', 'desc_en' => 'Garden green, rustic and natural', 'accent' => '#5c6b3a', 'hero_sizes' => '(max-width: 760px) 68vw, 310px'),
'demsao' => array('name' => 'Đêm sao', 'name_en' => 'Starry Night', 'desc' => 'Xanh đêm sao, lấp lánh huyền ảo', 'desc_en' => 'Starry night blue, softly sparkling', 'accent' => '#2a3868', 'hero_sizes' => '(max-width: 760px) 65vw, 300px'),
'datnung' => array('name' => 'Đất nung', 'name_en' => 'Terracotta', 'desc' => 'Nâu đất nung ấm, phong cách boho', 'desc_en' => 'Warm terracotta, boho style', 'accent' => '#a94f32', 'hero_sizes' => '(max-width: 760px) 68vw, 410px'),
'hongphan' => array('name' => 'Hồng phấn', 'name_en' => 'Blush', 'desc' => 'Hồng phấn, phong bì sáp, ảnh polaroid', 'desc_en' => 'Blush pink, wax-sealed envelope, polaroid photos', 'accent' => '#9c4565', 'hero_sizes' => '100vw'),



);