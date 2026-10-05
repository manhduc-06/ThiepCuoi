<?php
 defined('BASEPATH') OR exit('No direct script access allowed');
$labels = array('approved' => __('Đang hiện'), 'pending' => __('Chờ duyệt'), 'hidden' => __('Đã ẩn'));
$keep = $loc === 'pending' ? '?loc=pending' : ''; ?>
<h1 class="adm-title"><?= e(__('Lời chúc')) ?></h1>
<nav class="tabs" aria-label="<?= e(__('Lọc lời chúc')) ?>">
  <a class="<?= $loc === 'pending' ? 'on' : '' ?>" href="<?= base_url('admin/moderation/wishes?loc=pending') ?>"><?= e(__('Chờ duyệt')) ?> (<?= (int) $pending_wishes ?>)</a>
  <a class="<?= $loc === '' ? 'on' : '' ?>" href="<?= base_url('admin/moderation/wishes') ?>"><?= e(__('Tất cả')) ?> (<?= (int) $total ?>)</a>
</nav>
<?php if (!$wishes): ?><p class="muted"><?= e($loc === 'pending' ? __('Không có lời chúc nào chờ duyệt.') : __('Chưa có lời chúc nào.')) ?></p><?php endif; ?>
<div class="wish-admin">
  <?php foreach ($wishes as $w): $edited = !empty($w['updated_at']); ?>
  <div class="panel wish-row" id="w<?= (int) $w['id'] ?>">
    <div>
      <b><?= e($w['name']) ?></b> <span class="tag tag-<?= e($w['status']) ?>"><?= e($labels[$w['status']] ?? $w['status']) ?></span>
      <?php if ($edited): ?><span class="tag tag-edited" title="<?= e(__('Gửi lần đầu lúc {time}', array('time' => date('H:i d/m/Y', strtotime($w['created_at']))))) ?>"><?= e(__('đã sửa')) ?></span><?php endif; ?>
      <small class="muted"><?= $edited ? e(__('sửa lúc')) . ' ' : '' ?><?= e(date('H:i d/m/Y', strtotime($edited ? $w['updated_at'] : $w['created_at']))) ?></small>
      <p><?= nl2br(e($w['message'])) ?></p>
    </div>
    <div class="btn-row">
      <?php foreach (array('approved' => __('Hiện'), 'hidden' => __('Ẩn')) as $st => $lbl): if ($w['status'] === $st) continue; ?>
        <form method="post" action="<?= base_url('admin/moderation/wish_status/' . (int) $w['id']) . $keep ?>"><?= csrf_field() ?><input type="hidden" name="status" value="<?= $st ?>"><button class="btn btn-ghost btn-sm"><?= e($lbl) ?></button></form>
      <?php endforeach; ?>
      <form method="post" action="<?= base_url('admin/moderation/wish_delete/' . (int) $w['id']) . $keep ?>" data-confirm="<?= e(__('Xóa lời chúc này?')) ?>"><?= csrf_field() ?><button class="btn btn-danger btn-sm"><?= e(__('Xóa')) ?></button></form>
    </div>
  </div>
  <?php endforeach; ?>
</div>