<?php
 defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="wrap narrow lock">
  <p class="lock-icon" aria-hidden="true">🔒</p>
  <h1 class="page-title"><?= e($album['title']) ?></h1>
  <p class="muted"><?= e(__('Album này cần mật khẩu. Hỏi cô dâu chú rể để được xem nhé.')) ?></p>
  <?php if ($error): ?><p class="err"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="<?= base_url('a/' . $album['slug'] . '/unlock') ?>" class="stack">
    <?= csrf_field() ?>
    <label class="sr-only" for="album-pw"><?= e(__('Mật khẩu album')) ?></label>
    <input type="password" id="album-pw" name="password" placeholder="<?= e(__('Mật khẩu album')) ?>" required autofocus autocomplete="off">
    <button class="btn btn-accent" type="submit"><?= e(__('Xem album')) ?></button>
  </form>
</section>