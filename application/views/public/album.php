<?php
 defined('BASEPATH') OR exit('No direct script access allowed');

$can_dl = album_download_allowed() || $is_admin; ?>
<section class="wrap album-head">
  <a class="back" href="<?= base_url((isset($home_path) ? $home_path : '') . '#gallery') ?>">← <?= e(__('Tất cả album')) ?></a>
  <h1 class="page-title"><?= e($album['title']) ?></h1>
  <?php if ($album['event_date']): ?><p class="muted"><?= e(vn_date($album['event_date'])) ?></p><?php endif; ?>
  <?php if ($album['description']): ?><p class="album-desc"><?= nl2br(e($album['description'])) ?></p><?php endif; ?>
  <div class="album-actions">
    <span class="muted"><?= e(__n('{n} ảnh', count($photos))) ?></span>
    <?php if ($can_dl && $photos): ?><a class="btn btn-ghost" href="<?= base_url('a/' . $album['slug'] . '/zip') ?>"><?= e(__('Tải cả album (.zip)')) ?></a><?php endif; ?>
    <?php if ((int) $album['allow_guest_upload'] === 1 && $settings['guest_upload'] === '1'): ?>
      <a class="btn btn-accent" href="<?= base_url('gui-anh?album=' . rawurlencode($album['slug'])) ?>"><?= e(__('Gửi ảnh vào album này')) ?></a>
    <?php endif; ?>
  </div>
</section>
<section class="gallery-wrap">
  <?php if (!$photos): ?>
    <p class="muted center"><?= e(__('Album chưa có ảnh.')) ?></p>
  <?php else: ?>
  <div class="gallery" data-gallery>
    <?php $n_photos = count($photos); foreach ($photos as $pi => $p):
$cap = trim((string) $p['caption']);
if ($p['source'] === 'guest' && $p['guest_name']) {
$cap = trim($cap . ($cap !== '' ? ' — ' : '') . __('Ảnh của {ten}', array('ten' => $p['guest_name'])));
} ?>
      <?php $p_alt = __('Ảnh {i}/{n}', array('i' => $pi + 1, 'n' => $n_photos)) . ' — ' . $album['title'] . ($cap !== '' ? ': ' . $cap : ''); ?>
      <a class="g-item" href="<?= photo_url($p, 'm') ?>" data-srcset="<?= e(photo_srcset($p, 'm')) ?>" data-lb aria-label="<?= e(__('Xem {anh}', array('anh' => mb_strtolower(mb_substr($p_alt, 0, 1)) . mb_substr($p_alt, 1)))) ?>"
         <?php if ($can_dl): ?>data-full="<?= photo_url($p, 'o') ?>"<?php endif; ?>
         data-cap="<?= e($cap) ?>">
        <img src="<?= photo_url($p, 't') ?>" srcset="<?= e(photo_srcset($p, 's')) ?>" sizes="(max-width: 560px) 34vw, (max-width: 1024px) 33vw, 25vw" width="<?= (int) $p['width'] ?>" height="<?= (int) $p['height'] ?>" alt="<?= e($p_alt) ?>" loading="lazy" decoding="async">
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>