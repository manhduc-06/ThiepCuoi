<?php
 defined('BASEPATH') OR exit('No direct script access allowed');

$c = $content;
list($m1, $m2) = $monogram;
$real = function ($k) use ($img) { return !empty($img[$k]) && empty($img[$k]['borrowed']); };
$map_q = function ($ev) { return trim($ev['address'] !== '' ? $ev['address'] : $ev['place']); };

$has_img = function ($k) use ($img) { return !empty($img[$k]); };
// Album chưa có ảnh (vd "Ảnh từ khách mời" khi khách chưa gửi) không hiện cho khách — kể cả khi chủ nhà "xem như khách".
// Chỉ lúc đang chỉnh sửa mới hiện (kèm nhãn) để chủ nhà biết album đó có tồn tại.
$shown = array_filter($albums, function ($a) use ($draft) { return $draft || (int) $a['photo_count'] > 0; });
$show_gallery = $draft || $gallery_total > 0 || $shown;

$name_len = max(mb_strlen(trim($c->text('groom_name'))), mb_strlen(trim($c->text('bride_name'))));
$names_cls = $name_len >= 17 ? ' names--xl' : ($name_len >= 11 ? ' names--long' : '');
$has_addr = count(array_filter($events, function ($ev) { return trim($ev['address']) !== ''; })) > 0;

$svg_laurel = '<svg viewBox="0 0 60 110" aria-hidden="true" class="%s">'
. '<g fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round">'
. '<path class="dr" pathLength="1" d="M48 106C22 92 8 62 16 8"/>'
. '<path class="dr" pathLength="1" d="M40 99c-9 1-17-3-21-10 9-1 16 3 21 10zM41 99c1-9 7-15 15-17 0 9-6 15-15 17z"/>'
. '<path class="dr" pathLength="1" d="M28 86c-9-1-15-7-17-15 9 1 15 7 17 15zM29 85c3-8 10-12 18-12-2 8-9 12-18 12z"/>'
. '<path class="dr" pathLength="1" d="M20 70c-8-3-12-10-12-18 8 3 12 10 12 18zM21 69c4-7 11-10 19-9-3 8-11 10-19 9z"/>'
. '<path class="dr" pathLength="1" d="M16 53c-7-4-9-12-7-19 7 4 9 11 7 19zM17 52c5-6 13-8 20-5-4 7-12 8-20 5z"/>'
. '<path class="dr" pathLength="1" d="M15 36c-6-5-7-13-3-19 6 5 6 12 3 19zM16 35c6-5 14-5 20-1-5 6-13 6-20 1z"/>'
. '<path class="dr" pathLength="1" d="M16 9c2-4 6-7 10-7-1 5-5 7-10 7z"/>'
. '</g>' . '</svg>';
$svg_branch = '<svg viewBox="0 0 160 240" aria-hidden="true" class="%s">'
. '<g fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
. '<path class="dr" pathLength="1" d="M150 238C120 200 96 160 84 118S70 44 96 6"/>'
. '<path class="dr" pathLength="1" d="M120 196c-22-2-40 6-52 20 20 4 38-4 52-20zM120 196c-18 8-32 14-52 20"/>'
. '<path class="dr" pathLength="1" d="M98 152c-6-20-22-32-40-36 6 18 20 30 40 36zM98 152C84 138 72 128 58 116"/>'
. '<path class="dr" pathLength="1" d="M90 128c14-14 32-18 50-14-12 14-30 18-50 14zM90 128c18-6 32-10 50-14"/>'
. '<path class="dr" pathLength="1" d="M84 86c-14-14-18-32-12-48 12 14 16 32 12 48zM84 86C78 70 74 56 72 38"/>'
. '<path class="dr" pathLength="1" d="M88 62c10-16 26-24 44-22-8 16-26 24-44 22zM88 62c14-10 28-18 44-22"/>'
. '<path class="dr" pathLength="1" d="M96 6c10 2 16 10 14 20-10-2-16-10-14-20z"/>'
. '<circle class="dr" pathLength="1" cx="58" cy="176" r="15"/>'
. '<path class="dr" pathLength="1" d="M58 161c-8-12 8-18 10-6M73 176c12-6 16 10 4 12M58 191c6 12-10 16-10 4M43 176c-12 4-14-12-2-12"/>'
. '<path d="M66 184C80 200 104 206 124 210" class="dr" pathLength="1"/>'
. '</g>'
. '<g fill="currentColor"><circle cx="58" cy="176" r="4" opacity=".5"/><circle cx="106" cy="30" r="2.5" opacity=".5"/><circle cx="46" cy="104" r="2.5" opacity=".45"/></g>' . '</svg>';
$svg_rainbow = '<svg viewBox="0 0 200 104" aria-hidden="true" class="%s">'
. '<g fill="none" stroke-linecap="round" stroke-width="15">'
. '<path class="dr" pathLength="1" d="M12 100A88 88 0 0 1 188 100" stroke="#b5583a"/>'
. '<path class="dr" pathLength="1" d="M38 100A62 62 0 0 1 162 100" stroke="#d9a05b"/>'
. '<path class="dr" pathLength="1" d="M64 100A36 36 0 0 1 136 100" stroke="#e7c3ad"/>'
. '</g>' . '</svg>';
$deco = function ($svg, $class) { return sprintf($svg, $class); };


$date_html = function ($t) {
$i = strpos($t, ', ');
$nw = function ($s) { return '<span class="dt-nw">' . e($s) . '</span>'; };
$rest = $i === FALSE ? $t : substr($t, $i + 2);
$rest_html = lang_cur() !== 'vi' ? implode(' · ', array_map($nw, explode(' · ', $rest))) : $nw($rest);
return $i === FALSE ? $rest_html : e(substr($t, 0, $i + 1)) . ' ' . $rest_html;
};
$ornament = '<svg class="orn" viewBox="0 0 200 200" aria-hidden="true"><use href="#orn-flower"/></svg>';

