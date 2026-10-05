<?php
 defined('BASEPATH') OR exit('No direct script access allowed');
$s = $stats;
$tabs = array(
'' => __('Tất cả ({n})', array('n' => $s['total'])),
'unopened' => __('Chưa mở ({n})', array('n' => $s['inv_unopened'])),
'opened' => __('Đã mở, chưa trả lời ({n})', array('n' => $s['inv_pending'] - $s['inv_unopened'])),
'yes' => __('Tham dự ({n})', array('n' => $s['yes'])),
'no' => __('Từ chối ({n})', array('n' => $s['no'])),
);
if ($s['web']) {
$tabs['web'] = __('Tự xác nhận trên web ({n})', array('n' => $s['web']));
}

$extra_tabs = array(
'invited' => __('Thiệp mời riêng ({n})', array('n' => $s['invited'])),
'opened_all' => __('Đã mở thiệp ({n})', array('n' => $s['inv_opened'])),
'pending' => __('Chưa trả lời ({n})', array('n' => $s['pending'])),
);
if (isset($extra_tabs[$filter])) {
$tabs[$filter] = $extra_tabs[$filter];
}

$side_labels = array('' => __('Khách chung'), 'groom' => __('Nhà trai'), 'bride' => __('Nhà gái'));

$is_en = lang_cur() === 'en';
$cs_txt = function (array $cs, $k) use ($is_en) {
return ($is_en && !empty($cs[$k . '_en'])) ? (string) $cs[$k . '_en'] : (string) $cs[$k];
};
$dt_fmt = $is_en ? 'M j, g:i A' : 'H:i d/m';
$d_fmt = $is_en ? 'M j' : 'd/m';
$fb = $form_back; 
$fb_for = function ($id) use ($fb) { return ($fb && (int) $fb['id'] === (int) $id) ? $fb : NULL; };
$fb_err = function ($b, $field) {
return ($b && $b['field'] === $field) ? '<span class="field-err small" role="alert">' . e($b['error']) . '</span>' : '';
};
$side_opts = array('' => __('Cả hai bên'), 'groom' => __('Nhà trai'), 'bride' => __('Nhà gái'), 'none' => __('Chung'));

$qs = function (array $over) use ($filter, $side, $q) {
$p = array_filter(array_merge(array('loc' => $filter, 'ben' => $side, 'q' => $q), $over), 'strlen');
return base_url('admin/guests' . ($p ? '?' . http_build_query($p) : ''));
};

$keep_q = array_filter(array('loc' => $filter, 'ben' => $side, 'q' => $q, 'trang' => $page > 1 ? (string) $page : ''), 'strlen');
$keep = $keep_q ? '?' . http_build_query($keep_q) : '';
$host = preg_replace('~^https?://~', '', $base);
$pct = function ($a, $b) { return $b ? round($a * 100 / $b) . '%' : '—'; };
$first_link = NULL;
foreach ($invites as $g) {
if ($g['source'] === 'invite') { $first_link = base_url($g['slug'] ?: 'moi/' . $g['code']); break; } 
}
$sal_by_key = array();
foreach ($sal_list as $it) { $sal_by_key[mb_strtolower($it['s'])] = $it; }

