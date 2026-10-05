/* Ảnh Cưới — trang cưới (khách): menu, nhạc nền, thêm vào lịch (.ics), form xác nhận tham dự.
   Đếm ngược + trình xem ảnh nằm ở app.js. */
(function () {
  'use strict';
  var __ = window.__ || function (s) { return s; };   // i18n: chuỗi Việt -> tiếng Anh (application/language/en/ui_js_*.php)

  var nav = document.querySelector('[data-nav]');
  if (nav) {
    var onScroll = function () { nav.classList.toggle('scrolled', window.scrollY > 60); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    var burger = nav.querySelector('[data-burger]');
    var setMenu = function (open) {
      nav.classList.toggle('open', open);
      burger.textContent = open ? '✕' : '☰';
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
      burger.setAttribute('aria-label', open ? __('Đóng menu') : __('Mở menu'));
    };
    burger.addEventListener('click', function () { setMenu(!nav.classList.contains('open')); });
    nav.addEventListener('click', function (e) { if (e.target.closest('a')) setMenu(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && nav.classList.contains('open')) { setMenu(false); burger.focus(); } });
  }

  // Nhạc nền: assets/js/music.js (dùng chung cho trang cưới, album, gửi ảnh).

  // Thêm vào lịch: tạo file .ics ngay trên máy khách (không phụ thuộc dịch vụ ngoài). Giờ đã tính sẵn ở máy chủ
  // theo UTC (…Z) nên lịch nào cũng hiểu đúng; sự kiện không rõ giờ thành sự kiện cả ngày.
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-ics]');
    if (!b || document.body.classList.contains('is-draft') && b.closest('[data-events]')) return;
    var g = function (k) { return b.getAttribute('data-ics-' + k) || ''; };
    var esc = function (s) { return String(s || '').replace(/([,;\\])/g, '\\$1').replace(/\n/g, '\\n'); };
    var allday = g('allday') === '1';
    var start = g('start') || b.getAttribute('data-ics');
    var ics = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//AnhCuoi//VI', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH', 'BEGIN:VEVENT',
      'UID:' + (g('uid') || start + '@anhcuoi'), 'DTSTAMP:' + new Date().toISOString().replace(/[-:]/g, '').slice(0, 15) + 'Z',
      (allday ? 'DTSTART;VALUE=DATE:' : 'DTSTART:') + start,
      g('end') ? (allday ? 'DTEND;VALUE=DATE:' : 'DTEND:') + g('end') : 'DURATION:PT4H',
      'SUMMARY:' + esc(g('title')), 'LOCATION:' + esc(g('location')),
      'URL:' + location.origin + location.pathname,
      'BEGIN:VALARM', 'TRIGGER:-P1D', 'ACTION:DISPLAY', 'DESCRIPTION:' + esc(g('title')), 'END:VALARM',
      'END:VEVENT', 'END:VCALENDAR'].join('\r\n');
    var a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([ics], { type: 'text/calendar;charset=utf-8' }));
    a.download = (g('uid').split('@')[0] ? 'dam-cuoi-' + g('uid').split('@')[0].slice(0, 6) : 'dam-cuoi') + '.ics';
    document.body.appendChild(a);
    a.click();
    setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 500);
  });

  // Bản đồ: chỉ nạp Google Maps khi khách bấm "Xem bản đồ".
  document.addEventListener('click', function (e) {
    var m = e.target.closest('[data-map-load]');
    if (!m) return;
    var f = document.createElement('iframe');
    f.title = m.getAttribute('data-title') || __('Bản đồ');
    f.referrerPolicy = 'no-referrer-when-downgrade';
    f.src = m.getAttribute('data-src');
    m.replaceWith(f);
  });

  // Form xác nhận: chỉ hỏi số người khi chọn "Sẽ tham dự".
  var rsvp = document.querySelector('[data-rsvp-form]');
  if (rsvp) {
    var yesBox = rsvp.querySelector('[data-rsvp-yes]');
    var sync = function () {
      var v = rsvp.querySelector('input[name="attend"]:checked');
      yesBox.hidden = !!v && v.value === 'no';
    };
    rsvp.addEventListener('change', sync);
    sync();
  }

  // Lời chúc + xác nhận ở trang chính gửi bằng AJAX: lời cảm ơn hiện ngay tại mục vừa gửi (không tự tắt, không tải lại
  // trang nên màn "Mở thiệp" không che). Lỗi (kể cả bị giới hạn) giữ nguyên chữ đã gõ. Không có JS -> form thường.
  var checkForm = function (f) {
    var name = f.elements.name, msg = f.elements.message, guests = f.elements.guests;
    if (name && !name.value.trim()) return [name, __('Hãy nhập tên nhé.')];
    if (f.hasAttribute('data-wish-form') && !msg.value.trim()) return [msg, __('Hãy viết đôi lời chúc nhé.')];
    if (f.hasAttribute('data-rsvp-form')) {
      if (!f.querySelector('input[name="attend"]:checked')) return [f.querySelector('input[name="attend"]'), __('Hãy chọn "Sẽ tham dự" hoặc "Không thể tham dự".')];
      if (guests && guests.type === 'number') {
        var max = parseInt(guests.max, 10) || 20, n = parseInt(guests.value, 10);
        if (isNaN(n) || n < 1) guests.value = 1;
        else if (n > max) { guests.value = max; return [guests, __('Tối đa {n} người.', { n: max })]; }
      }
    }
    return null;
  };
  var addWish = function (w) {
    var list = document.querySelector('[data-wish-list]');
    if (!list || !w) return;
    var q = document.createElement('blockquote'), p = document.createElement('p'), c = document.createElement('cite');
    q.className = 'wish in';
    p.textContent = w.message;          // textContent: giữ xuống dòng qua CSS pre-line, không chèn HTML
    p.style.whiteSpace = 'pre-line';
    c.textContent = w.name;
    q.appendChild(p); q.appendChild(c);
    list.insertBefore(q, list.firstChild);
  };
  // Lời chúc: chỉ hiện 6 lời mới nhất, "Xem thêm" mở thêm 12 lời mỗi lần (R1-THEMES-08).
  var wishMore = document.querySelector('[data-wish-more]');
  if (wishMore) {
    wishMore.addEventListener('click', function () {
      var rest = document.querySelectorAll('[data-wish-list] [data-wish-extra][hidden]');
      for (var i = 0; i < rest.length && i < 12; i++) rest[i].hidden = false;
      var left = rest.length - Math.min(12, rest.length);
      if (left <= 0) wishMore.parentNode.remove();
      else wishMore.querySelector('[data-wish-more-n]').textContent = left;
    });
  }
  var clearFieldErr = function (f) {
    f.querySelectorAll('[data-field-err]').forEach(function (x) { x.remove(); });
    f.querySelectorAll('[aria-invalid="true"]').forEach(function (x) { x.removeAttribute('aria-invalid'); });
  };
  var fieldErr = function (input, msg) {
    var m = document.createElement('small');
    m.className = 'field-err';
    m.setAttribute('data-field-err', '');
    m.setAttribute('role', 'alert');
    m.id = (input.name || 'f') + '-err-' + Math.random().toString(36).slice(2, 7);
    m.textContent = msg || __('Ô này chưa đúng.');
    input.setAttribute('aria-invalid', 'true');
    input.setAttribute('aria-errormessage', m.id);
    input.insertAdjacentElement('afterend', m);
    input.focus();
    input.addEventListener('input', function off() {
      input.removeAttribute('aria-invalid'); m.remove(); input.removeEventListener('input', off);
    });
  };
  document.querySelectorAll('[data-ajax-form]').forEach(function (f) {
    var err = f.querySelector('[data-form-err]');
    var thanks = f.parentNode.querySelector('[data-form-thanks]');
    var btn = f.querySelector('button[type="submit"]');
    f.addEventListener('submit', function (e) {
      if (!window.AC || !window.fetch) return;
      e.preventDefault();
      var bad = checkForm(f);
      err.hidden = true;
      clearFieldErr(f);
      if (bad) { err.textContent = bad[1]; err.hidden = false; bad[0].focus(); return; }
      btn.disabled = true;
      var data = new FormData(f);
      data.delete(AC.csrfName);
      AC.post(f.getAttribute('action'), data).then(function (r) {
        btn.disabled = false;
        if (!r.ok) {
          // Lỗi của 1 ô (vd SĐT — R3-04): hiện ngay dưới ô đó, viền lỗi; chữ đã gõ giữ nguyên.
          if (r.field && f.elements[r.field] && f.elements[r.field].tagName) return fieldErr(f.elements[r.field], r.error);
          err.textContent = r.error || __('Chưa gửi được, bạn thử lại sau ít phút nhé.'); err.hidden = false; return;
        }
        var msg = r.thanks || __('Cảm ơn bạn!');
        if (f.hasAttribute('data-rsvp-form')) {
          var done = document.querySelector('[data-rsvp-done]');
          if (done && r.title) {
            done.hidden = false;
            done.querySelector('[data-rsvp-done-title]').textContent = r.title;
            done.querySelector('[data-rsvp-done-sub]').textContent = (r.status === 'yes' ? __('Số người: {n}.', { n: r.guests }) + ' ' : '') + __('Muốn đổi câu trả lời? Chọn lại bên dưới.');
          }
          // Thiệp mời riêng (nếu có) cập nhật theo câu trả lời mới (R2-23) — invite-card.js lắng nghe.
          document.dispatchEvent(new CustomEvent('ac:rsvp', { detail: r }));
        } else {
          f.elements.message.value = '';
          addWish(r.wish);
        }
        if (thanks) {
          thanks.textContent = msg;
          thanks.hidden = false;
          thanks.classList.remove('pop'); void thanks.offsetWidth; thanks.classList.add('pop');
          thanks.focus({ preventScroll: true });
          thanks.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
      });
    });
  });
})();

/* Hiện dần từng phần khi cuộn tới (nhẹ nhàng, hợp không khí đám cưới). Không có JS hoặc người dùng
   bật "giảm chuyển động" thì mọi thứ hiện sẵn — không bao giờ giấu nội dung. */
(function () {
  'use strict';
  if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var items = document.querySelectorAll('.sec-head, .save-card, .person, .event-card, .event-photo, .ev, .quote blockquote, .wd-g, .album-card, .rsvp-card, .wish, .wd-foot .wd-mono');
  document.documentElement.classList.add('js-reveal');
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      if (!en.isIntersecting) return;
      en.target.classList.add('in');
      io.unobserve(en.target);
    });
  }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
  items.forEach(function (el, i) {
    el.classList.add('reveal');
    // Ảnh trong lưới xuất hiện so le cho mềm mại.
    if (el.classList.contains('wd-g') || el.classList.contains('person')) el.style.transitionDelay = (i % 4) * 80 + 'ms';
    io.observe(el);
  });
})();
