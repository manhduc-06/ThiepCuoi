<?php
 defined('BASEPATH') OR exit('No direct script access allowed');






$gid = (int) $g['id'];
$gname = trim($g['salutation'] . ' ' . $g['name']);
$is_inv = $g['source'] === 'invite';
$ev = function ($k) use ($eb, $g) { return $eb && isset($eb['f'][$k]) ? (string) $eb['f'][$k] : (string) $g[$k]; };
$err = function ($field) use ($eb) {
return ($eb && $eb['field'] === $field) ? '<span class="field-err small" role="alert">' . e($eb['error']) . '</span>' : '';
};
?>
<?php if ($eb): ?><p class="err small" role="alert"><?= e(__('Chưa lưu được: {error} Thông tin bạn vừa sửa vẫn còn bên dưới.', array('error' => $eb['error']))) ?></p><?php endif; ?>
<p class="small muted" style="margin:10px 0 6px"><?= e(__('Khách trả lời qua điện thoại? Ghi nhận thay:')) ?></p>
<div class="btn-row mark-row">
  <?php $mark_n = $g['status'] === 'yes' ? (int) $g['guests'] : ((int) $g['max_guests'] ?: 1);
foreach (array('yes' => __('✓ Tham dự'), 'no' => __('✕ Từ chối'), 'pending' => __('Chưa rõ')) as $sv => $lbl): if ($sv === $g['status'] && $sv !== 'yes') continue; ?>
    <form method="post" action="<?= base_url('admin/guests/mark/' . $gid) . $keep ?>" class="<?= $sv === 'yes' ? 'mark-yes' : '' ?>"><?= csrf_field() ?><input type="hidden" name="status" value="<?= $sv ?>">
      <?php if ($sv === 'yes'): ?><label class="mark-n"><span><?= e(__('Số người')) ?></span><input type="number" name="guests" min="1" max="<?= (int) ($g['max_guests'] ?: 20) ?>" value="<?= max(1, $mark_n) ?>" inputmode="numeric"></label><?php endif; ?>
      <button class="btn btn-ghost btn-sm" type="submit"><?= e($sv === 'yes' && $g['status'] === 'yes' ? __('✓ Cập nhật số người') : $lbl) ?></button></form>
  <?php endforeach; ?>
  <form method="post" action="<?= base_url('admin/guests/delete/' . $gid) . $keep ?>" data-confirm="<?= e(__('Xóa thiệp mời của {name}?', array('name' => $gname))) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm btn-del" type="submit"><?= e(__('Xóa khách')) ?></button></form>
</div>
<form method="post" action="<?= base_url('admin/guests/edit/' . $gid) . $keep ?>" class="stack guest-form" data-slug-form data-sal-form data-id="<?= $gid ?>">
  <?= csrf_field() ?>
  <div class="sal-field">
    <div class="row-sal">
      <label><?= e(__('Xưng hô')) ?><input name="salutation" list="sal-list" maxlength="30" value="<?= e($ev('salutation')) ?>" data-sal-input autocomplete="off"></label>
      <label><?= e(__('Tên')) ?><input name="name" maxlength="80" required value="<?= e($ev('name')) ?>" data-name-input><?= $err('name') ?></label>
    </div>
    <div class="sal-chips" data-sal-chips role="group" aria-label="<?= e(__('Chọn nhanh xưng hô')) ?>"></div>
  </div>
  <div class="row2">
    <label><?= e(__('Khách bên')) ?><select name="side"><?php foreach ($side_labels as $k => $v): ?><option value="<?= e($k) ?>" <?= $ev('side') === (string) $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></label>
    <label><?= e(__('Số người tối đa')) ?><input type="number" name="max_guests" min="1" max="20" value="<?= $ev('max_guests') !== '' && (int) $ev('max_guests') > 0 ? (int) $ev('max_guests') : '' ?>"></label>
  </div>
  <?php if ($is_inv): ?>
  <label><?= e(__('Lời mời riêng')) ?> <small data-own-hint><?= e(__('(trống = dùng lời mời mẫu)')) ?></small><textarea name="invite_text" rows="2" maxlength="500" placeholder="<?= e($own_placeholder) ?>" data-own-text><?= e($ev('invite_text')) ?></textarea></label>
  <p class="inv-preview" data-inv-preview aria-live="polite"><?= e($g['_text']) ?></p>
  <label><?= e(__('Link riêng của khách')) ?>
    <span class="sub-input<?= ($eb && $eb['field'] === 'slug') ? ' is-bad' : '' ?>"><span class="sub-pre"><?= e($host) ?></span><input name="slug" maxlength="40" required pattern="[a-z0-9][a-z0-9\-]{0,38}[a-z0-9]" value="<?= e($ev('slug')) ?>" data-slug-input data-slug-orig="<?= e((string) $g['slug']) ?>" autocomplete="off" spellcheck="false"<?= ($eb && $eb['field'] === 'slug') ? ' aria-invalid="true"' : '' ?>></span>
    <span class="small" data-slug-state aria-live="polite"></span><?= $err('slug') ?>
    <?php if ($g['opened_at'] || $g['status'] !== 'pending'): ?><span class="small muted slug-note" data-slug-note hidden><?= e(__('Khách đã mở thiệp: link cũ vẫn mở được, nhưng hãy gửi link mới cho khách.')) ?></span>
    <?php else: ?><span class="small muted slug-note" data-slug-note hidden><?= e(__('Đổi link: link cũ (nếu đã gửi) vẫn mở được thiệp này.')) ?></span><?php endif; ?>
    <?php if ($g['_old_slugs']): ?><span class="small muted"><?= e(__('Link cũ vẫn mở thiệp này:')) ?> <?= e(implode(', ', array_map(function ($x) use ($host) { return $host . $x; }, $g['_old_slugs']))) ?></span><?php endif; ?></label>
  <?php endif; ?>
  <div class="row2">
    <label><?= e(__('Số điện thoại')) ?> <small><?= e(__('(chỉ bạn thấy)')) ?></small><input name="phone" type="tel" maxlength="20" inputmode="tel" autocomplete="off" placeholder="0912 345 678" value="<?= e($ev('phone')) ?>"<?= ($eb && $eb['field'] === 'phone') ? ' aria-invalid="true"' : '' ?>><?= $err('phone') ?></label>
    <label><?= e(__('Ghi chú')) ?><input name="note" maxlength="200" value="<?= e($ev('note')) ?>"></label>
  </div>
  <div class="form-actions"><button class="btn btn-accent btn-sm" type="submit"><?= e(__('Lưu')) ?></button></div>
</form>