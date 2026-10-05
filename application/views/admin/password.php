<?php
 defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<a class="back" href="<?= base_url('admin/settings') ?>">← <?= e(__('Cài đặt')) ?></a>
<h1 class="adm-title"><?= e(__('Đổi mật khẩu')) ?></h1>
<?php foreach ($errors as $err): ?><p class="err"><?= e($err) ?></p><?php endforeach; ?>
<form method="post" class="panel stack narrow-form">
  <?= csrf_field() ?>
  <label><?= e(__('Mật khẩu hiện tại')) ?><input type="password" name="current" required autocomplete="current-password"></label>
  <label><?= e(__('Mật khẩu mới')) ?><input type="password" name="new" minlength="8" required autocomplete="new-password"></label>
  <label><?= e(__('Nhập lại mật khẩu mới')) ?><input type="password" name="new2" minlength="8" required autocomplete="new-password"></label>
  <div class="form-actions"><button class="btn btn-accent" type="submit"><?= e(__('Đổi mật khẩu')) ?></button></div>
</form>