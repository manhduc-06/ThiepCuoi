<?php
 defined('BASEPATH') OR exit('No direct script access allowed');

$s = !empty($form_vals) ? array_merge($settings, $form_vals) : $settings;
if (!empty($form_vals['theme'])) { $cur_theme = $form_vals['theme']; }
$fe = function ($k) use ($errors) { return isset($errors[$k]) ? '<span class="field-err small" id="err-' . e($k) . '" role="alert">' . e($errors[$k]) . '</span>' : ''; };

$ia = function ($k) use ($errors) { return ' id="f-' . e($k) . '"' . (isset($errors[$k]) ? ' aria-invalid="true" aria-describedby="err-' . e($k) . '"' : ''); };
$chk = function ($k) use ($s) { return $s[$k] === '1' ? 'checked' : ''; };
$has_site_pw = $settings['site_password_hash'] !== '';

$tname = function ($t) { return lang_cur() === 'en' && !empty($t['name_en']) ? $t['name_en'] : $t['name']; };
$tdesc = function ($t) { return lang_cur() === 'en' && !empty($t['desc_en']) ? $t['desc_en'] : (isset($t['desc']) ? $t['desc'] : ''); }; ?>
<div class="panel-head">
  <h1 class="adm-title"><?= e(__('Cài đặt')) ?></h1>
  <div class="btn-row">
    <a class="btn btn-ghost" href="<?= base_url('admin/settings/password') ?>"><?= e(__('Đổi mật khẩu')) ?></a>
    <a class="btn btn-ghost" href="<?= base_url('admin/settings/backup') ?>" title="<?= e(__('Chỉ CSDL (.db): album, khách mời, lời chúc, cài đặt — KHÔNG kèm ảnh/nhạc')) ?>"><?= e(__('Tải bản sao dữ liệu')) ?></a>
    <a class="btn btn-ghost" href="<?= base_url('admin/settings/backup_full') ?>" title="<?= e(__('CSDL + toàn bộ ảnh, nhạc trong uploads/ (STORE, không nén thêm)')) ?>"><?= e(__('Tải toàn bộ (.zip)')) ?></a>
  </div>
</div>
<p class="small muted backup-note"><?= e(__('“Tải bản sao dữ liệu” chỉ là CSDL (.db) — ảnh và nhạc nằm trong thư mục uploads/, không nằm trong file này. “Tải toàn bộ (.zip)” gồm cả .db lẫn uploads/; giải nén đè vào thư mục cài mới là khôi phục xong.')) ?></p>
<?php if ($errors): ?><div class="err" role="alert"><b><?= e(__('Chưa lưu cài đặt.')) ?></b> <?= e(__('Sửa ô được báo đỏ bên dưới rồi bấm "Lưu cài đặt" lại — các ô khác vẫn giữ nguyên như bạn vừa nhập.')) ?>
  <ul><?php foreach ($errors as $ek => $err): ?><li><a href="#f-<?= e($ek) ?>" data-err-link><?= e($err) ?></a></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if (!empty($pending)):  ?>
<p class="notice small pub-changed" role="status"><?= e(__('Khách chưa thấy thay đổi:')) ?> <b><?= e(implode(', ', $pending)) ?></b>.
  <a href="<?= base_url() ?>"><?= e(__('Mở trang sửa')) ?></a> <?= e(__('rồi bấm “Cho khách xem” để khách thấy.')) ?></p>
