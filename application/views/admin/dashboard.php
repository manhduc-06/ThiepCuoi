<?php
 defined('BASEPATH') OR exit('No direct script access allowed');
$is_public = strpos($public, 'https://') === 0; ?>
<div class="panel-head">
  <h1 class="adm-title"><?= e(__('Tổng quan')) ?></h1>
  <div class="btn-row">
    <a class="btn btn-ghost" href="<?= base_url() ?>">✎ <?= e(__('Sửa trang cưới')) ?></a>
  </div>
</div>

<?php

$ck_done = count(array_filter(array_column($checklist, 'done'))); $ck_n = count($checklist); $cur = NULL;
foreach ($next as $i => $st) { if (!$st[2]) { $cur = $i; break; } }
if ($cur !== NULL || $ck_done < $ck_n): ?>
<section class="panel next-card" aria-labelledby="next-h">
  <div>
    <h2 id="next-h"><?= e(__('Việc tiếp theo')) ?> · <span class="next-ck" data-checklist="<?= $ck_done ?>/<?= $ck_n ?>"><?= e(__('Việc cần làm')) ?> <?= $ck_done ?>/<?= $ck_n ?></span></h2>
    <p class="small muted" style="margin:6px 0 0"><?= e(__('Cùng danh sách với nút “Việc cần làm” trên trang sửa. Làm lần lượt từ trên xuống — mỗi bước chỉ vài phút.')) ?></p>
  </div>
  <div class="progress" aria-hidden="true"><i style="width:<?= round($ck_done * 100 / max(1, $ck_n)) ?>%"></i></div>
  <ol class="next-steps">
    <?php foreach ($next as $i => $st): list($label, $sub, $ok, $act) = $st; $is_cur = $i === $cur; ?>
    <li class="<?= $ok ? 'ok' : '' ?><?= $is_cur ? ' cur' : '' ?>">
      <div><b><?= e($label) ?></b><span><?= e($sub) ?></span></div>
      <?php $cls = $is_cur ? 'btn btn-accent btn-sm' : 'btn btn-ghost btn-sm';
if ($act === 'edit' || $act === 'publish'): ?><a class="<?= $cls ?>" href="<?= base_url() ?>"><?= e($act === 'edit' ? __('Sửa trang') : __('Mở trang sửa')) ?></a>
      <?php elseif ($act === 'guests'): ?><a class="<?= $cls ?>" href="<?= base_url('admin/guests') ?>"><?= e(__('Thêm khách')) ?></a>
      <?php else: ?><button type="button" class="<?= $cls ?> btn-share" data-share="<?= e($public) ?>" data-share-text="<?= e($share_text) ?>" data-share-title="<?= e(__('Gửi link trang cưới')) ?>" data-live-share><?= e(__('Gửi link')) ?></button><?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ol>
</section>
<?php endif; ?>

