<?php
 defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<h1 class="adm-title"><?= e(__('Ảnh')) ?></h1>
<nav class="tabs" aria-label="<?= e(__('Ảnh')) ?>">
  <a class="<?= $this->uri->segment(2) === 'albums' ? 'on' : '' ?>" href="<?= base_url('admin/albums') ?>"><?= e(__('Album{_}', array('_' => ''))) ?></a>
  <a class="<?= $this->uri->segment(2) === 'moderation' ? 'on' : '' ?>" href="<?= base_url('admin/moderation') ?>"><?= e(__('Ảnh khách gửi chờ duyệt')) ?><span data-mod-tab><?= !empty($pending_photos) ? ' (' . (int) $pending_photos . ')' : '' ?></span></a>
</nav>
<?php if (!$photos): ?>
  <p class="muted"><?= e(__('Không có ảnh nào đang chờ. Ảnh khách gửi sẽ hiện ở đây nếu bạn bật "Duyệt ảnh trước khi hiển thị" trong Cài đặt.')) ?></p>
<?php else: ?>
<div class="toolbar sticky" data-mod-bar>
  <button type="button" class="btn btn-accent btn-sm" data-mod-all="approved"><?= e(__('Duyệt tất cả')) ?> (<span data-mod-left><?= count($photos) ?></span>)</button>
  <button type="button" class="btn btn-ghost btn-sm" data-mod-all="rejected"><?= e(__('Từ chối tất cả')) ?></button>
</div>
<p class="muted" data-mod-empty hidden><?= e(__('Đã xử lý hết ảnh chờ duyệt. Ảnh vừa duyệt/từ chối vẫn hiện bên dưới để bạn hoàn tác nếu bấm nhầm.')) ?></p>
<div class="mod-grid">
  <?php foreach ($photos as $p): ?>
  <figure class="mod-item" data-id="<?= (int) $p['id'] ?>">
    <a href="<?= photo_url($p, 'm') ?>" data-lb data-cap="<?= e(($p['guest_name'] ?: __('Khách ẩn danh')) . ($p['guest_message'] ? ' — ' . $p['guest_message'] : '')) ?>"><img src="<?= photo_url($p, 't') ?>" alt="" loading="lazy"></a>
    <figcaption>
      <b><?= e($p['guest_name'] ?: __('Khách ẩn danh')) ?></b>
      <?php if ($p['guest_message']): ?><span><?= e($p['guest_message']) ?></span><?php endif; ?>
      <small class="muted"><?= e($p['album_title']) ?> · <?= e(date('H:i d/m', strtotime($p['created_at']))) ?></small>
    </figcaption>
    <div class="mod-actions">
      <button type="button" class="btn btn-accent btn-sm" data-mod="approved"><?= e(__('Duyệt')) ?></button>
      <button type="button" class="btn btn-ghost btn-sm" data-mod="rejected"><?= e(__('Từ chối')) ?></button>
    </div>
  </figure>
  <?php endforeach; ?>
</div>
<?php endif;