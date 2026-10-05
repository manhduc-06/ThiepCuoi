<?php
 defined('BASEPATH') OR exit('No direct script access allowed');
$vis = array('public' => __('Công khai'), 'password' => __('Có mật khẩu'), 'hidden' => __('Ẩn')); ?>
<div class="panel-head">
  <h1 class="adm-title"><?= e(__('Ảnh')) ?></h1>
  <a class="btn btn-accent" href="<?= base_url('admin/albums/create') ?>">+ <?= e(__('Album mới')) ?></a>
</div>
<nav class="tabs" aria-label="<?= e(__('Ảnh')) ?>">
  <a class="<?= $this->uri->segment(2) === 'albums' ? 'on' : '' ?>" href="<?= base_url('admin/albums') ?>"><?= e(__('Album{_}', array('_' => ''))) ?></a>
  <a class="<?= $this->uri->segment(2) === 'moderation' ? 'on' : '' ?>" href="<?= base_url('admin/moderation') ?>"><?= e(__('Ảnh khách gửi chờ duyệt')) ?><?= !empty($pending_photos) ? ' (' . (int) $pending_photos . ')' : '' ?></a>
</nav>
<p class="muted small"><?= e(__('Bấm vào album để thêm ảnh. Kéo thả để đổi thứ tự hiển thị trên trang cưới.')) ?></p>
<ul class="album-rows" data-sortable data-sort-endpoint="<?= base_url('admin/albums/reorder') ?>">
  <?php foreach ($albums as $a): ?>
  <li class="album-row" draggable="true" data-id="<?= (int) $a['id'] ?>">
    <span class="drag" aria-hidden="true">⋮⋮</span>
    <span class="adm-album-cover sm"><?php if ($a['cover']): ?><img src="<?= photo_url($a['cover'], 't') ?>" alt="" loading="lazy"><?php endif; ?></span>
    <a class="album-row-title" href="<?= base_url('admin/albums/view/' . $a['id']) ?>"><?= e($a['title']) ?></a>
    <span class="tag tag-<?= e($a['visibility']) ?>"><?= e($vis[$a['visibility']]) ?></span>
    <span class="muted small"><?= e(__('{n} ảnh', array('n' => (int) $a['photo_count']))) ?><?php if ($a['pending_count']): ?> · <?= e(__('{n} chờ duyệt', array('n' => (int) $a['pending_count']))) ?><?php endif; ?><?php if ($a['allow_guest_upload']): ?> · <?= e(__('nhận ảnh khách')) ?><?php endif; ?></span>
    <a class="btn btn-ghost btn-sm" href="<?= base_url('admin/albums/edit/' . $a['id']) ?>"><?= e(__('Sửa')) ?></a>
  </li>
  <?php endforeach; ?>
</ul>