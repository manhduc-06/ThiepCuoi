<?php
 defined('BASEPATH') OR exit('No direct script access allowed');

$pub_t = (!empty($pub_theme) && !empty($theme)) ? (string) $theme : '';
$pub_pro = '';
if ($pub_t !== '' && pro_enabled()) {
$pub_reg = $this->content_model->registry('themes');
if (!empty($pub_reg[$pub_t]['pro']) && is_file(FCPATH . 'assets/css/pro/' . $pub_t . '.css')) {
$pub_pro = 'css/pro/' . $pub_t . '.css';
}
} ?>
<!doctype html>
<html lang="<?= lang_cur() ?>"<?= $pub_t !== '' ? ' data-theme="' . e($pub_t) . '"' : '' ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title><?= e(isset($title) ? __($title) : $couple) ?></title>
<meta name="csrf-name" content="<?= e($this->security->get_csrf_token_name()) ?>">
<meta name="csrf-hash" content="<?= e($this->security->get_csrf_hash()) ?>">
<meta name="base-url" content="<?= e(base_url()) ?>">
<link rel="icon" href="<?= asset_url('img/icon.svg') ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= asset_url('fonts/fonts.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/app.css') ?>">
<?php if ($pub_t !== ''): ?>
<link rel="stylesheet" href="<?= asset_url('css/wedding.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/wedding-themes.css') ?>">
<?php if ($pub_pro !== ''): ?><link rel="stylesheet" href="<?= asset_url($pub_pro) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= asset_url('css/pub-theme.css') ?>">
<?php else: ?>
<style>:root{--accent:<?= e($theme_accent) ?>}</style>
<?php endif; ?>
<?= i18n_script() ?>
</head>