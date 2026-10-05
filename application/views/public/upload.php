<?php
 defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="wrap narrow upload-page">
  <h1 class="page-title"><?= e(__('Gửi ảnh cho {cap_doi}', array('cap_doi' => $couple))) ?></h1>
  <p class="muted"><?= e(__('Ảnh sẽ vào album')) ?> <b><?= e($album['title']) ?></b><?= $settings['guest_upload_approval'] === '1' ? ' ' . e(__('sau khi cô dâu chú rể xem qua')) : '' ?>. <?= e(__('Chọn nhiều ảnh một lúc được nhé.')) ?></p>

  <form class="guest-upload" data-guest-upload
        data-endpoint="<?= base_url('gui-anh/upload') ?>"
        data-album="<?= e($album['slug']) ?>"
        data-max-mb="<?= (int) $max_mb ?>"
        data-approval="<?= $settings['guest_upload_approval'] === '1' ? '1' : '0' ?>">
    <label><?= e(__('Tên của bạn')) ?> <small><?= e(__('(không bắt buộc)')) ?></small><input name="guest_name" maxlength="60" autocomplete="name" placeholder="<?= e(__('Ví dụ: Lan – bạn thân cô dâu')) ?>"></label>
    <label><?= e(__('Lời nhắn kèm ảnh')) ?> <small><?= e(__('(không bắt buộc)')) ?></small><input name="guest_message" maxlength="300"></label>

    <label class="drop" data-drop>
      <input type="file" accept="image/*" multiple data-files>
      <span class="drop-big">＋</span>
      <span><?= e(__('Chạm để chọn ảnh từ điện thoại')) ?></span>
      <small><?= e(__('JPG, PNG, WEBP · tối đa {n} MB mỗi ảnh', array('n' => (int) $max_mb))) ?></small>
    </label>

    <div class="up-summary" data-summary hidden></div>
    <ul class="up-list" data-list></ul>
  </form>
  <p class="center"><a class="back" href="<?= base_url() ?>">← <?= e(__('Về trang chính')) ?></a></p>
</section>
<script src="<?= asset_url('js/uploader.js') ?>"></script>