<?php
 defined('BASEPATH') OR exit('No direct script access allowed');









list($ic_m1, $ic_m2) = $monogram;
$ic_groom = trim($content->get('groom_name'));
$ic_bride = trim($content->get('bride_name'));
// Thiệp khách nhà gái: tên cô dâu trước, gia đình nhà gái trước, tiêu đề lễ riêng (mặc định "Lễ vu quy").
$ic_side = isset($card_side) ? (string) $card_side : '';
$ic_bride_first = $ic_side === 'bride';
$ic_first = $ic_bride_first ? $ic_bride : $ic_groom;
$ic_second = $ic_bride_first ? $ic_groom : $ic_bride;
if ($ic_bride_first) {
list($ic_m1, $ic_m2) = array($ic_m2, $ic_m1);
}
$ic_label = $content->text($ic_bride_first ? 'c.event_title_bride' : 'c.event_title');
$ic_evs = isset($card_events) ? (array) $card_events : ($card_event ? array($card_event) : array());
$ic_map_of = function (array $ev) {
$q = trim($ev['address'] !== '' ? $ev['address'] : $ev['place']);
return $ev['map'] !== '' ? $ev['map'] : ($q !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($q) : '');
};
$ic_status = (string) $invite['status'];
$ic_answered = $ic_status !== 'pending';
$ic_key = 'ac-card:' . $invite['code'];
$v = $voice; 
$ic_style = $card_style;

$ic_photo = '';
$ic_photo_srcset = '';
$ic_photo_pos = '50% 50%';
if (($ic_style === 'watercolor' || strpos($ic_style, 'v-') === 0) && !empty($img['img.hero_main'])) { 

$ic_photo = photo_url($img['img.hero_main'], 't');
$ic_photo_srcset = photo_srcset($img['img.hero_main'], 's');
list($ic_px, $ic_py) = $content->image_pos('img.hero_main');
$ic_photo_pos = $ic_px . '% ' . $ic_py . '%';
}
$ic_cv = array(
'm1' => $ic_m1, 'm2' => $ic_m2, 'groom' => $ic_first, 'bride' => $ic_second, 'guest' => (string) $card_name,
'day' => $card_day, 'time' => $card_time, 'lunar' => $lunar_text, 'greet' => $content->text('c.invite_greeting'),
'photo' => $ic_photo, 'photo_srcset' => $ic_photo_srcset, 'photo_sizes' => '(max-width: 480px) 360px, 400px', 'photo_pos' => $ic_photo_pos,
);
$ic_view = $content->card_paths($ic_style)['view'];
$ic_part = function ($part) use ($ic_view, $ic_cv) {
get_instance()->load->view($ic_view, array('part' => $part, 'cv' => $ic_cv));
};
?>
<div class="ic ic--<?= e($ic_style) ?><?= $ic_answered ? ' is-open is-instant' : '' ?><?= mb_strlen((string) $card_name) >= 22 ? ' ic--long' : '' ?>" id="thiep-moi" data-invite-card data-key="<?= e($ic_key) ?>"
     data-card-style="<?= e($ic_style) ?>" data-open-ms="<?= (int) $card_open_ms ?>"
     data-status="<?= e($ic_status) ?>" data-guests="<?= (int) $invite['guests'] ?>" data-preview="<?= $card_preview ? '1' : '0' ?>"
     data-t-form-yes="<?= e($card_limit <= 1 ? $v['form_yes1'] : $v['form_yes']) ?>" data-t-form-no="<?= e($v['form_no']) ?>" data-t-done-yes="<?= e($v['done_yes']) ?>"
     data-t-done-no="<?= e($v['done_no']) ?>" data-t-thanks-yes="<?= e($v['thanks_yes']) ?>" data-t-thanks-no="<?= e($v['thanks_no']) ?>"
     role="dialog" aria-modal="true" aria-labelledby="ic-name">
  <script>(function(){var c=document.getElementById('thiep-moi'),s=null;try{s=sessionStorage.getItem(c.getAttribute('data-key'))}catch(e){}
  if(s==='closed'||location.hash){c.className+=' is-closed'}else{document.documentElement.className+=' ic-lock';if(s==='open')c.className+=' is-open is-instant';if(/\bis-open\b/.test(c.className))document.documentElement.className+=' ic-open'}})();</script>
  <div class="ic-backdrop" aria-hidden="true"></div>
  <div class="ic-scroll">
    <?php if ($card_preview): ?><p class="ic-preview"><?= e(__('Bản xem trước của chủ nhà — không tính là khách đã mở thiệp')) ?></p><?php endif; ?>

    <div class="ic-env">
      <div class="ic-stage">
        <button type="button" class="ic-env-btn" data-ic-open aria-label="<?= e(__('Mở thiệp mời gửi {ten}', array('ten' => $card_name))) ?>">
          <?php $ic_part('cover'); ?>
          <span class="ic-env-to"><small><?= e(__('Thân gửi')) ?></small><b><?= e($card_name) ?></b></span>
        </button>
      </div>
      <p class="ic-env-hint" aria-hidden="true" data-ic-open-hint><?= e(__('Chạm để mở thiệp')) ?></p>
    </div>

    <article class="ic-card">
      <?php $ic_part('deco'); ?>
      <div class="ic-p ic-p1">
        <div class="ic-mono" aria-hidden="true"><span><?= e($ic_m1) ?></span><i>&amp;</i><span><?= e($ic_m2) ?></span></div>
        <p class="ic-greet"><?= e($ic_cv['greet']) ?></p>
        <p class="ic-name" id="ic-name" role="heading" aria-level="2" tabindex="-1"><?= e($card_name) ?></p>
        <?php if ($card_text !== ''): ?><p class="ic-text"><?= nl2br(e($card_text)) ?></p><?php endif; ?>
      </div>

      <div class="ic-p ic-p2">
        <div class="ic-rule" aria-hidden="true"><span>♥</span></div>
        <?php if ($ic_style === 'songhy-tri'): 
