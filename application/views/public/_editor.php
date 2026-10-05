<?php
 defined('BASEPATH') OR exit('No direct script access allowed');

?>
<!--ed:start-->
<?php

$steps = $c->checklist();
$done = count(array_filter(array_column($steps, 'done')));
$pub = public_url();
$ed_share_text = share_invite_text($c->couple_title(), (string) $date);
$ed_en = lang_cur() === 'en';
?>
<aside class="ed-bar" data-editor data-fx-set="<?= $c->get('fx', '') !== '' && $c->get('fx_auto', '0') !== '1' ? '1' : '0' ?>" data-unpublished="<?= $unpublished ? '1' : '0' ?>" data-public-url="<?= e($pub) ?>" data-local="<?= (strpos($pub, 'https://') === 0) ? '0' : '1' ?>" data-first="<?= $published_at ? '0' : '1' ?>" data-share-text="<?= e($ed_share_text) ?>">
  <div class="ed-status">
    <b data-ed-state><?= e($unpublished ? ($published_at ? __('Có thay đổi khách chưa thấy') : __('Bản nháp — khách chưa xem được')) : __('Khách đang xem bản này ✓')) ?></b>
    <span class="ed-hint"><?= e(__('Chạm vào chữ hoặc ảnh có viền nét đứt để sửa — tự lưu')) ?></span>
  </div>
  <button type="button" class="btn btn-ghost btn-sm ed-steps-btn" data-ed-steps-toggle aria-expanded="false" aria-label="<?= e(__('Việc cần làm')) ?>"><span class="ed-steps-lbl"><?= e(__('Việc cần làm')) ?></span> <b><?= $done ?>/<?= count($steps) ?></b></button>
  <div class="ed-themes" role="group" aria-label="<?= e(__('Chọn giao diện')) ?>">
    <?php 
