<?php
 defined('BASEPATH') OR exit('No direct script access allowed');
$has_domain = $tunnel['mode'] === 'token' && $tunnel['hostname'] !== ''; ?>
<h1 class="adm-title"><?= e(__('Gửi link & mã QR')) ?></h1>
<p class="muted"><?= e(__('Gửi link trang cưới cho khách qua Zalo, Messenger, tin nhắn — hoặc in mã QR lên thiệp giấy.')) ?></p>
<?php $this->load->view('admin/_unpublished_note'); ?>

<?php
$dom = $this->config->item('cloud_domain');
$ident = cloud_identity();
$req = $ident && !empty($ident['request']) ? $ident['request'] : NULL;
$is_jagame = $tunnel['mode'] === 'token' && substr($tunnel['hostname'], -strlen('.' . $dom)) === '.' . $dom;
$req_labels = array('pending' => array('tag-pending', __('Đang chờ duyệt')), 'approved' => array('tag-approved', __('Đã duyệt')), 'rejected' => array('tag-hidden', __('Bị từ chối')));
?>
<?php if (!$hosted):  ?>
<section class="panel domain-card" data-domain>
  <h2><?= e(__('Link của trang cưới')) ?></h2>
  <?php if ($has_domain): ?>
    <div class="site-card inner">
      <div class="site-qr" data-qr="<?= e('https://' . $tunnel['hostname'] . '/') ?>" data-domain-qr></div>
      <div class="site-info">
        <p class="site-url"><a data-domain-link href="<?= e('https://' . $tunnel['hostname'] . '/') ?>" target="_blank" rel="noopener"><?= e('https://' . $tunnel['hostname'] . '/') ?></a></p>
        <p class="copy-row"><input readonly data-copy-src data-domain-input value="<?= e('https://' . $tunnel['hostname'] . '/') ?>" aria-label="<?= e(__('Link trang cưới')) ?>"><button class="btn btn-ghost btn-sm" type="button" data-copy><?= e(__('Sao chép')) ?></button>
          <button class="btn btn-ghost btn-sm" type="button" data-qr-download="trang-cuoi"><?= e(__('Tải mã QR')) ?></button></p>
        <p><span class="tag tag-pending" data-domain-state><?= e(__('Đang kiểm tra kết nối…')) ?></span></p>
      </div>
    </div>
  <?php elseif ($tunnel['auto']): ?>
    <?php $this->load->library('tunnelrunner'); $claim_err = $this->tunnelrunner->last_claim_error(); ?>
    <p class="notice" data-domain-wait<?= $claim_err ? ' hidden' : '' ?>>⏳ <?= e(__('Đang tạo link')) ?> <b>xxxx.<?= e($dom) ?></b> <?= e(__('cho máy này… (cần Internet, thường dưới 1 phút). Trang sẽ tự cập nhật.')) ?></p>
    <div class="notice notice-err" data-domain-err<?= $claim_err ? '' : ' hidden' ?> role="alert">
      <p><?= e(__('Chưa kết nối được máy chủ {domain} (kiểm tra Internet). Khách vẫn mở được link tạm bên dưới.', array('domain' => $dom))) ?></p>
      <details class="small muted tech-err"><summary><?= e(__('Chi tiết kỹ thuật')) ?></summary><?php  ?>
        <p data-domain-err-msg><?= $claim_err ? e($claim_err['message']) : '' ?></p></details>
      <button class="btn btn-accent btn-sm" type="button" data-domain-retry><?= e(__('Thử lại')) ?></button>
    </div>
  <?php else: ?>
    <p class="muted"><?= e(__('Đang dùng chế độ link tự chọn ở mục Tùy chọn nâng cao bên dưới.')) ?></p>
  <?php endif; ?>

  <?php if ($ident): ?>
  <div class="req-box">
    <h3><?= e(__('Tên miền riêng')) ?> <span class="muted small">— <?= e(__('ví dụ {name}', array('name' => 'minh-lan.' . $dom))) ?></span></h3>
    <?php if ($req): $rl = isset($req_labels[$req['status']]) ? $req_labels[$req['status']] : array('', $req['status']); ?>
      <p class="req-state" data-req-state><?= e(__('Yêu cầu gần nhất:')) ?> <b><?= e($req['hostname']) ?></b> <span class="tag <?= $rl[0] ?>"><?= e($rl[1]) ?></span>
        <?php if (!empty($req['note']) && $req['status'] === 'rejected'): ?><br><span class="small"><?= e(__('Lý do:')) ?> <?= e($req['note']) ?></span><?php endif; ?>
        <button class="btn btn-ghost btn-sm" type="button" data-req-refresh><?= e(__('Kiểm tra lại')) ?></button></p>
    <?php endif; ?>
    <?php if (!$req || $req['status'] !== 'pending'): ?>
    <form class="stack" data-req-form>
      <label><?= e(__('Tên bạn muốn')) ?>
        <span class="sub-input"><span class="sub-pre">https://</span><input name="subdomain" maxlength="20" autocomplete="off" spellcheck="false"
          placeholder="minh-lan" data-sub-input required><span class="sub-post">.<?= e($dom) ?></span></span></label>
      <p class="small" data-sub-state><?= e(__('Chữ thường không dấu, số, dấu gạch ngang · 3–20 ký tự')) ?></p>
      <label><?= e(__('Lời nhắn cho admin')) ?> <small><?= e(__('(không bắt buộc)')) ?></small><input name="reason" maxlength="300" placeholder="<?= e(__('Ví dụ: tên hai vợ chồng')) ?>"></label>
      <p class="err" data-req-err hidden></p>
      <div class="form-actions"><button class="btn btn-accent" type="submit"><?= e(__('Gửi yêu cầu')) ?></button></div>
      <p class="small muted"><?= e(__('Admin {domain} duyệt xong, link mới tự hoạt động — không cần cài đặt lại. Link cũ ngừng hoạt động khi đổi.', array('domain' => $dom))) ?></p>
    </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($is_local): ?>
  <p class="notice"><?= e(__('Link dưới đây hiện chỉ mở được trong mạng nhà — khách ở xa chưa vào được cho tới khi có link Internet ở trên.')) ?></p>