$wd_ts = $date ? strtotime($date) : 0;
$wd_days = array(); 
for ($wi = 0; $wi < 7; $wi++) { $wd_days[$wi] = lang_weekday($wi); }
$wd_en = lang_cur() === 'en';
$time_txt = fmt_time($time); 

$wd_month = function ($ts) { return lang_month(date('n', $ts)); };
$edit_date_attr = ' data-edit-date data-date="' . e($date) . '" data-time="' . e($time) . '"';

$dlock = '';
if ($wd_ts) {
$dlock = '<div class="dlock"' . $edit_date_attr . '>'
. '<span class="dl-wd">' . e($wd_days[(int) date('w', $wd_ts)]) . ($time !== '' ? ' <i>·</i> ' . e($time_txt) : '') . '</span>'
. '<span class="dl-row"><span class="dl-m">' . e($wd_month($wd_ts)) . '</span><b class="dl-d">' . date('d', $wd_ts) . '</b>'
. '<span class="dl-y">' . date('Y', $wd_ts) . '</span></span>'
. '<span class="dl-t">' . ($time !== '' ? e($time_txt) : '') . '</span>'
. '</div>';
}

$cal = '';
if ($wd_ts) {
$first = strtotime(date('Y-m-01', $wd_ts));
$lead_days = (int) date('N', $first) - 1;
$dim = (int) date('t', $wd_ts);
$today = (int) date('j', $wd_ts);
$cal = '<div class="cal"' . $edit_date_attr . '><p class="cal-h">' . e($wd_month($wd_ts)) . ' <i>·</i> ' . date('Y', $wd_ts) . '</p><div class="cal-g">';
foreach (array(1, 2, 3, 4, 5, 6, 0) as $wn) { $cal .= '<b>' . e(lang_weekday($wn, TRUE)) . '</b>'; }
for ($i = 0; $i < $lead_days; $i++) { $cal .= '<span></span>'; }
for ($dd = 1; $dd <= $dim; $dd++) { $cal .= '<span' . ($dd === $today ? ' class="on"' : '') . '>' . $dd . '</span>'; }
$cal .= '</div></div>';
}

$starchart = '';
if ($wd_ts) {
mt_srand(crc32($date));
$st = '';
$pts = array();
for ($i = 0; $i < 90; $i++) {
$a = mt_rand(0, 6283) / 1000; $r = sqrt(mt_rand(0, 1000) / 1000) * 128;
$x = round(160 + $r * cos($a), 1); $y = round(160 + $r * sin($a), 1);
$s = mt_rand(0, 100); $rad = $s > 94 ? 2.1 : ($s > 75 ? 1.4 : ($s > 40 ? .9 : .55));
$st .= '<circle cx="' . $x . '" cy="' . $y . '" r="' . $rad . '"' . ($s > 90 ? ' class="tw"' : '') . '/>';
}

$line_a = array(array(76, 118), array(104, 96), array(128, 124), array(160, 150));
$line_b = array(array(244, 110), array(214, 92), array(192, 126), array(160, 150));
$tail = array(array(160, 150), array(150, 190), array(168, 222), array(144, 246));
$poly = function ($p) { return implode(' ', array_map(function ($q) { return $q[0] . ',' . $q[1]; }, $p)); };
$cons = '';
foreach (array_merge($line_a, $line_b, $tail) as $q) { $cons .= '<circle cx="' . $q[0] . '" cy="' . $q[1] . '" r="2.6"/>'; }
$ticks = '';
for ($i = 0; $i < 72; $i++) {
$a = $i * M_PI / 36; $r1 = $i % 6 === 0 ? 140 : 144;
$ticks .= '<line x1="' . round(160 + $r1 * cos($a), 1) . '" y1="' . round(160 + $r1 * sin($a), 1) . '" x2="' . round(160 + 148 * cos($a), 1) . '" y2="' . round(160 + 148 * sin($a), 1) . '"/>';
}
$ring_txt = ($wd_en ? 'The night sky of ' . date('F j, Y', $wd_ts)
: (lang_cur() === 'vi' ? 'Bầu trời đêm ' . date('d', $wd_ts) . ' tháng ' . (int) date('n', $wd_ts) . ' năm ' . date('Y', $wd_ts)
: __('Bầu trời đêm {ngay}', array('ngay' => vn_date($date, FALSE))))) . ' · ' . $couple . ' · ';
$starchart = '<figure class="starchart" aria-hidden="true"><svg viewBox="-12 -12 344 344">'
. '<defs><path id="sc-ring" d="M160 160m-156 0a156 156 0 1 1 312 0a156 156 0 1 1-312 0"/>'
. '<radialGradient id="sc-bg"><stop offset="0" stop-color="#1d2a5c"/><stop offset="1" stop-color="#0c1331"/></radialGradient></defs>'
. '<circle cx="160" cy="160" r="148" fill="url(#sc-bg)"/>'
. '<g class="sc-grid" fill="none"><circle cx="160" cy="160" r="148"/><circle cx="160" cy="160" r="100"/><circle cx="160" cy="160" r="52"/>'
. '<path d="M160 12V308M12 160H308M55 55L265 265M265 55L55 265"/></g>'
. '<g class="sc-ticks">' . $ticks . '</g>'
. '<g class="sc-stars">' . $st . '</g>'
. '<g class="sc-cons"><polyline points="' . $poly($line_a) . '"/><polyline points="' . $poly($line_b) . '"/><polyline points="' . $poly($tail) . '"/></g>'
. '<g class="sc-cdots">' . $cons . '</g>'
. '<path class="sc-star" d="M160 138C161.4 147 163 148.6 172 150 163 151.4 161.4 153 160 162 158.6 153 157 151.4 148 150 157 148.6 158.6 147 160 138Z"/>'
. '<text class="sc-txt"><textPath href="#sc-ring" startOffset="0">' . e(mb_strtoupper(str_repeat($ring_txt, 2))) . '</textPath></text>'
. '</svg></figure>';
mt_srand();
}