<?php endif; ?>
<form method="post" class="stack" data-settings-form<?= $errors ? ' data-has-errors' : '' ?>>
  <?= csrf_field() ?>
  <section class="panel stack">
    <h2><?= e(__('Thông tin đám cưới')) ?></h2>
    <div class="row2">
      <label><?= e(__('Chú rể{_}', array('_' => ''))) ?><input name="groom_name"<?= $ia('groom_name') ?> value="<?= e($s['groom_name']) ?>" maxlength="80" required><?= $fe('groom_name') ?></label>
      <label><?= e(__('Cô dâu{_}', array('_' => ''))) ?><input name="bride_name"<?= $ia('bride_name') ?> value="<?= e($s['bride_name']) ?>" maxlength="80" required><?= $fe('bride_name') ?></label>
    </div>
    <div class="row2">
      <label><?= e(__('Ngày cưới')) ?><input type="date" name="wedding_date"<?= $ia('wedding_date') ?> value="<?= e($s['wedding_date']) ?>"><?= $fe('wedding_date') ?></label>
      <label><?= e(__('Giờ{_}', array('_' => ''))) ?><input type="time" name="wedding_time"<?= $ia('wedding_time') ?> value="<?= e($s['wedding_time']) ?>"><?= $fe('wedding_time') ?></label>
    </div>
    <p class="small muted draft-note"><?= e(__('Tên, ngày, giờ: khách thấy sau khi bấm “Cho khách xem” trên')) ?> <a href="<?= base_url() ?>"><?= e(__('trang sửa')) ?></a>.</p>
    <p class="small muted"><?= e(__('Địa điểm và lời giới thiệu được sửa ngay trên')) ?> <a href="<?= base_url() ?>#location"><?= e(__('trang cưới')) ?> →</a></p>
  </section>

  <section class="panel stack" id="ngon-ngu">
    <h2><?= e(__('Ngôn ngữ')) ?></h2>
    <?php 
