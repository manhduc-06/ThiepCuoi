<?php
 defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="setup-lang" role="group" aria-label="<?= e(__('Ngôn ngữ')) ?>" style="display:flex;justify-content:center;gap:8px;margin:0 0 14px">
  <?php foreach (array('vi' => 'Tiếng Việt', 'en' => 'English') as $sl_k => $sl_t): ?>
    <a class="btn btn-sm <?= lang_cur() === $sl_k ? 'btn-accent' : 'btn-ghost' ?>" href="<?= base_url('setup?lang=' . $sl_k) ?>" lang="<?= $sl_k ?>" hreflang="<?= $sl_k ?>"<?= lang_cur() === $sl_k ? ' aria-current="true"' : '' ?>><?= $sl_t ?></a>
  <?php endforeach; ?>
</div>
<h1 class="page-title center"><?= e(__('Chào mừng hai bạn ♡')) ?></h1>
<p class="muted center"><?= e(__('Điền vài thông tin là có ngay trang cưới mẫu. Mọi thứ đều sửa lại được sau.')) ?></p>
<?php foreach ($errors as $err): ?><p class="err"><?= e($err) ?></p><?php endforeach; ?>
<form method="post" class="stack setup-form">
  <?= csrf_field() ?>
  <fieldset>
    <legend><span class="setup-n">1</span> <?= e(__('Hai bạn là ai?')) ?></legend>
    <div class="row2">
      <label><?= e(__('Tên chú rể')) ?><input name="groom_name" value="<?= e($form['groom_name']) ?>" maxlength="80" required placeholder="<?= e(__('Ví dụ: Minh Anh')) ?>" autocomplete="off"></label>
      <label><?= e(__('Tên cô dâu')) ?><input name="bride_name" value="<?= e($form['bride_name']) ?>" maxlength="80" required placeholder="<?= e(__('Ví dụ: Thu Hà')) ?>" autocomplete="off"></label>
    </div>
    <label><?= e(__('Ngày cưới')) ?> <small><?= e(__('(chưa chắc thì để trống)')) ?></small><input type="date" name="wedding_date" value="<?= e($form['wedding_date']) ?>"></label>
  </fieldset>

  <fieldset>
    <legend><span class="setup-n">2</span> <?= e(__('Tạo tài khoản quản trị')) ?></legend>
    <p class="small muted"><?= e(__('Để chỉ hai bạn sửa được trang. Lần sau đăng nhập ở địa chỉ trang cưới thêm')) ?> <b>/admin</b>.</p>
    <label><?= e(__('Tên đăng nhập')) ?> <small><?= e(__('(chữ không dấu, không dấu cách, 3–32 ký tự)')) ?></small><input name="username" value="<?= e($form['username']) ?>" required pattern="[A-Za-z0-9_.\-]{3,32}"
      title="<?= e(__('Chỉ gồm chữ không dấu, số và dấu . _ - (không dấu cách), 3–32 ký tự. Ví dụ: minhlan')) ?>" autocomplete="username" autocapitalize="none" spellcheck="false" data-username></label>
    <div class="row2">
      <label><?= e(__('Mật khẩu')) ?> <small><?= e(__('(ít nhất 8 ký tự)')) ?></small><input type="password" name="password" minlength="8" required autocomplete="new-password" data-pw></label>
      <label><?= e(__('Nhập lại mật khẩu')) ?><input type="password" name="password2" minlength="8" required autocomplete="new-password" data-pw></label>
    </div>
    <label class="check small"><input type="checkbox" data-pw-show> <?= e(__('Hiện mật khẩu')) ?></label>
    <p class="small notice"><?= e(__('Hãy ghi nhớ (hoặc ghi lại) mật khẩu này — trang chưa có nút "Quên mật khẩu".')) ?></p>
  </fieldset>

  <details class="setup-more">
    <summary><?= e(__('Tùy chọn khác')) ?> <span class="muted small">— <?= e(__('không cần đổi')) ?></span></summary>
    <label class="radio"><input type="radio" name="tunnel_mode" value="auto" <?= $form['tunnel_mode'] !== 'off' ? 'checked' : '' ?>>
      <span><b><?= e(__('Khách ở đâu cũng mở được (khuyên dùng)')) ?></b> — <?= e(__('tự tạo link riêng dạng')) ?> <b>https://ab12.jagame.vn</b>, <?= e(__('miễn phí.')) ?>
        <?= e(__('Muốn tên đẹp như minh-lan.jagame.vn thì chọn sau ở mục Gửi link & QR.')) ?></span></label>
    <label class="radio"><input type="radio" name="tunnel_mode" value="off" <?= $form['tunnel_mode'] === 'off' ? 'checked' : '' ?>>
      <span><b><?= e(__('Chỉ trong mạng nhà')) ?></b> — <?= e(__('chỉ máy dùng chung wifi mới mở được.')) ?></span></label>
  </details>

  <button class="btn btn-accent btn-block btn-lg" type="submit" data-setup-submit><?= e(__('Tạo trang cưới')) ?> →</button>
</form>
<script>
document.querySelector('[data-pw-show]').addEventListener('change', function (e) {
  document.querySelectorAll('[data-pw]').forEach(function (i) { i.type = e.target.checked ? 'text' : 'password'; });
});
// Tên đăng nhập: rời ô thì tự bỏ dấu tiếng Việt + dấu cách ("Minh Lân" -> "MinhLan").
var un = document.querySelector('[data-username]');
un.addEventListener('blur', function () {
  var v = un.value.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'D')
    .replace(/\s+/g, '').replace(/[^A-Za-z0-9_.\-]/g, '');
  if (v !== un.value) { un.value = v; }
});
// Bấm "Tạo trang cưới" 1 lần là khóa nút (tạo trang mất 10–20 giây, bấm lại sẽ gửi trùng).
document.querySelector('.setup-form').addEventListener('submit', function () {
  var b = document.querySelector('[data-setup-submit]');
  setTimeout(function () {
    b.disabled = true;
    b.textContent = <?= json_encode(__('Đang tạo trang cưới… (có thể mất 10–20 giây)'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  }, 0);
});
</script>