<div class="panel site-card" data-live-link data-public="<?= $is_public ? '1' : '0' ?>" data-mode="<?= e($tunnel['mode']) ?>">
  <div class="site-qr" data-qr="<?= e($public) ?>"></div>
  <div class="site-info">
    <p class="small muted"><?= e(__('Link trang cưới cho khách mời')) ?></p>
    <p class="site-url"><a data-live-url href="<?= e($public) ?>" target="_blank" rel="noopener"><?= e($public) ?></a></p>
    <p class="copy-row"><input readonly value="<?= e($public) ?>" data-copy-src aria-label="<?= e(__('Link trang cưới')) ?>"><button class="btn btn-ghost btn-sm" type="button" data-copy><?= e(__('Sao chép')) ?></button></p>
    <p class="site-actions"><button class="btn btn-accent btn-sm btn-share" type="button" data-share="<?= e($public) ?>" data-share-text="<?= e($share_text) ?>" data-share-title="<?= e(__('Gửi link trang cưới')) ?>" data-live-share><?= e(__('Gửi cho khách')) ?></button>
      <button class="btn btn-ghost btn-sm" type="button" data-qr-download="trang-cuoi"><?= e(__('Tải mã QR')) ?></button></p>
    <p class="site-state">
      <?php if (!$published): ?><span class="tag tag-pending"><?= e(__('Khách chưa xem được — đang thấy trang "đang chuẩn bị"')) ?></span>
      <?php elseif ($changes): ?><span class="tag tag-pending"><?= e(__('Có thay đổi khách chưa thấy')) ?></span>
      <?php else: ?><span class="tag tag-approved"><?= e(__('Khách đang xem được ✓')) ?></span><?php endif; ?>
      <?php if ($tunnel['mode'] === 'token'): ?>
        <?php if ($tun_state === 'error'): ?><span class="tag tag-hidden" title="<?= e((string) $tun_error) ?>"><?= e(__('Link Internet chưa hoạt động')) ?><?= $tun_error ? ': ' . e($tun_error) : '' ?></span>
        <?php else: ?><span class="tag <?= $tun_state === 'connected' ? 'tag-approved' : 'tag-pending' ?>"><?= e($tun_state === 'connected' ? __('Link Internet đang hoạt động') : __('Đang kết nối link Internet…')) ?></span><?php endif; ?>
      <?php endif; ?>
    </p>
    <p class="small muted"><?= e(__('Trang quản trị:')) ?> <b><?= e(rtrim($public, '/') . '/admin') ?></b> — <?= e(__('đăng nhập bằng tài khoản')) ?> <b><?= e($user['username']) ?></b></p>
    <?php if (!$is_public && $tunnel['mode'] === 'quick'): ?>
      <p class="notice small" data-live-note>⏳ <?= e(__('Đang tạo link cho khách ở xa… (thường 5–15 giây, trang tự cập nhật)')) ?></p>
    <?php elseif (!$is_public): ?>
      <p class="notice small" data-live-note><?= e(__('Khách ở xa chưa mở được link này (đang để chế độ chỉ trong mạng nhà).')) ?>
        <button class="btn btn-accent btn-sm" type="button" data-live-on><?= e(__('Tạo link cho khách ở xa')) ?></button></p>
    <?php elseif ($tunnel['mode'] === 'quick'): ?>
      <p class="small muted" data-live-note><?= e(__('Đây là link tạm — sẽ đổi nếu máy khởi động lại. Muốn link cố định, dễ nhớ:')) ?>
        <a href="<?= base_url('admin/share') ?>"><?= e(__('chọn link .{domain}', array('domain' => $this->config->item('cloud_domain')))) ?></a>.</p>
    <?php endif; ?>
  </div>
</div>

<div class="dash-top">
  <div class="panel countdown-card">
    <?php if ($wed_ts && $wed_ts > time()): ?>
      <p class="small muted"><?= e(__('Còn lại tới ngày cưới')) ?></p>
      <div class="big-countdown" data-countdown="<?= (int) $wed_ts ?>">
        <div><b data-d>0</b><span><?= e(__('ngày')) ?></span></div><div><b data-h>0</b><span><?= e(__('giờ')) ?></span></div>
        <div><b data-m>0</b><span><?= e(__('phút')) ?></span></div><div><b data-s>0</b><span><?= e(__('giây')) ?></span></div>
      </div>
      <p class="cd-date"><?= e($wed_text) ?></p>
      <p class="small muted"><?= e($wed_lunar) ?></p>
    <?php elseif ($wed_ts): ?>
      <p class="cd-date"><?= e(__('Ngày cưới {date} đã qua', array('date' => $wed_text))) ?> ♡</p>
      <p class="small muted"><?= e(__('Chúc hai bạn trăm năm hạnh phúc!')) ?></p>
    <?php else: ?>
      <p class="cd-date"><?= e(__('Chưa đặt ngày cưới')) ?></p>
      <a class="btn btn-accent btn-sm" href="<?= base_url() ?>"><?= e(__('Đặt ngày trên trang cưới')) ?></a>
    <?php endif; ?>
  </div>
  <div class="panel kpi-card">
    <p class="small muted"><?= e(__('Sẽ tham dự{_}', array('_' => ''))) ?></p>
    <p class="kpi-big"><?= (int) $rsvp['people'] ?> <span><?= e(__('người')) ?></span></p>
    <p class="small muted"><?= e(__('{n} người từ thiệp mời', array('n' => (int) $rsvp['inv_people']))) ?><?= $rsvp['web_people'] ? ' · ' . e(__('{n} người tự xác nhận trên web', array('n' => (int) $rsvp['web_people']))) : '' ?></p>
    <p class="small"><a href="<?= base_url('admin/guests') ?>"><?= e(__('Khách mời')) ?></a> · <a href="<?= base_url('admin/moderation/wishes') ?>"><?= e(__('{n} lời chúc', array('n' => (int) $wishes))) ?></a></p>
  </div>
