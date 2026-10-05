/* Ảnh Cưới — JS dùng chung: CSRF + POST, thông báo, đếm ngược, lightbox, sao chép, xác nhận. */
(function () {
  'use strict';
  var __ = window.__ || function (s) { return s; };   // i18n: chuỗi Việt -> tiếng Anh (application/language/en/ui_js_*.php)

  var meta = function (n) { var m = document.querySelector('meta[name="' + n + '"]'); return m ? m.content : ''; };

  var AC = window.AC = {
    csrfName: meta('csrf-name'),
    csrfHash: meta('csrf-hash'),
    baseUrl: meta('base-url'),

    /**
     * POST FormData/obj kèm CSRF, trả Promise<json>. Lỗi mạng/không phải JSON -> {ok:false,error}.
     * Token CSRF hết hạn/mất cookie (403 không phải JSON của ứng dụng): tự lấy token mới ở /health/csrf rồi gửi lại
     * ĐÚNG 1 lần; vẫn 403 -> {ok:false, code:'csrf', status:403, error} (hợp đồng với editor.js, round2-deps.md).
     */
    post: function (url, data) {
      var fd = data instanceof FormData ? data : new FormData();
      if (!(data instanceof FormData)) {
        Object.keys(data || {}).forEach(function (k) {
          var v = data[k];
          if (Array.isArray(v)) v.forEach(function (x) { fd.append(k + '[]', x); });
          else fd.append(k, v);
        });
      }
      var send = function () {
        fd.set(AC.csrfName, AC.csrfHash);
        return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
          .then(function (r) {
            return r.text().then(function (t) {
              var j = null;
              try { j = JSON.parse(t); } catch (e) { /* trang lỗi HTML */ }
              if (j === null || typeof j !== 'object') j = null;
              return { status: r.status, json: j };
            });
          });
      };
      // 403 do CSRF là trang lỗi HTML của CI; 403 của ứng dụng (mục đang tắt, chủ nhà xem thử…) là JSON {ok:false}.
      var isCsrf = function (res) { return res.status === 403 && (!res.json || res.json.code === 'csrf'); };
      var CSRF_ERR = { ok: false, code: 'csrf', status: 403, error: __('Trang đã mở quá lâu. Hãy sao chép chữ đang gõ rồi tải lại trang.') };
      var finish = function (res) {
        if (isCsrf(res)) return CSRF_ERR;
        return res.json || { ok: false, error: __('Máy chủ trả lỗi {code}.', { code: res.status }) };
      };
      return send()
        .then(function (res) {
          if (!isCsrf(res)) return finish(res);
          return AC.refreshCsrf().then(function (ok) { return ok ? send().then(finish) : CSRF_ERR; });
        })
        .catch(function () { return { ok: false, error: __('Mất kết nối tới máy chủ.') }; });
    },

    /** Lấy token CSRF mới (GET /health/csrf, no-store), cập nhật AC + <meta>. Promise<bool>. */
    refreshCsrf: function () {
      return fetch((AC.baseUrl || '/') + 'health/csrf', { credentials: 'same-origin', cache: 'no-store' })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (j) {
          if (!j || !j.ok || !j.hash) return false;
          AC.csrfHash = j.hash;
          if (j.name) AC.csrfName = j.name;
          var m = document.querySelector('meta[name="csrf-hash"]');
          if (m) m.content = j.hash;
          // Form thường trên trang (không AJAX) cũng dùng token mới.
          document.querySelectorAll('input[type="hidden"][name="' + AC.csrfName + '"]').forEach(function (i) { i.value = j.hash; });
          return true;
        })
        .catch(function () { return false; });
    },

    toast: function (msg, type) {
      var old = document.querySelector('.flash');
      if (old) old.remove();
      var d = document.createElement('div');
      d.className = 'flash flash-' + (type || 'success');
      d.setAttribute('role', 'status');
      d.textContent = msg;
      var x = document.createElement('button');
      x.type = 'button'; x.className = 'flash-x'; x.setAttribute('aria-label', __('Đóng')); x.textContent = '×';
      d.appendChild(x);
      document.body.appendChild(d);
      bindFlash(d);
    }
  };

  function bindFlash(el) {
    var t = setTimeout(function () { el.remove(); }, el.classList.contains('flash-error') ? 9000 : 4500);
    el.querySelector('.flash-x').addEventListener('click', function () { clearTimeout(t); el.remove(); });
  }
  document.querySelectorAll('.flash').forEach(bindFlash);

  // ── Đếm ngược tới ngày cưới ─────────────────────────────────
  document.querySelectorAll('[data-countdown]').forEach(function (el) {
    var target = parseInt(el.getAttribute('data-countdown'), 10) * 1000;
    var parts = { d: el.querySelector('[data-d]'), h: el.querySelector('[data-h]'), m: el.querySelector('[data-m]'), s: el.querySelector('[data-s]') };
    function tick() {
      var left = Math.max(0, target - Date.now()) / 1000;
      parts.d.textContent = Math.floor(left / 86400);
      parts.h.textContent = Math.floor(left % 86400 / 3600);
      parts.m.textContent = Math.floor(left % 3600 / 60);
      parts.s.textContent = Math.floor(left % 60);
      if (left <= 0) { el.hidden = true; clearInterval(iv); }
    }
    var iv = setInterval(tick, 1000);
    tick();
  });

  // ── Lightbox: mọi [data-lb] trong cùng [data-gallery] (hoặc cả trang) là một bộ ─────
  // Điện thoại: vuốt ngang đổi ảnh, chạm 2 lần / chụm 2 ngón để phóng (kéo để xem khi đang phóng), nút Back đóng ảnh.
  var lb = document.getElementById('lb');
  if (lb) {
    var img = lb.querySelector('.lb-img'), cap = lb.querySelector('.lb-cap'), cnt = lb.querySelector('.lb-count');
    var dl = lb.querySelector('.lb-dl'), items = [], idx = 0, lastFocus = null, pushed = false;
    var zoom = { s: 1, x: 0, y: 0 };

    var applyZoom = function () {
      img.style.transform = zoom.s > 1 ? 'translate(' + zoom.x + 'px,' + zoom.y + 'px) scale(' + zoom.s + ')' : '';
      lb.classList.toggle('is-zoomed', zoom.s > 1);
    };
    var clampPan = function () {
      var mx = img.clientWidth * (zoom.s - 1) / 2, my = img.clientHeight * (zoom.s - 1) / 2;
      zoom.x = Math.max(-mx, Math.min(mx, zoom.x));
      zoom.y = Math.max(-my, Math.min(my, zoom.y));
    };
    var drag = null;   // kéo chuột khi đã phóng (S1-DESK-04)
    var resetZoom = function () { zoom.s = 1; zoom.x = 0; zoom.y = 0; drag = null; lb.classList.remove('is-dragging'); applyZoom(); };
    // Phóng quanh điểm chạm (cx, cy toạ độ màn hình).
    var zoomTo = function (s, cx, cy) {
      var r = img.getBoundingClientRect();       // khung đã dịch (translate) -> trừ lại để lấy tâm gốc
      var ox = cx - (r.left + r.width / 2 - zoom.x), oy = cy - (r.top + r.height / 2 - zoom.y);
      var k = s / zoom.s;
      zoom.x = (zoom.x - ox) * k + ox;
      zoom.y = (zoom.y - oy) * k + oy;
      zoom.s = s;
      if (s <= 1) { zoom.x = 0; zoom.y = 0; zoom.s = 1; }
      clampPan();
      applyZoom();
    };

    var show = function (i) {
      idx = (i + items.length) % items.length;
      var a = items[idx];
      resetZoom();
      // srcset: điện thoại tải bản 1280px, máy tính bản 2048px (không tải ảnh to hơn màn hình cần).
      // Thứ tự quan trọng với WebKit (R4-05): gỡ srcset khi src còn là ảnh cũ làm Safari tải thêm bản _m của ảnh vừa rời.
      // Có srcset mới -> gán sizes + srcset TRƯỚC rồi mới src; không có -> đổi src trước rồi mới gỡ srcset.
      var ss = a.getAttribute('data-srcset');
      img.sizes = '100vw';
      if (ss) { img.srcset = ss; img.src = a.getAttribute('href'); }
      else { img.src = a.getAttribute('href'); img.removeAttribute('srcset'); }
      var th = a.querySelector('img');
      img.alt = (th && th.alt) || a.getAttribute('data-cap') || __('Ảnh cưới');
      cap.textContent = a.getAttribute('data-cap') || '';
      cnt.textContent = items.length > 1 ? (idx + 1) + ' / ' + items.length : '';
      lb.classList.toggle('is-single', items.length < 2);
      // Nút "Tải ảnh" chỉ có trong markup khi chủ nhà cho khách tải (settings.album_download) hoặc chủ nhà đang xem.
      var full = a.getAttribute('data-full');
      if (dl) { dl.hidden = !full; if (full) dl.href = full; }
      // Tải trước ảnh kế tiếp để vuốt không phải chờ.
      if (items.length > 1) {
        var nx = items[(idx + 1) % items.length], pre = new Image();
        pre.sizes = '100vw';
        if (nx.getAttribute('data-srcset')) pre.srcset = nx.getAttribute('data-srcset');
        pre.src = nx.getAttribute('href');
      }
    };
    var hide = function () {
      // Gỡ src trước srcset (R4-05): ngược lại WebKit rơi về src (_m) và tải ảnh lớn ngay lúc đóng.
      lb.hidden = true; img.removeAttribute('src'); img.removeAttribute('srcset'); document.body.style.overflow = '';
      resetZoom();
      if (lastFocus) lastFocus.focus({ preventScroll: true });
    };
    var open = function (a) {
      var scope = a.closest('[data-gallery]') || document;
      items = Array.prototype.slice.call(scope.querySelectorAll('[data-lb]'));
      // Esc trả focus về đúng ô ảnh vừa mở (Firefox/Safari không focus link khi bấm chuột -> activeElement là body).
      lastFocus = a.tabIndex >= 0 ? a : document.activeElement;
      lb.hidden = false;
      document.body.style.overflow = 'hidden';
      show(items.indexOf(a));
      lb.querySelector('.lb-close').focus();
      // Nút Back của điện thoại đóng ảnh thay vì rời trang.
      try { history.pushState({ acLb: 1 }, ''); pushed = true; } catch (e) { pushed = false; }
    };
    // Đóng bằng ✕/Esc/nền: lùi mục lịch sử đã thêm (không để lại mục thừa); popstate sẽ ẩn.
    var close = function () {
      if (lb.hidden) return;
      if (pushed) { pushed = false; history.back(); } else hide();
    };
    window.addEventListener('popstate', function () { if (!lb.hidden) { pushed = false; hide(); } });
    AC.openLightbox = open;

    document.addEventListener('click', function (e) {
      var a = e.target.closest('[data-lb]');
      if (!a || e.metaKey || e.ctrlKey) return;
      e.preventDefault();
      open(a);
    });
    lb.querySelector('.lb-close').addEventListener('click', close);
    lb.querySelector('.lb-prev').addEventListener('click', function () { show(idx - 1); });
    lb.querySelector('.lb-next').addEventListener('click', function () { show(idx + 1); });
    lb.addEventListener('click', function (e) { if (e.target === lb && zoom.s === 1) close(); });
    img.addEventListener('dblclick', function (e) { zoomTo(zoom.s > 1 ? 1 : 2.5, e.clientX, e.clientY); });
    document.addEventListener('keydown', function (e) {
      if (lb.hidden) return;
      if (e.key === 'Escape') close();
      else if (e.key === 'ArrowLeft') show(idx - 1);
      else if (e.key === 'ArrowRight') show(idx + 1);
      else if (e.key === 'Tab') {
        // Bẫy focus trong hộp thoại (aria-modal): Tab vòng qua các nút đang hiện (✕, ‹, ›, Tải ảnh), không rơi xuống trang.
        // Tự chuyển focus (không để trình duyệt làm): Safari mặc định bỏ qua nút khi Tab -> vẫn đi đúng vòng trên mọi engine.
        var f = Array.prototype.filter.call(lb.querySelectorAll('button, a[href]'), function (el) { return !el.hidden && el.offsetWidth > 0; });
        if (!f.length) return;
        e.preventDefault();
        var i = f.indexOf(document.activeElement);
        f[i < 0 ? 0 : (i + (e.shiftKey ? -1 : 1) + f.length) % f.length].focus();
      }
    });

    // S1-DESK-04 — máy tính: kéo chuột để xem phần khác khi đã phóng (pointer events, bỏ qua cảm ứng — đã có nhánh touch bên dưới);
    // cuộn chuột / Ctrl+cuộn / chụm trackpad (wheel + ctrlKey) phóng-thu quanh con trỏ.
    img.addEventListener('dragstart', function (e) { e.preventDefault(); });
    img.addEventListener('pointerdown', function (e) {
      if (e.pointerType === 'touch' || zoom.s <= 1 || e.button !== 0) return;
      e.preventDefault();
      drag = { id: e.pointerId, x: e.clientX, y: e.clientY, zx: zoom.x, zy: zoom.y };
      lb.classList.add('is-dragging');
      try { img.setPointerCapture(e.pointerId); } catch (err) { /* trình duyệt cũ */ }
    });
    img.addEventListener('pointermove', function (e) {
      if (!drag || e.pointerId !== drag.id) return;
      zoom.x = drag.zx + (e.clientX - drag.x);
      zoom.y = drag.zy + (e.clientY - drag.y);
      clampPan(); applyZoom();
    });
    var endDrag = function (e) { if (!drag || e.pointerId !== drag.id) return; drag = null; lb.classList.remove('is-dragging'); };
    img.addEventListener('pointerup', endDrag);
    img.addEventListener('pointercancel', endDrag);
    lb.addEventListener('wheel', function (e) {
      e.preventDefault();   // không cuộn trang phía sau, không phóng cả trang khi Ctrl+cuộn
      var k = Math.exp(-e.deltaY * (e.ctrlKey ? 0.01 : 0.0022));
      zoomTo(Math.max(1, Math.min(4, zoom.s * k)), e.clientX, e.clientY);
    }, { passive: false });

    // Cử chỉ chạm: 1 ngón = vuốt đổi ảnh (hoặc kéo khi đang phóng), 2 ngón = chụm phóng, chạm đúp = phóng/thu.
    var t0 = null, pinch = null, lastTap = 0, moved = false;
    var dist = function (a, b) { return Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY); };
    lb.addEventListener('touchstart', function (e) {
      if (e.target.closest('.lb-btn')) return;
      moved = false;
      if (e.touches.length === 2) {
        pinch = { d: dist(e.touches[0], e.touches[1]), s: zoom.s,
          cx: (e.touches[0].clientX + e.touches[1].clientX) / 2, cy: (e.touches[0].clientY + e.touches[1].clientY) / 2 };
        t0 = null;
      } else if (e.touches.length === 1) {
        t0 = { x: e.touches[0].clientX, y: e.touches[0].clientY, zx: zoom.x, zy: zoom.y };
      }
    }, { passive: true });
    lb.addEventListener('touchmove', function (e) {
      if (pinch && e.touches.length === 2) {
        e.preventDefault();
        moved = true;
        zoomTo(Math.max(1, Math.min(4, pinch.s * dist(e.touches[0], e.touches[1]) / pinch.d)), pinch.cx, pinch.cy);
      } else if (t0 && e.touches.length === 1) {
        var dx = e.touches[0].clientX - t0.x, dy = e.touches[0].clientY - t0.y;
        if (Math.abs(dx) > 8 || Math.abs(dy) > 8) moved = true;
        if (zoom.s > 1) {
          e.preventDefault();
          zoom.x = t0.zx + dx; zoom.y = t0.zy + dy;
          clampPan(); applyZoom();
        }
      }
    }, { passive: false });
    lb.addEventListener('touchend', function (e) {
      if (pinch) { if (e.touches.length < 2) pinch = null; return; }
      if (!t0) return;
      var c = e.changedTouches[0], dx = c.clientX - t0.x;
      if (!moved) {
        var now = Date.now();
        if (now - lastTap < 300 && !e.target.closest('.lb-btn')) {
          e.preventDefault();
          zoomTo(zoom.s > 1 ? 1 : 2.5, c.clientX, c.clientY);
          lastTap = 0;
        } else lastTap = now;
      } else if (zoom.s === 1 && Math.abs(dx) > 50 && items.length > 1) {
        show(idx + (dx < 0 ? 1 : -1));
      }
      t0 = null;
    });
  }

  // ── Sao chép link ───────────────────────────────────────────
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copy]');
    if (!b) return;
    var input = b.parentElement.querySelector('[data-copy-src]');
    var done = function () { var t = b.textContent; b.textContent = __('Đã chép ✓'); setTimeout(function () { b.textContent = t; }, 1500); };
    // M2-OWNER-04: trình duyệt từ chối ghi clipboard (quyền, trang http) -> thử execCommand; vẫn không được thì bôi chọn link + báo.
    AC.copy(input.value).then(function (ok) {
      if (ok) return done();
      try { input.focus(); input.select(); input.setSelectionRange(0, input.value.length); } catch (e) { /* bỏ qua */ }
      AC.toast(__('Không sao chép tự động được — link đã được bôi chọn, hãy chép thủ công.'), 'error');
    });
  });

  // ── Gửi link cho khách: bảng chia sẻ của điện thoại (Zalo/Messenger/SMS…) ──
  // Máy không hỗ trợ (máy tính, trang http trong mạng nhà) -> bảng dự phòng: sao chép, Zalo, SMS, email.
  var legacyCopy = function (text) {
    var t = document.createElement('textarea');
    t.value = text; t.setAttribute('readonly', ''); t.style.position = 'fixed'; t.style.opacity = '0';
    document.body.appendChild(t); t.select();
    var ok = false;
    try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
    t.remove();
    return ok;
  };
  /** Sao chép vào clipboard -> Promise<boolean>. Clipboard API bị từ chối (quyền) -> thử execCommand trước khi báo false (M2-OWNER-04). */
  AC.copy = function (text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text).then(function () { return true; }, function () { return legacyCopy(text); });
    }
    return Promise.resolve(legacyCopy(text));
  };
  var shareSheet = function (o) {
    var full = o.text ? o.text + (o.url ? ' ' + o.url : '') : o.url;
    var old = document.querySelector('.share-sheet');
    if (old) old.remove();
    var m = document.createElement('div');
    m.className = 'share-sheet';
    m.setAttribute('role', 'dialog');
    m.setAttribute('aria-modal', 'true');
    m.innerHTML = '<div class="share-box"><h3></h3><p class="share-sub">' + __('Chép tin nhắn rồi dán vào Zalo, Messenger… hoặc chọn cách gửi:') + '</p>' +
      '<textarea readonly rows="4" aria-label="' + __('Tin nhắn sẽ gửi') + '"></textarea>' +
      '<button type="button" class="share-main" data-s-copy>' + __('Sao chép tin nhắn') + '</button>' +
      '<div class="share-apps">' +
      '<button type="button" data-s-zalo><span class="share-ic sz">Z</span>Zalo</button>' +
      '<a data-s-sms><span class="share-ic ss">✉</span>' + __('Tin nhắn SMS') + '</a>' +
      '<a data-s-mail><span class="share-ic sm">@</span>Email</a></div>' +
      '<button type="button" class="share-x" data-s-close>' + __(__('Đóng')) + '</button></div>';
    m.querySelector('h3').textContent = o.title || __('Gửi cho khách');
    m.querySelector('textarea').value = full;
    m.querySelector('[data-s-sms]').href = 'sms:?&body=' + encodeURIComponent(full);
    m.querySelector('[data-s-mail]').href = 'mailto:?subject=' + encodeURIComponent(o.subject || __('Thiệp mời cưới')) + '&body=' + encodeURIComponent(full);
    document.body.appendChild(m);
    var close = function () { m.remove(); document.removeEventListener('keydown', esc); };
    var esc = function (e) { if (e.key === 'Escape') close(); };
    document.addEventListener('keydown', esc);
    m.addEventListener('click', function (e) {
      if (e.target === m || e.target.closest('[data-s-close]')) return close();
      var cp = e.target.closest('[data-s-copy]'), z = e.target.closest('[data-s-zalo]');
      if (cp || z) {
        AC.copy(full).then(function (ok) {
          if (!ok) { m.querySelector('textarea').select(); return AC.toast(__('Chọn và sao chép nội dung trong khung giúp mình nhé.'), 'error'); }
          if (cp) { cp.textContent = __('Đã chép ✓ — dán vào tin nhắn là xong'); setTimeout(function () { cp.textContent = __('Sao chép tin nhắn'); }, 2500); }
          if (z) { AC.toast(__('Đã chép tin nhắn — mở Zalo, chọn người nhận rồi dán.')); window.open(/Mobi|Android|iPhone/i.test(navigator.userAgent) ? 'https://zalo.me/' : 'https://chat.zalo.me/', '_blank', 'noopener'); }
        });
      }
    });
    m.querySelector('[data-s-copy]').focus();
  };
  /** o = {url, text (không kèm link), title, subject} */
  AC.share = function (o) {
    if (navigator.share && window.isSecureContext) {
      var data = { title: o.title || document.title, url: o.url };
      if (o.text) data.text = o.text;
      return navigator.share(data).catch(function (err) { if (!err || err.name !== 'AbortError') shareSheet(o); });
    }
    shareSheet(o);
    return Promise.resolve();
  };
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-share]');
    if (!b) return;
    e.preventDefault();
    AC.share({ url: b.getAttribute('data-share-url') || b.getAttribute('data-share'), text: b.getAttribute('data-share-text') || '',
      title: b.getAttribute('data-share-title') || '', subject: b.getAttribute('data-share-subject') || '' });
  });

  // ── Giao diện VIP trên bản cài máy: hộp giới thiệu (ảnh xem trước + link thiep.site), KHÔNG đổi giao diện ──
  // Nút/ô chọn có data-vip-locked + data-vip-name/-desc/-img (thanh sửa trang cưới, Cài đặt).
  AC.vip = function (b) {
    var prev = document.activeElement;
    var m = document.createElement('div');
    m.className = 'vip-modal';
    m.setAttribute('role', 'dialog');
    m.setAttribute('aria-modal', 'true');
    m.setAttribute('aria-labelledby', 'vip-h');
    m.innerHTML = '<div class="vip-box"><button type="button" class="vip-x" data-vip-close aria-label="' + __(__('Đóng')) + '">✕</button>' +
      '<figure class="vip-fig"><img alt=""></figure><div class="vip-body">' +
      '<p class="vip-tag">' + __('Giao diện VIP') + '</p><h3 id="vip-h"></h3><p class="vip-desc"></p>' +
      '<p class="vip-lead"></p>' +
      '<a class="btn btn-accent vip-go" href="https://thiep.site/dang-ky/" target="_blank" rel="noopener">' + __('Đăng ký dùng VIP ↗') + '</a>' +
      '<button type="button" class="btn btn-ghost vip-later" data-vip-close>' + __('Để sau') + '</button></div></div>';
    // data-vip-kind="card": mẫu thiệp VIP (Khách mời -> Mẫu thiệp), còn lại là giao diện VIP.
    var card = b.getAttribute('data-vip-kind') === 'card', kind = card ? __('Mẫu thiệp VIP') : __('Giao diện VIP');
    m.querySelector('.vip-tag').textContent = kind;
    m.querySelector('.vip-lead').innerHTML = __(card ? '{kind} — dùng được khi tạo trang trên <b>thiep.site</b>. Bản cài trên máy có sẵn 10 mẫu thiệp miễn phí.'
      : '{kind} — dùng được khi tạo trang trên <b>thiep.site</b>. Bản cài trên máy có sẵn 10 giao diện miễn phí.', { kind: kind });
    m.querySelector('h3').textContent = b.getAttribute('data-vip-name') || kind;
    m.querySelector('.vip-desc').textContent = b.getAttribute('data-vip-desc') || '';
    var img = m.querySelector('img');
    img.src = b.getAttribute('data-vip-img') || '';
    img.alt = __(card ? 'Ảnh xem trước mẫu thiệp {ten}' : 'Ảnh xem trước giao diện {ten}', { ten: b.getAttribute('data-vip-name') || 'VIP' });
    img.onerror = function () { m.querySelector('.vip-fig').hidden = true; };
    document.body.appendChild(m);
    var close = function () { m.remove(); document.removeEventListener('keydown', esc); if (prev && prev.focus) prev.focus(); };
    var esc = function (e) { if (e.key === 'Escape') close(); };
    document.addEventListener('keydown', esc);
    m.addEventListener('click', function (e) { if (e.target === m || e.target.closest('[data-vip-close]')) close(); });
    m.querySelector('.vip-go').focus();
  };
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-vip-locked]');
    if (!b) return;
    e.preventDefault();
    AC.vip(b);
  });
  // Ô mẫu thiệp VIP bị khóa là <div role="button" tabindex="0"> -> Enter/Space cũng mở hộp giới thiệu.
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    var b = e.target.closest && e.target.closest('div[data-vip-locked]');
    if (!b || b !== e.target) return;
    e.preventDefault();
    AC.vip(b);
  });

  // ── Form cần xác nhận ──────────────────────────────────────
  document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) e.preventDefault();
  });

  var navBtn = document.querySelector('[data-toggle-nav]');
  if (navBtn) navBtn.addEventListener('click', function () { document.getElementById('adm-nav').classList.toggle('open'); });

  // ── Nút VI | EN (M1-GUEST-06): đổi ngôn ngữ là tải lại trang -> nhớ vị trí đang đọc, tải xong cuộn về đúng chỗ. ──
  var LANG_Y = 'ac-lang-y';
  document.addEventListener('click', function (e) {
    var a = e.target.closest('.lang-sw a');
    if (!a) return;
    try { sessionStorage.setItem(LANG_Y, location.pathname + '|' + Math.round(window.scrollY)); } catch (err) { /* chế độ riêng tư */ }
  });
  try {
    var saved = sessionStorage.getItem(LANG_Y);
    if (saved !== null) {
      sessionStorage.removeItem(LANG_Y);
      var parts = saved.split('|'), y = parseInt(parts[1], 10);
      if (parts[0] === location.pathname && y > 0 && !location.hash) {
        // Chờ bố cục ổn định (ảnh/phông nạp xong) rồi mới cuộn; không cuộn mượt để khách không thấy trang chạy.
        var jump = function () {
          var de = document.documentElement, sb = de.style.scrollBehavior;
          de.style.scrollBehavior = 'auto'; window.scrollTo(0, y); de.style.scrollBehavior = sb;
        };
        jump();
        window.addEventListener('load', function () { requestAnimationFrame(jump); });
      }
    }
  } catch (err) { /* bỏ qua */ }
})();
