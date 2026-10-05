/* Mục "Mừng cưới": vẽ mã VietQR từ chuỗi [data-vietqr] (vendor/qrcode.js, không gọi dịch vụ ngoài),
   sao chép số tài khoản, tải mã QR (PNG có ghi của nhà nào), ẩn hiệu ứng rơi khi đang xem mã. */
(function () {
  'use strict';
  var __ = window.__ || function (s) { return s; };   // i18n: chuỗi Việt -> tiếng Anh (application/language/en/ui_js_*.php)
  var sec = document.getElementById('mung-cuoi');

  // R4-17: hiệu ứng rơi (tim/tuyết…) không bay ngang mã QR — mục chiếm >= 30% khung nhìn thì ẩn .fx, rời đi hiện lại.
  // Người dùng bật "giảm chuyển động": giữ nguyên hành vi hiện có.
  var fx = document.querySelector('.fx');
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (sec && fx && !reduce && 'IntersectionObserver' in window) {
    fx.style.transition = 'opacity .3s ease';
    // Mục cao hơn màn hình nên tính theo phần KHUNG NHÌN mục chiếm (không theo intersectionRatio của mục).
    var th = []; for (var t = 0; t <= 20; t++) th.push(t / 20);
    new IntersectionObserver(function (ens) {
      ens.forEach(function (en) {
        var vh = window.innerHeight || document.documentElement.clientHeight || 1;
        var on = en.isIntersecting && en.intersectionRect.height / vh >= 0.3;
        fx.style.opacity = on ? '0' : '';
        fx.classList.toggle('is-gift-hidden', on);
      });
    }, { threshold: th }).observe(sec);
  }

  // Trình duyệt trong Zalo/Messenger/Facebook… hay chặn tải file: gợi ý chụp màn hình thay cho "chọn ảnh QR đã tải".
  var inApp = /Zalo|FBAN|FBAV|FB_IAB|Messenger|Instagram|Line\//i.test(navigator.userAgent || '');
  if (sec && (inApp || !('download' in document.createElement('a')))) {
    var alt = sec.querySelector('[data-gift-alt]');
    if (alt) alt.textContent = __('(hoặc chụp màn hình mã QR)');
  }

  if (!window.qrcode) return;
  var draw = function (box) {
    var qr = window.qrcode(0, 'M');
    qr.addData(box.getAttribute('data-vietqr'));
    qr.make();
    box.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 4, scalable: true });
    box.qr = qr;
  };
  document.querySelectorAll('[data-vietqr]').forEach(function (b) { if (b.getAttribute('data-vietqr')) draw(b); });

  var toast = function (btn, msg) {
    var old = btn.textContent; btn.textContent = msg; btn.disabled = true;
    setTimeout(function () { btn.textContent = old; btn.disabled = false; }, 1600);
  };
  var txt = function (card, sel) { var el = card.querySelector(sel); return el ? el.textContent.replace(/\s+/g, ' ').trim() : ''; };

  // R4-13: PNG = mã QR (lề tĩnh 4 ô) + 3 dòng dưới mã: "Nhà trai · Ngân hàng", "CHỦ TK", "STK chia nhóm".
  var png = function (card, qr) {
    var n = qr.getModuleCount(), cell = 10, pad = 4 * cell, size = n * cell + pad * 2;
    var lines = [
      [txt(card, '.gift-side') + (txt(card, '.gift-bank') ? ' · ' + txt(card, '.gift-bank') : ''), 600, 22, '#555'],
      [txt(card, '.gift-holder').toUpperCase(), 700, 26, '#111'],
      [txt(card, '.gift-num'), 600, 26, '#111']
    ].filter(function (l) { return l[0] !== ''; });
    var lh = 38, textH = lines.length ? lines.length * lh + 24 : 0;
    var cv = document.createElement('canvas'); cv.width = size; cv.height = size + textH;
    var ctx = cv.getContext('2d');
    ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, cv.width, cv.height); ctx.fillStyle = '#000';
    for (var r = 0; r < n; r++) for (var col = 0; col < n; col++) if (qr.isDark(r, col)) ctx.fillRect(pad + col * cell, pad + r * cell, cell, cell);
    ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
    var fam = 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
    lines.forEach(function (l, i) {
      var px = l[2];
      ctx.font = l[1] + ' ' + px + 'px ' + fam;
      while (px > 12 && ctx.measureText(l[0]).width > size - 24) { px -= 1; ctx.font = l[1] + ' ' + px + 'px ' + fam; }
      ctx.fillStyle = l[3];
      // Dòng chữ nằm DƯỚI vùng lề tĩnh của mã (size), không lấn vào 4 ô trắng quanh mã.
      ctx.fillText(l[0], size / 2, size + 4 + i * lh + lh / 2);
    });
    return cv;
  };

  document.addEventListener('click', function (e) {
    var c = e.target.closest('[data-gift-copy]');
    if (c) {
      var v = c.getAttribute('data-gift-copy');
      var done = function () { toast(c, __('Đã chép ✓')); };
      if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(v).then(done, function () { window.prompt(__('Số tài khoản:'), v); });
      else { window.prompt(__('Số tài khoản:'), v); }
      return;
    }
    var d = e.target.closest('[data-gift-dl]');
    if (d) {
      var card = d.closest('.gift-card');
      var box = card.querySelector('[data-vietqr]');
      if (!box || !box.qr) return;
      var a = document.createElement('a'); a.download = d.getAttribute('data-gift-dl') + '.png'; a.href = png(card, box.qr).toDataURL('image/png'); a.click();
    }
  });
})();
