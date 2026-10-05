<?php
 defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<h1 class="page-title center"><?= e(__('Đăng xuất?')) ?></h1>
<p class="muted center"><?= e(__('Bạn sẽ cần đăng nhập lại để sửa trang cưới.')) ?></p>
<form method="post" action="<?= base_url('admin/logout') ?>" class="stack">
  <?= csrf_field() ?>
  <button class="btn btn-accent btn-block" type="submit" autofocus><?= e(__('Đăng xuất')) ?></button>
</form>
<p class="center small"><a href="<?= base_url('admin') ?>">← <?= e(__('Quay lại trang quản trị')) ?></a></p>