<?php
 defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div style="border:1px solid #d99;padding:8px 12px;margin:8px;font:13px monospace;background:#fff5f5">
<b><?= htmlspecialchars($severity, ENT_QUOTES, 'UTF-8') ?>:</b> <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
— <?= htmlspecialchars(basename($filepath), ENT_QUOTES, 'UTF-8') ?>:<?= (int) $line ?>
</div>