$lang_list = lang_site_list(isset($s['site_lang']) ? $s['site_lang'] : 'vi', isset($s['site_langs']) ? $s['site_langs'] : '');
$site_lang = $lang_list[0];
$admin_lang_v = isset($s['admin_lang']) && $s['admin_lang'] === 'en' ? 'en' : 'vi'; ?>
    <fieldset id="f-site_langs" class="lang-pick"<?= isset($errors['site_langs']) ? ' aria-invalid="true" aria-describedby="err-site_langs"' : '' ?>>
      <legend><?= e(__('Ngôn ngữ trang cưới')) ?></legend>
      <p class="small muted"><?= e(__('Chọn những ngôn ngữ khách được xem. Chọn từ 2 trở lên thì trên trang có nút đổi ngôn ngữ; lần đầu mở, khách thấy ngôn ngữ trình duyệt của mình nếu có trong danh sách, không thì thấy ngôn ngữ mặc định.')) ?></p>
      <div class="lang-grid">
      <?php foreach (lang_all() as $lk => $lt): $on = in_array($lk, $lang_list, TRUE); ?>
        <label class="check lang-opt"><input type="checkbox" name="site_langs[]" value="<?= $lk ?>" <?= $on ? 'checked' : '' ?> data-lang-opt><span lang="<?= $lk ?>"><b><?= e($lt[0]) ?></b></span><small class="muted"><?= e(lang_cur() === 'en' ? ucfirst(locale_lang_name_en($lk)) : $lt[2]) ?></small></label>
      <?php endforeach; ?>
      </div>
      <?= $fe('site_langs') ?>
      <label><?= e(__('Ngôn ngữ mặc định')) ?>
        <select name="site_lang"<?= $ia('site_lang') ?> data-lang-default>
          <?php foreach (lang_all() as $lk => $lt): ?>
            <option value="<?= $lk ?>" lang="<?= $lk ?>" <?= $site_lang === $lk ? 'selected' : '' ?>><?= e($lt[0]) ?></option>
          <?php endforeach; ?>
        </select><?= $fe('site_lang') ?></label>
      <p class="small muted"><?= e(__('Lưu là áp dụng ngay. Chữ hai bạn tự gõ trên trang giữ nguyên như đã gõ; chữ mẫu, nút bấm, ngày tháng đổi theo ngôn ngữ.')) ?></p>
    </fieldset>
    <label><?= e(__('Ngôn ngữ quản trị mặc định')) ?>
      <select name="admin_lang"<?= $ia('admin_lang') ?>>
        <option value="vi" <?= $admin_lang_v === 'vi' ? 'selected' : '' ?>>Tiếng Việt</option>
        <option value="en" <?= $admin_lang_v === 'en' ? 'selected' : '' ?>>English</option>
      </select><?= $fe('admin_lang') ?></label>
    <p class="small muted"><?= e(__('Mỗi trình duyệt vẫn đổi nhanh được bằng nút VI | EN ở thanh trên cùng.')) ?></p>
  </section>

  <section class="panel stack">
    <h2><?= e(__('Giao diện trang cưới')) ?></h2>
    <div class="theme-pick" id="f-theme">
      <?php foreach ($themes as $k => $t): if (!empty($t['pro'])) continue; ?>
        <label class="theme-opt"><input type="radio" name="theme" value="<?= e($k) ?>" <?= $cur_theme === $k ? 'checked' : '' ?>>
          <span class="ed-theme-logo tl-<?= e($k) ?>"></span><span><?= e($tname($t)) ?></span></label>
      <?php endforeach; ?>
    </div>
    <?= $fe('theme') ?>
    <label><?= e(__('Album hiện ở trang chính')) ?>
      <select name="home_album_id">
        <?php foreach ($albums as $a): ?>
          <option value="<?= (int) $a['id'] ?>" <?= $home_album && (int) $home_album['id'] === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['title']) ?> (<?= e(__('{n} ảnh', array('n' => (int) $a['photo_count']))) ?>)</option>
        <?php endforeach; ?>
      </select></label>
    <label><?= e(__('Hiệu ứng trên trang')) ?>
      <select name="fx"><?php $cur_fx = $this->content_model->fx(); foreach (Content_model::EFFECTS as $k => $v): ?><option value="<?= e($k) ?>" <?= $cur_fx === $k ? 'selected' : '' ?>><?= e(__($v)) ?></option><?php endforeach; ?></select></label>
    <p class="small muted draft-note"><?= e(__('Giao diện, album trang chính, hiệu ứng: khách thấy sau khi bấm “Cho khách xem” trên')) ?> <a href="<?= base_url() ?>"><?= e(__('trang sửa')) ?></a>.</p>
    <p class="small muted"><?= e(__('Chữ, ảnh, địa điểm, nhạc nền: sửa trực tiếp trên')) ?> <a href="<?= base_url() ?>"><?= e(__('trang cưới')) ?></a> <?= e(__('(bấm vào chỗ muốn sửa).')) ?></p>
  </section>

  <section class="panel stack">
    <h2><?= e(__('Xác nhận tham dự')) ?></h2>
    <label class="check"><input type="checkbox" name="rsvp_enabled" value="1" <?= $chk('rsvp_enabled') ?>> <?= e(__('Hiện mục "Xác nhận tham dự" trên trang cưới và giấy mời')) ?></label>
    <label class="check"><input type="checkbox" name="invite_card" value="1" <?= $chk('invite_card') ?>> <?= e(__('Thiệp mời trên trang ngoài: khách mở link riêng (vd /anh-tuan) thấy thiệp có tên họ trước, trả lời ngay trên thiệp')) ?></label>
    <label class="check"><input type="checkbox" name="music_autoplay" value="1" <?= $chk('music_autoplay') ?>> <?= e(__('Nhạc nền tự phát khi khách vào trang (điện thoại chặn tự phát thì khách thấy nút "Mở thiệp", chạm 1 lần là nhạc chạy)')) ?></label>
    <p class="small muted"><?= e(__('Tắt thì link riêng vào thẳng trang cưới. Tạo thiệp, lời mời riêng cho từng khách ở mục')) ?> <a href="<?= base_url('admin/guests') ?>"><?= e(__('Khách mời')) ?></a>.</p>
  </section>

  <section class="panel stack" id="anh-album">
    <h2><?= e(__('Ảnh & album')) ?></h2>
    <label class="check"><input type="checkbox" name="album_download" value="1" <?= $chk('album_download') ?>> <?= e(__('Cho phép khách tải ảnh về')) ?></label>
    <p class="small muted"><?= e(__('Tắt (mặc định): khách chỉ xem ảnh trên trang — không có nút "Tải ảnh" trong trình xem ảnh, không có nút "Tải cả album (.zip)", trang không chứa link ảnh gốc. Hai bạn đăng nhập vẫn tải được. Bật: khách tải được ảnh gốc từng tấm và cả album.')) ?></p>
    <p class="small muted"><?= e(__('Lưu ý: tắt chỉ bỏ các nút tải và link ảnh gốc; khách vẫn có thể chụp màn hình hoặc lưu bản ảnh đang hiển thị.')) ?></p>
  </section>

  <section class="panel stack">
    <h2><?= e(__('Khách mời gửi ảnh')) ?></h2>
    <label class="check"><input type="checkbox" name="guest_upload" value="1" <?= $chk('guest_upload') ?>> <?= e(__('Cho phép khách mời gửi ảnh (trang /gui-anh)')) ?></label>
    <label class="check"><input type="checkbox" name="guest_upload_approval" value="1" <?= $chk('guest_upload_approval') ?>> <?= e(__('Duyệt ảnh trước khi hiển thị')) ?></label>
    <div class="row2">
      <label><?= e(__('Ảnh khách gửi vào album')) ?>
        <select name="guest_upload_album_id">
          <?php foreach ($albums as $a): ?>
            <option value="<?= (int) $a['id'] ?>" <?= (int) $s['guest_upload_album_id'] === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['title']) ?></option>
          <?php endforeach; ?>
        </select></label>
      <label><?= e(__('Dung lượng tối đa mỗi ảnh (MB)')) ?><input type="number" name="guest_upload_max_mb" min="1" max="100" value="<?= (int) $s['guest_upload_max_mb'] ?>"></label>
    </div>
  </section>

  <section class="panel stack">
    <h2><?= e(__('Lời chúc')) ?></h2>
    <label class="check"><input type="checkbox" name="wishes_enabled" value="1" <?= $chk('wishes_enabled') ?>> <?= e(__('Hiện mục lời chúc trên trang chính')) ?></label>
    <label class="check"><input type="checkbox" name="wishes_approval" value="1" <?= $chk('wishes_approval') ?>> <?= e(__('Duyệt lời chúc trước khi hiện')) ?></label>
  </section>

  <section class="panel stack gift-admin" id="mung-cuoi" data-gift-admin>
    <h2><?= e(__('Mừng cưới (QR ngân hàng)')) ?></h2>
    <p class="small muted"><?= e(__('Hiện ở cuối trang cưới, sau Lời chúc: mã QR chuyển khoản (chuẩn VietQR — quét bằng mọi ứng dụng ngân hàng) + số tài khoản có nút sao chép. Để trống một bên nếu chỉ dùng một tài khoản.')) ?></p>
    <label class="check"><input type="checkbox" name="gift_enabled"<?= $ia('gift_enabled') ?> value="1" <?= $chk('gift_enabled') ?>> <?= e(__('Hiện mục "Mừng cưới" trên trang cưới')) ?></label><?= $fe('gift_enabled') ?>
    <p class="notice small" data-gift-live><?= e(__('Lưu là khách thấy ngay (không cần bấm “Cho khách xem”). Hãy quét thử mã xem trước bằng app ngân hàng trước khi bật.')) ?></p>
    <div class="row2">
      <label><?= e(__('Tiêu đề')) ?><input name="gift_title" maxlength="60" value="<?= e($s['gift_title']) ?>" placeholder="<?= e(__('Hộp mừng cưới')) ?>"></label>
      <label><?= e(__('Nội dung chuyển khoản gợi ý')) ?> <small><?= e(__('(không bắt buộc)')) ?></small><input name="gift_note" id="f-gift_note" maxlength="80" value="<?= e($s['gift_note']) ?>" placeholder="<?= e(__('Ví dụ: Mung cuoi Minh Lan')) ?>" aria-describedby="gift-note-out" data-gift-note>
        <small class="muted" id="gift-note-out" data-gift-note-out aria-live="polite"></small></label>
    </div>
    <label><?= e(__('Lời nhắn')) ?><textarea name="gift_text" rows="2" maxlength="300"><?= e($s['gift_text']) ?></textarea></label>
    <div class="gift-sides">
      <?php foreach (array('groom' => __('Nhà trai (chú rể)'), 'bride' => __('Nhà gái (cô dâu)')) as $side => $label): ?>
      <fieldset class="gift-side-f" data-gift-side>
        <legend><?= e($label) ?></legend>
        <label><?= e(__('Ngân hàng')) ?>
          <select name="gift_<?= $side ?>_bin"<?= $ia('gift_' . $side . '_bin') ?> data-gift-bin>
            <option value=""><?= e(__('— Chọn ngân hàng —')) ?></option>
            <?php foreach ($banks as $bin => $bn): ?><option value="<?= e($bin) ?>" <?= $s['gift_' . $side . '_bin'] === (string) $bin ? 'selected' : '' ?>><?= e($bn) ?></option><?php endforeach; ?>
          </select></label><?= $fe('gift_' . $side . '_bin') ?>
        <label><?= e(__('Số tài khoản')) ?><input name="gift_<?= $side ?>_acct"<?= $ia('gift_' . $side . '_acct') ?> inputmode="numeric" maxlength="40" autocomplete="off" value="<?= e($s['gift_' . $side . '_acct']) ?>" data-gift-acct></label><?= $fe('gift_' . $side . '_acct') ?><span class="field-err small" data-gift-acct-err hidden></span>
        <label><?= e(__('Tên chủ tài khoản')) ?><input name="gift_<?= $side ?>_holder"<?= $ia('gift_' . $side . '_holder') ?> maxlength="60" autocomplete="off" value="<?= e($s['gift_' . $side . '_holder']) ?>" placeholder="NGUYEN VAN A" data-gift-holder></label><?= $fe('gift_' . $side . '_holder') ?>
        <div class="gift-prev" data-gift-prev aria-live="polite"><span class="small muted"><?= e(__('Nhập ngân hàng + số tài khoản để xem trước mã QR')) ?></span></div>
      </fieldset>
      <?php endforeach; ?>
    </div>
    <p class="small muted"><?= e(__('Mẹo: quét thử mã xem trước bằng app ngân hàng của bạn — phải hiện đúng tên chủ tài khoản trước khi bật cho khách.')) ?></p>
  </section>

  <section class="panel stack">
    <h2><?= e(__('Quyền riêng tư')) ?></h2>
    <fieldset>
      <legend><?= e(__('Mật khẩu xem cả trang')) ?> <?= $has_site_pw ? '<span class="tag tag-password">' . e(__('đang bật')) . '</span>' : '' ?></legend>
      <?php $pw_mode = isset($s['site_password_mode']) ? $s['site_password_mode'] : 'keep'; ?>
      <label class="radio"><input type="radio" name="site_password_mode" value="keep" <?= $pw_mode === 'keep' || $pw_mode === '' ? 'checked' : '' ?>><span><?= e($has_site_pw ? __('Giữ nguyên mật khẩu hiện tại') : __('Không đặt (ai có link đều xem được)')) ?></span></label>
      <label class="radio"><input type="radio" name="site_password_mode" value="set" <?= $pw_mode === 'set' ? 'checked' : '' ?>><span><?= e(__('Đặt mật khẩu mới:')) ?></span></label>
      <input type="text" name="site_password"<?= $ia('site_password') ?> maxlength="100" autocomplete="off" placeholder="<?= e(__('Ví dụ: in trên thiệp mời')) ?>" value="<?= e(isset($s['site_password']) ? $s['site_password'] : '') ?>"><?= $fe('site_password') ?>
      <?php if ($has_site_pw): ?><label class="radio"><input type="radio" name="site_password_mode" value="off" <?= $pw_mode === 'off' ? 'checked' : '' ?>><span><?= e(__('Tắt mật khẩu')) ?></span></label><?php endif; ?>
      <p class="small muted"><?= e(__('Lưu là áp dụng ngay. Khách mở link mã trong tin nhắn mời (vd /moi/abcd2345) không cần nhập mật khẩu; link theo tên (vd /anh-tuan) sẽ hỏi mật khẩu.')) ?></p>
    </fieldset>
  </section>

  <div class="form-actions"><button class="btn btn-accent" type="submit"><?= e(__('Lưu cài đặt')) ?></button></div>