$constel = '<svg class="constel" viewBox="0 0 160 70" aria-hidden="true"><polyline points="4,40 38,22 70,44 96,18 124,36 156,28" fill="none" stroke="currentColor" stroke-width=".8" stroke-dasharray="2 3"/>'
. '<g fill="currentColor"><circle cx="4" cy="40" r="2"/><circle cx="38" cy="22" r="1.6"/><circle cx="70" cy="44" r="2.2"/><circle cx="96" cy="18" r="1.5"/><circle cx="124" cy="36" r="1.8"/><circle cx="156" cy="28" r="2"/></g>'
. '<path d="M80 50c-1.6-2.4-5.6-1.9-5.6 1 0 2.4 5.6 5.8 5.6 5.8s5.6-3.4 5.6-5.8c0-2.9-4-3.4-5.6-1z" fill="currentColor"/></svg>';
// Khách nhà gái (mở từ thiệp riêng): tên cô dâu đứng trước.
$bride_first = !$draft && isset($guest_side) && $guest_side === 'bride';
$names_line = $bride_first
? '<span>' . e($c->text('bride_name')) . '</span><i>&amp;</i><span>' . e($c->text('groom_name')) . '</span>'
: '<span>' . e($c->text('groom_name')) . '</span><i>&amp;</i><span>' . e($c->text('bride_name')) . '</span>';




$with_alt = function ($html, $alt, $eager = FALSE) {
$html = str_replace('alt=""', 'alt="' . e($alt) . '"', $html);
return $eager ? str_replace('loading="lazy"', 'loading="eager" fetchpriority="high"', $html)
: str_replace('loading="lazy"', 'loading="lazy" fetchpriority="low"', $html);
};

$ics_btn = function ($d, $label, $class) {
if (!$d) {
return '';
}
return '<button type="button" class="' . $class . '" data-ics="' . e($d['start']) . '" data-ics-start="' . e($d['start']) . '"'
. ' data-ics-end="' . e($d['end']) . '"' . ($d['allday'] ? ' data-ics-allday="1"' : '')
. ' data-ics-title="' . e($d['title']) . '" data-ics-location="' . e($d['location']) . '" data-ics-uid="' . e($d['uid']) . '">'
. e($label) . '</button>';
};

$rsvp_inv = $invite ?: $rsvp_prev;
$v = $voice;

$msg_note = ($settings['wishes_enabled'] ?? '1') !== '1' ? ''
: (($settings['wishes_approval'] ?? '0') === '1' ? __('Lời nhắn sẽ hiện trong mục Lời chúc trên trang cưới sau khi cô dâu chú rể duyệt.')
: __('Lời nhắn sẽ hiện trong mục Lời chúc trên trang cưới.'));



$pro_parts = array();
if (pro_enabled()) {
foreach ($themes as $pk => $pt) {
$pf = APPPATH . 'views/public/pro/' . $pk . '.php';
if (!empty($pt['pro']) && ($draft || $pk === $theme) && is_file($pf)) {
$pro_parts[] = $pf;
}
}
}
?>
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <defs>
    <symbol id="orn-flower" viewBox="0 0 200 200">
      <g fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity=".9">
        <path d="M30 185 C70 150 95 120 110 70 C118 45 140 25 170 18"/>
        <path d="M70 150 C55 140 42 138 28 142 C42 150 55 154 70 150Z" fill="currentColor" fill-opacity=".25"/>
        <path d="M92 118 C98 100 96 86 88 74 C84 90 84 104 92 118Z" fill="currentColor" fill-opacity=".25"/>
        <path d="M104 92 C120 88 132 80 138 66 C122 70 110 78 104 92Z" fill="currentColor" fill-opacity=".25"/>
        <path d="M128 42 C136 30 136 18 130 8 C122 20 122 32 128 42Z" fill="currentColor" fill-opacity=".25"/>
      </g>
      <g fill="currentColor">
        <circle cx="170" cy="18" r="9" opacity=".55"/><circle cx="160" cy="12" r="7" opacity=".4"/>
        <circle cx="178" cy="28" r="6" opacity=".4"/><circle cx="170" cy="18" r="3.5" fill="#fff"/>
        <circle cx="52" cy="160" r="4" opacity=".5"/><circle cx="118" cy="56" r="3" opacity=".5"/>
      </g>
    </symbol>
    <symbol id="ico-rings" viewBox="0 0 64 64">
      <circle cx="25" cy="38" r="14" fill="none" stroke="currentColor" stroke-width="4"/>
      <circle cx="39" cy="38" r="14" fill="none" stroke="currentColor" stroke-width="4"/>
      <path d="M32 18c-2-3-7-2.4-7 1.2 0 3 7 7.3 7 7.3s7-4.3 7-7.3c0-3.6-5-4.2-7-1.2z" fill="currentColor"/>
    </symbol>
    <!-- Họa tiết riêng của 6 giao diện mới (ẩn mặc định, wedding-themes.css bật theo data-theme). -->
    <symbol id="d-songhy" viewBox="0 0 100 100">
      <g fill="none" stroke="currentColor" stroke-width="5.5" stroke-linecap="square" stroke-linejoin="miter">
        <path d="M8 12H45M55 12H92M26.5 3V24M73.5 3V24M15 24H38M62 24H85M15 32H38V45H15ZM62 32H85V45H62Z"/>
        <path d="M19 52l3 6M34 52l-3 6M66 52l3 6M81 52l-3 6M5 64H95M15 73H38V95H15ZM62 73H85V95H62Z"/>
      </g>
    </symbol>
    <symbol id="d-corner" viewBox="0 0 60 60">
      <path d="M2 58V2H58M10 50V10H50M18 42V18H36V36H26V26" fill="none" stroke="currentColor" stroke-width="2.4"/>
    </symbol>
    <symbol id="d-lantern" viewBox="0 0 60 120">
      <path d="M30 0V16" stroke="#e6c071" stroke-width="1.5"/>
      <rect x="19" y="15" width="22" height="7" rx="1.5" fill="#e6c071"/>
      <ellipse cx="30" cy="54" rx="25" ry="32" fill="#c8261c"/>
      <ellipse cx="30" cy="54" rx="12" ry="32" fill="none" stroke="#e6c071" stroke-width="1.3" opacity=".8"/>
      <path d="M30 22V86M6 54H54" stroke="#e6c071" stroke-width="1.1" opacity=".55"/>
      <ellipse cx="22" cy="44" rx="6" ry="12" fill="#fff" opacity=".12"/>
      <rect x="19" y="85" width="22" height="7" rx="1.5" fill="#e6c071"/>
      <path d="M30 92V118M25 93V112M35 93V112" stroke="#e6c071" stroke-width="1.6" stroke-linecap="round"/>
    </symbol>
    <symbol id="d-moon" viewBox="0 0 100 100">
      <path d="M62 6A44 44 0 1 0 94 72A36 36 0 1 1 62 6Z" fill="currentColor"/>
    </symbol>
    <symbol id="d-star" viewBox="0 0 20 20">
      <path d="M10 0C11 7 13 9 20 10 13 11 11 13 10 20 9 13 7 11 0 10 7 9 9 7 10 0Z" fill="currentColor"/>
    </symbol>
  </defs>
