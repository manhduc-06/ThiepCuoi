<?php
 defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<h1 class="page-title center"><?= e($couple) ?></h1>
<p class="muted center"><?= e(__('Đăng nhập quản trị')) ?> · <?= e(base_url('admin')) ?></p>
<?php if (!empty($notice)): ?><p class="notice"><?= e($notice) ?></p><?php endif; ?>
<?php if ($error): ?><p class="err"><?= e($error) ?></p><?php endif; ?>
<?php if (getenv('ANHCUOI_DEMO')): ?>
<p class="notice center"><?= __('Trang demo — đăng nhập bằng <b>demo</b> / <b>demo2026</b> để thử sửa trang, mời khách…')  ?><br><span class="small muted"><?= e(__('Dữ liệu tự khôi phục mỗi giờ.')) ?></span></p>
<?php endif; ?>
<form method="post" action="<?= base_url('admin/login') ?>" class="stack">
  <?= csrf_field() ?>
  <input type="hidden" name="next" value="<?= e($next) ?>">
  <label><?= e(__('Tên đăng nhập')) ?><input name="username" value="<?= e($username) ?>" required <?= $username === '' ? 'autofocus' : '' ?> autocomplete="username"></label>
  <label><?= e(__('Mật khẩu')) ?><input type="password" name="password" required <?= $username !== '' ? 'autofocus' : '' ?> autocomplete="current-password"></label>
  <button class="btn btn-accent btn-block" type="submit"><?= e(__('Đăng nhập')) ?></button>
</form>
<p class="center small"><a href="<?= base_url() ?>">← <?= e(__('Về trang ảnh cưới')) ?></a></p>