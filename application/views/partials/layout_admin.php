<?php
 defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('partials/head');
$seg = $this->uri->segment(2) ?: 'dashboard';
$nav = array(
'dashboard' => array('admin', __('Tổng quan'), 0),
'edit' => array('', '✎ ' . __('Sửa trang cưới'), 0),
'guests' => array('admin/guests', __('Khách mời'), 0),
'albums' => array('admin/albums', __('Ảnh'), $pending_photos),
'wishes' => array('admin/moderation/wishes', __('Lời chúc'), $pending_wishes),
'share' => array('admin/share', __('Gửi link & QR'), 0),
'settings' => array('admin/settings', __('Cài đặt'), 0),
);

$active = ($seg === 'moderation' && $this->uri->segment(3) === 'wishes') ? 'wishes' : ($seg === 'moderation' ? 'albums' : $seg);
?>
<link rel="stylesheet" href="<?= asset_url('css/admin.css') ?>">
<body class="adm">
<header class="adm-top">
  <a class="adm-brand" href="<?= base_url('admin') ?>"><?= e($couple) ?></a>
  <button class="adm-menu-btn" type="button" aria-label="<?= e(__('Mở menu')) ?>" data-toggle-nav>☰</button>
  <nav class="adm-nav" id="adm-nav">
    <?php foreach ($nav as $key => $n): ?>
      <a href="<?= base_url($n[0]) ?>" class="<?= $active === $key ? 'on' : '' ?><?= $key === 'edit' ? ' nav-edit' : '' ?>"><?= e($n[1]) ?><?php if ($n[2]): ?> <span class="badge"><?= (int) $n[2] ?></span><?php endif; ?></a>
    <?php endforeach; ?>
    <?php if (!hosted()):  ?>
    <a class="nav-donate" href="<?= e($this->config->item('donate_url')) ?>" target="_blank" rel="noopener" title="<?= e(__('Phần mềm miễn phí — ủng hộ để dự án phát triển tiếp')) ?>">♡ <?= e(__('Ủng hộ')) ?></a>
    <?php endif; ?>
    <a href="<?= base_url('admin/logout') ?>"><?= e(__('Đăng xuất')) ?></a>
  </nav>
  <?php $this->load->view('partials/_lang_switch'); ?>
</header>
<?php $this->load->view('partials/flash'); ?>
<main class="adm-main">
<?php $this->load->view($content_view); ?>
</main>
<?php $this->load->view('partials/lightbox'); ?>
<script src="<?= asset_url('js/app.js') ?>"></script>
<script src="<?= asset_url('js/uploader.js') ?>"></script>
<script src="<?= asset_url('js/admin.js') ?>"></script>
</body>
</html>