</svg>
<?php $slot = 'defs'; foreach ($pro_parts as $pf) include $pf; ?>
<?php if (!empty($invite_card)) $this->load->view('public/_invite_card', array('msg_note' => $msg_note));  ?>

<?php $fx = $c->fx(); ?>
<div class="fx" data-fx="<?= e($fx) ?>" aria-hidden="true"><?php for ($i = 0; $i < 14; $i++): ?><i></i><?php endfor; ?></div>
<?php $slot = 'page'; foreach ($pro_parts as $pf) include $pf; ?>

<header class="wd-nav" data-nav>
  <nav class="wd-nav-l">
    <a href="#couple"><?= e(__('Cặp đôi')) ?></a><a href="#event"><?= e(__('Lễ cưới')) ?></a><a href="#location"><?= e(__('Địa điểm')) ?></a>
  </nav>
  <a class="wd-mono" href="#top" aria-label="<?= e(__('Về đầu trang')) ?>"><span><?= e($m1) ?></span><i>♡</i><span><?= e($m2) ?></span></a>
  <nav class="wd-nav-r">
    <?php if ($show_gallery): ?><a href="#gallery">Album</a><?php endif; ?>
    <?php if ($settings['guest_upload'] === '1'): ?><a href="<?= base_url('gui-anh') ?>"><?= e(__('Gửi ảnh')) ?></a><?php endif; ?>
    <?php if ($rsvp_on): ?><a href="#rsvp"><?= e(__('Xác nhận')) ?></a><?php endif; ?>
    <?php if ($settings['wishes_enabled'] === '1'): ?><a href="#loi-chuc"><?= e(__('Lời chúc')) ?></a><?php endif; ?>
  </nav>
  <button class="wd-burger" type="button" aria-label="<?= e(__('Mở menu')) ?>" aria-expanded="false" data-burger>☰</button>
</header>

<?= lang_switch_html($draft) ?>
<?php if ($music || $draft): ?>
<div class="music" data-music data-autoplay="<?= $settings['music_autoplay'] === '1' ? '1' : '0' ?>">
  <button type="button" class="music-btn" aria-label="<?= e(__('Bật/tắt nhạc')) ?>" data-music-toggle>♫</button>
  <?php if ($music): ?>
    <audio src="<?= e($music['url']) ?>" loop preload="none"></audio>
    <span class="music-hint" data-music-hint hidden>♫ <?= e(__('Chạm để nghe nhạc')) ?></span>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php 
if ($music && !$draft && $settings['music_autoplay'] === '1' && empty($invite_card)): ?>
<div class="wd-cover" data-cover hidden>
  <div class="wd-cover-in">
    <p class="wd-cover-mono"><span><?= e($m1) ?></span><i>♡</i><span><?= e($m2) ?></span></p>
    <p class="wd-cover-eyebrow"><?= e(__('Thiệp mời cưới')) ?></p>
    <p class="wd-cover-names"><span><?= e($c->text('groom_name')) ?></span><i>&amp;</i><span><?= e($c->text('bride_name')) ?></span></p>
    <?php if ($date): ?><p class="wd-cover-date"><?= e(fmt_dmy($date)) ?></p><?php endif; ?>
    <button type="button" class="wd-cover-btn" data-cover-open><?= e(__('Mở thiệp')) ?> <span aria-hidden="true">♫</span></button>
  </div>
</div>
<?php endif; ?>

