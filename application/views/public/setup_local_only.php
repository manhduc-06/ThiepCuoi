<?php
 defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<h1 class="page-title center"><?= e(__('Chưa cài đặt xong')) ?></h1>
<p class="muted center"><?= e(__('Để bảo mật, bước tạo tài khoản quản trị chỉ làm được trên chính máy đang chạy Ảnh Cưới (hoặc máy trong cùng mạng nhà): mở')) ?>
  <b>http://localhost:<?= (int) app_port() ?>/admin</b> <?= e(__('trên máy đó.')) ?></p>