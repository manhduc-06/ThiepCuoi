<?php
 defined('BASEPATH') OR exit('No direct script access allowed');
// Trang chưa từng "Cho khách xem": mọi link khách mở (trang chủ, link thiệp riêng, gửi ảnh) đều ra trang
// "đang được chuẩn bị" — trong khi chủ nhà đăng nhập vẫn thấy đủ, nên dễ tưởng link hỏng. Nhắc ngay chỗ gửi link.
if (!$this->content_model->is_published()): ?>
<p class="notice notice-err" role="alert" data-unpublished-note><b><?= e(__('Khách chưa xem được trang.')) ?></b>
  <?= e(__('Khách mở link (kể cả link thiệp riêng) đang thấy “Trang cưới đang được chuẩn bị”. Bạn đăng nhập nên vẫn thấy đầy đủ.')) ?>
  <a href="<?= base_url() ?>"><?= e(__('Mở trang sửa')) ?></a> <?= e(__('rồi bấm “Cho khách xem” trên thanh công cụ.')) ?></p>
<?php endif; ?>
