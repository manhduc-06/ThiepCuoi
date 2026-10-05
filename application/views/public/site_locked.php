<?php
 defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="wrap narrow lock">
  <p class="lock-icon" aria-hidden="true">♡</p>
  <h1 class="page-title"><?= e($couple) ?></h1>
  <p class="muted"><?= e(__('Trang ảnh cưới này chỉ dành cho người thân và bạn bè. Nhập mật khẩu trên thiệp mời để xem.')) ?></p>
  <?php if (!empty($invite_hint)):  ?>
  <p class="muted small" data-invite-hint><?= e(__('Bạn có thể dùng link trong tin nhắn mời để vào thẳng, không cần mật khẩu.')) ?></p>
  <?php endif; ?>
  <?php if (!empty($error)): ?><p class="err"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="<?= base_url('unlock') ?>" class="stack">
    <?= csrf_field() ?>
    <input type="hidden" name="back" value="<?= e((string) ($this->input->post('back') ?? uri_string())) ?>">
    <label class="sr-only" for="site-pw"><?= e(__('Mật khẩu xem trang')) ?></label>
    <input type="password" id="site-pw" name="password" placeholder="<?= e(__('Mật khẩu')) ?>" required autofocus autocomplete="off">
    <button class="btn btn-accent" type="submit"><?= e(__('Vào xem')) ?></button>
  </form>
</section>