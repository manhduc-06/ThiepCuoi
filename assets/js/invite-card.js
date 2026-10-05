/* Ảnh Cưới — thiệp mời riêng toàn màn hình (views/public/_invite_card.php), dùng chung cho 10 mẫu thiệp.
   Bìa -> thiệp (hiệu ứng mở của từng mẫu là CSS theo class is-opening; JS chờ data-open-ms rồi mới hiện thiệp);
   trả lời ngay trên thiệp (AC.post /xac-nhan, có CSRF + mã lời mời); "Xem trang cưới" đóng thiệp và nhớ trong
   sessionStorage (lần sau vào thẳng trang, có nút nhỏ mở lại).
   Nghiêng 3D: chuột (máy tính) hoặc cảm biến nghiêng (điện thoại không đòi quyền; iOS không xin quyền — D43) đặt
   --tx/--ty (-1…1) trên .ic, các mẫu dùng cho bìa nghiêng, lớp giấy lệch nhau, ánh nhũ lướt. Làm mượt bằng rAF.
   Người dùng bật "giảm chuyển động": mở/đóng tức thì, không hiệu ứng, không nghiêng. */
(function () {
  'use strict';
  var __ = window.__ || function (s) { return s; };   // i18n: chuỗi Việt -> tiếng Anh (application/language/en/ui_js_*.php)
  var card = document.querySelector('[data-invite-card]');
  if (!card) return;
  var root = document.documentElement;
  var reopen = document.querySelector('[data-ic-reopen]');
  var key = card.getAttribute('data-key');
  var preview = card.getAttribute('data-preview') === '1';
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var lastFocus = null;
  var openMs = parseInt(card.getAttribute('data-open-ms'), 10) || 650;

  // ── Nghiêng 3D theo chuột / cảm biến ─────────────────────
  var tilt = (function () {
    if (reduced) return {};
    var cur = { x: 0, y: 0 }, aim = { x: 0, y: 0 }, raf = 0, base = null;
    var clamp = function (v) { return Math.max(-1, Math.min(1, v)); };
    function step() {
      cur.x += (aim.x - cur.x) * 0.1;
      cur.y += (aim.y - cur.y) * 0.1;
      card.style.setProperty('--tx', cur.x.toFixed(3));
      card.style.setProperty('--ty', cur.y.toFixed(3));
      raf = (Math.abs(aim.x - cur.x) > 0.002 || Math.abs(aim.y - cur.y) > 0.002) ? requestAnimationFrame(step) : 0;
    }
    function set(x, y) {
      if (card.classList.contains('is-closed') || document.hidden) return;
      aim.x = clamp(x); aim.y = clamp(y);
      if (!raf) raf = requestAnimationFrame(step);
    }
    window.addEventListener('pointermove', function (e) {
      if (e.pointerType !== 'mouse') return;
      set((e.clientX / window.innerWidth - 0.5) * 2, (e.clientY / window.innerHeight - 0.5) * 2);
    }, { passive: true });
    document.addEventListener('mouseleave', function () { set(0, 0); });
    function onOrient(e) {
      if (e.gamma === null || e.beta === null) return;
      if (!base) base = { g: e.gamma, b: e.beta };       // tư thế cầm máy lúc đầu = thăng bằng
      set((e.gamma - base.g) / 22, (e.beta - base.b) / 22);
    }
    // D43 (R5-12): chỉ nghe cảm biến khi trình duyệt KHÔNG đòi xin quyền (Android/Chrome). iOS 13+ đòi quyền:
    // không bao giờ hỏi (hộp thoại hệ thống bật giữa lúc mở thiệp làm người lớn tuổi hoảng) -> iPhone chỉ không nghiêng.
    var D = window.DeviceOrientationEvent;
    if (D && typeof D.requestPermission !== 'function') window.addEventListener('deviceorientation', onOrient);
    return {};
  })();

  var store = function (v) { try { sessionStorage.setItem(key, v); } catch (e) { /* chế độ riêng tư */ } };
  var q = function (s) { return card.querySelector(s); };
  var focusables = function () {
    return Array.prototype.filter.call(card.querySelectorAll('button, a[href], input:not([type=hidden]), textarea, [tabindex="-1"]#ic-name'),
      function (el) { return !el.disabled && el.offsetParent !== null && !el.classList.contains('hp'); });
  };

  // ── Mở / đóng ─────────────────────────────────────────────
  function openEnvelope() {
    if (card.classList.contains('is-open') || card.classList.contains('is-opening') || card.classList.contains('is-closed')) return;
    store('open');
    // Chạm phong bì = đã "mở thiệp" trong phiên: sang trang chính không hiện màn "Mở thiệp" lần 2 (R3-20).
    try { sessionStorage.setItem('wd-cover-seen', '1'); } catch (e) { /* chế độ riêng tư */ }
    root.classList.add('ic-open');   // M1-CARDS-01: thiệp đang mở -> CSS ẩn nút VI|EN nổi (không đè lên thiệp)
    if (reduced) {
      card.classList.add('is-open', 'is-instant');
      q('#ic-name').focus();
      return;
    }
    card.classList.add('is-opening');
    setTimeout(function () {
      card.classList.add('is-open');
      card.classList.remove('is-opening');
      q('#ic-name').focus({ preventScroll: true });
    }, openMs);
  }

  function close() {
    store('closed');
    lastFocus = null;
    var done = function () {
      card.classList.add('is-closed');
      card.classList.remove('is-leaving');
      root.classList.remove('ic-lock', 'ic-open');
      if (reopen) { reopen.hidden = false; reopen.focus({ preventScroll: true }); }
    };
    if (reduced) return done();
    card.classList.add('is-leaving');
    setTimeout(done, 380);
  }

  function show() {
    lastFocus = document.activeElement;
    card.classList.remove('is-closed', 'is-opening');
    card.classList.add('is-open', 'is-instant');
    root.classList.add('ic-lock', 'ic-open');
    if (reopen) reopen.hidden = true;
    store('open');
    q('.ic-scroll').scrollTop = 0;
    q('#ic-name').focus({ preventScroll: true });
  }

  if (card.classList.contains('is-closed')) {
    if (reopen) reopen.hidden = false;
  } else if (card.classList.contains('is-open')) {
    q('#ic-name').focus({ preventScroll: true });
  }

  // R5-02: lúc bìa đóng, chạm/bấm BẤT KỲ ĐÂU trên thiệp đều mở: nút bìa, dòng "Chạm để mở thiệp", nền mờ, và cả
  // sân khấu 3D .ic-stage — WebKit (iPhone) hit-test theo chiều sâu nên phần bìa đang nghiêng ra sau nhận click ở
  // .ic-stage chứ không ở nút. Một chỗ nghe duy nhất trên .ic; openEnvelope tự chống mở 2 lần.
  card.addEventListener('click', function (e) {
    if (card.classList.contains('is-open') || e.target.closest('.ic-card, a')) return;
    openEnvelope();
  });
  q('[data-ic-close]').addEventListener('click', close);
  if (reopen) reopen.addEventListener('click', show);

  document.addEventListener('keydown', function (e) {
    if (card.classList.contains('is-closed')) return;
    if (e.key === 'Escape') { e.preventDefault(); close(); return; }
    if (e.key !== 'Tab') return;
    var f = focusables();
    if (!f.length) return;
    var first = f[0], last = f[f.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    else if (!card.contains(document.activeElement)) { e.preventDefault(); first.focus(); }
  });

  // ── Trả lời ngay trên thiệp ───────────────────────────────
  var box = q('[data-ic-rsvp]');
  if (!box) return;
  var form = q('[data-ic-form]'), ask = q('[data-ic-ask]'), done = q('[data-ic-done]');
  var err = q('[data-ic-err]'), guests = form.elements.guests, submit = q('[data-ic-submit]');
  var yesOnly = q('[data-ic-yes-only]');           // không có khi thiệp chỉ dành cho 1 người
  var max = parseInt(guests.getAttribute('max'), 10) || (guests.type === 'hidden' ? 1 : 20);
  var t = function (k) { return card.getAttribute('data-t-' + k) || ''; };
  var showErr = function (msg) { err.textContent = msg; err.hidden = false; };
  // Kẹp số người về [1, tối đa]; vượt thì báo ngay trên thiệp (không dùng bong bóng mặc định của trình duyệt).
  var clampGuests = function () {
    if (guests.type === 'hidden') return true;
    var raw = guests.value.trim(), n = parseInt(raw, 10);
    if (raw === '' || isNaN(n) || n < 1) { guests.value = 1; return true; }
    if (n > max) {
      guests.value = max;
      warnMax();
      return false;
    }
    return true;
  };
  // Báo ngay cạnh ô số người (dòng gợi ý đổi màu) + dòng lỗi của thiệp.
  var countHint = q('[data-ic-count-hint]');
  function warnMax() {
    showErr(__('Thiệp dành cho tối đa {n} người.', { n: max }));
    if (countHint) { countHint.classList.remove('is-warn'); void countHint.offsetWidth; countHint.classList.add('is-warn'); }
  }
  if (guests.type !== 'hidden') {
    guests.addEventListener('blur', clampGuests);
    guests.addEventListener('input', function () {
      var n = parseInt(guests.value, 10);
      if (!isNaN(n) && n > max) clampGuests(); else err.hidden = true;
    });
  }

  function view(name) {
    ask.hidden = name !== 'ask';
    form.hidden = name !== 'form';
    done.hidden = name !== 'done';
  }

  function choose(attend) {
    if (preview) return;
    form.elements.attend.value = attend;
    if (yesOnly) yesOnly.hidden = attend !== 'yes';
    var title = q('[data-ic-form-title]');
    title.textContent = attend === 'yes' ? t('form-yes') : t('form-no');
    submit.textContent = attend === 'yes' ? __('Gửi xác nhận tham dự') : __('Gửi lời báo');
    submit.className = 'ic-btn ' + (attend === 'yes' ? 'ic-btn-yes' : 'ic-btn-no');
    err.hidden = true;
    view('form');
    // Focus tiêu đề (không focus ô số): trên điện thoại bàn phím không bật lên che nút +/− và nút Gửi.
    title.focus({ preventScroll: true });
  }

  box.addEventListener('click', function (e) {
    var b = e.target.closest('[data-ic-attend]');
    if (b) return choose(b.getAttribute('data-ic-attend'));
    if (e.target.closest('[data-ic-back]')) {
      view(card.getAttribute('data-status') === 'pending' ? 'ask' : 'done');
      return;
    }
    if (e.target.closest('[data-ic-change]')) return view('ask');
    var st = e.target.closest('[data-ic-step]');
    if (st) {
      var v = (parseInt(guests.value, 10) || 1) + parseInt(st.getAttribute('data-ic-step'), 10);
      guests.value = Math.max(1, Math.min(max, v));
      err.hidden = true;
      if (v > max) warnMax(); else if (countHint) countHint.classList.remove('is-warn');
    }
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (preview || !window.AC) return;
    if (form.elements.attend.value === 'yes' && !clampGuests()) { guests.focus(); return; }
    submit.disabled = true;
    err.hidden = true;
    var data = new FormData(form);
    data.delete(AC.csrfName);            // AC.post tự thêm token CSRF
    AC.post(form.getAttribute('action'), data).then(function (r) {
      submit.disabled = false;
      if (!r.ok) { showErr(r.error || __('Chưa gửi được, bạn thử lại sau ít phút nhé.')); return; }
      applyAnswer(r);
      done.classList.remove('ic-pop');
      void done.offsetWidth;
      done.classList.add('ic-pop');
      syncPageForm(r);
    });
  });

  // Hiện câu trả lời đã gửi trên thiệp (từ chính thiệp hoặc từ form cuối trang — R2-23).
  function applyAnswer(r) {
    card.setAttribute('data-status', r.status);
    card.setAttribute('data-guests', r.guests || 0);
    q('[data-ic-done-title]').textContent = r.title || (r.status === 'yes' ? t('done-yes') : t('done-no'));
    // Số ít EN qua khóa '<chuỗi>|1' CHỈ khi từ điển có (như __n() PHP); tiếng Việt không có từ điển -> dùng khóa thường
    // (trước đây in nguyên "1 người|1" ở bản VI — audit designer P0).
    var dict = window.AC_I18N || {};
    var nGuests = (r.guests === 1 && dict['{n} người|1']) ? '{n} người|1' : '{n} người';
    q('[data-ic-done-sub]').textContent = r.status === 'yes'
      ? __(nGuests, { n: r.guests }) + ' · ' + (r.thanks || t('thanks-yes')) : (r.thanks || t('thanks-no'));
    if (r.status === 'yes' && guests.type !== 'hidden' && r.guests) guests.value = r.guests;
    // Lời nhắn trống = máy chủ giữ lời cũ: không xóa chữ đang có trong ô.
    if (r.message && form.elements.message) form.elements.message.value = r.message;
    view('done');
  }
  document.addEventListener('ac:rsvp', function (e) { if (e.detail && e.detail.status) applyAnswer(e.detail); });

  // Đồng bộ ô xác nhận ở cuối trang cưới với câu trả lời vừa gửi (khách đóng thiệp sẽ thấy đúng).
  function syncPageForm(r) {
    var pf = document.querySelector('[data-rsvp-form]');
    if (!pf) return;
    var radio = pf.querySelector('input[name="attend"][value="' + r.status + '"]');
    if (radio) { radio.checked = true; radio.dispatchEvent(new Event('change', { bubbles: true })); }
    if (pf.elements.guests && r.status === 'yes') pf.elements.guests.value = r.guests;
    if (pf.elements.message && r.message) pf.elements.message.value = r.message;
    var pd = document.querySelector('[data-rsvp-done]');
    if (pd) {
      pd.hidden = false;
      pd.querySelector('[data-rsvp-done-title]').textContent = r.title || '';
      pd.querySelector('[data-rsvp-done-sub]').textContent = (r.status === 'yes' ? __('Số người: {n}.', { n: r.guests }) + ' ' : '') + __('Muốn đổi câu trả lời? Chọn lại bên dưới.');
    }
  }
})();
