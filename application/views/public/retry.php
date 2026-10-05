<?php
 defined('BASEPATH') OR exit('No direct script access allowed');




$vis = array('name' => __('Tên'), 'message' => $action === 'loi-chuc' ? __('Lời chúc') : __('Lời chúc gửi cô dâu chú rể'));
if (array_key_exists('phone', $fields)) {
$vis['phone'] = __('Số điện thoại'); 
}
?>
<section class="wrap narrow lock retry">
  <p class="lock-icon" aria-hidden="true">♡</p>
  <h1 class="page-title"><?= e(__('Chưa gửi được')) ?></h1>
  <p class="err" role="alert"><?= e($error) ?></p>
  <form method="post" action="<?= base_url($action) ?>" class="stack">
    <?= csrf_field() ?>
    <?php foreach ($fields as $k => $v): if (isset($vis[$k])) continue; ?>
      <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
    <?php endforeach; ?>
    <?php if (array_key_exists('name', $fields) && ($action === 'loi-chuc' || ($fields['code'] ?? '') === '')): ?>
      <label><?= e(__('Tên')) ?><input name="name" maxlength="80" required value="<?= e($fields['name']) ?>" autocomplete="name"></label>
    <?php endif; ?>
    <?php if (isset($vis['phone'])): ?>
      <label><?= e(__('Số điện thoại')) ?> <small><?= e(__('(không bắt buộc)')) ?></small><input name="phone" type="tel" maxlength="20" inputmode="tel" value="<?= e($fields['phone']) ?>" autocomplete="tel"<?= ($error_field ?? '') === 'phone' ? ' aria-invalid="true" autofocus' : '' ?>></label>
    <?php endif; ?>
    <label><?= e($vis['message']) ?><?= $action === 'loi-chuc' ? '' : ' <small>' . e(__('(không bắt buộc)')) . '</small>' ?>
      <textarea name="message" rows="4" maxlength="1000"<?= $action === 'loi-chuc' ? ' required' : '' ?>><?= e($fields['message'] ?? '') ?></textarea></label>
    <button class="btn btn-accent" type="submit"><?= e($action === 'loi-chuc' ? __('Gửi lại lời chúc') : __('Gửi lại xác nhận')) ?></button>
  </form>
  <p><a class="back" href="<?= base_url(ltrim($back, '/')) ?>">← <?= e(__('Quay lại trang cưới')) ?></a></p>
</section>