<section id="top" class="intro<?= !$draft && !$has_img('img.hero_main') ? ' intro--noimg' : '' ?>">
  <?php $slot = 'hero'; foreach ($pro_parts as $pf) include $pf; ?>
  <div class="hero-deco" aria-hidden="true">
    <div class="sky"><i class="sky-a"></i><i class="sky-b"></i><i class="sky-c"></i><b class="shoot"></b></div>
    <svg class="hd-moon"><use href="#d-moon"/></svg>
    <svg class="hd-corner hc-1"><use href="#d-corner"/></svg><svg class="hd-corner hc-2"><use href="#d-corner"/></svg>
    <svg class="hd-corner hc-3"><use href="#d-corner"/></svg><svg class="hd-corner hc-4"><use href="#d-corner"/></svg>
    <svg class="hd-lantern hl-1"><use href="#d-lantern"/></svg><svg class="hd-lantern hl-2"><use href="#d-lantern"/></svg>
  </div>
  <div class="masthead" aria-hidden="true"><span><?= e(__('Số đặc biệt')) ?></span><b><?= e($m1) ?> &amp; <?= e($m2) ?></b><span><?= $wd_ts ? e(fmt_dmy($date)) : e(__('Ấn bản cưới')) ?></span></div>
  <svg class="hd-mark" aria-hidden="true"><use href="#d-songhy"/></svg>
  <div class="photowall">
    <?= $with_alt(ed_img($img['img.hero_left'], 'img.hero_left', 'pw pw-l', 'm', __('Ảnh nghiêng trái')), __('Ảnh cưới {cap_doi}', array('cap_doi' => $couple))) ?>
    <?= $with_alt(ed_img($img['img.hero_right'], 'img.hero_right', 'pw pw-r', 'm', __('Ảnh nghiêng phải')), __('Ảnh cưới {cap_doi}', array('cap_doi' => $couple))) ?>
  </div>
  <div class="intro-main">
    <?= $with_alt(ed_img($img['img.hero_main'], 'img.hero_main', 'photo main-photo', 'm', __('Ảnh chính')), __('Ảnh cưới {cap_doi}', array('cap_doi' => $couple)), TRUE) ?>
    <?= str_replace('class="orn"', 'class="orn orn-1"', $ornament) ?>
    <div class="pm-deco" aria-hidden="true">
      <span class="hd-sun"></span><?= $deco($svg_rainbow, 'hd-rainbow') ?>
      <?= $deco($svg_branch, 'hd-branch hb-1') ?><?= $deco($svg_branch, 'hd-branch hb-2') ?>
    </div>
  </div>
  <div class="save-card paper">
    <?php $slot = 'card'; foreach ($pro_parts as $pf) include $pf; ?>
    <?php if ($invite): ?>
      <p class="invitee"><?= ed_text($c, 'c.invite_greeting', 'span') ?> <b><?= e($invite['name']) ?></b></p>
    <?php endif; ?>
    <div class="crest" aria-hidden="true"><?= $deco($svg_laurel, 'crest-l') ?><span><?= e($m1) ?><i>&amp;</i><?= e($m2) ?></span><?= $deco($svg_laurel, 'crest-r') ?></div>
    <?= ed_text($c, 'c.hero_eyebrow', 'p', 'eyebrow') ?>
    <h1 class="names<?= $names_cls ?>">
      <?= ed_text($c, 'groom_name', 'span', 'name') ?>
      <span class="amp">&amp;</span>
      <?= ed_text($c, 'bride_name', 'span', 'name') ?>
    </h1>
    <p class="date" data-edit-date data-date="<?= e($date) ?>" data-time="<?= e($time) ?>"><?= $date_text !== '' ? $date_html($date_text) : ($draft ? e(__('Chọn ngày cưới')) : '') ?></p>
    <?php if ($lunar_text !== ''): ?><p class="lunar"><?= str_replace(' (Âm lịch)', "\u{00A0}<span class=\"dt-nw\">(Âm lịch)</span>", e($lunar_text)) ?></p><?php endif; ?>
    <div class="divider"><span>♥</span></div>
  </div>
</section>

<?php $mq = $couple . ($date ? ' · ' . fmt_dmy($date) : ''); ?>
<div class="marquee" aria-hidden="true"><div class="mq-track"><?php for ($i = 0; $i < 8; $i++): ?><span><?= e($mq) ?></span><?php endfor; ?></div></div>

<section id="couple" class="couple watercolor">
  <?php $slot = 'couple'; foreach ($pro_parts as $pf) include $pf; ?>
  <div class="sec-head"><?= ed_text($c, 'c.couple_title', 'h2') ?><div class="divider"><span>♥</span></div></div>
  <div class="couple-row">
    <div class="person person-g">
      <p class="house"><?= e(__('Nhà trai')) ?></p>
      <?= $with_alt(ed_img($img['img.groom'], 'img.groom', 'oval', 't', __('Ảnh chú rể')), __('Ảnh chú rể {ten}', array('ten' => $c->text('groom_name')))) ?>
      <p class="role"><?= e(__('Chú rể')) ?></p>
      <?= ed_text($c, 'c.groom_fullname', 'h3', 'banner') ?>
      <?= ed_text($c, 'c.groom_info', 'p', 'info', __('Con trai ông … và bà … (bấm để nhập)')) ?>
    </div>
    <svg class="rings" aria-hidden="true"><use href="#ico-rings"/></svg>
    <svg class="rings rings-sh" aria-hidden="true"><use href="#d-songhy"/></svg>
    <?= $constel ?>
    <span class="couple-amp" aria-hidden="true">&amp;</span>
    <div class="person person-b">
      <p class="house"><?= e(__('Nhà gái')) ?></p>
      <?= $with_alt(ed_img($img['img.bride'], 'img.bride', 'oval', 't', __('Ảnh cô dâu')), __('Ảnh cô dâu {ten}', array('ten' => $c->text('bride_name')))) ?>
      <p class="role"><?= e(__('Cô dâu')) ?></p>
      <?= ed_text($c, 'c.bride_fullname', 'h3', 'banner') ?>
      <?= ed_text($c, 'c.bride_info', 'p', 'info', __('Con gái ông … và bà … (bấm để nhập)')) ?>
    </div>
  </div>
  <?= str_replace('class="orn"', 'class="orn orn-2"', $ornament) ?>
</section>