$vip_on = pro_enabled(); $vip_lbl = vip_labels(); $vip_first = TRUE;
foreach ($themes as $key => $t): $vip = !empty($t['pro']); $locked = $vip && !$vip_on;
$tn = ($ed_en && !empty($t['name_en'])) ? $t['name_en'] : $t['name'];
$td = ($ed_en && !empty($t['desc_en'])) ? $t['desc_en'] : (isset($t['desc']) ? $t['desc'] : ''); ?>
      <?php if ($vip && $vip_first && $vip_lbl): $vip_first = FALSE; ?><span class="ed-vip-sep" aria-hidden="true"><b>VIP</b><?= $vip_on ? '' : ' · ' . e(__('có trên thiep.site')) ?></span><?php endif; ?>
      <button type="button" class="ed-theme<?= $key === $theme ? ' on' : '' ?><?= $vip && $vip_lbl ? ' ed-theme--vip' : '' ?><?= $locked ? ' is-locked' : '' ?>" data-theme-pick="<?= e($key) ?>" data-accent="<?= e($t['accent']) ?>"
        title="<?= e($tn) ?><?= $locked ? e($vip_lbl ? ' — ' . __('VIP · có trên thiep.site') : ' — ' . __('có trên thiep.site')) : '' ?>"<?php if ($vip): ?> data-vip-name="<?= e($tn) ?>" data-vip-desc="<?= e($td) ?>" data-vip-img="<?= asset_url('img/theme-previews/' . $key . '.jpg') ?>"<?php endif; ?><?= $locked ? ' data-vip-locked aria-label="' . e($tn . ' — ' . ($vip_lbl ? __('giao diện VIP, có trên thiep.site') : __('có trên thiep.site'))) . '"' : '' ?>>
        <?php if ($vip): ?>
        <span class="ed-theme-logo ed-theme-thumb" aria-hidden="true"><img src="<?= asset_url('img/theme-previews/' . $key . '-t.jpg') ?>" alt="" loading="lazy" decoding="async"><?php if ($locked): ?><i class="ed-lock"><svg viewBox="0 0 16 16"><path d="M4.5 7V5a3.5 3.5 0 0 1 7 0v2" fill="none" stroke="currentColor" stroke-width="1.6"/><rect x="3" y="7" width="10" height="7.5" rx="1.6" fill="currentColor"/></svg></i><?php endif; ?></span>
        <?php else: ?>
        <span class="ed-theme-logo tl-<?= e($key) ?>" aria-hidden="true"><svg viewBox="0 0 40 40"><use href="#<?= $key === 'songhy' ? 'd-songhy' : ($key === 'demsao' ? 'd-moon' : 'ico-rings') ?>"/></svg></span>
        <?php endif; ?>
        <span class="ed-theme-name"><?= e($tn) ?></span>
      </button>
    <?php endforeach; ?>
  </div>
  <div class="ed-actions">
    <?php ?>
    <button type="button" class="btn btn-ghost btn-sm ed-mob ed-fx-bar" data-fx-toggle aria-label="<?= e(__('Chọn hiệu ứng rơi')) ?>" title="<?= e(__('Hiệu ứng rơi')) ?>">✨</button>
    <a class="btn btn-ghost btn-sm ed-desk" href="<?= base_url('?xem=khach') ?>" target="_blank" rel="noopener"><?= e(__('Xem như khách')) ?></a>
    <a class="btn btn-ghost btn-sm ed-desk" href="<?= base_url('admin') ?>"><?= e(__('Quản trị')) ?></a>
    <?php $this->load->view('partials/_lang_switch');  ?>
    <button type="button" class="btn btn-accent btn-sm ed-primary" data-publish><?= e($unpublished ? __('Cho khách xem') : __('Gửi cho khách')) ?></button>
  </div>
  <div class="ed-steps" data-ed-steps hidden>
    <p class="ed-steps-title"><?= e(__('Làm trang cưới trong 3 bước')) ?></p>
    <p class="ed-step-h"><span>1</span> <?= e(__('Điền thông tin & thêm ảnh')) ?></p>
    <ol>
      <?php foreach ($steps as $s): if ($s['group'] !== 1) continue; ?>
        <li class="<?= $s['done'] ? 'ok' : '' ?>" data-step="<?= e($s['key']) ?>"><a href="<?= e($s['href']) ?>"><?= e($s['label']) ?></a></li>
      <?php endforeach; ?>
    </ol>
    <p class="ed-step-h"><span>2</span> <?= e(__('Chọn giao diện, nhạc & hiệu ứng')) ?></p>
    <p class="small ed-step-tip"><?= e(__('Giao diện: chọn ở thanh dưới. Nhạc: chạm nút ♫ trên trang. Hiệu ứng rơi (tim, hoa, tuyết…): nút ✨ ngay trên nút ♫, hoặc chọn ở đây.')) ?></p>
    <label class="small ed-fx-label"><?= e(__('Hiệu ứng')) ?>
      <select data-fx-pick><?php foreach (Content_model::EFFECTS as $k => $v): ?><option value="<?= e($k) ?>" <?= $fx === $k ? 'selected' : '' ?>><?= e(__($v)) ?></option><?php endforeach; ?></select></label>
    <p class="ed-step-h"><span>3</span> <?= e(__('Cho khách xem & gửi link')) ?></p>
    <ol class="ed-ol3">
      <?php foreach ($steps as $s): if ($s['group'] !== 3) continue; ?>
        <li class="<?= $s['done'] ? 'ok' : '' ?>" data-step="<?= e($s['key']) ?>"><a href="#top" data-ed-go-publish><?= e(__('{step} rồi gửi link', array('step' => $s['label']))) ?></a></li>
      <?php endforeach; ?>
    </ol>
    <?php if ($home_album): ?><p class="small muted"><?= e(__('Album ở trang chủ:')) ?> <b><?= e($home_album['title']) ?></b> — <a href="<?= base_url('admin/albums/view/' . $home_album['id']) ?>"><?= e(__('thêm ảnh')) ?></a></p><?php endif; ?>
    <p class="ed-links small">
      <a href="<?= base_url('?xem=khach') ?>" target="_blank" rel="noopener"><?= e(__('Xem như khách')) ?></a>
      <a href="<?= base_url('admin/share') ?>"><?= e(__('Link & mã QR')) ?></a>
      <a href="<?= base_url('admin/guests') ?>"><?= e(__('Khách mời')) ?></a>
      <a href="<?= base_url('admin') ?>"><?= e(__('Quản trị')) ?></a>
      <button type="button" data-ed-welcome-open><?= e(__('Xem lại hướng dẫn')) ?></button>
    </p>
  </div>
</aside>
<div class="ed-modal ed-welcome" data-ed-welcome hidden role="dialog" aria-modal="true" aria-labelledby="edw-h">
  <div class="ed-modal-box">
    <h3 id="edw-h"><?= e(__('Chào {name} ♡', array('name' => $c->couple_title()))) ?></h3>
    <p><?= e(__('Trang cưới mẫu đã sẵn sàng. Chỉ cần 3 bước:')) ?></p>
    <ol class="edw-steps">
      <li><span><b><?= e(__('Chạm vào chữ hoặc ảnh có viền nét đứt')) ?></b> <?= e(__('để sửa tên, ngày, ảnh, địa điểm. Tự lưu ngay.')) ?></span></li>
      <li><span><b><?= e(__('Chọn giao diện')) ?></b> <?= e(__('ở thanh dưới cùng, chạm nút ♫ để chọn nhạc, nút ✨ để chọn hiệu ứng rơi.')) ?></span></li>
      <li><span><?= e(__('Bấm “Cho khách xem” rồi gửi link qua Zalo, Messenger.')) ?></span></li>
    </ol>
    <p class="small muted"><?= e(__('Khách chỉ thấy trang sau bước 3. Cần xem lại? Bấm "Việc cần làm".')) ?></p>
    <button type="button" class="btn btn-accent edw-go" data-ed-welcome-close><?= e(__('Bắt đầu sửa')) ?></button>
  </div>
</div>
<!--ed:end-->