/* Cài đặt → Mừng cưới: xem trước mã VietQR ngay khi nhập (cùng thuật toán với libraries/Vietqr.php). */
(function () {
  'use strict';
  var __ = window.__ || function (t, v) { if (v) { for (var k in v) { t = t.split('{' + k + '}').join(v[k]); } } return t; };
  var root = document.querySelector('[data-gift-admin]');
  if (!root || !window.qrcode) return;

  var tlv = function (id, v) { return id + ('0' + v.length).slice(-2) + v; };
  var crc16 = function (s) {
    var crc = 0xFFFF;
    for (var i = 0; i < s.length; i++) {
      crc ^= s.charCodeAt(i) << 8;
      for (var b = 0; b < 8; b++) crc = (crc & 0x8000) ? ((crc << 1) ^ 0x1021) & 0xFFFF : (crc << 1) & 0xFFFF;
    }
    return ('000' + crc.toString(16).toUpperCase()).slice(-4);
  };
  var ascii = function (s) {
    return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd')
      .replace(/[^a-z0-9 ]+/g, ' ').replace(/\s+/g, ' ').trim();
  };
  // D35 (R4-07b, cùng luật Vietqr.php R4-07a): bỏ dấu -> gộp khoảng trắng -> > 25 ký tự thì cắt ở ranh giới từ gần nhất -> rtrim.
  var NOTE_MAX = 25;
  var noteText = function (s) {
    s = ascii(s || '');
    if (s.length > NOTE_MAX) {
      var i = s.slice(0, NOTE_MAX + 1).lastIndexOf(' ');
      s = i > 0 ? s.slice(0, i) : s.slice(0, NOTE_MAX);
    }
    return s.replace(/\s+$/, '');
  };
  // D36 (R4-08): STK chỉ gồm chữ số sau khi bỏ dấu cách/chấm/gạch.
  var acctClean = function (a) { return (a || '').replace(/[\s.\-]/g, ''); };
  var payload = function (bin, acct, note) {
    acct = acctClean(acct);
    if (!/^\d{6}$/.test(bin) || !/^\d{4,19}$/.test(acct)) return '';
    var card = /^\d{16,19}$/.test(acct) && acct.indexOf(bin) === 0;
    var m = tlv('00', 'A000000727') + tlv('01', tlv('00', bin) + tlv('01', acct)) + tlv('02', card ? 'QRIBFTTC' : 'QRIBFTTA');
    var s = tlv('00', '01') + tlv('01', '11') + tlv('38', m) + tlv('53', '704') + tlv('58', 'VN');
    note = noteText(note);
    if (note) s += tlv('62', tlv('08', note));
    s += '6304';
    return s + crc16(s);
  };

  var noteIn = root.querySelector('[data-gift-note]'), noteOut = root.querySelector('[data-gift-note-out]');
  var showNote = function () {
    if (!noteIn || !noteOut) return;
    var t = noteText(noteIn.value);
    noteOut.textContent = t ? __('Khách sẽ thấy nội dung: "{note}" ({n}/{max})', { note: t, n: t.length, max: NOTE_MAX })
      : __('Để trống: khách tự gõ nội dung khi chuyển khoản.');
  };
  var render = function (f) {
    var bin = f.querySelector('[data-gift-bin]').value, acctIn = f.querySelector('[data-gift-acct]'), acct = acctIn.value;
    var holder = f.querySelector('[data-gift-holder]').value.trim(), box = f.querySelector('[data-gift-prev]');
    var err = f.querySelector('[data-gift-acct-err]'), bad = /[^0-9]/.test(acctClean(acct));
    if (err) {   // lỗi STK có chữ cái hiện ngay cạnh ô (không vẽ QR sai)
      err.textContent = bad ? __('Số tài khoản gồm 4–19 chữ số (không có chữ cái).') : '';
      err.hidden = !bad;
      if (bad) { acctIn.setAttribute('aria-invalid', 'true'); acctIn.setAttribute('data-gift-bad', ''); }
      else if (acctIn.hasAttribute('data-gift-bad')) { acctIn.removeAttribute('aria-invalid'); acctIn.removeAttribute('data-gift-bad'); }
    }
    var p = bad ? '' : payload(bin, acct, noteIn ? noteIn.value : '');
    if (!p) {
      box.innerHTML = '<span class="small muted">' + (bad ? __('Sửa số tài khoản (chỉ chữ số) để xem trước mã QR')
        : __('Nhập ngân hàng + số tài khoản để xem trước mã QR')) + '</span>';
      return;
    }
    var qr = window.qrcode(0, 'M'); qr.addData(p); qr.make();
    var sel = f.querySelector('[data-gift-bin]');
    box.innerHTML = '<div class="gift-prev-qr">' + qr.createSvgTag({ cellSize: 3, margin: 2, scalable: true }) + '</div>'
      + '<div class="small"><b></b><br><span></span></div>';
    box.querySelector('b').textContent = holder || __('(chưa có tên chủ TK)');
    box.querySelector('span').textContent = sel.options[sel.selectedIndex].text + ' · ' + acctClean(acct);
    box.setAttribute('data-payload', p);   // để kiểm thử đối chiếu với Vietqr.php
  };
  var all = function () { root.querySelectorAll('[data-gift-side]').forEach(render); };
  root.addEventListener('input', function (e) {
    var f = e.target.closest('[data-gift-side]');
    if (f) render(f); else if (e.target === noteIn) { showNote(); all(); }
  });
  root.addEventListener('change', function (e) { var f = e.target.closest('[data-gift-side]'); if (f) render(f); });
  // R5-24: bộ gõ ghép dấu (NFD) -> chuẩn hóa NFC khi rời ô (không đổi giữa lúc đang gõ để khỏi phá bộ gõ tiếng Việt).
  if (noteIn && noteIn.value.normalize) {
    noteIn.addEventListener('change', function () {
      var v = noteIn.value.normalize('NFC');
      if (v !== noteIn.value) { noteIn.value = v; }
      showNote(); all();
    });
  }
  // Tên chủ TK: gợi ý viết hoa không dấu như trên thẻ.
  root.addEventListener('blur', function (e) {
    if (e.target.matches('[data-gift-holder]')) { e.target.value = ascii(e.target.value).toUpperCase(); render(e.target.closest('[data-gift-side]')); }
  }, true);
  showNote();
  all();
})();
