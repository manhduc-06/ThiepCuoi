<?php
 defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="wrap narrow lock">
  <p class="lock-icon" aria-hidden="true">♡</p>
  <h1 class="page-title"><?= e(__('Bạn thử lại sau ít phút nhé')) ?></h1>
  <?php if (!empty($message)): ?>
  <p class="muted"><?= e($message) ?></p>
  <p><a class="btn btn-ghost" href="<?= e($back_url) ?>"><?= e($back_text) ?></a></p>
  <?php else: ?>
  <p class="muted"><?= e(__('Có nhiều lần mở đường dẫn chưa đúng từ mạng của bạn. Đợi khoảng 10 phút rồi mở lại link trên thiệp mời nhé.')) ?></p>
  <p><a class="btn btn-ghost" href="<?= base_url() ?>"><?= e(__('Xem trang cưới')) ?> →</a></p>
  <?php endif; ?>
</section>