<?php
 defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->helper('edit'); ?>
<!doctype html>
<html lang="<?= lang_cur() ?>" data-theme="<?= e($theme) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title><?= e(__($title)) ?></title>
<meta property="og:title" content="<?= e(__($title)) ?>">
<?php if (!empty($img['img.hero_main'])): ?><meta property="og:image" content="<?= photo_url($img['img.hero_main'], 'm') ?>">
<?php 
if (empty($invite_card)): list($p_x, $p_y, $p_z) = $this->content_model->image_pos('img.hero_main'); ?>
<link rel="preload" as="image" imagesrcset="<?= e(photo_srcset($img['img.hero_main'], 'm')) ?>" imagesizes="<?= e(ed_img_sizes('img.hero_main', $p_z, $theme)) ?>" fetchpriority="high">
<?php endif; endif; ?>
<meta name="csrf-name" content="<?= e($this->security->get_csrf_token_name()) ?>">
<meta name="csrf-hash" content="<?= e($this->security->get_csrf_hash()) ?>">
<meta name="base-url" content="<?= e(base_url()) ?>">
<link rel="icon" href="<?= asset_url('img/icon.svg') ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= asset_url('fonts/fonts.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/app.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/wedding.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/wedding-themes.css') ?>">
<?php 
if (pro_enabled()): foreach ($this->content_model->registry('themes') as $pk => $pt): if (!empty($pt['pro']) && ($draft || $pk === $theme) && is_file(FCPATH . 'assets/css/pro/' . $pk . '.css')): ?>
<link rel="stylesheet" href="<?= asset_url('css/pro/' . $pk . '.css') ?>">
<?php endif; endforeach; endif; ?>
<?php if ($draft): ?><link rel="stylesheet" href="<?= asset_url('css/editor.css') ?>"><?php endif; ?>
<?php if (!empty($invite_card)):  ?><link rel="stylesheet" href="<?= asset_url('css/invite-card.css') ?>">
<link rel="stylesheet" href="<?= asset_url($this->content_model->card_paths($card_style)['css']) ?>"><?php endif; ?>
<?php if (!empty($gift) || $draft): ?><link rel="stylesheet" href="<?= asset_url('css/gift.css') ?>"><?php endif; ?>
<?= i18n_script() ?>
</head>
<body class="wd<?= $draft ? ' is-draft' : '' ?>">
<?php $this->load->view('partials/flash'); ?>
<?php $this->load->view($content_view); ?>
<?php $this->load->view('partials/lightbox'); ?>
<script src="<?= asset_url('js/app.js') ?>"></script>
<script src="<?= asset_url('js/music.js') ?>"></script>
<script src="<?= asset_url('js/wedding.js') ?>"></script>
<?php if (!empty($invite_card)): ?><script src="<?= asset_url('js/invite-card.js') ?>"></script><?php endif; ?>
<?php if (!empty($gift)): ?><?php if (!$draft): ?><script src="<?= asset_url('js/vendor/qrcode.js') ?>"></script><?php endif; ?><script src="<?= asset_url('js/gift.js') ?>" defer></script><?php endif; ?>
<?php if ($draft): ?>
<script src="<?= asset_url('js/vendor/qrcode.js') ?>"></script>
<script src="<?= asset_url('js/uploader.js') ?>"></script>
<script src="<?= asset_url('js/editor.js') ?>"></script>
<?php endif; ?>
</body>
</html>