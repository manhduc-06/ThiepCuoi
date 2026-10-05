<?php
 defined('BASEPATH') OR exit('No direct script access allowed');





$rs = $rsvp_s;
$gl = function ($loc) { return base_url('admin/guests' . ($loc !== '' ? '?loc=' . $loc : '')); };
?>
<div class="stats guest-stats rsvp-stats">
  <a class="stat" href="<?= $gl('invited') ?>"><b><?= (int) $rs['invited'] ?></b><span><?= e(__('thiệp mời riêng')) ?></span></a>
  <a class="stat" href="<?= $gl('opened_all') ?>"><b><?= (int) $rs['inv_opened'] ?></b><span><?= e(__('đã mở thiệp · {p}%', array('p' => (int) $rs['open_rate']))) ?></span></a>
  <a class="stat stat-ok" href="<?= $gl('yes') ?>"><b><?= (int) $rs['yes'] ?></b><span><?= e(__('tham dự · {n} người', array('n' => (int) $rs['people']))) ?><?php if ($rs['web_yes']): ?> · <?= e(__('trong đó {n} tự xác nhận trên web', array('n' => (int) $rs['web_yes']))) ?><?php endif; ?></span></a>
  <a class="stat" href="<?= $gl('no') ?>"><b><?= (int) $rs['no'] ?></b><span><?= e(__('từ chối')) ?><?php if ($rs['web_no']): ?> · <?= e(__('trong đó {n} tự xác nhận trên web', array('n' => (int) $rs['web_no']))) ?><?php endif; ?></span></a>
  <a class="stat" href="<?= $gl('pending') ?>"><b><?= (int) $rs['pending'] ?></b><span><?= e(__('chưa trả lời · {n} chưa mở', array('n' => (int) $rs['inv_unopened']))) ?></span></a>
  <div class="stat"><b><?= (int) $rs['response_rate'] ?>%</b><span><?= e(__('tỉ lệ phản hồi ({a}/{b})', array('a' => (int) $rs['inv_responded'], 'b' => (int) $rs['invited']))) ?><?= $rs['web'] ? ' · ' . e(__('{n} tự xác nhận trên web', array('n' => (int) $rs['web']))) : '' ?></span></div>
</div>