<section id="event" class="event<?= !$draft && !$has_img('img.event') ? ' event--noimg' : '' ?>">
  <?php $slot = 'event'; foreach ($pro_parts as $pf) include $pf; ?>
  <div class="sec-head"><?= $bride_first ? '<h2>' . e($c->text('c.event_title_bride')) . '</h2>' : ed_text($c, 'c.event_title', 'h2') ?><div class="divider"><span>♥</span></div></div>
  <?php if ($draft): ?><p class="small muted center ed-side-title"><?= e(__('Tiêu đề trên thiệp gửi khách nhà gái:')) ?> <?= ed_text($c, 'c.event_title_bride', 'b') ?></p><?php endif; ?>
  <div class="event-row">
    <?= $with_alt(ed_img($img['img.event'], 'img.event', 'photo event-photo', 'm', __('Ảnh lễ cưới')), __('Ảnh lễ cưới {cap_doi}', array('cap_doi' => $couple))) ?>
    <div class="paper event-card">
      <div class="ev-intro"><p class="ev-k"><?= e(__('Trân trọng báo tin lễ thành hôn của')) ?></p><p class="ev-names"><?= $names_line ?></p></div>
      <?= $starchart ?>
      <?= ed_text($c, 'c.event_text', 'p', 'lead') ?>
      <p class="date big" data-edit-date data-date="<?= e($date) ?>" data-time="<?= e($time) ?>"><?= $date_text !== '' ? $date_html($date_text) : ($draft ? e(__('Chọn ngày cưới')) : '') ?></p>
      <?= $dlock ?>
      <?= $cal ?>
      <?php if ($lunar_text !== ''): ?><p class="lunar"><?= str_replace(' (Âm lịch)', "\u{00A0}<span class=\"dt-nw\">(Âm lịch)</span>", e($lunar_text)) ?></p><?php endif; ?>
      <div class="countdown" data-countdown="<?= (int) $countdown ?>"<?= $countdown > time() ? '' : ' hidden' ?>>
        <div><b data-d>0</b><span><?= e(__('Ngày')) ?></span></div><div><b data-h>0</b><span><?= e(__('Giờ')) ?></span></div>
        <div><b data-m>0</b><span><?= e(__('Phút')) ?></span></div><div><b data-s>0</b><span><?= e(__('Giây')) ?></span></div>
      </div>
      <div class="btn-row center-row">
        <?= $ics_btn($ics['main'], __('Thêm vào lịch'), 'btn btn-accent') ?>
        <a class="btn btn-ghost" href="#location"><?= e(__('Xem địa điểm')) ?></a>
      </div>
    </div>
  </div>
</section>

<section id="location" class="location">
  <?php $slot = 'location'; foreach ($pro_parts as $pf) include $pf; ?>
  <div class="sec-head"><?= ed_text($c, 'c.location_title', 'h2') ?><div class="divider"><span>♥</span></div></div>
  <div class="events" data-events>
    <?php foreach ($events as $i => $ev): $q = $map_q($ev); $show_map = $draft || trim($ev['address']) !== ''; ?>
    <article class="ev<?= $show_map ? '' : ' ev--nomap' ?>" data-ev-item data-ev-side="<?= e(isset($ev['side']) ? (string) $ev['side'] : '') ?>" data-ev-guess="<?= e($c->event_side(array('title' => $ev['title'], 'place' => $ev['place']))) ?>">
      <?php if ($show_map): ?>
      <div class="ev-map">
        <?php $map_src = 'https://maps.google.com/maps?q=' . rawurlencode($q) . '&hl=' . lang_cur() . '&z=15&output=embed'; ?>
        <?php if ($q !== '' && $draft): ?>
          <iframe title="<?= e(__('Bản đồ {ten}', array('ten' => $ev['title']))) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="<?= e($map_src) ?>"></iframe>
        <?php elseif ($q !== ''):  ?>
          <button type="button" class="ev-map-load" data-map-load data-src="<?= e($map_src) ?>" data-title="<?= e(__('Bản đồ {ten}', array('ten' => $ev['title']))) ?>">
            <span class="ev-map-pin" aria-hidden="true">📍</span><b><?= e(__('Xem bản đồ')) ?></b><small><?= e($q) ?></small>
          </button>
        <?php else: ?>
          <div class="ev-map-ph"><?= e(__('Nhập địa chỉ để hiện bản đồ')) ?></div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <div class="paper ev-card">
        <h3 data-ev="title" data-ph="<?= e(__('Tên sự kiện (vd: Tiệc cưới)')) ?>"><?= e($ev['title']) ?></h3>
        <p class="ev-place" data-ev="place" data-ph="<?= e(__('Tên nơi tổ chức')) ?>"><?= e($ev['place']) ?></p>
        <p class="ev-addr" data-ev="address" data-ph="<?= e(__('Địa chỉ (vd: 12 Nguyễn Du, Hoàn Kiếm, Hà Nội)')) ?>"><?= e($ev['address']) ?></p>
        <p class="ev-time" data-ev="time" data-ph="<?= e(__('Thời gian (vd: 10 giờ, Chủ nhật 20/12/2026)')) ?>"><?= e($ev['time']) ?></p>
        <?php $href = $ev['map'] !== '' ? $ev['map'] : ($q !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($q) : ''); ?>
        <a class="btn btn-accent<?= $href === '' ? ' is-hidden' : '' ?>" data-ev-link data-map="<?= e($ev['map']) ?>" href="<?= e($href) ?>" target="_blank" rel="noopener noreferrer"><?= e(__('Chỉ đường')) ?></a>
        <?php if (!$draft && isset($ics['events'][$i])): ?><?= $ics_btn($ics['events'][$i], __('Thêm vào lịch'), 'btn btn-ghost ev-ics') ?><?php endif; ?>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="quote<?= !$draft && !$has_img('img.quote') ? ' quote--noimg' : '' ?>">
  <?= ed_img($img['img.quote'], 'img.quote', 'quote-bg', 'm', __('Ảnh nền')) ?>
  <?php $slot = 'quote'; foreach ($pro_parts as $pf) include $pf; ?>
  <blockquote><?= ed_text($c, 'c.quote', 'p') ?></blockquote>
</section>

<?php if ($show_gallery): ?>
<section id="gallery" class="gallery-sec watercolor">
  <?php $slot = 'gallery'; foreach ($pro_parts as $pf) include $pf; ?>
  <div class="sec-head"><?= ed_text($c, 'c.album_title', 'h2') ?><div class="divider"><span>♥</span></div></div>
  <?php if ($home_album): ?>
  <?php 
$can_dl = album_download_allowed() || $is_admin;