</form>

<section class="panel stack" id="nhac">
  <h2><?= e(__('Nhạc nền')) ?></h2>
  <p class="small muted"><?= e(__('Nhạc tự phát khi khách vào trang (bật/tắt ở mục Xác nhận tham dự & thiệp phía trên); nếu trình duyệt chặn, khách chạm "Mở thiệp" là nhạc chạy. Bài "Có sẵn" là nhạc bản quyền tự do hoặc CC BY (có ghi công ở chân trang); tải thêm bài của bạn (MP3/M4A, tối đa 20 MB) nếu bạn có quyền sử dụng.')) ?></p>
  <p class="small muted draft-note"><?= e(__('Đổi nhạc: khách nghe bài mới sau khi bấm “Cho khách xem” trên')) ?> <a href="<?= base_url() ?>"><?= e(__('trang sửa')) ?></a>.</p>
  <form method="post" action="<?= base_url('admin/settings/music') ?>" class="music-list" data-music-form>
    <?= csrf_field() ?>
    <button type="submit" class="sr-only" tabindex="-1" aria-hidden="true"><?= e(__('Chọn')) ?></button><?php  ?>
    <label class="radio"><input type="radio" name="music" value="" <?= $music_cur === '' ? 'checked' : '' ?>><span><?= e(__('Tắt nhạc nền')) ?></span></label>
    <?php foreach ($music_list as $m): ?>
      <div class="music-item">
        <label class="radio"><input type="radio" name="music" value="<?= e($m['id']) ?>" <?= $music_cur === $m['id'] ? 'checked' : '' ?>>
          <span><b><?= e($m['title']) ?></b><br><small class="muted"><?= e($m['credit'] ?: ($m['builtin'] ? __('Có sẵn') : __('Bạn tải lên'))) ?></small></span></label>
        <audio controls preload="none" src="<?= e($m['url']) ?>"></audio>
        <?php if (!$m['builtin']): ?><button class="btn btn-ghost btn-sm" name="delete" value="<?= e($m['id']) ?>" type="submit" onclick="return confirm(<?= e(json_encode(__('Xóa bài này?'), JSON_UNESCAPED_UNICODE)) ?>)"><?= e(__('Xóa')) ?></button><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </form>
  <?php if ($music_sugs):  ?>
  <div class="music-sugs" data-music-sugs>
    <h3 class="music-sugs-h"><?= e(__('Bài hát cưới phổ biến')) ?> <small class="muted">— <?= e(__('bấm 1 bài rồi chọn file MP3 của bài đó trên máy bạn; bài được thêm đúng tên và phát luôn.')) ?></small></h3>
    <form method="post" action="<?= base_url('admin/settings/music') ?>" enctype="multipart/form-data" hidden data-music-sug-form>
      <?= csrf_field() ?>
      <input type="hidden" name="suggest" value="">
      <input type="file" name="music" accept="audio/mpeg,audio/mp4,audio/x-m4a,audio/ogg,.mp3,.m4a,.ogg" tabindex="-1">
    </form>
    <ul class="music-sug-list">
      <?php foreach ($music_sugs as $sg): ?>
        <li class="music-sug">
          <button type="button" class="music-sug-btn" data-sug="<?= e($sg['key']) ?>" data-sug-name="<?= e($sg['name']) ?>" title="<?= e(__('Chọn file MP3 "{name}" trên máy để tải lên', array('name' => $sg['name']))) ?>">
            <b><?= e($sg['title']) ?></b><small class="muted"><?= e($sg['artist']) ?></small></button>
          <span class="tag tag-need"><?= e(__('Cần tải bài')) ?></span>
          <a class="music-sug-yt" href="<?= e($sg['search']) ?>" target="_blank" rel="noopener noreferrer" title="<?= e(__('Nghe thử trên YouTube')) ?>"><?= e(__('Nghe thử')) ?> ↗</a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>
  <form method="post" action="<?= base_url('admin/settings/music') ?>" enctype="multipart/form-data" class="row2">
    <?= csrf_field() ?>
    <label><?= e(__('Tải bài hát lên')) ?><input type="file" name="music" accept="audio/mpeg,audio/mp4,audio/x-m4a,audio/ogg,.mp3,.m4a,.ogg" required></label>
    <div class="form-actions" style="align-items:flex-end"><button class="btn btn-accent" type="submit"><?= e(__('Tải lên & chọn')) ?></button></div>
  </form>
</section>
<script src="<?= asset_url('js/vendor/qrcode.js') ?>"></script>
<script src="<?= asset_url('js/gift-admin.js') ?>"></script>