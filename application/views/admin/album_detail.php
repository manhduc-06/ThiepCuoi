<?php
 defined('BASEPATH') OR exit('No direct script access allowed');
$vis = array('public' => __('Công khai'), 'password' => __('Có mật khẩu'), 'hidden' => __('Ẩn')); ?>
<div class="panel-head">
  <div>
    <a class="back" href="<?= base_url('admin/albums') ?>">← <?= e(__('Album{_}', array('_' => ''))) ?></a>
    <h1 class="adm-title"><?= e($album['title']) ?> <span class="tag tag-<?= e($album['visibility']) ?>"><?= e($vis[$album['visibility']]) ?></span></h1>
  </div>
  <div class="btn-row">
    <a class="btn btn-ghost" href="<?= base_url('a/' . $album['slug']) ?>" target="_blank" rel="noopener"><?= e(__('Xem')) ?> ↗</a>
    <a class="btn btn-ghost" href="<?= base_url('admin/albums/edit/' . (int) $album['id']) ?>"><?= e(__('Sửa')) ?></a>
  </div>
</div>

<div class="panel" data-owner-upload
     data-endpoint="<?= base_url('admin/photos/upload') ?>"
     data-album-id="<?= (int) $album['id'] ?>"
     data-max-mb="<?= (int) $max_mb ?>">
  <label class="drop" data-drop>
    <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple data-files>
    <span class="drop-big">＋</span>
    <?php ?>
    <span class="drop-desk"><?= e(__('Kéo thả ảnh vào đây hoặc bấm để chọn')) ?></span>
    <span class="drop-touch"><?= e(__('Chạm để chọn ảnh trong máy')) ?></span>
    <small><?= e(__('Giữ nguyên bản gốc · tối đa {mb} MB mỗi ảnh · tải lần lượt, đóng trang sẽ dừng', array('mb' => (int) $max_mb))) ?></small>
  </label>
  <div class="up-summary" data-summary hidden></div>
  <ul class="up-list" data-list></ul>
</div>

<div class="toolbar" data-toolbar hidden>
  <span data-sel-count>0</span> <?= e(__('ảnh đã chọn')) ?>
  <button type="button" class="btn btn-ghost btn-sm" data-act="select-all"><?= e(__('Chọn tất cả')) ?></button>
  <select data-move-target aria-label="<?= e(__('Chuyển sang album')) ?>">
    <option value=""><?= e(__('Chuyển sang album…')) ?></option>
    <?php foreach ($albums as $a): if ((int) $a['id'] === (int) $album['id']) continue; ?>
      <option value="<?= (int) $a['id'] ?>"><?= e($a['title']) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="button" class="btn btn-danger btn-sm" data-act="delete"><?= e(__('Xóa')) ?></button>
  <button type="button" class="btn btn-ghost btn-sm" data-act="clear"><?= e(__('Bỏ chọn')) ?></button>
</div>

<p class="muted small"><?= e(__('Bấm ảnh để chọn nhiều · kéo thả để sắp xếp · nút ★ đặt làm ảnh bìa album, ♥ làm ảnh nền trang chủ.')) ?></p>
<div class="adm-grid" data-sortable data-sort-endpoint="<?= base_url('admin/photos/reorder') ?>" data-album-id="<?= (int) $album['id'] ?>" data-photo-grid>
  <?php foreach ($photos as $p): ?>
  <figure class="adm-photo<?= $p['status'] === 'pending' ? ' is-pending' : '' ?><?= (int) $album['cover_photo_id'] === (int) $p['id'] ? ' is-cover' : '' ?>" draggable="true" data-id="<?= (int) $p['id'] ?>">
    <img src="<?= photo_url($p, 't') ?>" alt="" loading="lazy" data-medium="<?= photo_url($p, 'm') ?>">
    <?php if ($p['status'] === 'pending'): ?><span class="ph-badge"><?= e(__('Chờ duyệt')) ?></span><?php endif; ?>
    <?php if ($p['source'] === 'guest'): ?><span class="ph-guest" title="<?= e(__('Khách gửi: {name}', array('name' => $p['guest_name'] ?: __('ẩn danh'))) . ($p['guest_message'] ? ' — ' . $p['guest_message'] : '')) ?>">👤</span><?php endif; ?>
    <div class="ph-actions">
      <button type="button" title="<?= e(__('Xem lớn')) ?>" data-view>⤢</button>
      <button type="button" title="<?= e(__('Đặt làm ảnh bìa album')) ?>" data-cover>★</button>
      <button type="button" title="<?= e(__('Đặt làm ảnh nền trang chủ')) ?>" data-hero>♥</button>
      <button type="button" title="<?= e(__('Chú thích')) ?>" data-caption="<?= e($p['caption']) ?>">✎</button>
    </div>
  </figure>
  <?php endforeach; ?>
</div>
<?php if (!$photos): ?><p class="muted center" data-empty><?= e(__('Chưa có ảnh nào. Kéo ảnh vào ô phía trên để bắt đầu.')) ?></p><?php endif;