<?php endif; ?>
<div class="share-grid">
  <div class="panel share-card">
    <h2><?= e(__('Trang cưới')) ?></h2>
    <p class="muted small"><?= e(__('Link chung cho mọi khách. Muốn thiệp ghi tên từng người: vào')) ?> <a href="<?= base_url('admin/guests') ?>"><?= e(__('Khách mời')) ?></a>.</p>
    <div class="qr" data-qr="<?= e($home_url) ?>"></div>
    <p class="copy-row"><input readonly value="<?= e($home_url) ?>" data-copy-src aria-label="<?= e(__('Link trang cưới')) ?>"><button class="btn btn-ghost" type="button" data-copy><?= e(__('Sao chép')) ?></button></p>
    <div class="btn-row center-row"><button class="btn btn-accent btn-share" type="button" data-share="<?= e($home_url) ?>" data-share-text="<?= e($share_text) ?>" data-share-title="<?= e(__('Gửi link trang cưới')) ?>"><?= e(__('Gửi cho khách')) ?></button>
    <button class="btn btn-ghost btn-sm" type="button" data-qr-download="trang-anh-cuoi"><?= e(__('Tải mã QR (PNG)')) ?></button></div>
  </div>
  <div class="panel share-card">
    <h2><?= e(__('Khách gửi ảnh')) ?></h2>
    <p class="muted small"><?= e(__('In mã này đặt trên bàn tiệc: khách quét là gửi ảnh ngay.')) ?></p>
    <div class="qr" data-qr="<?= e($upload_url) ?>"></div>
    <p class="copy-row"><input readonly value="<?= e($upload_url) ?>" data-copy-src aria-label="<?= e(__('Link gửi ảnh')) ?>"><button class="btn btn-ghost" type="button" data-copy><?= e(__('Sao chép')) ?></button></p>
    <div class="btn-row center-row"><button class="btn btn-ghost btn-sm btn-share" type="button" data-share="<?= e($upload_url) ?>" data-share-text="<?= e(__('Gửi giúp chúng mình những tấm ảnh bạn chụp trong ngày cưới nhé:')) ?>" data-share-title="<?= e(__('Gửi link nhận ảnh')) ?>"><?= e(__('Gửi link')) ?></button>
    <button class="btn btn-ghost btn-sm" type="button" data-qr-download="gui-anh"><?= e(__('Tải mã QR (PNG)')) ?></button></div>
  </div>
</div>

<?php if (!$hosted): ?>
<details class="panel">
  <summary><b><?= e(__('Tùy chọn nâng cao')) ?></b> <span class="muted small">— <?= e(__('chỉ dành cho người rành máy tính, thường không cần đổi')) ?></span></summary>
  <form method="post" action="<?= base_url('admin/settings/tunnel') ?>" class="stack" style="margin-top:12px">
    <?= csrf_field() ?>
    <p class="muted small"><?= e(__('Thay đổi ở đây có hiệu lực sau khi khởi động lại chương trình.')) ?></p>
    <label class="radio"><input type="radio" name="mode" value="auto" <?= $tunnel['auto'] ? 'checked' : '' ?>><span><b><?= e(__('Tự động (khuyên dùng)')) ?></b> — <?= e(__('link riêng xxxx.{domain}, xin được tên miền riêng.', array('domain' => $this->config->item('cloud_domain')))) ?></span></label>
    <label class="radio"><input type="radio" name="mode" value="off" <?= !$tunnel['auto'] && $tunnel['mode'] === 'off' ? 'checked' : '' ?>><span><b><?= e(__('Tắt')) ?></b> — <?= e(__('chỉ dùng trong mạng nhà.')) ?></span></label>
    <label class="radio"><input type="radio" name="mode" value="quick" <?= !$tunnel['auto'] && $tunnel['mode'] === 'quick' ? 'checked' : '' ?>><span><b><?= e(__('Link tạm miễn phí')) ?></b> — <?= e(__('https://….trycloudflare.com, đổi mỗi lần khởi động lại.')) ?></span></label>
    <label class="radio"><input type="radio" name="mode" value="token" <?= !$tunnel['auto'] && $tunnel['mode'] === 'token' ? 'checked' : '' ?>><span><b><?= e(__('Tunnel của riêng bạn')) ?></b> — <?= e(__('tên miền bạn đã thêm vào Cloudflare.')) ?></span></label>
    <div class="token-fields stack">
      <label><?= e(__('Tên miền')) ?><input name="hostname" value="<?= e($tunnel['auto'] ? '' : $tunnel['hostname']) ?>" placeholder="cuoi.tenban.com"></label>
      <label><?= e(__('Token tunnel')) ?><?= $tunnel['has_token'] ? ' <small>' . e(__('(đã lưu — để trống để giữ nguyên)')) . '</small>' : '' ?><input name="token" autocomplete="off" placeholder="eyJhIjoi..."></label>
    </div>
    <div class="form-actions"><button class="btn btn-ghost" type="submit"><?= e(__('Lưu cấu hình')) ?></button></div>
  </form>
</details>
<?php endif; ?>
<script src="<?= asset_url('js/vendor/qrcode.js') ?>"></script>