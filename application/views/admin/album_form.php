<?php
 defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<h1 class="adm-title"><?= e($title) ?></h1>
<?php foreach ($errors as $err): ?><p class="err"><?= e($err) ?></p><?php endforeach; ?>
<form method="post" class="panel stack">
  <?= csrf_field() ?>
  <label><?= e(__('Tên album')) ?><input name="title" value="<?= e($album['title']) ?>" maxlength="120" required autofocus></label>
  <label><?= e(__('Ngày{_}', array('_' => ''))) ?><input type="date" name="event_date" value="<?= e($album['event_date']) ?>"></label>
  <label><?= e(__('Mô tả')) ?><textarea name="description" rows="3" maxlength="2000"><?= e($album['description']) ?></textarea></label>

  <fieldset>
    <legend><?= e(__('Ai được xem?')) ?></legend>
    <label class="radio"><input type="radio" name="visibility" value="public" <?= $album['visibility'] === 'public' ? 'checked' : '' ?>><span><b><?= e(__('Công khai')) ?></b> — <?= e(__('mọi người có link trang ảnh cưới.')) ?></span></label>
    <label class="radio"><input type="radio" name="visibility" value="password" <?= $album['visibility'] === 'password' ? 'checked' : '' ?> data-show-when="pw"><span><b><?= e(__('Có mật khẩu')) ?></b> — <?= e(__('ví dụ album ảnh gia đình.')) ?></span></label>
    <label class="radio"><input type="radio" name="visibility" value="hidden" <?= $album['visibility'] === 'hidden' ? 'checked' : '' ?>><span><b><?= e(__('Ẩn')) ?></b> — <?= e(__('chỉ hai bạn thấy khi đăng nhập.')) ?></span></label>
    <label data-pw-field><?= e(__('Mật khẩu album')) ?><?php if (!$is_new && !empty($album['password_hash'])): ?> <small><?= e(__('(để trống = giữ mật khẩu cũ)')) ?></small><?php endif; ?>
      <input type="text" name="password" maxlength="100" autocomplete="off"></label>
  </fieldset>

  <label class="check"><input type="checkbox" name="allow_guest_upload" value="1" <?= (int) $album['allow_guest_upload'] ? 'checked' : '' ?>>
    <?= e(__('Cho khách mời gửi ảnh vào album này')) ?></label>

  <div class="form-actions">
    <button class="btn btn-accent" type="submit"><?= e($is_new ? __('Tạo album') : __('Lưu')) ?></button>
    <a class="btn btn-ghost" href="<?= base_url($is_new ? 'admin/albums' : 'admin/albums/view/' . (int) $album['id']) ?>"><?= e(__('Hủy')) ?></a>
  </div>
</form>

<?php if (!$is_new):

$di = $del_info;
$roles = array();
if ($di['is_home']) { $roles[] = __('đang hiện ở mục Album trên TRANG CHỦ (trang chủ sẽ chuyển sang album công khai khác)'); }
if ($di['is_guest']) { $roles[] = __('đang NHẬN ẢNH KHÁCH GỬI (ảnh khách gửi sau sẽ vào album mới "Ảnh từ khách mời")'); }
$warn = __('Xóa album "{title}" và TOÀN BỘ {n} ảnh trong đó', array('title' => $album['title'], 'n' => $di['photos']))
. ($di['pending'] ? ' ' . __('(có {n} ảnh khách gửi đang chờ duyệt)', array('n' => $di['pending'])) : '') . '?'
. ($roles ? ' ' . __('Album này {roles}.', array('roles' => implode('; ', $roles))) : '') . ' ' . __('Không hoàn tác được.'); ?>
<form method="post" action="<?= base_url('admin/albums/delete/' . (int) $album['id']) ?>" class="panel danger"
      data-confirm="<?= e($warn) ?>">
  <?= csrf_field() ?>
  <h2><?= e(__('Xóa album')) ?></h2>
  <p class="muted small"><?= e(__('Xóa vĩnh viễn album cùng mọi ảnh (cả bản gốc trên máy):')) ?> <b><?= e(__('{n} ảnh', array('n' => (int) $di['photos']))) ?></b><?= $di['pending'] ? ', ' . e(__('trong đó')) . ' <b>' . e(__('{n} ảnh khách chờ duyệt', array('n' => (int) $di['pending']))) . '</b>' : '' ?>.</p>
  <?php foreach ($roles as $r): ?><p class="notice small"><?= e(__('Album này {roles}.', array('roles' => $r))) ?></p><?php endforeach; ?>
  <button class="btn btn-danger" type="submit"><?= e(__('Xóa album')) ?></button>
</form>
<?php endif;