$ic_fam = array(
array(__('Nhà trai'), trim($content->text('c.groom_fullname')), trim($content->text('c.groom_info'))),
array(__('Nhà gái'), trim($content->text('c.bride_fullname')), trim($content->text('c.bride_info'))),
);
if ($ic_bride_first) {
$ic_fam = array_reverse($ic_fam);
} ?>
        <div class="ic-fam">
          <?php foreach ($ic_fam as $ic_f): ?>
          <div class="ic-fam-col"><p class="ic-fam-side"><?= e($ic_f[0]) ?></p><?php if ($ic_f[2] !== ''): ?><p class="ic-fam-info"><?= nl2br(e($ic_f[2])) ?></p><?php endif; ?><?php if ($ic_f[1] !== ''): ?><p class="ic-fam-name"><?= e($ic_f[1]) ?></p><?php endif; ?></div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <p class="ic-label"><?= e($ic_label) ?></p>
        <p class="ic-couple"><span><?= e($ic_first) ?></span><i>&amp;</i><span><?= e($ic_second) ?></span></p>

        <?php if ($card_day): ?>
        <div class="ic-date">
          <p class="ic-wd"><?= e($card_day['weekday']) ?><?= $card_time !== '' ? ' · ' . e($card_time) : '' ?></p>
          <p class="ic-dmy"><b><?= e($card_day['d']) ?></b><i></i><b><?= e($card_day['m']) ?></b><i></i><b><?= e($card_day['y']) ?></b></p>
          <?php if ($lunar_text !== ''):  ?><p class="ic-lunar"><?= str_replace(' (Âm lịch)', "\u{00A0}<span class=\"dt-nw\">(Âm lịch)</span>", e($lunar_text)) ?></p><?php endif; ?>
        </div>
        <?php endif; ?>

        <?php foreach ($ic_evs as $ic_ev): $ic_map = $ic_map_of($ic_ev); ?>
        <div class="ic-place">
          <p class="ic-place-title"><?= e($ic_ev['title']) ?><?= $ic_ev['time'] !== '' ? ' · ' . e($ic_ev['time']) : '' ?></p>
          <?php if ($ic_ev['place'] !== ''): ?><p class="ic-place-name"><?= e($ic_ev['place']) ?></p><?php endif; ?>
          <?php if ($ic_ev['address'] !== ''): ?><p class="ic-place-addr"><?= e($ic_ev['address']) ?></p><?php endif; ?>
          <?php if ($ic_map !== ''): ?><a class="ic-link" href="<?= e($ic_map) ?>" target="_blank" rel="noopener noreferrer"><?= e(__('Chỉ đường')) ?> ↗</a><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="ic-p ic-p3">
      <?php if ($rsvp_on): ?>
      <div class="ic-rsvp" data-ic-rsvp>
        <div class="ic-done" data-ic-done<?= $ic_answered ? '' : ' hidden' ?>>
          <p class="ic-done-title" data-ic-done-title><?= $ic_status === 'yes' ? e($v['done_yes']) : ($ic_status === 'no' ? e($v['done_no']) : '') ?></p>
          <p class="ic-done-sub" data-ic-done-sub><?= $ic_status === 'yes' ? e(__n('{n} người', (int) $invite['guests'])) . ' · ' . e($v['thanks_yes']) : ($ic_status === 'no' ? e($v['thanks_no']) : '') ?></p>
          <button type="button" class="ic-link" data-ic-change><?= e(__('Đổi câu trả lời')) ?></button>
        </div>
        <div class="ic-ask" data-ic-ask<?= $ic_answered ? ' hidden' : '' ?>>
          <p class="ic-q"><?= e($v['ask']) ?></p>
          <div class="ic-choices">
            <button type="button" class="ic-btn ic-btn-yes" data-ic-attend="yes"<?= $card_preview ? ' disabled' : '' ?>><?= e(__('Sẽ tham dự')) ?></button>
            <button type="button" class="ic-btn ic-btn-no" data-ic-attend="no"<?= $card_preview ? ' disabled' : '' ?>><?= e(__('Không thể đến')) ?></button>
          </div>
          <?php if ($card_preview): ?><p class="ic-note"><?= e(__('Khách sẽ trả lời tại đây. Chủ nhà ghi nhận thay ở trang Khách mời.')) ?></p><?php endif; ?>
        </div>
        <form class="ic-form" method="post" action="<?= base_url('xac-nhan') ?>" data-ic-form novalidate hidden>
          <?= csrf_field() ?>
          <input type="hidden" name="code" value="<?= e($invite['code']) ?>">
          <input type="hidden" name="attend" value="">
          <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
          <p class="ic-form-title" data-ic-form-title tabindex="-1"></p>
          <?php if ($card_limit <= 1):  ?>
          <input type="hidden" name="guests" value="1">
          <?php else: ?>
          <div class="ic-count" data-ic-yes-only>
            <span class="ic-count-label" id="ic-count-l"><?= e($v['count']) ?></span>
            <div class="ic-stepper" role="group" aria-labelledby="ic-count-l">
              <button type="button" data-ic-step="-1" aria-label="<?= e(__('Bớt 1 người')) ?>">−</button>
              <input type="number" name="guests" min="1" max="<?= (int) $card_limit ?>" value="<?= max(1, min((int) $card_limit, (int) $invite['guests'] ?: 1)) ?>" inputmode="numeric" aria-labelledby="ic-count-l">
              <button type="button" data-ic-step="1" aria-label="<?= e(__('Thêm 1 người')) ?>">+</button>
            </div>
            <?php if ($card_limit < 20): ?><span class="ic-count-hint" data-ic-count-hint><?= e(__('Thiệp dành cho tối đa {n} người', array('n' => (int) $card_limit))) ?></span><?php endif; ?>
          </div>
          <?php endif; ?>
          <?php  ?>
          <label class="ic-msg ic-phone"><span><?= e(__('Số điện thoại')) ?> <small><?= e(__('(không bắt buộc)')) ?></small></span>
            <input type="tel" name="phone" maxlength="20" inputmode="tel" autocomplete="tel" value="<?= e(isset($invite['phone']) ? (string) $invite['phone'] : '') ?>"></label>
          <label class="ic-msg"><span><?= e(__('Lời chúc gửi cô dâu chú rể')) ?> <small><?= e(__('(không bắt buộc)')) ?></small></span>
            <textarea name="message" rows="3" maxlength="1000"<?= $msg_note !== '' ? ' aria-describedby="ic-msg-note"' : '' ?>><?= e((string) $invite['message']) ?></textarea>
            <?php if ($msg_note !== ''): ?><small class="ic-note" id="ic-msg-note"><?= e($msg_note) ?></small><?php endif; ?></label>
          <p class="ic-err" data-ic-err role="alert" hidden></p>
          <div class="ic-form-actions">
            <button type="submit" class="ic-btn ic-btn-yes" data-ic-submit><?= e(__('Gửi xác nhận')) ?></button>
            <button type="button" class="ic-link" data-ic-back><?= e(__('Quay lại')) ?></button>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <button type="button" class="ic-enter" data-ic-close><?= e(__('Xem trang cưới')) ?> <span aria-hidden="true">→</span></button>
      </div>
      <?php $ic_part('end'); ?>
    </article>
  </div>
</div>
<button type="button" class="ic-reopen" data-ic-reopen aria-controls="thiep-moi" hidden><span aria-hidden="true">✉</span> <?= e(__('Thiệp mời')) ?></button>
<noscript><style>.ic { display: none !important; }</style></noscript>