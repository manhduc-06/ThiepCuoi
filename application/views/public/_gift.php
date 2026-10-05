<?php
 defined('BASEPATH') OR exit('No direct script access allowed');





if (empty($gift) || empty($gift['accounts'])) {
return;
}

$gift_group = function ($acct) {
$acct = (string) $acct;
$n = strlen($acct);
if ($n > 4 && $n % 4 === 1) {
return trim(chunk_split(substr($acct, 0, $n - 5), 4, ' ') . substr($acct, -5));
}
return trim(chunk_split($acct, 4, ' '));
};

$gift_thanks = isset($voice['gift_thanks']) ? $voice['gift_thanks'] : __('Cảm ơn tấm lòng của bạn ♡');
?>
<section class="wrap gift" id="mung-cuoi" aria-labelledby="gift-h">
  <div class="sec-head">
    <h2 id="gift-h"><?= e($gift['title']) ?></h2>
    <div class="divider"><span>♥</span></div>
  </div>
  <?php if ($gift['text'] !== ''): ?><p class="gift-text"><?= nl2br(e($gift['text'])) ?></p><?php endif; ?>
  <div class="gift-cards<?= count($gift['accounts']) === 1 ? ' is-single' : '' ?>">
    <?php foreach ($gift['accounts'] as $a): ?>
    <article class="gift-card">
      <p class="gift-side"><?= e($a['side']) ?></p>
      <div class="gift-qr" role="img" aria-label="<?= e(__('Mã QR chuyển khoản {bank} {acct}', array('bank' => $a['bank'], 'acct' => $a['account']))) ?>"
        <?php if ($a['qr_url'] !== ''): ?>><img src="<?= e($a['qr_url']) ?>" alt="" loading="lazy" decoding="async">
        <?php else: ?>data-vietqr="<?= e($a['payload']) ?>"><span class="gift-qr-ph">QR</span><?php endif; ?>
      </div>
      <p class="gift-holder"><?= e($a['holder']) ?></p>
      <?php if ($a['bank'] !== ''): ?><p class="gift-bank"><?= e($a['bank']) ?></p><?php endif; ?>
      <?php if ($a['account'] !== ''): ?>
      <p class="gift-acct"><span class="gift-num"><?= e($gift_group($a['account'])) ?></span>
        <button type="button" class="gift-copy" data-gift-copy="<?= e($a['account']) ?>"><?= e(__('Sao chép')) ?></button></p>
      <?php endif; ?>
      <?php if ($a['qr_url'] === '' && $a['payload'] !== ''): ?>
        <button type="button" class="gift-dl" data-gift-dl="<?= e('mung-cuoi-' . $a['key']) ?>"><?= e(__('Tải mã QR')) ?></button>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
  </div>
  <p class="gift-hint"><?= e(__('Mở ứng dụng ngân hàng → Quét QR')) ?> <span data-gift-alt><?= e(__('(hoặc chọn ảnh QR đã tải)')) ?></span>.
    <span data-gift-shot><?= e(__('Nếu không tải được, hãy chụp màn hình mã QR.')) ?></span><br><span class="gift-thanks"><?= e($gift_thanks) ?></span></p>
</section>