$big_tile = function ($i) use ($theme) {
return ($theme === 'songhy' && $i % 6 === 0) || ($theme === 'tapchi' && ($i % 9 === 0 || $i % 9 === 8));
}; ?>
  <div class="wd-gallery" data-gallery data-home-gallery data-album-id="<?= (int) $home_album['id'] ?>">
    <?php foreach ($gallery as $gi => $p): $g_alt = __('Ảnh cưới {i}/{n}', array('i' => $gi + 1, 'n' => $gallery_total)) . (trim((string) $p['caption']) !== '' ? ': ' . trim($p['caption']) : ''); ?>
      <a class="wd-g" href="<?= photo_url($p, 'm') ?>" data-srcset="<?= e(photo_srcset($p, 'm')) ?>" data-lb data-photo-id="<?= (int) $p['id'] ?>" aria-label="<?= e(__('Xem {anh}', array('anh' => mb_strtolower(mb_substr($g_alt, 0, 1)) . mb_substr($g_alt, 1)))) ?>"
         <?php if ($can_dl): ?>data-full="<?= photo_url($p, 'o') ?>"<?php endif; ?>
         data-cap="<?= e((string) $p['caption']) ?>"><img src="<?= photo_url($p, 't') ?>" srcset="<?= e(photo_srcset($p, 's')) ?>" sizes="<?= $big_tile($gi) ? '(max-width: 560px) 96vw, (max-width: 1100px) 66vw, 760px' : '(max-width: 560px) 34vw, (max-width: 1024px) 33vw, 25vw' ?>" alt="<?= e($g_alt) ?>" loading="lazy" decoding="async"></a>
    <?php endforeach; ?>
  </div>
  <?php if (!$gallery && !$draft): ?><p class="muted center"><?= e(__('Ảnh đang được cô dâu chú rể chọn lọc, bạn quay lại sau nhé.')) ?></p><?php endif; ?>
  <?php if ($gallery_total > 0 && ($gallery_total > count($gallery) || $albums)): ?>
    <p class="center"><a class="btn btn-ghost" href="<?= base_url('a/' . $home_album['slug']) ?>"><?= e(__n('Xem tất cả {n} ảnh', (int) $gallery_total)) ?></a></p>
  <?php endif; ?>
  <?php endif; ?>
  <?php if ($shown): ?>
  <div class="album-grid more-albums">
    <?php foreach ($shown as $a): ?>
      <a class="album-card" href="<?= base_url('a/' . $a['slug']) ?>">
        <div class="album-cover">
          <?php if ($a['cover'] && ($a['visibility'] === 'public' || $is_admin)): ?><img src="<?= photo_url($a['cover'], 't') ?>" alt="<?= e(__('Ảnh bìa album {ten}', array('ten' => $a['title']))) ?>" loading="lazy">
          <?php else: ?><span class="album-cover-ph"><?= $a['visibility'] === 'password' ? '🔒' : '♡' ?></span><?php endif; ?>
        </div>
        <div class="album-meta"><h3><?= e($a['title']) ?></h3><p><?= e(__n('{n} ảnh', (int) $a['photo_count'])) ?><?= $a['visibility'] === 'password' ? ' · ' . e(__('cần mật khẩu')) : '' ?><?= $draft && (int) $a['photo_count'] === 0 ? ' · ' . e(__('trống — khách chưa thấy')) : '' ?></p></div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($settings['guest_upload'] === '1'): ?>
<section class="wrap cta">
  <?= ed_text($c, 'c.upload_title', 'h2', 'sec-title') ?>
  <?= ed_text($c, 'c.upload_text', 'p') ?>
  <a class="btn btn-accent" href="<?= base_url('gui-anh') ?>"><?= e(__('Gửi ảnh ngay')) ?></a>
</section>
<?php endif; ?>

