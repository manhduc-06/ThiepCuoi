/* Ảnh Cưới — trang quản trị: chọn nhiều ảnh, thao tác hàng loạt, kéo thả sắp xếp, duyệt ảnh, mã QR. */
(function () {
  'use strict';
  var AC = window.AC;
  var __ = window.__ || function (t, v) { if (v) { for (var k in v) { t = t.split('{' + k + '}').join(v[k]); } } return t; };
  var url = function (p) { return AC.baseUrl + p; };

  // ── M1-OWNER-09: menu ☰ (app.js chỉ bật/tắt) — aria-expanded, đóng khi chạm ra ngoài / Esc / phóng to cửa sổ ──
  var navBtn = document.querySelector('[data-toggle-nav]'), nav = document.getElementById('adm-nav');
  if (navBtn && nav) {
    var navSync = function () { navBtn.setAttribute('aria-expanded', nav.classList.contains('open') ? 'true' : 'false'); };
    var navClose = function () { if (nav.classList.contains('open')) { nav.classList.remove('open'); navSync(); } };
    navBtn.setAttribute('aria-controls', 'adm-nav');
    navSync();
    navBtn.addEventListener('click', navSync);   // chạy SAU listener toggle của app.js (đăng ký trước)
    // pointerdown: iOS Safari không phát click tới document khi chạm vào chữ/khoảng trống.
    document.addEventListener('pointerdown', function (e) {
      if (nav.classList.contains('open') && !nav.contains(e.target) && !navBtn.contains(e.target)) navClose();
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') navClose(); });
    window.addEventListener('resize', navClose);
  }

  // ── Ô ảnh trong lưới album ─────────────────────────────────
  var ACAdmin = window.ACAdmin = {
    photoTile: function (p) {
      var f = document.createElement('figure');
      f.className = 'adm-photo';
      f.draggable = true;
      f.setAttribute('data-id', p.id);
      f.innerHTML = '<img alt="" loading="lazy"><div class="ph-actions">' +
        '<button type="button" title="' + __('Xem lớn') + '" data-view>⤢</button>' +
        '<button type="button" title="' + __('Đặt làm ảnh bìa album') + '" data-cover>★</button>' +
        '<button type="button" title="' + __('Đặt làm ảnh nền trang chủ') + '" data-hero>♥</button>' +
        '<button type="button" title="' + __('Chú thích') + '" data-caption="">✎</button></div>';
      f.querySelector('img').src = p.thumb;
      f.querySelector('img').setAttribute('data-medium', p.medium);
      return f;
    }
  };

  var grid = document.querySelector('[data-photo-grid]');
  var toolbar = document.querySelector('[data-toolbar]');
  if (grid && toolbar) {
    var selCount = toolbar.querySelector('[data-sel-count]');
    var selected = function () { return Array.prototype.slice.call(grid.querySelectorAll('.adm-photo.is-selected')); };
    var ids = function (els) { return els.map(function (el) { return el.getAttribute('data-id'); }); };
    var refresh = function () { var n = selected().length; selCount.textContent = n; toolbar.hidden = n === 0; };

    grid.addEventListener('click', function (e) {
      var tile = e.target.closest('.adm-photo');
      if (!tile) return;
      var btn = e.target.closest('button');
      var id = tile.getAttribute('data-id');
      if (!btn) { tile.classList.toggle('is-selected'); refresh(); return; }

      if (btn.hasAttribute('data-view')) {
        var a = document.createElement('a');
        a.href = tile.querySelector('img').getAttribute('data-medium');
        a.setAttribute('data-lb', '');
        a.hidden = true;
        document.body.appendChild(a);
        AC.openLightbox(a);
        a.remove();
      } else if (btn.hasAttribute('data-cover')) {
        AC.post(url('admin/photos/cover'), { id: id }).then(function (r) {
          if (!r.ok) return AC.toast(r.error, 'error');
          grid.querySelectorAll('.is-cover').forEach(function (x) { x.classList.remove('is-cover'); });
          tile.classList.add('is-cover');
          AC.toast(__('Đã đặt làm ảnh bìa album.'));
        });
      } else if (btn.hasAttribute('data-hero')) {
        AC.post(url('admin/photos/hero'), { id: id }).then(function (r) {
          AC.toast(r.ok ? __('Đã đặt làm ảnh nền trang chủ.') : r.error, r.ok ? 'success' : 'error');
        });
      } else if (btn.hasAttribute('data-caption')) {
        var cur = btn.getAttribute('data-caption') || '';
        var cap = window.prompt(__('Chú thích cho ảnh:'), cur);
        if (cap === null) return;
        AC.post(url('admin/photos/caption'), { id: id, caption: cap }).then(function (r) {
          if (r.ok) { btn.setAttribute('data-caption', cap); AC.toast(__('Đã lưu chú thích.')); } else AC.toast(r.error, 'error');
        });
      }
    });

    toolbar.addEventListener('click', function (e) {
      var b = e.target.closest('[data-act]');
      if (!b) return;
      var act = b.getAttribute('data-act');
      if (act === 'select-all') { grid.querySelectorAll('.adm-photo').forEach(function (x) { x.classList.add('is-selected'); }); refresh(); }
      if (act === 'clear') { selected().forEach(function (x) { x.classList.remove('is-selected'); }); refresh(); }
      if (act === 'delete') {
        var els = selected();
        if (!window.confirm(__('Xóa vĩnh viễn {n} ảnh (cả bản gốc)?', { n: els.length }))) return;
        AC.post(url('admin/photos/delete'), { ids: ids(els) }).then(function (r) {
          if (!r.ok) return AC.toast(r.error, 'error');
          els.forEach(function (x) { x.remove(); });
          refresh();
          AC.toast(__('Đã xóa {n} ảnh.', { n: r.deleted }));
        });
      }
    });

    toolbar.querySelector('[data-move-target]').addEventListener('change', function (e) {
      var target = e.target.value, els = selected();
      if (!target) return;
      AC.post(url('admin/photos/move'), { ids: ids(els), album_id: target }).then(function (r) {
        e.target.value = '';
        if (!r.ok) return AC.toast(r.error, 'error');
        els.forEach(function (x) { x.remove(); });
        refresh();
        AC.toast(__('Đã chuyển {n} ảnh.', { n: r.moved }));
      });
    });
  }

  // ── Kéo thả sắp xếp (album & ảnh) ──────────────────────────
  document.querySelectorAll('[data-sortable]').forEach(function (list) {
    var dragging = null;
    list.addEventListener('dragstart', function (e) {
      dragging = e.target.closest('[data-id]');
      if (!dragging) return;
      dragging.classList.add('is-dragging');
      e.dataTransfer.effectAllowed = 'move';
    });
    list.addEventListener('dragover', function (e) {
      if (!dragging) return;
      e.preventDefault();
      var over = e.target.closest('[data-id]');
      if (!over || over === dragging || over.parentNode !== list) return;
      var r = over.getBoundingClientRect();
      var after = list.classList.contains('adm-grid') ? (e.clientX - r.left) > r.width / 2 : (e.clientY - r.top) > r.height / 2;
      list.insertBefore(dragging, after ? over.nextSibling : over);
    });
    list.addEventListener('dragend', function () {
      if (!dragging) return;
      dragging.classList.remove('is-dragging');
      dragging = null;
      var order = Array.prototype.map.call(list.querySelectorAll(':scope > [data-id]'), function (x) { return x.getAttribute('data-id'); });
      var data = { ids: order.join(',') };
      if (list.hasAttribute('data-album-id')) data.album_id = list.getAttribute('data-album-id');
      AC.post(list.getAttribute('data-sort-endpoint'), data).then(function (r) {
        if (!r.ok) AC.toast(r.error || __('Không lưu được thứ tự.'), 'error');
      });
    });
  });

  // ── Duyệt ảnh khách gửi ────────────────────────────────────
  // R3-13: sau Duyệt/Từ chối, số trên tab, nút "Duyệt tất cả" và huy hiệu menu giảm theo; thẻ đổi thành
  // "Đã duyệt / Đã từ chối · Hoàn tác" (Hoàn tác = về pending, số tăng lại). Hết ảnh chờ thì ẩn "Duyệt tất cả".
  var modCounts = function (delta) {
    var left = document.querySelectorAll('.mod-item:not(.done)').length;
    var tab = document.querySelector('[data-mod-tab]');
    if (tab) tab.textContent = left ? ' (' + left + ')' : '';
    var n = document.querySelector('[data-mod-left]');
    if (n) n.textContent = left;
    var bar = document.querySelector('[data-mod-bar]');
    if (bar) bar.hidden = !left;
    var empty = document.querySelector('[data-mod-empty]');
    if (empty) empty.hidden = !!left;
    var badge = document.querySelector('.adm-nav a[href$="admin/albums"] .badge');
    if (badge && delta) {
      var v = Math.max(0, (parseInt(badge.textContent, 10) || 0) + delta);
      badge.textContent = v;
      badge.hidden = !v;
    }
  };
  var modSet = function (els, status) {
    if (!els.length) return;
    AC.post(url('admin/photos/status'), { ids: els.map(function (x) { return x.getAttribute('data-id'); }), status: status }).then(function (r) {
      if (!r.ok) return AC.toast(r.error, 'error');
      var delta = 0;
      els.forEach(function (x) {
        var act = x.querySelector('.mod-actions'), st = x.querySelector('.mod-state');
        if (status === 'pending') {
          if (x.classList.contains('done')) delta++;
          x.classList.remove('done');
          if (st) st.remove();
          if (act) act.hidden = false;
          return;
        }
        if (!x.classList.contains('done')) delta--;
        x.classList.add('done');
        if (act) act.hidden = true;
        if (!st) {
          st = document.createElement('div');
          st.className = 'mod-state';
          st.innerHTML = '<span></span><button type="button" class="btn btn-ghost btn-sm" data-mod-undo>' + __('Hoàn tác') + '</button>';
          x.appendChild(st);
        }
        st.firstChild.textContent = status === 'approved' ? '✓ ' + __('Đã duyệt') : '✕ ' + __('Đã từ chối');
      });
      modCounts(delta);
      AC.toast(status === 'pending' ? __('Đã đưa {n} ảnh về chờ duyệt.', { n: r.updated })
        : (status === 'approved' ? __('Đã duyệt {n} ảnh.', { n: r.updated }) : __('Đã từ chối {n} ảnh.', { n: r.updated })));
      if (status === 'pending' && els[0]) { var b0 = els[0].querySelector('[data-mod]'); if (b0) b0.focus(); }
      else if (els.length === 1) { var u = els[0].querySelector('[data-mod-undo]'); if (u) u.focus(); }
    });
  };
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-mod]');
    if (b) return modSet([b.closest('.mod-item')], b.getAttribute('data-mod'));
    var undo = e.target.closest('[data-mod-undo]');
    if (undo) return modSet([undo.closest('.mod-item')], 'pending');
    var all = e.target.closest('[data-mod-all]');
    if (all) {
      var st = all.getAttribute('data-mod-all');
      if (st === 'rejected' && !window.confirm(__('Từ chối tất cả ảnh đang chờ?'))) return;
      modSet(Array.prototype.slice.call(document.querySelectorAll('.mod-item:not(.done)')), st);
    }
  });

  // ── Mã QR (qrcode-generator, MIT, tự host) ─────────────────
  var qrCanvas = function (text, cell) {
    if (typeof window.qrcode !== 'function' || !text) return null;
    var qr = window.qrcode(0, 'M');
    qr.addData(text, 'Byte');
    qr.make();
    cell = cell || 12;
    var n = qr.getModuleCount(), margin = 2, size = (n + margin * 2) * cell;
    var c = document.createElement('canvas');
    c.width = c.height = size;
    var ctx = c.getContext('2d');
    ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, size, size);
    ctx.fillStyle = '#231a1a';
    for (var r = 0; r < n; r++) for (var col = 0; col < n; col++) {
      if (qr.isDark(r, col)) ctx.fillRect((col + margin) * cell, (r + margin) * cell, cell, cell);
    }
    return c;
  };
  var drawQr = function (box) {
    box.innerHTML = '';
    var c = qrCanvas(box.getAttribute('data-qr'));
    if (c) box.appendChild(c);
  };
  document.querySelectorAll('[data-qr]').forEach(drawQr);
  var downloadCanvas = function (c, name) {
    var a = document.createElement('a');
    a.download = 'qr-' + name + '.png';
    a.href = c.toDataURL('image/png');
    a.click();
  };
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-qr-download]');
    if (b) {
      var c = b.closest('.share-card, .site-card').querySelector('canvas');
      if (c) downloadCanvas(c, b.getAttribute('data-qr-download'));
      return;
    }
    var m = e.target.closest('[data-qr-modal]');
    if (m) {
      var link = m.getAttribute('data-qr-modal');
      var box = document.createElement('div');
      box.className = 'qr-modal';
      // R3-17: hộp thoại đúng chuẩn — role=dialog, focus vào nút Đóng, Tab vòng trong hộp, Esc đóng, đóng thì focus về nút "Mã QR".
      box.innerHTML = '<div class="qr-modal-box" role="dialog" aria-modal="true" aria-labelledby="qr-modal-title"><h3 id="qr-modal-title"></h3><div class="qr"></div><p class="small muted"></p>' +
        '<div class="btn-row"><button class="btn btn-accent btn-sm" type="button" data-dl>' + __('Tải mã QR') + '</button><button class="btn btn-ghost btn-sm" type="button" data-x>' + __('Đóng') + '</button></div></div>';
      box.querySelector('h3').textContent = __('Thiệp mời: {name}', { name: m.getAttribute('data-qr-name') });
      box.querySelector('p').textContent = link;
      var c2 = qrCanvas(link);
      if (c2) {
        c2.setAttribute('role', 'img');
        c2.setAttribute('aria-label', __('Mã QR mở {link}', { link: link }));
        box.querySelector('.qr').appendChild(c2);
      }
      var old = document.querySelector('.qr-modal');
      if (old) old.remove();
      document.body.appendChild(box);
      var close = function () {
        box.remove();
        document.removeEventListener('keydown', onKey, true);
        if (document.contains(m)) m.focus();
      };
      var onKey = function (ev) {
        if (ev.key === 'Escape') { ev.preventDefault(); close(); return; }
        if (ev.key !== 'Tab') return;
        var f = box.querySelectorAll('button');
        var first = f[0], last = f[f.length - 1];
        if (!box.contains(document.activeElement)) { ev.preventDefault(); first.focus(); }
        else if (ev.shiftKey && document.activeElement === first) { ev.preventDefault(); last.focus(); }
        else if (!ev.shiftKey && document.activeElement === last) { ev.preventDefault(); first.focus(); }
      };
      document.addEventListener('keydown', onKey, true);
      box.querySelector('[data-x]').focus();
      box.addEventListener('click', function (ev) {
        if (ev.target === box || ev.target.closest('[data-x]')) return close();
        if (ev.target.closest('[data-dl]') && c2) downloadCanvas(c2, 'thiep-' + (m.getAttribute('data-qr-file') || m.getAttribute('data-qr-name').toLowerCase().replace(/\s+/g, '-')));
      });
    }
  });

  // ── Link công khai tự tạo: chờ Cloudflare cấp link rồi cập nhật chữ + QR tại chỗ ──
  var live = document.querySelector('[data-live-link]');
  if (live) {
    var liveNote = live.querySelector('[data-live-note]');
    var apply = function (u) {
      var a = live.querySelector('[data-live-url]');
      a.href = u; a.textContent = u;
      var inp = live.querySelector('[data-copy-src]');
      if (inp) inp.value = u;
      var q = live.querySelector('[data-qr]');
      q.setAttribute('data-qr', u); drawQr(q);
      live.setAttribute('data-public', '1');
      document.querySelectorAll('[data-live-share]').forEach(function (b) { b.setAttribute('data-share', u); });
      if (liveNote) {
        liveNote.className = 'small muted';
        liveNote.innerHTML = '✓ ' + __('Link đã sẵn sàng — bấm "Gửi cho khách" hoặc tải mã QR. Đây là link tạm, sẽ đổi nếu máy khởi động lại; muốn link cố định hãy {a}chọn link .jagame.vn{/a}.', { a: '<a href="' + url('admin/share') + '">', '/a': '</a>' });
      }
    };
    var tries = 0;
    var pollLink = function () {
      fetch(url('admin/domain/status'), { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
        if (r.ok && r.is_public) { apply(r.public_url); AC.toast(__('Đã có link cho khách ✓')); return; }
        if (r.mode !== 'off' && ++tries < 40) setTimeout(pollLink, 3000);
        else if (liveNote && r.mode !== 'off') liveNote.textContent = __('Chưa tạo được link — kiểm tra Internet của máy rồi tải lại trang.');
      }).catch(function () { if (++tries < 40) setTimeout(pollLink, 5000); });
    };
    if (live.getAttribute('data-public') !== '1' && live.getAttribute('data-mode') !== 'off') pollLink();
    var onBtn = live.querySelector('[data-live-on]');
    if (onBtn) onBtn.addEventListener('click', function () {
      onBtn.disabled = true;
      AC.post(url('admin/domain/quick'), {}).then(function (r) {
        if (!r.ok) { onBtn.disabled = false; return AC.toast(r.error || __('Không bật được.'), 'error'); }
        liveNote.textContent = '⏳ ' + __('Đang tạo link cho khách ở xa…');
        live.setAttribute('data-mode', 'quick');
        pollLink();
      });
    });
  }

  // ── Link cố định xxxx.jagame.vn + yêu cầu tên miền riêng ────
  var dom = document.querySelector('[data-domain]');
  if (dom) {
    var stateTag = dom.querySelector('[data-domain-state]');
    var waitBox = dom.querySelector('[data-domain-wait]'), errBox = dom.querySelector('[data-domain-err]');
    var waitStart = Date.now();
    // Cloud không tới được: sau 90 giây, hoặc khi máy chủ báo lỗi, thì thôi "Đang tạo…" mà hiện lỗi + nút Thử lại.
    var showErr = function (msg) {
      if (!errBox) return;
      waitBox.hidden = true;
      errBox.hidden = false;
      var m = errBox.querySelector('[data-domain-err-msg]');
      if (m && msg) m.textContent = msg;
    };
    if (errBox) errBox.querySelector('[data-domain-retry]').addEventListener('click', function (e) {
      var b = e.currentTarget;
      b.disabled = true; b.textContent = __('Đang thử lại…');
      AC.post(url('admin/domain/retry'), {}).then(function (r) {
        b.disabled = false; b.textContent = __('Thử lại');
        if (r.ok && r.mode === 'token' && r.hostname && !r.last_error) { location.reload(); return; }
        if (r.ok && !r.last_error) { errBox.hidden = true; waitBox.hidden = false; waitStart = Date.now(); poll(20); return; }
        showErr((r.last_error && r.last_error.message) || r.error || __('Vẫn chưa kết nối được.'));
      });
    });
    var poll = function (tries) {
      fetch(url('admin/domain/status'), { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
        if (!r.ok) return;
        // Link vừa được cấp (trang đang ở trạng thái chờ) -> tải lại để hiện link + QR.
        if (waitBox && r.mode === 'token' && r.hostname) { location.reload(); return; }
        if (waitBox && (r.last_error || Date.now() - waitStart > 90000)) {
          showErr(r.last_error ? r.last_error.message : __('Đã chờ hơn 90 giây mà chưa có phản hồi.'));
          return;
        }
        if (!stateTag) { if (tries > 0) setTimeout(function () { poll(tries - 1); }, 4000); return; }
        var ok = r.status === 'connected';
        stateTag.textContent = ok ? __('Đang hoạt động ✓') : __('Đang kết nối… (thường 10–30 giây)');
        stateTag.className = 'tag ' + (ok ? 'tag-approved' : 'tag-pending');
        if (!ok && tries > 0) setTimeout(function () { poll(tries - 1); }, 3000);
        if (!ok && tries === 0) stateTag.textContent = __('Chưa kết nối được — kiểm tra Internet của máy');
      }).catch(function () {});
    };
    poll(20);
    var refresh = dom.querySelector('[data-req-refresh]');
    if (refresh) refresh.addEventListener('click', function () {
      refresh.disabled = true;
      fetch(url('admin/domain/status?refresh=1'), { credentials: 'same-origin' }).then(function () { location.reload(); });
    });
    var form = dom.querySelector('[data-req-form]');
    if (form) {
      var subIn = form.querySelector('[data-sub-input]'), subState = form.querySelector('[data-sub-state]'), subT;
      subIn.addEventListener('input', function () {
        var v = subIn.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
        if (v !== subIn.value) subIn.value = v;
        clearTimeout(subT);
        if (v.length < 3) { subState.className = 'small'; subState.textContent = __('Tối thiểu 3 ký tự.'); return; }
        subT = setTimeout(function () {
          fetch(url('admin/domain/check?name=' + encodeURIComponent(v)), { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
            if (subIn.value !== v) return;
            subState.className = 'small ' + (r.ok && r.available ? 'sub-ok' : 'sub-bad');
            subState.textContent = !r.ok ? r.error : (r.available ? '✓ ' + __('{host} còn trống', { host: 'https://' + r.hostname }) : '✕ ' + r.message);
          });
        }, 400);
      });
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var err = form.querySelector('[data-req-err]'), btn = form.querySelector('button[type=submit]');
        err.hidden = true; btn.disabled = true;
        AC.post(url('admin/domain/request'), { subdomain: subIn.value.trim(), reason: form.elements.reason.value }).then(function (r) {
          btn.disabled = false;
          if (!r.ok) { err.textContent = r.error || __('Không gửi được yêu cầu.'); err.hidden = false; return; }
          sessionStorage.setItem('ac-toast', r.message || __('Đã gửi yêu cầu ✓'));
          location.reload();
        });
      });
    }
  }
  try { var pt = sessionStorage.getItem('ac-toast'); if (pt) { sessionStorage.removeItem('ac-toast'); AC.toast(pt); } } catch (e) { /* bỏ qua */ }

  // ── Khách mời: sao chép link / tin nhắn mời, kiểm tra đường dẫn riêng ──
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copy-text]');
    if (!b) return;
    var text = b.getAttribute('data-copy-text'), label = b.textContent;
    var ok = function () { b.textContent = __('Đã chép ✓'); setTimeout(function () { b.textContent = label; }, 1500); };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(ok, function () { window.prompt(__('Sao chép nội dung sau:'), text); });
    } else {
      var t = document.createElement('textarea');
      t.value = text; t.setAttribute('readonly', ''); t.style.position = 'fixed'; t.style.opacity = '0';
      document.body.appendChild(t); t.select();
      try { document.execCommand('copy'); ok(); } catch (err) { window.prompt(__('Sao chép nội dung sau:'), text); }
      t.remove();
    }
  });
  // "Anh Tuấn" -> "anh-tuan" (giống ascii_slug() phía máy chủ) để gợi ý đường dẫn khi đang gõ tên.
  // Dãy ≥ 6 chữ số (số điện thoại) không vào link tự sinh — giống Invite_model::unique_slug() (R2-10).
  var toSlug = function (s) {
    s = s.replace(/\+?\d[\d .\-()]*\d/g, function (m) { return (m.match(/\d/g) || []).length >= 6 ? ' ' : m; });
    return s.toLowerCase().replace(/đ/g, 'd').normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 36).replace(/-+$/, '');
  };
  var initSlugForm = function (f) {
    if (f.hasAttribute('data-slug-ready')) return;
    f.setAttribute('data-slug-ready', '1');
    var inp = f.querySelector('[data-slug-input]'), state = f.querySelector('[data-slug-state]');
    if (!inp) return;
    var id = f.getAttribute('data-id') || '0', timer;
    var orig = inp.getAttribute('data-slug-orig') || '';
    var note = f.querySelector('[data-slug-note]');
    // Link đang báo ✕ thì chặn gửi form (không để máy chủ từ chối rồi mất công nhập lại).
    var setBad = function (msg) {
      inp.setCustomValidity(msg || '');
      inp.closest('.sub-input').classList.toggle('is-bad', !!msg);
    };
    var check = function (v) {
      clearTimeout(timer);
      if (!v) { state.textContent = ''; setBad(''); return; }
      timer = setTimeout(function () {
        fetch(url('admin/guests/check_slug?slug=' + encodeURIComponent(v) + '&id=' + id), { credentials: 'same-origin' })
          .then(function (r) { return r.json(); }).then(function (r) {
            var auto = !inp.value.trim();
            if ((auto ? inp.placeholder : inp.value.trim()) !== v) return;
            if (auto) {   // để trống: máy chủ tự đánh số nếu trùng (anh-tuan-2)
              setBad('');
              state.className = 'small muted';
              state.textContent = r.available ? __('Link sẽ là: {url}', { url: r.url }) : __('Tên này đã có, link sẽ được đánh số (vd {slug}).', { slug: v + '-2' });
              return;
            }
            setBad(r.available ? '' : r.message);
            state.className = 'small ' + (r.available ? 'sub-ok' : 'sub-bad');
            state.textContent = r.available ? '✓ ' + r.url : '✕ ' + r.message;
          }).catch(function () {});
      }, 350);
    };
    inp.addEventListener('input', function () {
      var v = inp.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
      if (v !== inp.value) inp.value = v;
      if (note) note.hidden = !orig || v === orig;
      setBad('');   // đang chờ kiểm tra: không chặn nhầm
      check(v || inp.placeholder);
    });
    // Ô link nằm trong "Tùy chọn thêm" đang đóng: mở ra để trình duyệt chỉ đúng chỗ lỗi.
    inp.addEventListener('invalid', function () {
      for (var d = inp.closest('details'); d; d = d.parentElement && d.parentElement.closest('details')) d.open = true;
    });
    if (id === '0') {
      var parts = f.querySelectorAll('[data-slug-part]');
      parts.forEach(function (p) {
        p.addEventListener('input', function () {
          var sug = toSlug(Array.prototype.map.call(parts, function (x) { return x.value; }).join(' '));
          inp.placeholder = sug || 'anh-tuan';
          if (!inp.value) check(sug);
        });
      });
    }
  };
  document.querySelectorAll('[data-slug-form]').forEach(initSlugForm);

  // ── Khách mời: form "Sửa · ghi nhận thay khách · xóa" dựng KHI MỞ (R2-12), không in sẵn cho từng dòng ──
  // Nội dung chèn vào xong phát sự kiện 'ac:dom' để guests.js gắn nút chọn xưng hô + xem trước.
  var loadGuestForm = function (d) {
    var slot = d.querySelector('[data-guest-form-slot]');
    if (!slot || slot.firstElementChild || d.hasAttribute('data-loading')) return;
    d.setAttribute('data-loading', '1');
    slot.innerHTML = '<p class="small muted">' + __('Đang tải…') + '</p>';
    fetch(d.getAttribute('data-form-src'), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { if (!r.ok) throw new Error(r.status === 401 ? __('Phiên đăng nhập đã hết, hãy tải lại trang.') : __('Lỗi {code}', { code: r.status })); return r.text(); })
      .then(function (html) {
        slot.innerHTML = html;
        // Token CSRF có thể đã được làm mới trên trang (AC.refreshCsrf): form mới tải dùng token hiện tại.
        if (AC.csrfName) slot.querySelectorAll('input[name="' + AC.csrfName + '"]').forEach(function (i) { i.value = AC.csrfHash; });
        slot.querySelectorAll('[data-slug-form]').forEach(initSlugForm);
        slot.dispatchEvent(new CustomEvent('ac:dom', { bubbles: true }));
      })
      .catch(function (e) {
        slot.innerHTML = '';
        var p = document.createElement('p');
        p.className = 'err small';
        p.textContent = __('Chưa tải được form ({error}).', { error: e.message }) + ' ';
        var b = document.createElement('button');
        b.type = 'button'; b.className = 'btn btn-ghost btn-sm'; b.textContent = __('Thử lại');
        b.addEventListener('click', function (ev) { ev.preventDefault(); slot.innerHTML = ''; loadGuestForm(d); });
        p.appendChild(b);
        slot.appendChild(p);
      })
      .then(function () { d.removeAttribute('data-loading'); });
  };
  document.querySelectorAll('details.guest-edit[data-form-src]').forEach(function (d) {
    d.addEventListener('toggle', function () { if (d.open) loadGuestForm(d); });
  });

  // ── Form album: chỉ hiện ô mật khẩu khi chọn "Có mật khẩu" ──
  var pw = document.querySelector('[data-pw-field]');
  if (pw) {
    var sync = function () {
      var v = document.querySelector('input[name="visibility"]:checked');
      pw.hidden = !v || v.value !== 'password';
    };
    document.querySelectorAll('input[name="visibility"]').forEach(function (r) { r.addEventListener('change', sync); });
    sync();
  }

  // ── R4-09: ô bị máy chủ báo lỗi (aria-invalid) ──
  // Sửa ô -> ẩn dòng lỗi của ĐÚNG ô đó + bỏ aria-invalid. Trang tải lên có lỗi -> cuộn + focus ô lỗi đầu tiên.
  var clearErr = function (t) {
    if (!t || !t.matches || !t.matches('[aria-invalid="true"]') || t.hasAttribute('data-gift-bad')) return;
    var errs = [];
    (t.getAttribute('aria-describedby') || '').split(/\s+/).forEach(function (id) {
      var el = id && document.getElementById(id);
      if (el && el.classList.contains('field-err')) errs.push(el);
    });
    if (!errs.length) {
      var lb = t.closest('label');
      if (lb) errs = Array.prototype.slice.call(lb.querySelectorAll('.field-err'));
    }
    errs.forEach(function (el) { el.hidden = true; });
    t.removeAttribute('aria-invalid');
    var wrap = t.closest('.is-bad');
    if (wrap) wrap.classList.remove('is-bad');
  };
  document.addEventListener('input', function (e) { clearErr(e.target); });
  document.addEventListener('change', function (e) { clearErr(e.target); });
  var focusField = function (el) {
    if (!el) return;
    var d = el.closest('details');
    if (d && !d.open) d.open = true;
    el.scrollIntoView({ block: 'center', behavior: 'auto' });
    try { el.focus({ preventScroll: true }); } catch (x) { el.focus(); }
  };
  var errForm = document.querySelector('form[data-has-errors]');
  if (errForm && !location.hash) {
    focusField(errForm.querySelector('[aria-invalid="true"]'));
  }
  document.querySelectorAll('a[data-err-link]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var el = document.getElementById(a.getAttribute('href').slice(1));
      if (!el) return;
      e.preventDefault();
      focusField(el);
    });
  });

  // ── R5-09: form Cài đặt có thay đổi chưa lưu -> hỏi trước khi form khác trên trang (chọn/tải/xóa nhạc) gửi đi
  // hoặc trước khi rời trang. Chọn nhạc gửi form ngay (không còn onchange inline).
  var setForm = document.querySelector('form[data-settings-form]');
  if (setForm) {
    var snapForm = function () {
      var parts = [];
      Array.prototype.forEach.call(setForm.elements, function (el) {
        if (!el.name || el.name === 'csrf_token' || el.type === 'submit' || el.type === 'button') return;
        if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
        parts.push(el.name + '=' + el.value);
      });
      return parts.join('&');
    };
    var initial = null;
    var leaving = false;
    var takeSnap = function () { if (initial === null) initial = snapForm(); };
    if (document.readyState === 'complete') takeSnap(); else window.addEventListener('load', takeSnap);
    var isDirty = function () { return !leaving && initial !== null && snapForm() !== initial; };
    setForm.addEventListener('submit', function () { leaving = true; });
    // M1-OWNER-07: có thay đổi chưa lưu -> nút "Lưu cài đặt" (dính đáy) nhấp nháy nhắc bấm lưu.
    var markSetDirty = function () { setForm.classList.toggle('is-dirty', isDirty()); };
    setForm.addEventListener('input', markSetDirty);
    setForm.addEventListener('change', markSetDirty);
    window.addEventListener('beforeunload', function (e) {
      if (isDirty()) { e.preventDefault(); e.returnValue = ''; return ''; }
    });
    var askDiscard = function () {
      return !isDirty() || window.confirm(__('Bạn có thay đổi chưa lưu ở Cài đặt. Bỏ thay đổi và đổi nhạc?'));
    };
    document.querySelectorAll('form').forEach(function (f) {
      if (f === setForm) return;
      f.addEventListener('submit', function (e) {
        if (!askDiscard()) { e.preventDefault(); return; }
        leaving = true;
      });
    });
    // Gợi ý bài hát cưới: bấm tên bài -> mở chọn file -> gửi kèm suggest=<khóa> (máy chủ đặt đúng tên bài).
    var sform = document.querySelector('form[data-music-sug-form]');
    if (sform) {
      var sfile = sform.querySelector('input[type="file"]');
      document.querySelectorAll('[data-sug]').forEach(function (b) {
        b.addEventListener('click', function () {
          sform.elements.suggest.value = b.getAttribute('data-sug');
          sfile.value = '';
          sfile.click();
        });
      });
      sfile.addEventListener('change', function () {
        var f = sfile.files[0];
        if (!f) return;
        if (f.size > 20 * 1024 * 1024) {
          sfile.value = '';
          return window.alert(__('Bài "{name}" nặng {mb} MB — tối đa 20 MB.', { name: f.name, mb: (f.size / 1048576).toFixed(1) }));
        }
        if (!askDiscard()) { sfile.value = ''; return; }
        var b = document.querySelector('[data-sug="' + sform.elements.suggest.value + '"]');
        if (b) { b.disabled = true; var t = b.parentNode.querySelector('.tag-need'); if (t) t.textContent = __('Đang tải…'); }
        leaving = true;
        sform.submit();
      });
    }
    var mform = document.querySelector('form[data-music-form]');
    if (mform) {
      var checkedMusic = function () { return mform.querySelector('input[name="music"]:checked'); };
      var prev = checkedMusic();
      mform.addEventListener('change', function (e) {
        var r = e.target;
        if (r.name !== 'music' || r.type !== 'radio') return;
        if (!askDiscard()) {
          r.checked = false;
          if (prev) prev.checked = true;
          return;
        }
        prev = r;
        leaving = true;
        mform.submit();   // submit() không phát sự kiện submit -> không hỏi lần 2
      });
    }
  }

  // ── R4-16: tải trang có #g… -> nhảy thẳng tới thẻ (admin.css đặt scroll-behavior:auto); sau khi tải xong
  // mới bật lại cuộn mượt cho link trong trang.
  window.addEventListener('load', function () {
    setTimeout(function () { document.documentElement.style.scrollBehavior = 'smooth'; }, 300);
  });
})();
