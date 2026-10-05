<?php
 defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="lb" id="lb" hidden aria-modal="true" role="dialog" aria-label="<?= e(__('Xem ảnh')) ?>">
  <img class="lb-img" alt="">
  <p class="lb-cap"></p>
  <button type="button" class="lb-btn lb-close" aria-label="<?= e(__('Đóng')) ?>">×</button>
  <button type="button" class="lb-btn lb-prev" aria-label="<?= e(__('Ảnh trước')) ?>">‹</button>
  <button type="button" class="lb-btn lb-next" aria-label="<?= e(__('Ảnh sau')) ?>">›</button>
  <?php if (!empty($is_admin) || album_download_allowed()):  ?>
  <a class="lb-btn lb-dl" download title="<?= e(__('Tải ảnh gốc về máy')) ?>"><span aria-hidden="true">⤓</span> <?= e(__('Tải ảnh')) ?></a>
  <?php endif; ?>
  <span class="lb-count"></span>
  <span class="lb-tip" aria-hidden="true"><?= e(__('Chạm 2 lần hoặc chụm 2 ngón để phóng to')) ?></span>
</div>