<?php if ($rsvp_on): ?>
<section class="wrap rsvp" id="rsvp">
  <?php $slot = 'rsvp'; foreach ($pro_parts as $pf) include $pf; ?>
  <div class="sec-head"><?= ed_text($c, 'c.rsvp_title', 'h2') ?><div class="divider"><span>♥</span></div></div>
  <?php $answered = $rsvp_inv && $rsvp_inv['status'] !== 'pending'; ?>
  <div class="paper rsvp-done" data-rsvp-done<?= $answered ? '' : ' hidden' ?>>
    <b data-rsvp-done-title><?= $answered ? e($rsvp_inv['status'] === 'yes' ? $v['done_yes'] : $v['done_no']) : '' ?></b>
    <p class="muted" data-rsvp-done-sub><?= $answered && $rsvp_inv['status'] === 'yes' ? e(__('Số người: {n}.', array('n' => (int) $rsvp_inv['guests']))) . ' ' : '' ?><?= e(__('Muốn đổi câu trả lời? Chọn lại bên dưới.')) ?></p>
  </div>
  <form class="paper rsvp-card" method="post" action="<?= base_url('xac-nhan') ?>" data-rsvp-form data-ajax-form novalidate>
    <?= csrf_field() ?>
    <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
    <?= ed_text($c, 'c.rsvp_text', 'p', 'center lead') ?>
    <?php if ($invite): ?>
      <input type="hidden" name="code" value="<?= e($invite['code']) ?>">
      <p class="rsvp-invitee"><?= e(__('Lời mời dành cho')) ?> <b><?= e($invite['name']) ?></b></p>
    <?php else: ?>
      <label><?= e(__('Tên của bạn')) ?><input name="name" maxlength="80" required autocomplete="name" value="<?= e($rsvp_prev ? (string) $rsvp_prev['name'] : '') ?>"></label>
    <?php endif; ?>
    <div class="rsvp-choice" role="radiogroup" aria-label="<?= e(__('Có tham dự không?')) ?>">
      <label><input type="radio" name="attend" value="yes" required <?= $rsvp_inv && $rsvp_inv['status'] === 'yes' ? 'checked' : '' ?>><span>✓ <?= e(__('Sẽ tham dự')) ?></span></label>
      <label><input type="radio" name="attend" value="no" <?= $rsvp_inv && $rsvp_inv['status'] === 'no' ? 'checked' : '' ?>><span>✕ <?= e(__('Không thể tham dự')) ?></span></label>
    </div>
    <?php $g_max = $rsvp_inv && !empty($rsvp_inv['max_guests']) ? max(1, min(20, (int) $rsvp_inv['max_guests'])) : 20; ?>
    <div class="row2" data-rsvp-yes>
      <?php if ($g_max > 1): ?>
      <label><?= e($v['count']) ?><input type="number" name="guests" min="1" max="<?= $g_max ?>" inputmode="numeric" value="<?= $rsvp_inv && $rsvp_inv['guests'] ? min($g_max, (int) $rsvp_inv['guests']) : 1 ?>"></label>
      <?php else: ?><input type="hidden" name="guests" value="1"><?php endif; ?>
      <label><?= e(__('Số điện thoại')) ?> <small><?= e(__('(không bắt buộc)')) ?></small><input name="phone" inputmode="tel" maxlength="20" autocomplete="tel" aria-describedby="rsvp-phone-hint">
        <?php  ?>
        <?php if ($rsvp_inv && $rsvp_inv['status'] !== 'pending'): ?><small class="field-hint" id="rsvp-phone-hint"><?= e(__('Để trống nếu không đổi')) ?></small><?php endif; ?></label>
    </div>
    <label><?= e(__('Lời chúc gửi cô dâu chú rể')) ?> <small><?= e(__('(không bắt buộc)')) ?></small><textarea name="message" rows="3" maxlength="1000"<?= $msg_note !== '' ? ' aria-describedby="rsvp-msg-note"' : '' ?>><?= e($rsvp_inv ? (string) $rsvp_inv['message'] : '') ?></textarea>
      <?php if ($msg_note !== ''): ?><small class="field-hint" id="rsvp-msg-note"><?= e($msg_note) ?></small><?php endif; ?></label>
    <p class="err" data-form-err role="alert" hidden></p>
    <button class="btn btn-accent" type="submit"><?= e(__('Gửi xác nhận')) ?></button>
  </form>
  <div class="paper form-thanks" data-form-thanks role="status" tabindex="-1" hidden></div>
</section>
<?php endif; ?>

<?php if ($settings['wishes_enabled'] === '1'): ?>
<section class="wrap wishes" id="loi-chuc">
  <?php $slot = 'wishes'; foreach ($pro_parts as $pf) include $pf; ?>
  <div class="sec-head"><?= ed_text($c, 'c.wishes_title', 'h2') ?><div class="divider"><span>♥</span></div></div>
  <form class="wish-form paper" method="post" action="<?= base_url('loi-chuc') ?>" data-wish-form data-ajax-form novalidate>
    <?= csrf_field() ?>
    <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
    <?php if ($invite): ?><input type="hidden" name="code" value="<?= e($invite['code']) ?>"><?php endif; ?>
    <label><?= e($v['name_label']) ?><input name="name" maxlength="60" required autocomplete="name"<?= $rsvp_inv ? ' value="' . e($invite ? $invite['name'] : (string) $rsvp_prev['name']) . '"' : '' ?>></label>
    <label><?= e(__('Lời chúc')) ?><textarea name="message" rows="3" maxlength="1000" required></textarea></label>
    <p class="err" data-form-err role="alert" hidden></p>
    <button class="btn btn-accent" type="submit"><?= e(__('Gửi lời chúc')) ?></button>
  </form>
  <div class="paper form-thanks" data-form-thanks role="status" tabindex="-1" hidden></div>
  <?php 
$wish_show = 6; $wish_more = max(0, count($wishes) - $wish_show); ?>
  <div class="wish-list" data-wish-list>
    <?php foreach ($wishes as $wi => $w): ?>
      <blockquote class="wish"<?= $wi >= $wish_show ? ' data-wish-extra hidden' : '' ?>><p><?= nl2br(e($w['message'])) ?></p><cite><?= e($w['name']) ?></cite></blockquote>
    <?php endforeach; ?>
  </div>
  <?php if ($wish_more > 0): ?>
  <p class="center wish-more-row"><button type="button" class="btn btn-ghost" data-wish-more><span><?= e(__('Xem thêm lời chúc')) ?> (<span data-wish-more-n><?= $wish_more ?></span>)</span></button></p>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php 
$this->load->view('public/_gift');
if ($draft && empty($gift)): ?>
<section class="wrap gift gift-off"><p class="gift-text">💝 <?= e(__('Mục Mừng cưới (mã QR ngân hàng) đang tắt — khách không thấy.')) ?>
  <a href="<?= base_url('admin/settings#mung-cuoi') ?>"><?= e(__('Bật & nhập tài khoản trong Cài đặt')) ?></a></p></section>
<?php endif; ?>

<footer class="wd-foot">
  <?php $slot = 'foot'; foreach ($pro_parts as $pf) include $pf; ?>
  <div class="wd-mono big"><span><?= e($m1) ?></span><i>♡</i><span><?= e($m2) ?></span></div>
  <?= ed_text($c, 'c.footer', 'p') ?>
  <p class="muted small"><?= e($couple) ?><?= $date ? ' · ' . e(vn_date($date, FALSE)) : '' ?></p>
  <?php if ($music): ?><p class="music-credit">♫ <?= e(__('Nhạc nền:')) ?> <?= e($music['title']) ?><?= $music['credit'] !== '' ? ' — ' . e($music['credit']) : '' ?></p><?php endif; ?>
  <?php 
$cr_gh = '<svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>';
$cr_rings = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="13" r="6"/><circle cx="15" cy="13" r="6"/><path d="M12 4l-1.5 3h3z"/></svg>'; ?>
</footer>

<?php if ($draft) include __DIR__ . '/_editor.php';