$tpl_for = function ($sal) use ($sal_by_key, $template) {
$k = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $sal)));
return isset($sal_by_key[$k]) && $sal_by_key[$k]['t'] !== '' ? $sal_by_key[$k]['t'] : $template;
};
$sal_json = json_encode(array('list' => $sal_list, 'common' => $template, 'couple' => $couple_short, 'date' => $date_short),
JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$sal_options = function ($cur) use ($sal_list) {
$h = '<option value="">—</option>';
foreach ($sal_list as $it) {
$h .= '<option value="' . e($it['s']) . '"' . ($cur === $it['s'] ? ' selected' : '') . '>' . e($it['s']) . '</option>';
}
return $h;
};
$side_select = function ($name, $cur = '') use ($side_labels) {
$h = '<select name="' . e($name) . '" aria-label="' . e(__('Khách bên')) . '" data-bulk-side>';
foreach ($side_labels as $k => $v) {
$h .= '<option value="' . e($k) . '"' . ((string) $cur === (string) $k ? ' selected' : '') . '>' . e($v) . '</option>';
}
return $h . '</select>';
};
$bulk_row = function () use ($sal_options, $side_select) {
return '<div class="bulk-row" data-bulk-row><select name="rows_sal[]" aria-label="' . e(__('Xưng hô')) . '" data-bulk-sal>' . $sal_options('') . '</select>'
. '<input name="rows_name[]" maxlength="80" placeholder="' . e(__('Tên khách')) . '" aria-label="' . e(__('Tên khách')) . '" data-bulk-name autocomplete="off">'
. $side_select('rows_side[]')
. '<input name="rows_text[]" maxlength="500" placeholder="' . e(__('Lời mời riêng (trống = dùng mẫu)')) . '" aria-label="' . e(__('Lời mời riêng')) . '" data-bulk-own>'
. '<button class="bulk-x" type="button" aria-label="' . e(__('Xóa dòng')) . '" data-bulk-del>×</button>'
. '<input type="hidden" data-bulk-phone value=""><input type="hidden" data-bulk-guests value=""><input type="hidden" data-bulk-note value=""></div>';

};
$sal_row = function ($it) {
return '<li class="sal-row" data-sal-row>'
. '<div class="sal-row-top"><span class="sal-grip" aria-hidden="true" title="' . e(__('Kéo để đổi thứ tự')) . '">⋮⋮</span>'
. '<input name="sal[]" maxlength="30" required value="' . e($it['s']) . '" placeholder="' . e(__('Xưng hô, vd Cô chú')) . '" aria-label="' . e(__('Xưng hô')) . '" data-sal-name>'
. '<span class="sal-move"><button type="button" class="btn btn-ghost btn-sm" data-sal-up aria-label="' . e(__('Lên trên')) . '">↑</button>'
. '<button type="button" class="btn btn-ghost btn-sm" data-sal-down aria-label="' . e(__('Xuống dưới')) . '">↓</button>'
. '<button type="button" class="btn btn-ghost btn-sm btn-del" data-sal-del aria-label="' . e(__('Xóa xưng hô này')) . '">×</button></span></div>'
. '<textarea name="tpl[]" rows="2" maxlength="500" placeholder="' . e(__('Lời mời mẫu (trống = dùng mẫu chung)')) . '" aria-label="' . e(__('Lời mời mẫu')) . '" data-sal-tpl>' . e($it['t']) . '</textarea>'
. '<p class="sal-prev small" data-sal-prev aria-live="polite"></p></li>';
};
?>
<datalist id="sal-list"><?php foreach ($sal_list as $it): ?><option value="<?= e($it['s']) ?>"><?php endforeach; ?></datalist>
<script type="application/json" id="sal-data"><?= $sal_json ?></script>

<div class="panel-head">
  <h1 class="adm-title"><?= e(__('Khách mời & thiệp mời')) ?></h1>
  <a class="btn btn-ghost" href="<?= base_url('admin/guests/export') ?>"><?= e(__('Tải danh sách (Excel)')) ?></a>
</div>
<?php $this->load->view('admin/_unpublished_note'); ?>

<?php if (!empty($pub_changed)): ?>
<p class="notice small pub-changed" role="status"><?= e(__('Có thay đổi chưa cho khách xem (tên cặp đôi / ngày cưới): thiệp, tin nhắn “Gửi thiệp” và mã QR đang dùng bản khách đang thấy')) ?>
  — <b><?= e($couple_short) ?></b>. <a href="<?= base_url() ?>"><?= e(__('Mở trang sửa')) ?></a> <?= e(__('rồi bấm “Cho khách xem” để cập nhật.')) ?></p>
<?php endif; ?>
<?php if ($this->invite_model->site_locked()):  ?>
<p class="notice small" role="status" data-locked-note><?= e(__('Trang đang bật mật khẩu xem trang: link gửi khách là link mã (vd {link}) để khách vào thẳng không cần mật khẩu; link theo tên sẽ hỏi mật khẩu.', array('link' => $host . 'moi/abcd2345'))) ?></p>
<?php endif; ?>
<?php if (!$s['total'] && $filter === '' && $side === '' && $q === ''): ?>
<section class="panel guest-empty">
  <p><b><?= e(__('Mỗi khách một thiệp riêng mang tên họ.')) ?></b></p>
  <p class="small muted"><?= strtr(e(__('Thêm tên khách bên dưới (gõ cả danh sách một lần cũng được) → bấm {b} để gửi qua Zalo, Messenger, tin nhắn.')), array('{b}' => '<b>' . e(__('Gửi thiệp')) . '</b>')) ?>
    <?= e(__('Khách mở link sẽ thấy phong bì thiệp ghi tên mình và trả lời có đến dự hay không ngay trên thiệp.')) ?></p>
</section>
<?php endif; ?>
<?php $nb = $fb_for(0); $nf = $nb ? $nb['f'] : array(); $nv = function ($k) use ($nf) { return isset($nf[$k]) ? (string) $nf[$k] : ''; }; ?>
<div class="add-grid">
  <details class="panel" <?= ($s['invited'] && !$nb) ? '' : 'open' ?> id="them-khach">
    <summary><b><?= e(__('+ Thêm 1 khách')) ?></b> <span class="muted small"><?= e(__('— xưng hô, lời mời riêng, số người')) ?></span></summary>
    <form method="post" action="<?= base_url('admin/guests/create') ?>" class="stack guest-form" data-slug-form data-sal-form>
      <?= csrf_field() ?>
      <?php if ($nb): ?><p class="err small" role="alert"><?= e(__('Chưa tạo được: {error} Thông tin bạn nhập vẫn còn bên dưới.', array('error' => $nb['error']))) ?></p><?php endif; ?>
      <div class="sal-field">
        <div class="row-sal">
          <label><?= e(__('Xưng hô')) ?><input name="salutation" list="sal-list" maxlength="30" placeholder="<?= e(__('Anh')) ?>" value="<?= e($nv('salutation')) ?>" data-slug-part data-sal-input autocomplete="off"></label>
          <label><?= e(__('Tên')) ?><input name="name" maxlength="80" required placeholder="<?= e(__('Tuấn')) ?>" value="<?= e($nv('name')) ?>" data-slug-part data-name-input><?= $fb_err($nb, 'name') ?></label>
        </div>
        <div class="sal-chips" data-sal-chips role="group" aria-label="<?= e(__('Chọn nhanh xưng hô')) ?>"></div>
      </div>
      <label><?= e(__('Khách bên')) ?><select name="side"><?php foreach ($side_labels as $k => $v): ?><option value="<?= e($k) ?>" <?= $nv('side') === (string) $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></label>
      <p class="inv-preview" data-inv-preview aria-live="polite" hidden></p>
      <details class="guest-more" <?= ($nb && ($nb['field'] === 'slug' || $nb['field'] === 'phone' || $nv('invite_text') !== '' || $nv('max_guests') !== '' || $nv('note') !== '' || $nv('slug') !== '' || $nv('phone') !== '')) ? 'open' : '' ?>>
        <summary><?= e(__('Tùy chọn thêm')) ?> <span class="small"><?= e(__('— lời mời riêng, số người, link, số điện thoại, ghi chú (không bắt buộc)')) ?></span></summary>
        <div class="stack">
          <label><?= e(__('Số người tối đa')) ?> <small><?= e(__('(trống = không giới hạn)')) ?></small><input type="number" name="max_guests" min="1" max="20" inputmode="numeric" value="<?= e($nv('max_guests')) ?>"></label>
          <label><?= e(__('Lời mời riêng')) ?> <small data-own-hint><?= e(__('(trống = dùng lời mời mẫu)')) ?></small><textarea name="invite_text" rows="2" maxlength="500" placeholder="<?= e($template) ?>" data-own-text><?= e($nv('invite_text')) ?></textarea></label>
          <label><?= e(__('Link riêng của khách')) ?> <small><?= e(__('(tự tạo theo tên nếu để trống)')) ?></small>
            <span class="sub-input<?= ($nb && $nb['field'] === 'slug') ? ' is-bad' : '' ?>"><span class="sub-pre"><?= e($host) ?></span><input name="slug" maxlength="40" pattern="[a-z0-9][a-z0-9\-]{0,38}[a-z0-9]" placeholder="<?= e(__('anh-tuan')) ?>" value="<?= e($nv('slug')) ?>" data-slug-input autocomplete="off" spellcheck="false"></span>
            <span class="small" data-slug-state aria-live="polite"></span><?= $fb_err($nb, 'slug') ?></label>
          <label><?= e(__('Số điện thoại')) ?> <small><?= e(__('(chỉ bạn thấy)')) ?></small><input name="phone" type="tel" maxlength="20" inputmode="tel" autocomplete="off" placeholder="0912 345 678" value="<?= e($nv('phone')) ?>"<?= ($nb && $nb['field'] === 'phone') ? ' aria-invalid="true"' : '' ?>><?= $fb_err($nb, 'phone') ?></label>
          <label><?= e(__('Ghi chú')) ?> <small><?= e(__('(chỉ bạn thấy)')) ?></small><input name="note" maxlength="200" placeholder="<?= e(__('Bạn đại học, số bàn…')) ?>" value="<?= e($nv('note')) ?>"></label>
        </div>
      </details>
      <div class="form-actions"><button class="btn btn-accent" type="submit"><?= e(__('Tạo thiệp mời')) ?></button></div>
    </form>
  </details>
  <details class="panel bulk" id="nhanh">
    <summary><b><?= e(__('+ Lên danh sách nhanh')) ?></b> <span class="muted small"><?= e(__('— nhiều khách một lúc')) ?></span></summary>
    <div class="seg" role="tablist" aria-label="<?= e(__('Cách nhập')) ?>">
      <button type="button" role="tab" aria-selected="true" data-bulk-mode="table"><?= e(__('Nhập theo bảng')) ?></button>
      <button type="button" role="tab" aria-selected="false" data-bulk-mode="text"><?= e(__('Dán văn bản')) ?></button>
    </div>
    <form method="post" action="<?= base_url('admin/guests/add') ?>" class="bulk-form" data-bulk-table data-bulk-max="<?= (int) $bulk_max ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="rows_json" value="" data-bulk-json disabled>
      <div class="bulk-head" aria-hidden="true"><span><?= e(__('Xưng hô')) ?></span><span><?= e(__('Tên')) ?></span><span><?= e(__('Bên')) ?></span><span><?= e(__('Lời mời riêng')) ?> <small><?= e(__('(không bắt buộc)')) ?></small></span><span></span></div>
      <div class="bulk-rows" data-bulk-rows><?= $bulk_row() . $bulk_row() . $bulk_row() ?></div>
      <template data-bulk-tpl><?= $bulk_row() ?></template>
      <p class="small muted bulk-tip"><?= strtr(e(__('Gõ tên rồi nhấn {enter} để xuống dòng mới. Gõ "Cô chú Lan Hùng" vào ô tên cũng được — xưng hô tự nhận ra.')), array('{enter}' => '<kbd>Enter</kbd>')) ?>
        <?= e(__('Dán cả cột tên từ Excel/Zalo vào ô Tên: mỗi dòng thành 1 khách. Hơn {n} khách thì tự chia nhiều lượt.', array('n' => (int) $bulk_max))) ?></p>
      <div class="form-actions">
        <button class="btn btn-ghost btn-sm" type="button" data-bulk-add><?= e(__('+ Thêm dòng')) ?></button>
        <button class="btn btn-accent" type="submit" data-bulk-submit><?= e(__('Tạo thiệp mời')) ?></button>
      </div>
    </form>
    <form method="post" action="<?= base_url('admin/guests/add') ?>" class="stack bulk-form" data-bulk-text hidden>
      <?= csrf_field() ?>
      <label><?= e(__('Mỗi dòng 1 người/1 gia đình:')) ?> <code><?= e(__('Xưng hô | Tên | Lời mời riêng')) ?></code> <small><?= e(__('(lời mời riêng không bắt buộc; gõ "Anh Tuấn" hay "Cô chú Lan Hùng" cũng được)')) ?></small>
        <textarea name="names" rows="5" placeholder="<?= str_replace("\n", '&#10;', e(__("Anh | Tuấn\nCô chú | Lan Hùng | Cháu kính mời cô chú đến chung vui cùng gia đình cháu.\nGia đình bác Tư\nBạn Minh"))) ?>"></textarea></label>
      <div class="row2">
        <label><?= e(__('Khách bên')) ?><select name="side"><?php foreach ($side_labels as $k => $v): ?><option value="<?= e($k) ?>"><?= e($v) ?></option><?php endforeach; ?></select></label>
        <div class="form-actions" style="align-items:flex-end"><button class="btn btn-accent" type="submit"><?= e(__('Tạo thiệp mời')) ?></button></div>
      </div>
    </form>
  </details>
</div>

<?php if ($s['total']):
$ppl = function ($n) { return array('{p}' => '<span data-people-part>' . (int) $n . '</span>'); };
?>
<?php $this->load->view('admin/_rsvp_stats', array('rsvp_s' => $s)); ?>
<p class="small muted guest-sides">
  <b><?= e(__('Nhà trai')) ?></b>: <?= strtr(e(__('{a} thiệp · {b} trả lời · {p} người đến', array('a' => (int) $s['inv_groom'], 'b' => (int) $s['inv_groom_resp']))), $ppl($s['people_groom'])) ?>
  &nbsp;·&nbsp; <b><?= e(__('Nhà gái')) ?></b>: <?= strtr(e(__('{a} thiệp · {b} trả lời · {p} người đến', array('a' => (int) $s['inv_bride'], 'b' => (int) $s['inv_bride_resp']))), $ppl($s['people_bride'])) ?>
  <?php if ($s['inv_common']):  ?>&nbsp;·&nbsp; <b><?= e(__('Khách chung')) ?></b>: <?= strtr(e(__('{a} thiệp · {b} trả lời · {p} người đến', array('a' => (int) $s['inv_common'], 'b' => (int) $s['inv_common_resp']))), $ppl($s['people_common'])) ?><?php endif; ?>
  <?php if ($s['web']): ?>&nbsp;·&nbsp; <b><?= e(__('Tự xác nhận trên trang chính')) ?></b> <?= strtr(e(__('(không có thiệp): {y} tham dự · {p} người · {n} từ chối', array('y' => (int) $s['web_yes'], 'n' => (int) $s['web_no']))), $ppl($s['web_people_common'])) ?><?php endif; ?>
  &nbsp;·&nbsp; <b><?= strtr(e(__('Tổng sẽ đến: {p} người')), array('{p}' => '<span data-people-total>' . (int) $s['people'] . '</span>')) ?></b>
</p>
<?php endif; ?>

<section class="panel card-switch<?= $card_on ? ' is-on' : '' ?>" aria-labelledby="cs-title">
  <div class="card-switch-main">
    <form method="post" action="<?= base_url('admin/guests/card') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="invite_card" value="<?= $card_on ? '0' : '1' ?>">
      <button class="switch" type="submit" role="switch" aria-checked="<?= $card_on ? 'true' : 'false' ?>" aria-labelledby="cs-title"><span></span></button>
    </form>
    <div>
      <h2 id="cs-title"><?= e(__('Phong bì thiệp khi khách mở link riêng:')) ?> <b><?= e($card_on ? __('Đang bật') : __('Đang tắt')) ?></b></h2>
      <p class="small muted card-switch-desc"><?= e($card_on
? __('Khách mở link riêng (vd {link}) thấy phong bì mở ra thiệp có tên họ, trả lời ngay trên thiệp rồi vào trang cưới.', array('link' => $host . (setting('site_password_hash') !== '' ? 'moi/' . __('ma-rieng') : __('anh-tuan')))) 
: __('Link riêng của khách vào thẳng trang cưới (vẫn chào tên khách và điền sẵn ô xác nhận). Bật lên để khách thấy thiệp mời trước.')) ?></p>
    </div>
    <?php if ($card_sample !== '' || $first_link): ?><a class="btn btn-ghost btn-sm" href="<?= e($card_sample !== '' ? $card_sample : $first_link) ?>" target="_blank" rel="noopener"><?= e(__('Xem thử thiệp ↗')) ?></a><?php endif; ?>
  </div>
  <?php $cur_cs_name = $cs_txt($card_styles[$card_style], 'name'); ?>
  <details class="card-styles-box" id="mau-thiep"<?= empty($card_chosen) ? ' open' : '' ?>>
  <summary><span class="cst-sum"><?= e(__('Mẫu thiệp — Đang dùng:')) ?> <b><?= e($cur_cs_name) ?></b></span> <span class="cst-change"><?= e(__('Đổi mẫu')) ?></span></summary>
  <form method="post" action="<?= base_url('admin/guests/card') ?>" class="card-styles" aria-labelledby="cst-title">
    <?= csrf_field() ?>
    <h3 id="cst-title"><?= e(__('Mẫu thiệp')) ?> <span class="muted small">— <?= e(__('{n} kiểu mở thiệp có hiệu ứng 3D, tự lấy màu theo giao diện trang.', array('n' => count($card_styles)))) ?> <?= e(__('Đang dùng:')) ?> <b><?= e($cur_cs_name) ?></b></span></h3>
    <div class="cst-grid" role="radiogroup" aria-labelledby="cst-title">
      <?php $cs_vip_first = TRUE; $vip_lbl = vip_labels(); 
foreach ($card_styles as $cs_key => $cs): $cs_vip = !empty($cs['pro']); $cs_locked = $cs_vip && !pro_enabled(); $cs_name = $cs_txt($cs, 'name'); $cs_desc = $cs_txt($cs, 'desc'); ?>
      <?php if ($cs_vip && $cs_vip_first): $cs_vip_first = FALSE; ?><p class="cst-vip-h"><?= $vip_lbl ? '<b>VIP</b> · ' . e(__('10 thiệp đi cùng 10 giao diện VIP')) : e(__('10 thiệp đi cùng 10 giao diện riêng')) ?><?= pro_enabled() ? '' : ' — ' . e(__('có trên thiep.site')) ?></p><?php endif; ?>
      <div class="cst<?= $cs_key === $card_style ? ' is-on' : '' ?><?= $cs_vip ? ' cst--vip' : '' ?><?= $cs_locked ? ' is-locked' : '' ?>"<?php if ($cs_locked):  ?>
        data-vip-locked data-vip-kind="card" data-vip-name="<?= e($cs_name) ?>" data-vip-desc="<?= e($cs_desc) ?>"
        data-vip-img="<?= asset_url('img/cards/' . $cs_key . '.jpg') ?>" tabindex="0" role="button" aria-label="<?= e($vip_lbl ? __('{name} — mẫu thiệp VIP, có trên thiep.site', array('name' => $cs_name)) : __('{name} — có trên thiep.site', array('name' => $cs_name))) ?>"<?php endif; ?>>
        <label>
          <input type="radio" name="invite_card_style" value="<?= e($cs_key) ?>"<?= $cs_key === $card_style ? ' checked' : '' ?><?= $cs_locked ? ' disabled' : '' ?>>
          <img src="<?= asset_url('img/cards/' . $cs_key . '.jpg') ?>" alt="" width="240" height="200" loading="lazy" decoding="async">
          <b><?php if ($cs_vip && $vip_lbl): ?><span class="tag tag-vip">VIP</span> <?php endif; ?><?= e($cs_name) ?><?= $cs_key === $card_style ? ' <span class="tag tag-public">' . e(__('Đang dùng')) . '</span>' : '' ?></b>
          <span class="small muted"><?= e($cs_desc) ?><?= $cs_locked ? ' · 🔒 ' . e(__('có trên thiep.site')) : '' ?></span>
        </label>
        <?php if ($card_sample !== '' && !$cs_locked): ?><a class="cst-try small" href="<?= e($card_sample . '?mau=' . rawurlencode($cs_key)) ?>" target="_blank" rel="noopener" aria-label="<?= e(__('Xem thử mẫu {name}', array('name' => $cs_name))) ?>"><?= e(__('Xem thử ↗')) ?></a><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="form-actions cst-actions"><button class="btn btn-accent" type="submit"><?= e(__('Dùng mẫu đã chọn')) ?></button>
      <?php if ($card_sample === ''): ?><span class="small muted"><?= e(__('Thêm một khách mời để xem thử thiệp với tên thật.')) ?></span><?php endif; ?></div>
  </form>
  </details>
  <details class="card-tpl">
    <summary><?= e(__('Mẫu lời mời chung')) ?> <span class="muted small"><?= e(__('— khi khách không có lời riêng và xưng hô chưa có mẫu')) ?></span></summary>
    <form method="post" action="<?= base_url('admin/guests/card') ?>" class="stack">
      <?= csrf_field() ?>
      <textarea name="invite_template" rows="3" maxlength="500"><?= e($template) ?></textarea>
      <p class="small muted"><?= e(__('Chèn tự động:')) ?> <code>{xung_ho}</code> <?= e(__('(anh, chị, cô chú…)')) ?>, <code>{ten}</code>, <code>{cap_doi}</code> (<?= e($couple_short) ?>), <code>{ngay}</code> (<?= e($date_short ?: __('ngày cưới')) ?>).</p>
      <div class="form-actions"><button class="btn btn-accent btn-sm" type="submit"><?= e(__('Lưu mẫu')) ?></button></div>
    </form>
  </details>
</section>

<details class="panel sal-editor" id="xung-ho">
  <summary><b><?= e(__('Xưng hô & lời mời mẫu')) ?></b> <span class="muted small"><?= e(__('— {n} xưng hô · sửa, thêm, sắp xếp', array('n' => count($sal_list)))) ?></span></summary>
  <p class="small muted sal-intro"><?= e(__('Khách có xưng hô trùng tên trong danh sách sẽ nhận lời mời mẫu tương ứng (trừ khi bạn viết lời mời riêng cho khách đó).')) ?>
    <?= e(__('Thứ tự ở đây cũng là thứ tự nút chọn nhanh.')) ?> <?= e(__('Chèn tự động:')) ?> <code>{xung_ho}</code> <code>{ten}</code> <code>{cap_doi}</code> <code>{ngay}</code>.</p>
  <label class="sal-sample"><?= e(__('Xem thử với tên')) ?> <input value="<?= e(__('Lan')) ?>" maxlength="40" data-sal-sample></label>
  <form method="post" action="<?= base_url('admin/guests/salutations') ?>" data-sal-editor>
    <?= csrf_field() ?>
    <ol class="sal-rows" data-sal-rows><?php foreach ($sal_list as $it) { echo $sal_row($it); } ?></ol>
    <template data-sal-tpl-row><?= $sal_row(array('s' => '', 't' => '')) ?></template>
    <p class="sal-err small" data-sal-err hidden></p>
    <div class="form-actions sal-actions">
      <button class="btn btn-ghost btn-sm" type="button" data-sal-add><?= e(__('+ Thêm xưng hô')) ?></button>
      <button class="btn btn-accent btn-sm" type="submit"><?= e(__('Lưu danh sách')) ?></button>
    </div>
  </form>
  <form method="post" action="<?= base_url('admin/guests/salutations') ?>" class="sal-reset" data-confirm="<?= e(__('Khôi phục danh sách xưng hô mặc định? Các xưng hô và lời mời mẫu bạn đã sửa sẽ mất (khách đã tạo không bị ảnh hưởng).')) ?>">
    <?= csrf_field() ?><input type="hidden" name="reset" value="1">
    <button class="btn btn-ghost btn-sm" type="submit"><?= e(__('Khôi phục mặc định')) ?></button>
  </form>
</details>

<?php if ($s['total'] || $filter !== '' || $side !== '' || $q !== ''): ?>
<div class="guest-filter">
  <nav class="tabs" aria-label="<?= e(__('Lọc theo trạng thái')) ?>">
    <?php foreach ($tabs as $k => $v): ?><a class="<?= $filter === $k ? 'on' : '' ?>" href="<?= $qs(array('loc' => $k)) ?>"><?= e($v) ?></a><?php endforeach; ?>
  </nav>
  <form method="get" action="<?= base_url('admin/guests') ?>" class="guest-search" role="search">
    <?php if ($filter !== ''): ?><input type="hidden" name="loc" value="<?= e($filter) ?>"><?php endif; ?>
    <select name="ben" aria-label="<?= e(__('Lọc theo bên')) ?>" onchange="this.form.submit()"><?php foreach ($side_opts as $k => $v): ?><option value="<?= e($k) ?>" <?= $side === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(__('Tìm tên, số điện thoại…')) ?>" aria-label="<?= e(__('Tìm khách')) ?>">
    <button class="btn btn-ghost btn-sm" type="submit"><?= e(__('Tìm')) ?></button>
  </form>
</div>
<?php endif; ?>

<?php if (!$invites): ?>
  <p class="muted"><?= e(($filter || $side || $q) ? __('Không có khách nào khớp bộ lọc.') : __('Chưa có khách nào. Khách tự xác nhận trên trang cưới cũng sẽ hiện ở đây.')) ?>
  <?php if ($q !== ''):  ?> <a href="<?= $qs(array('q' => '')) ?>"><?= e(__('Xóa tìm kiếm')) ?></a><?php endif; ?></p>
<?php endif; ?>
<?php if ($total > 0): ?>
<p class="small muted guest-count" data-total="<?= (int) $total ?>" data-pages="<?= (int) $pages ?>" data-page="<?= (int) $page ?>">
  <?= $total > $per_page ? e(__('Khách {a}–{b} /', array('a' => ($page - 1) * $per_page + 1, 'b' => min($total, $page * $per_page)))) . ' ' : '' ?><?= strtr(e(($filter || $side || $q) ? __('{n} khách khớp bộ lọc') : __('{n} khách')), array('{n}' => '<b>' . (int) $total . '</b>')) ?></p>
<?php endif; ?>
<div class="guest-list">
  <?php 
$share_lbl = __c('Thiệp mời:');
foreach ($invites as $g):
$gid = (int) $g['id'];
$gname = trim($g['salutation'] . ' ' . $g['name']);
$is_inv = $g['source'] === 'invite';

$link = $is_inv ? $base . $this->invite_model->guest_path($g) : '';
$msg = $is_inv ? $g['_text'] . ' ' . $share_lbl : '';
$src_tag = $g['_src'][0] === 'own' ? __('lời mời riêng') : ($g['_src'][0] === 'sal' ? __('mẫu: {s}', array('s' => $g['_src'][1])) : __('mẫu chung'));
if ($g['status'] === 'yes') { $st = array('approved', __('Tham dự · {n} người', array('n' => (int) $g['guests']))); }
elseif ($g['status'] === 'no') { $st = array('hidden', __('Từ chối{_}', array('_' => ''))); } 
elseif ($g['opened_at']) { $st = array('pending', __('Đã mở {t}', array('t' => date($dt_fmt, strtotime($g['opened_at']))))); }
else { $st = array('none', $is_inv ? __('Chưa mở') : __('Chưa trả lời')); }
?>
  <article class="panel guest" id="g<?= $gid ?>">
    <div class="guest-main">
      <b class="guest-name"><?= e($gname) ?></b>
      <span class="tag tag-<?= $st[0] ?>"><?= e($st[1]) ?></span>
      <?php if ($g['side']): ?><span class="tag"><?= e(__($sides[$g['side']] ?? '')) ?></span><?php endif; ?>
      <?php if ($g['max_guests']): ?><span class="tag"><?= e(__('tối đa {n} người', array('n' => (int) $g['max_guests']))) ?></span><?php endif; ?>
      <?php if (!$is_inv): ?><span class="tag"><?= e(__('tự xác nhận trên web')) ?></span><?php else: ?><span class="tag tag-src<?= $g['_src'][0] === 'own' ? ' is-own' : '' ?>" title="<?= e($g['_text']) ?>"><?= e($src_tag) ?></span><?php endif; ?>
      <?php if ($g['_dup']): ?><a class="tag tag-dup" href="<?= $qs(array('q' => $g['name'], 'loc' => '')) ?>" title="<?= e(__('Có khách khác cùng tên — bấm để xem các khách trùng tên')) ?>"><?= e(__('trùng tên')) ?></a><?php endif; ?>
      <?php if ($g['invite_text']): ?><p class="guest-inv small">✉ <?= e(mb_strimwidth($g['invite_text'], 0, 140, '…')) ?></p><?php endif; ?>
      <?php if ($g['message']): ?><p class="guest-msg">“<?= e($g['message']) ?>”</p><?php endif; ?>
      <?php if (!empty($back_link) && (int) $back_link['id'] === $gid && (int) $back_link['page'] !== (int) $page):  ?>
      <p class="back-page small" role="status"><?= e(__('Khách này đã chuyển sang trang {n} (danh sách sắp theo giờ trả lời).', array('n' => (int) $page))) ?>
        <a class="btn btn-ghost btn-sm" href="<?= base_url($back_link['url']) ?>"><?= e(__('← Về trang {n}', array('n' => (int) $back_link['page']))) ?></a></p>
      <?php endif; ?>
      <p class="muted small"><?= $g['phone'] ? '☎ ' . e($g['phone']) . ' · ' : '' ?><?= e($g['responded_at'] ? __('trả lời {t}', array('t' => date($dt_fmt, strtotime($g['responded_at'])))) : __('tạo {d}', array('d' => date($d_fmt, strtotime($g['created_at']))))) ?><?= $g['note'] ? ' · ' . e($g['note']) : '' ?></p>
    </div>
    <div class="guest-actions">
      <?php if ($is_inv): ?>
        <p class="guest-link"><a href="<?= e(base_url($g['slug'] ?: 'moi/' . $g['code'])) ?>" target="_blank" rel="noopener" title="<?= e(__('Xem trước thiệp (không tính là khách đã mở)')) ?>"><?= e(preg_replace('~^https?://~', '', $link)) ?></a></p>
        <div class="guest-send">
          <button class="btn btn-accent btn-sm btn-share" type="button" data-share="<?= e($link) ?>" data-share-text="<?= e($msg) ?>" data-share-title="<?= e(__('Gửi thiệp cho {name}', array('name' => $gname))) ?>"><?= e(__('Gửi thiệp')) ?></button>
          <button class="btn btn-ghost btn-sm" type="button" data-copy-text="<?= e($link) ?>"><?= e(__('Sao chép link')) ?></button>
          <button class="btn btn-ghost btn-sm" type="button" data-qr-modal="<?= e($link) ?>" data-qr-name="<?= e($gname) ?>" data-qr-file="<?= e($g['slug']) ?>"><?= e(__('Mã QR')) ?></button>
        </div>
      <?php endif; ?>
    </div>
    <?php $eb = $fb_for($gid); ?>
    <details class="guest-edit" <?= $eb ? 'open' : '' ?> data-action="<?= base_url('admin/guests/edit/' . $gid) . $keep ?>" data-form-src="<?= base_url('admin/guests/form/' . $gid) . $keep ?>">
      <summary><?= e(__('Sửa · ghi nhận thay khách · xóa')) ?></summary>
      <div class="guest-edit-body" data-guest-form-slot><?php if ($eb) {

$this->load->view('admin/_guest_edit', array('g' => $g, 'eb' => $eb, 'keep' => $keep, 'host' => $host,
'side_labels' => $side_labels, 'own_placeholder' => $tpl_for($g['salutation'])));
} ?></div>
    </details>
  </article>
  <?php endforeach; ?>
</div>
<?php if ($pages > 1):

$nums = array_unique(array(1, max(1, $page - 1), $page, min($pages, $page + 1), $pages));
sort($nums);
?>
<nav class="pager" aria-label="<?= e(__('Trang danh sách khách')) ?>">
  <?php if ($page > 1): ?><a class="btn btn-ghost btn-sm" href="<?= $qs(array('trang' => (string) ($page - 1))) ?>" rel="prev"><?= e(__('‹ Trước')) ?></a><?php endif; ?>
  <?php $prev = 0; foreach ($nums as $n): if ($n - $prev > 1): ?><span class="pager-gap" aria-hidden="true">…</span><?php endif; $prev = $n; ?>
    <?php if ($n === $page): ?><span class="btn btn-accent btn-sm" aria-current="page"><?= $n ?></span>
    <?php else: ?><a class="btn btn-ghost btn-sm" href="<?= $qs(array('trang' => $n > 1 ? (string) $n : '')) ?>"><?= $n ?></a><?php endif; ?>
  <?php endforeach; ?>
  <?php if ($page < $pages): ?><a class="btn btn-ghost btn-sm" href="<?= $qs(array('trang' => (string) ($page + 1))) ?>" rel="next"><?= e(__('Sau ›')) ?></a><?php endif; ?>
</nav>
<?php endif; ?>
<script src="<?= asset_url('js/vendor/qrcode.js') ?>"></script>
<script src="<?= asset_url('js/guests.js') ?>" defer></script>