</div>

<?php if ($has_data): ?>
<div class="dash-invites"><?php $this->load->view('admin/_rsvp_stats', array('rsvp_s' => $rsvp)); ?></div>

<script type="application/json" id="dash-data"><?= json_encode($charts, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<div class="viz-grid viz-root">
  <section class="panel viz" aria-labelledby="v1">
    <h3 id="v1"><?= e(__('Tình hình xác nhận')) ?></h3>
    <p class="viz-sub"><?= e(__('Mọi khách theo câu trả lời')) ?><?= $rsvp['web'] ? ' ' . e(__('(gồm {n} khách tự xác nhận trên web)', array('n' => (int) $rsvp['web']))) : '' ?> — <?= e(__('cùng số với các ô phía trên')) ?></p>
    <div data-viz="status"></div>
  </section>
  <section class="panel viz" aria-labelledby="v2">
    <h3 id="v2"><?= e(__('Người sẽ đến theo bên')) ?></h3>
    <p class="viz-sub"><?= e(__('Tổng số người (kể cả người đi cùng)')) ?></p>
    <div data-viz="people"></div>
  </section>
  <section class="panel viz viz-wide" aria-labelledby="v3">
    <h3 id="v3"><?= e(__('Xác nhận theo ngày')) ?></h3>
    <p class="viz-sub"><?= e(__('30 ngày gần nhất · số khách trả lời mỗi ngày (gồm khách tự xác nhận trên web; tính theo lần trả lời gần nhất)')) ?></p>
    <div data-viz="rsvp-days"></div>
  </section>
  <section class="panel viz" aria-labelledby="v4">
    <h3 id="v4"><?= e(__('Lời chúc mới')) ?></h3>
    <p class="viz-sub"><?= e(__('30 ngày gần nhất · mỗi ngày')) ?></p>
    <div data-viz="wishes-days"></div>
  </section>
  <section class="panel viz" aria-labelledby="v5">
    <h3 id="v5"><?= e(__('Ảnh khách gửi')) ?></h3>
    <p class="viz-sub"><?= e(__('30 ngày gần nhất · mỗi ngày')) ?></p>
    <div data-viz="photos-days"></div>
  </section>
</div>

<?php endif; ?>

<?php if ($responses): ?>
<div class="panel">
  <div class="panel-head"><h2><?= e(__('Trả lời mới nhất')) ?></h2><a class="btn btn-ghost btn-sm" href="<?= base_url('admin/guests') ?>"><?= e(__('Tất cả khách mời')) ?></a></div>
  <ul class="resp-list">
    <?php foreach ($responses as $r): ?>
      <li><span class="resp-dot <?= $r['status'] === 'yes' ? 'yes' : 'no' ?>"><?= $r['status'] === 'yes' ? '✓' : '✕' ?></span>
        <b><?= e($this->invite_model->display_name($r)) ?></b> <?= e($r['status'] === 'yes' ? __('sẽ tham dự · {n} người', array('n' => (int) $r['guests'])) : __('không tham dự được')) ?>
        <?php if ($r['message']): ?><span class="muted">— “<?= e(mb_strimwidth($r['message'], 0, 90, '…')) ?>”</span><?php endif; ?>
        <small class="muted"><?= e(date('H:i d/m', strtotime($r['responded_at']))) ?></small></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php elseif ($has_data): ?>
  <p class="muted small"><?= e(__('Chưa có ai trả lời. Khách trả lời trên thiệp sẽ hiện ở đây.')) ?></p>
<?php else: ?>
  <p class="muted small"><?= e(__('Biểu đồ khách trả lời, lời chúc và ảnh khách gửi sẽ hiện ở đây khi có dữ liệu.')) ?></p>
<?php endif; ?>

<h2 class="adm-sub"><?= e(__('Ảnh')) ?></h2>
<div class="stats">
  <div class="stat"><b><?= (int) $stats['approved'] ?></b><span><?= e(__('ảnh đang hiển thị')) ?><?php if (!empty($stats['deco'])): ?> · <?= e(__('+{n} ảnh trang trí', array('n' => (int) $stats['deco']))) ?><?php endif; ?></span></div>
  <a class="stat<?= $stats['pending'] ? ' stat-warn' : '' ?>" href="<?= base_url('admin/moderation') ?>"><b><?= (int) $stats['pending'] ?></b><span><?= e(__('ảnh khách chờ duyệt')) ?></span></a>
  <div class="stat"><b><?= (int) $stats['from_guests'] ?></b><span><?= e(__('ảnh khách mời gửi')) ?></span></div>
  <div class="stat"><b><?= e(human_size($stats['bytes'])) ?></b><span><?= e(__('dung lượng')) ?><?php if ($quota === NULL && $disk_free !== NULL): ?> · <?= e(__('ổ còn {size}', array('size' => human_size($disk_free)))) ?><?php endif; ?></span></div>
</div>
<?php if ($quota !== NULL): ?>
  <?php $q_state = $quota['pct'] >= 100 ? ' is-full' : ($quota['pct'] >= 85 ? ' is-warn' : ''); ?>
  <div class="quota<?= $q_state ?>">
    <div class="quota-head"><span><?= e(__('Dung lượng trang cưới')) ?></span><b><?= e(__('Đã dùng {used} / {limit}', array('used' => $quota['used_text'], 'limit' => $quota['limit_text']))) ?></b></div>
    <div class="quota-bar" role="progressbar" aria-label="<?= e(__('Dung lượng đã dùng')) ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $quota['pct'] ?>"><i style="width: <?= (int) $quota['pct'] ?>%"></i></div>
    <p class="muted small"><?php if ($quota['pct'] >= 100): ?><?= e(__('Đã hết dung lượng: chưa tải thêm ảnh/nhạc được (khách gửi ảnh cũng bị chặn). Xóa bớt ảnh không cần hoặc liên hệ thiep.site để nâng hạn mức.')) ?><?php elseif ($quota['pct'] >= 85): ?><?= e(__('Sắp hết dung lượng — còn {size}.', array('size' => Quota::size_text(max(0, $quota['limit'] - $quota['used']))))) ?> <?= e(__('Gồm ảnh album, ảnh khách gửi, ảnh trang trí và nhạc tải lên.')) ?><?php else: ?><?= e(__('Gồm ảnh album, ảnh khách gửi, ảnh trang trí và nhạc tải lên.')) ?> <?= e(__('Số đo cập nhật ngay khi thêm/xóa.')) ?><?php endif; ?></p>
  </div>
<?php endif; ?>

<div class="panel">
  <div class="panel-head"><h2><?= e(__('Album{_}', array('_' => ''))) ?></h2><a class="btn btn-accent btn-sm" href="<?= base_url('admin/albums/create') ?>">+ <?= e(__('Album mới')) ?></a></div>
  <div class="adm-albums">
    <?php foreach ($albums as $a): ?>
      <a class="adm-album" href="<?= base_url('admin/albums/view/' . $a['id']) ?>">
        <span class="adm-album-cover"><?php if ($a['cover']): ?><img src="<?= photo_url($a['cover'], 't') ?>" alt="" loading="lazy"><?php endif; ?></span>
        <span class="adm-album-title"><?= e($a['title']) ?></span>
        <span class="muted small"><?= e(__('{n} ảnh', array('n' => (int) $a['photo_count']))) ?><?php if ($a['pending_count']): ?> · <?= e(__('{n} chờ duyệt', array('n' => (int) $a['pending_count']))) ?><?php endif; ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</div>
<script src="<?= asset_url('js/vendor/qrcode.js') ?>"></script>
<script src="<?= asset_url('js/admin-charts.js') ?>" defer></script>