<?php
 defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('partials/head', array('pub_theme' => TRUE));

$home = isset($home_path) ? (string) $home_path : ''; ?>
<body class="pub">
<header class="pub-nav">
  <a class="pub-brand" href="<?= base_url($home) ?>"><?= e($couple) ?></a>
  <nav>
    <a href="<?= base_url($home . '#gallery') ?>"><?= e(__('Album')) ?></a>
    <?php if ($settings['guest_upload'] === '1'): ?><a href="<?= base_url('gui-anh') ?>"><?= e(__('Gửi ảnh')) ?></a><?php endif; ?>
    <?php if ($settings['wishes_enabled'] === '1'): ?><a href="<?= base_url($home . '#loi-chuc') ?>"><?= e(__('Lời chúc')) ?></a><?php endif; ?>
    <?php if ($is_admin): ?><a class="pub-admin" href="<?= base_url('admin') ?>"><?= e(__('Quản trị')) ?></a><?php endif; ?>
    <?= lang_switch_html() ?>
  </nav>
</header>
<?php $this->load->view('partials/flash'); ?>
<main>
<?php $this->load->view($content_view); ?>
</main>
<footer class="pub-foot">
  <p><?= e($couple) ?><?php if ($settings['wedding_date']): ?> · <?= e(vn_date($settings['wedding_date'], FALSE)) ?><?php endif; ?></p>
  <?php 
$cr_gh = '<svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>';
$cr_rings = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="13" r="6"/><circle cx="15" cy="13" r="6"/><path d="M12 4l-1.5 3h3z"/></svg>'; ?>
</footer>
<?php $this->load->view('partials/lightbox'); ?>
<?php
// Trang con của trang cưới (album, gửi ảnh) phát tiếp nhạc nền; trang khóa/404/"đang chuẩn bị" thì không.
$pub_music = in_array($content_view, array('public/album', 'public/album_locked', 'public/upload', 'public/upload_closed'), TRUE)
? $content->music() : NULL;
if ($pub_music): ?>
<div class="music" data-music data-autoplay="<?= $settings['music_autoplay'] === '1' ? '1' : '0' ?>">
  <button type="button" class="music-btn" aria-label="<?= e(__('Bật/tắt nhạc')) ?>" data-music-toggle>♫</button>
  <audio src="<?= e($pub_music['url']) ?>" loop preload="none"></audio>
  <span class="music-hint" data-music-hint hidden>♫ <?= e(__('Chạm để nghe nhạc')) ?></span>
</div>
<?php endif; ?>
<script src="<?= asset_url('js/app.js') ?>"></script>
<?php if ($pub_music): ?><script src="<?= asset_url('js/music.js') ?>"></script><?php endif; ?>
</body>
</html>