/* Ảnh Cưới — SỬA TRỰC TIẾP trang cưới (chỉ nạp khi chủ nhà đăng nhập, bản nháp).
 *   [data-edit="key"]        chữ: bấm để sửa, Enter/bấm ra ngoài để lưu, Esc để hủy
 *   [data-edit-img="key"]    ảnh theo vị trí: nút "Đổi ảnh"
 *   [data-edit-date]         ngày + giờ cưới
 *   [data-events]            danh sách địa điểm: sửa từng dòng, thêm/xóa, link bản đồ
 *   [data-home-gallery]      album trang chủ: thêm/thay/xóa ảnh
 *   [data-theme-pick]        20 giao diện (10 VIP: bản cài máy bị khóa -> AC.vip), đổi ngay
 *   [data-publish]           nút chính: "Cho khách xem" (xuất bản) / "Gửi cho khách" (bảng chia sẻ)
 * Mọi thay đổi lưu ngay vào BẢN NHÁP; khách chỉ thấy sau khi bấm Xuất bản.
 */
(function () {
  'use strict';
  var AC = window.AC;
  // Song ngữ: window.__ do i18n_script() in trong <head> (theo ngôn ngữ QUẢN TRỊ khi đang sửa bản nháp).
  var __ = window.__ || function (s, v) { if (v) { for (var k in v) s = s.split('{' + k + '}').join(v[k]); } return s; };
  var url = function (p) { return AC.baseUrl + p; };
  var bar = document.querySelector('[data-editor]');
  if (!bar) return;
  document.body.classList.add('is-editing');

  var stateEl = bar.querySelector('[data-ed-state]');
  var publishBtn = bar.querySelector('[data-publish]');
  var markDirty = function () {
    bar.setAttribute('data-unpublished', '1');
    stateEl.textContent = __('Có thay đổi khách chưa thấy');
    publishBtn.textContent = __('Cho khách xem');
    publishBtn.classList.add('pulse');
    refreshChecklist();
  };
  /** "Việc cần làm x/N" theo Content_model::checklist() (cùng nguồn với Tổng quan) — cập nhật ngay, không cần F5 (R2-13). */
  function applyChecklist(list) {
    if (!Array.isArray(list)) return;
    var done = 0;
    list.forEach(function (it) {
      if (it.done) done++;
      var li = bar.querySelector('[data-step="' + it.key + '"]');
      if (li) li.classList.toggle('ok', !!it.done);
    });
    var cnt = bar.querySelector('[data-ed-steps-toggle] b');
    if (cnt) cnt.textContent = done + '/' + list.length;
  }
  var ckTimer = null;
  function refreshChecklist() {
    clearTimeout(ckTimer);
    ckTimer = setTimeout(function () {
      AC.post(url('admin/content/checklist'), {}).then(function (r) { if (r && r.ok) applyChecklist(r.checklist); });
    }, 250);
  }
  // Hết phiên đăng nhập: thông báo KHÔNG tự tắt + nút đăng nhập lại ở tab mới (chữ đang sửa trên trang vẫn còn).
  // CHỈ lỗi hết phiên đăng nhập (401 của Admin_Controller). Lỗi token CSRF (r.code === 'csrf', AC.post đã tự làm mới
  // token và gửi lại 1 lần) KHÔNG phải hết phiên: chỉ hiện hộp "Chưa lưu" + lời khuyên của AC.post (R2-08c).
  var SESSION_RE = /Phiên đăng nhập đã hết/;
  var isSession = function (r) {
    return !!r && r.code !== 'csrf' && (r.status === 401 || SESSION_RE.test(r.error || '') || (r.error || '') === __('Phiên đăng nhập đã hết, hãy đăng nhập lại.'));
  };
  var sessionNotice = function () {
    if (document.querySelector('.ed-session')) return;
    var d = document.createElement('div');
    d.className = 'ed-session';
    d.setAttribute('role', 'alert');
    d.innerHTML = '<span>' + __('Phiên đăng nhập đã hết — thay đổi vừa rồi <b>chưa được lưu</b>. Đăng nhập lại ở tab mới, rồi quay lại đây bấm lưu tiếp.') + '</span>' +
      '<a class="btn btn-accent btn-sm" target="_blank" rel="noopener" href="' + url('admin/login?next=admin') + '">' + __('Đăng nhập lại') + '</a>' +
      '<button type="button" class="btn btn-ghost btn-sm" data-x>' + __('Đã đăng nhập xong') + '</button>';
    d.querySelector('[data-x]').addEventListener('click', function () { d.remove(); });
    document.body.appendChild(d);
  };
  /** Thông báo không tự tắt (có nút đóng) — cho kết quả chủ nhà cần đọc kỹ. */
  var persistNotice = function (msg) {
    var old = document.querySelector('.ed-persist');
    if (old) old.remove();
    var d = document.createElement('div');
    d.className = 'ed-persist';   // không dùng .flash: toast sau (AC.toast) sẽ xóa mất
    d.setAttribute('role', 'alert');
    d.textContent = msg;
    var x = document.createElement('button');
    x.type = 'button'; x.className = 'flash-x'; x.setAttribute('aria-label', __('Đóng')); x.textContent = '×';
    x.addEventListener('click', function () { d.remove(); });
    d.appendChild(x);
    document.body.appendChild(d);
  };
  /** Chỉ 1 popup (.ed-pop) mở một lúc: mở popup nào (✨, ♫, Ngày, Căn chỉnh) thì đóng mọi popup khác (R2-16). */
  var closePops = function (except) {
    document.querySelectorAll('.ed-pop').forEach(function (p) { if (p !== except) p.remove(); });
    if (typeof preview !== 'undefined' && preview) preview.pause();
  };
  var fail = function (r) {
    var msg = (r && r.error) || __('Không lưu được.');
    if (isSession(r)) sessionNotice();
    AC.toast(msg, 'error');
  };

  // Đang tải file lên: rời trang thì hỏi lại (không mất ảnh/nhạc đang gửi dở).
  // Cùng cơ chế cho mọi thay đổi CHƯA LƯU được: ô chữ lưu lỗi, địa điểm lưu lỗi (R2-09).
  var uploading = 0;
  var unsaved = function () {
    return uploading > 0 || !!document.querySelector('.ed-ev-unsaved, [data-edit].ed-error');
  };
  window.addEventListener('beforeunload', function (e) {
    if (unsaved()) { e.preventDefault(); e.returnValue = ''; return ''; }
  });

  /**
   * Ảnh trang trí (ảnh đầu trang, cô dâu, chú rể…): thu nhỏ ngay trên máy về cạnh dài 2560px trước khi gửi.
   * Ảnh máy ảnh 20–50 MB thành ~1–2 MB: gửi nhanh (kể cả qua 4G), máy chủ xử lý nhẹ; vẫn dư nét cho màn 4K.
   * Trình duyệt tự áp chiều xoay EXIF khi vẽ. Lỗi / không thu nhỏ được thì gửi nguyên file.
   */
  var SLOT_MAX_PX = 2560;
  function shrinkImage(file) {
    return new Promise(function (resolve) {
      if (!/^image\/(jpeg|png|webp)$/.test(file.type) || !window.URL || !document.createElement('canvas').toBlob) return resolve(file);
      var src = URL.createObjectURL(file), im = new Image();
      im.onload = function () {
        var w = im.naturalWidth, h = im.naturalHeight, r = Math.min(1, SLOT_MAX_PX / Math.max(w, h));
        if (r === 1 && file.size < 3 * 1048576) { URL.revokeObjectURL(src); return resolve(file); }
        var c = document.createElement('canvas');
        c.width = Math.round(w * r); c.height = Math.round(h * r);
        var ctx = c.getContext('2d');
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(im, 0, 0, c.width, c.height);
        URL.revokeObjectURL(src);
        c.toBlob(function (blob) {
          if (!blob || blob.size >= file.size) return resolve(file);
          resolve(new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }));
        }, 'image/jpeg', 0.9);
      };
      im.onerror = function () { URL.revokeObjectURL(src); resolve(file); };
      im.src = src;
    });
  }

  /** Gửi 1 file (XHR để có tiến độ). fields.__shrink = thu nhỏ ảnh trước khi gửi (ảnh trang trí). */
  function upload(endpoint, fields, file, onProgress, retried) {
    if (fields.__shrink && !retried) {
      var rest = {};
      Object.keys(fields).forEach(function (k) { if (k !== '__shrink') rest[k] = fields[k]; });
      return shrinkImage(file).then(function (f) { return upload(endpoint, rest, f, onProgress, false); });
    }
    if (!retried) uploading++;
    return new Promise(function (resolve) {
      var fd = new FormData();
      Object.keys(fields).forEach(function (k) { fd.append(k, fields[k]); });
      fd.append(fields.__field || 'photo', file, file.name);
      fd.append(AC.csrfName, AC.csrfHash);
      var xhr = new XMLHttpRequest();
      xhr.open('POST', endpoint);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.upload.onprogress = function (e) { if (e.lengthComputable && onProgress) onProgress(e.loaded / e.total); };
      xhr.onload = function () {
        var r = null;
        try { r = JSON.parse(xhr.responseText); } catch (e) { /* không phải JSON */ }
        // 403 không phải JSON = token CSRF hết hạn/mất cookie (giống AC.post): lấy token mới, gửi lại đúng 1 lần.
        if (xhr.status === 403 && (!r || r.code === 'csrf')) {
          r = { ok: false, code: 'csrf', status: 403, error: __('Trang đã mở quá lâu. Hãy tải lại trang rồi thử lại.') };
          if (!retried && AC.refreshCsrf) {
            return resolve(AC.refreshCsrf().then(function (ok) { return ok ? upload(endpoint, fields, file, onProgress, true) : r; }));
          }
        }
        resolve(r || { ok: false, status: xhr.status, error: xhr.status === 413 ? __('File quá lớn so với máy chủ.') : __('Lỗi máy chủ {code}', { code: xhr.status }) });
      };
      xhr.onerror = function () { resolve({ ok: false, error: __('Mất kết nối.') }); };
      xhr.send(fd);
    }).then(function (r) { if (!retried) uploading--; return r; });
  }

  function pickFile(accept, multiple) {
    return new Promise(function (resolve) {
      var inp = document.createElement('input');
      inp.type = 'file'; inp.accept = accept; inp.multiple = !!multiple;
      inp.style.display = 'none';
      // Cú click giả lên ô chọn file không được lan ra document (sẽ bị hiểu là "bấm ra ngoài" và đóng popup đang mở).
      inp.addEventListener('click', function (e) { e.stopPropagation(); });
      document.body.appendChild(inp);
      inp.addEventListener('change', function () { resolve(Array.prototype.slice.call(inp.files)); inp.remove(); });
      inp.click();
    });
  }

  function busy(el, on, pct) {
    var b = el.querySelector(':scope > .ed-busy');
    el.classList.toggle('is-busy', !!on);   // đang tải: ẩn nút 📷/✥/🗑 (không để "0%" đè lên nút — R2-19)
    if (!on) { if (b) b.remove(); return; }
    if (!b) { b = document.createElement('div'); b.className = 'ed-busy'; b.innerHTML = '<span></span>'; el.appendChild(b); }
    b.firstChild.textContent = pct == null ? __('Đang lưu…') : Math.round(pct * 100) + '%';
  }

  // ── 1. Chữ ────────────────────────────────────────────────
  /** Chữ THẬT trong ô (theo DOM, không qua CSS): KHÔNG dùng innerText vì nó trả chữ đã qua `text-transform`
   *  (giao diện in hoa -> lưu nhầm IN HOA vĩnh viễn, R2-04). <br> / khối <div> (Firefox) = xuống dòng. */
  var rawText = function (el) {
    var out = '';
    var walk = function (n) {
      for (var c = n.firstChild; c; c = c.nextSibling) {
        if (c.nodeType === 3) out += c.nodeValue;
        else if (c.nodeName === 'BR') out += '\n';
        else if (c.nodeType === 1) {
          if (/^(DIV|P|LI)$/.test(c.nodeName) && out && out.slice(-1) !== '\n') out += '\n';
          walk(c);
        }
      }
    };
    walk(el);
    out = out.replace(/\u00a0/g, ' ').replace(/\r/g, '');
    // Ô 1 dòng: mọi khoảng trắng (kể cả \n) gộp 1 dấu cách như lúc hiển thị; ô nhiều dòng giữ \n (white-space: pre-line).
    return el.hasAttribute('data-multiline') ? out.replace(/[ \t\f\v]+/g, ' ').replace(/ *\n */g, '\n') : out.replace(/\s+/g, ' ');
  };
  var textOf = function (el) { return rawText(el).trim(); };
  /** Hộp nhỏ ngay dưới 1 ô chữ (đếm ký tự / lỗi + Thử lại). */
  var tipFor = function (el, cls) {
    var t = document.createElement('div');
    t.className = 'ed-tip ' + (cls || '');
    document.body.appendChild(t);
    t.place = function () {
      var r = el.getBoundingClientRect();
      t.style.top = (r.bottom + window.scrollY + 6) + 'px';
      t.style.left = Math.max(8, Math.min(r.left + window.scrollX, window.scrollX + innerWidth - t.offsetWidth - 8)) + 'px';
    };
    return t;
  };
  var clearRetry = function (el) {
    if (el._retry) { el._retry.remove(); el._retry = null; }
    el.classList.remove('ed-error');
  };

  /** Lưu 1 ô chữ. Lỗi -> GIỮ chữ vừa gõ, viền đỏ, nút "Thử lại" / "Sửa tiếp" / "Bỏ thay đổi" (không mất chữ). */
  function saveText(el, value) {
    var key = el.getAttribute('data-edit'), original = el.getAttribute('data-ed-orig') || '';
    clearRetry(el);
    el.classList.add('ed-saving');
    AC.post(url('admin/content/text'), { key: key, value: value }).then(function (r) {
      el.classList.remove('ed-saving');
      if (!r.ok) {
        el.innerText = value;
        el.classList.add('ed-error');
        var t = el._retry = tipFor(el, 'is-bad');
        t.setAttribute('role', 'alert');
        t.innerHTML = '<span></span><button type="button" class="btn btn-accent btn-sm" data-retry>' + __('Thử lại') + '</button>' +
          '<button type="button" class="btn btn-ghost btn-sm" data-edit-again>' + __('Sửa tiếp') + '</button>' +
          '<button type="button" class="btn btn-ghost btn-sm" data-undo>' + __('Bỏ thay đổi') + '</button>';
        t.querySelector('span').textContent = __('Chưa lưu: {error}', { error: r.error || __('lỗi không rõ') }) + ' ';
        t.place();
        t.querySelector('[data-retry]').addEventListener('click', function () { saveText(el, textOf(el)); });
        t.querySelector('[data-edit-again]').addEventListener('click', function () { clearRetry(el); startEdit(el, true); });
        t.querySelector('[data-undo]').addEventListener('click', function () {
          clearRetry(el); el.innerText = original; el.removeAttribute('data-ed-orig');
        });
        return fail(r);
      }
      el.removeAttribute('data-ed-orig');
      // Cùng khóa có thể xuất hiện nhiều chỗ (vd tên ở đầu trang và chân trang).
      document.querySelectorAll('[data-edit="' + key + '"]').forEach(function (x) { x.innerText = r.value; });
      markDirty();
      AC.toast(value === '' ? __('Để trống sẽ dùng câu mặc định — đã đặt lại câu mặc định ✓') : __('Đã lưu ✓'));
    });
  }

  function startEdit(el, keepCaret) {
    if (el.isContentEditable || el.classList.contains('ed-saving')) return;
    clearRetry(el);
    // Bản gốc = chữ trước lần sửa ĐẦU (lưu lỗi rồi sửa tiếp vẫn "Bỏ thay đổi" về đúng chữ cũ).
    if (!el.hasAttribute('data-ed-orig')) el.setAttribute('data-ed-orig', rawText(el));
    var original = el.getAttribute('data-ed-orig');
    var multi = el.hasAttribute('data-multiline');
    var max = parseInt(el.getAttribute('data-max'), 10) || 0;
    try { el.contentEditable = 'plaintext-only'; } catch (e) { el.contentEditable = 'true'; }
    if (el.contentEditable !== 'plaintext-only') el.contentEditable = 'true';
    el.classList.add('ed-active');
    var unbump = bumpFont(el);
    el.focus();
    var range = document.createRange();
    range.selectNodeContents(el);
    // Ô nhiều dòng (tiểu sử, trích dẫn): đặt con trỏ CUỐI chữ, không bôi chọn cả đoạn (điện thoại: phím đầu xóa sạch — R2-20).
    // Điện thoại (pointer: coarse): MỌI ô đều đặt con trỏ cuối — bôi chọn cả tên rồi gõ 1 phím là mất cả tên (audit designer 03/10).
    if (keepCaret || multi || coarse.matches) range.collapse(false);
    var sel = window.getSelection(); sel.removeAllRanges(); sel.addRange(range);
    keepAboveBar(el);

    // Còn ≤ 20 ký tự thì báo "còn N ký tự"; quá giới hạn thì báo đỏ và KHÔNG gửi.
    var tip = tipFor(el, 'ed-count');
    tip.hidden = true;
    var counter = function () {
      if (!max) return true;
      var left = max - textOf(el).length;
      tip.hidden = left > 20;
      tip.classList.toggle('is-bad', left < 0);
      tip.textContent = left < 0 ? __('Quá {n} ký tự — xóa bớt để lưu (tối đa {max})', { n: -left, max: max }) : __('còn {n} ký tự', { n: left });
      if (!tip.hidden) { tip.place(); keepAboveBar(tip); }
      return left >= 0;
    };
    counter();

    // Audit P0 (b): màn cảm ứng — nút ✓ Lưu / ✕ Hủy nổi ngay trên ô đang sửa, không phải "chạm ra ngoài" để lưu.
    // pointerdown đặt ý định trước khi ô mất focus (blur chạy trước click): Hủy -> trả chữ cũ, Lưu -> lưu như bấm ra ngoài.
    var intent = null, tools = null;
    var placeTools = function () { if (tools) tools.place(); };
    if (coarse.matches) {
      tools = document.createElement('div');
      tools.className = 'ed-edit-tools';
      tools.setAttribute('role', 'group');
      tools.innerHTML = '<button type="button" data-ed-cancel>✕ ' + __('Hủy') + '</button><button type="button" data-ed-ok>✓ ' + __('Lưu') + '</button>';
      document.body.appendChild(tools);
      tools.place = function () {
        var r = el.getBoundingClientRect(), h = tools.offsetHeight, w = tools.offsetWidth;
        var above = r.top - h - 8 >= 8;
        tools.style.top = (window.scrollY + (above ? r.top - h - 8 : r.bottom + 8)) + 'px';
        tools.style.left = Math.max(8, Math.min(r.right + window.scrollX - w, window.scrollX + innerWidth - w - 8)) + 'px';
        if (!above && !tip.hidden) tip.style.top = (window.scrollY + r.bottom + h + 14) + 'px';
      };
      tools.place();
      tools.addEventListener('pointerdown', function (e) { intent = e.target.closest('[data-ed-cancel]') ? 'cancel' : 'save'; e.preventDefault(); });
      tools.addEventListener('click', function (e) {
        var cancel = !!e.target.closest('[data-ed-cancel]');
        if (el.isContentEditable) { intent = null; done(!cancel); }
      });
    }
    var cleanup = function () {
      el.removeEventListener('keydown', onKey);
      el.removeEventListener('beforeinput', onBeforeInput);
      el.removeEventListener('blur', onBlur);
      el.removeEventListener('input', counter);
      el.removeEventListener('input', placeTools);
      el.contentEditable = 'false';
      el.removeAttribute('contenteditable');
      el.classList.remove('ed-active');
      unbump();
      tip.remove();
      if (tools) { tools.remove(); tools = null; }
    };
    var done = function (save) {
      if (!el.isContentEditable) return;   // đã kết thúc (blur + click nút nổi cùng gọi)
      var value = textOf(el);
      if (save && !counter()) {   // quá dài: giữ nguyên chế độ sửa + chữ đang gõ
        el.classList.add('ed-error');
        if (document.activeElement !== el) el.focus();   // S2-FREE-01: Enter làm blur -> trả focus để Esc/bấm ngoài vẫn hủy được
        // Mẹo "Quá N ký tự…" đã hiện ngay dưới ô: không chồng thêm toast lên (S1-FREE-07); chỉ toast khi mẹo bị ẩn.
        if (tip.hidden) AC.toast(__('Tối đa {max} ký tự — hãy xóa bớt rồi bấm ra ngoài để lưu.', { max: max }), 'error');
        return;
      }
      cleanup();
      if (!save || value === original.trim()) {
        el.innerText = original;
        el.removeAttribute('data-ed-orig');
        el.classList.remove('ed-error');
        return;
      }
      saveText(el, value);
    };
    var onKey = function (e) {
      if (e.key === 'Escape') { e.preventDefault(); done(false); }
      else if (e.key === 'Enter' && (!multi || e.ctrlKey || e.metaKey)) { e.preventDefault(); el.blur(); }
    };
    // Ô 1 dòng: bàn phím ảo (Android IME keyCode 229, iOS "Xong"/"Return") có thể không phát keydown Enter chuẩn mà chỉ
    // beforeinput insertParagraph/insertLineBreak -> chặn ở đây, không bao giờ chèn <div>/<br> (contentEditable=true dự phòng).
    var onBeforeInput = function (e) {
      if (!multi && (e.inputType === 'insertParagraph' || e.inputType === 'insertLineBreak')) { e.preventDefault(); el.blur(); }
    };
    var onBlur = function () { var save = intent !== 'cancel'; intent = null; done(save); };
    el.addEventListener('keydown', onKey);
    el.addEventListener('beforeinput', onBeforeInput);
    el.addEventListener('blur', onBlur);
    el.addEventListener('input', counter);
    el.addEventListener('input', placeTools);
  }
  // M1-OWNER-01: iOS Safari KHÔNG phát `click` tới listener ở document khi chạm vào span/p "không bấm được"
  // -> gắn listener trực tiếp lên từng ô (kèm cursor:pointer trong editor.css @media (hover:none)).
  var onEditTap = function (e) {
    var el = e.currentTarget;
    if (el.closest('.ed-bar')) return;
    e.preventDefault();
    startEdit(el);
  };
  document.querySelectorAll('[data-edit]').forEach(function (el) {
    el.title = __('Bấm để sửa');
    el.addEventListener('click', onEditTap);
  });
  /** Ô nhập/contenteditable < 16px: iOS tự phóng to trang khi focus (M1-OWNER-03) -> nâng tạm lên 16px lúc đang sửa. */
  var coarse = window.matchMedia ? window.matchMedia('(pointer: coarse)') : { matches: false };
  var bumpFont = function (el) {
    if (!coarse.matches || parseFloat(getComputedStyle(el).fontSize) >= 16) return function () {};
    var old = el.style.fontSize;
    el.style.fontSize = '16px';
    return function () { el.style.fontSize = old; };
  };

  // ── 2. Ảnh theo vị trí: đổi ảnh + căn chỉnh (kéo để di chuyển, phóng to/thu nhỏ) ──
  var posOf = function (slot) {
    var v = (slot.getAttribute('data-pos') || '50 50 1').split(' ').map(Number);
    return { x: isNaN(v[0]) ? 50 : v[0], y: isNaN(v[1]) ? 50 : v[1], z: isNaN(v[2]) ? 1 : v[2] };
  };
  var applyPos = function (img, p) {
    img.style.objectPosition = p.x + '% ' + p.y + '%';
    img.style.transform = p.z > 1.001 ? 'scale(' + p.z + ')' : '';
    img.style.transformOrigin = p.x + '% ' + p.y + '%';
  };

  /** Cửa sổ căn chỉnh: khung đúng tỉ lệ ô ảnh trên trang, kéo ảnh để chọn phần hiện ra. */
  function adjust(slot) {
    var img = slot.querySelector('img');
    if (!img) return;
    closePops();
    var rect = slot.getBoundingClientRect();
    // Ô ảnh đang ẩn ở giao diện này (0×0) -> khung 3:4, không ra khung rộng 0px.
    var ratio = (rect.width > 0 && rect.height > 0) ? rect.width / rect.height : 3 / 4;
    // Khung phải lọt màn hình cùng tiêu đề, thanh phóng và các nút (~250px).
    var maxW = Math.min(innerWidth - 72, 680), maxH = Math.max(160, innerHeight - 260);
    var w = maxW, h = w / ratio;
    if (h > maxH) { h = maxH; w = h * ratio; }
    var p = posOf(slot), start = { x: p.x, y: p.y, z: p.z };
    var m = document.createElement('div');
    m.className = 'ed-modal ed-crop';
    // M1-OWNER-12: chú thích theo kiểu thiết bị (màn cảm ứng: chụm 2 ngón, không nói "lăn chuột").
    m.innerHTML = '<div class="ed-modal-box ed-crop-box"><h3>' + __('Căn chỉnh ảnh') + '</h3>' +
      '<p class="small muted">' + (coarse.matches ? __('Kéo ảnh để chọn phần muốn hiện · chụm 2 ngón hoặc kéo thanh trượt để phóng to')
        : __('Kéo ảnh để chọn phần muốn hiện · kéo thanh trượt hoặc lăn chuột để phóng to')) + '</p>' +
      '<div class="ed-crop-frame' + (slot.classList.contains('oval') ? ' is-oval' : '') + '"><img alt="" draggable="false"></div>' +
      '<label class="ed-zoom">🔍<input type="range" min="1" max="3" step="0.01" aria-label="' + __('Phóng to') + '"></label>' +
      '<div class="btn-row ed-btns"><button type="button" class="btn btn-ghost btn-sm ed-act-left" data-reset>' + __('Đặt lại') + '</button>' +
      '<button type="button" class="btn btn-ghost btn-sm" data-x>' + __('Hủy') + '</button>' +
      '<button type="button" class="btn btn-accent btn-sm" data-ok>' + __('Lưu') + '</button></div></div>';
    document.body.appendChild(m);
    var frame = m.querySelector('.ed-crop-frame'), pic = frame.querySelector('img'), zoom = m.querySelector('input[type=range]');
    frame.style.width = w + 'px'; frame.style.height = h + 'px';
    // Khung vòm / tròn của giao diện đang dùng (bo góc theo %) -> khung căn chỉnh cũng đúng hình đó.
    var br = getComputedStyle(slot).borderRadius;
    if (br.indexOf('%') > -1) frame.style.borderRadius = br;
    pic.src = img.currentSrc || img.src;
    zoom.value = p.z;
    var paint = function () { applyPos(pic, p); applyPos(img, p); };
    paint();

    // M1-OWNER-06: theo dõi TỪNG ngón (pointerId). 1 ngón = kéo; 2 ngón = chụm để phóng (khoảng cách 2 ngón / lúc đặt).
    // Ngón thứ 2 đặt xuống không ghi đè gốc kéo của ngón 1; nhấc 1 ngón thì ngón còn lại kéo tiếp từ vị trí hiện tại.
    var ptrs = {}, drag = null, pinch = null;
    var ids = function () { return Object.keys(ptrs); };
    var setZoom = function (z) { p.z = Math.max(1, Math.min(3, z)); zoom.value = p.z; };
    var dist = function (a, b) { return Math.hypot(a.x - b.x, a.y - b.y); };
    var startDrag = function (pt) { drag = { x: pt.x, y: pt.y, px: p.x, py: p.y }; };
    frame.addEventListener('pointerdown', function (e) {
      e.preventDefault();
      ptrs[e.pointerId] = { x: e.clientX, y: e.clientY };
      try { frame.setPointerCapture(e.pointerId); } catch (err) { /* pointer giả (kiểm thử) */ }
      frame.classList.add('dragging');
      var k = ids();
      if (k.length === 1) { pinch = null; startDrag(ptrs[e.pointerId]); }
      else if (k.length === 2) { drag = null; pinch = { d: dist(ptrs[k[0]], ptrs[k[1]]), z: p.z }; }
    });
    frame.addEventListener('pointermove', function (e) {
      if (!ptrs[e.pointerId]) return;
      ptrs[e.pointerId] = { x: e.clientX, y: e.clientY };
      var k = ids();
      if (pinch && k.length >= 2) {
        setZoom(pinch.z * dist(ptrs[k[0]], ptrs[k[1]]) / Math.max(1, pinch.d));
        paint();
        return;
      }
      if (!drag) return;
      // Kéo ảnh sang phải = xem phần bên trái của ảnh -> giảm x. Chia cho zoom để kéo "dính tay".
      var r = 100 / p.z;
      p.x = Math.max(0, Math.min(100, drag.px - (e.clientX - drag.x) / w * r));
      p.y = Math.max(0, Math.min(100, drag.py - (e.clientY - drag.y) / h * r));
      paint();
    });
    var end = function (e) {
      delete ptrs[e.pointerId];
      var k = ids();
      pinch = null;
      if (k.length === 1) startDrag(ptrs[k[0]]);   // còn 1 ngón: kéo tiếp từ chỗ đang đứng, không nhảy
      else { drag = null; if (!k.length) frame.classList.remove('dragging'); }
    };
    frame.addEventListener('pointerup', end);
    frame.addEventListener('pointercancel', end);
    zoom.addEventListener('input', function () { p.z = parseFloat(zoom.value); paint(); });
    frame.addEventListener('wheel', function (e) {
      e.preventDefault();
      setZoom(p.z - e.deltaY * 0.002);
      paint();
    }, { passive: false });

    // Hệ số kéo 1,0: ảnh đi đúng theo ngón tay/chuột (trước đây 1,6 -> quá nhạy). Esc = Hủy.
    var esc = function (e) { if (e.key === 'Escape') { e.preventDefault(); p = start; paint(); close(); } };
    document.addEventListener('keydown', esc);
    var close = function () { m.remove(); document.removeEventListener('keydown', esc); };
    m.querySelector('[data-reset]').addEventListener('click', function () { p = { x: 50, y: 50, z: 1 }; zoom.value = 1; paint(); });
    m.querySelector('[data-x]').addEventListener('click', function () { p = start; paint(); close(); });
    m.addEventListener('click', function (e) { if (e.target === m) { p = start; paint(); close(); } });
    m.querySelector('[data-ok]').addEventListener('click', function () {
      AC.post(url('admin/content/position'), { key: slot.getAttribute('data-edit-img'), x: p.x.toFixed(1), y: p.y.toFixed(1), z: p.z.toFixed(2) })
        .then(function (r) {
          if (!r.ok) return fail(r);
          slot.setAttribute('data-pos', p.x.toFixed(1) + ' ' + p.y.toFixed(1) + ' ' + p.z.toFixed(2));
          markDirty();
          AC.toast(__('Đã căn chỉnh ảnh ✓'));
          close();
        });
    });
  }

  document.querySelectorAll('[data-edit-img]').forEach(function (slot) {
    var tools = document.createElement('div');
    tools.className = 'ed-img-tools';
    tools.innerHTML = '<button type="button" class="ed-img-btn" data-pick>📷 <span></span></button>' +
      '<button type="button" class="ed-img-btn" data-adjust>✥ ' + __('Căn chỉnh') + '</button>';
    slot.appendChild(tools);
    var pickBtn = tools.querySelector('[data-pick]'), adjBtn = tools.querySelector('[data-adjust]');
    var sync = function () {
      var has = !slot.classList.contains('slot-empty');
      pickBtn.querySelector('span').textContent = has ? __('Đổi ảnh') : __('Thêm ảnh');
      adjBtn.hidden = !has;
    };
    sync();
    pickBtn.title = __('{label}: chọn ảnh khác', { label: slot.getAttribute('data-label') || __('Ảnh này') });
    adjBtn.title = __('Kéo để chọn phần ảnh hiện ra, phóng to/thu nhỏ');
    adjBtn.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); adjust(slot); });
    pickBtn.addEventListener('click', function (e) {
      e.preventDefault(); e.stopPropagation();
      pickFile('image/*').then(function (files) {
        if (!files.length) return;
        busy(slot, true, 0);
        upload(url('admin/content/image'), { key: slot.getAttribute('data-edit-img'), __shrink: 1 }, files[0], function (p) { busy(slot, true, p); })
          .then(function (r) {
            busy(slot, false);
            if (!r.ok) return fail(r);
            var img = slot.querySelector('img');
            if (!img) {
              img = document.createElement('img');
              img.alt = '';
              slot.insertBefore(img, slot.firstChild);
              var ph = slot.querySelector('.slot-ph');
              if (ph) ph.remove();
            }
            slot.setAttribute('data-pos', '50 50 1');
            applyPos(img, { x: 50, y: 50, z: 1 });
            slot.classList.remove('slot-empty');
            sync();
            markDirty();
            img.onload = function () { img.onload = null; adjust(slot); };   // chọn xong mở luôn khung căn chỉnh
            // Ô có srcset (tối ưu ảnh): phải thay cả srcset, chỉ đổi src thì trình duyệt vẫn hiện ảnh cũ.
            if (r.srcset) { img.sizes = r.sizes || '100vw'; img.srcset = r.srcset; } else img.removeAttribute('srcset');
            img.src = r.medium;
          });
      });
    });
  });

  // ── 3. Ngày cưới ──────────────────────────────────────────
  // M1-OWNER-01: listener gắn thẳng lên từng ô ngày (iOS không phát click tới document cho span thường).
  document.querySelectorAll('[data-edit-date]').forEach(function (dateEl) { dateEl.addEventListener('click', openDate); });
  function openDate(e) {
    var el = e.currentTarget;
    e.preventDefault();
    var had = document.querySelector('.ed-pop-date');
    closePops();
    if (had) return;   // bấm lại vào ngày cưới khi popup ngày đang mở = đóng
    var pop = document.createElement('div');
    pop.className = 'ed-pop ed-pop-date';
    pop.innerHTML = '<p><b>' + __('Ngày giờ cưới') + '</b></p><label>' + __('Ngày') + '<input type="date" name="d"></label>' +
      '<label>' + __('Giờ') + '<input type="time" name="t"></label><p class="small muted">' + __('Ngày Âm lịch và đếm ngược tự tính.') + '</p>' +
      '<p class="small" data-date-past role="status" hidden style="margin:6px 0;padding:6px 8px;border-radius:8px;background:#fff4cc;color:#5c4500">' +
      __('Ngày này đã qua — kiểm tra lại năm?') + '</p>' +
      '<div class="btn-row ed-btns"><button type="button" class="btn btn-ghost btn-sm" data-x>' + __('Hủy') + '</button>' +
      '<button type="button" class="btn btn-accent btn-sm" data-ok>' + __('Lưu') + '</button></div>';
    pop.querySelector('[name=d]').value = el.getAttribute('data-date') || '';
    pop.querySelector('[name=t]').value = el.getAttribute('data-time') || '';
    document.body.appendChild(pop);
    // R5-27: đặt hộp theo kích thước ĐO THẬT -> luôn nằm trọn khung nhìn (390: không cắt nút Hủy/Lưu).
    var place = function () {
      var r = el.getBoundingClientRect(), vh = window.innerHeight, vw = window.innerWidth;
      pop.style.maxHeight = (vh - 20) + 'px';
      pop.style.overflowY = 'auto';
      var h = pop.offsetHeight, w = pop.offsetWidth;
      var top = r.bottom + 8;
      if (top + h > vh - 10) top = r.top - h - 8 >= 10 ? r.top - h - 8 : vh - h - 10;
      pop.style.top = Math.max(10, top) + 'px';
      pop.style.left = Math.max(10, Math.min(vw - w - 10, r.left + r.width / 2 - w / 2)) + 'px';
    };
    // Ngày đã qua (theo giờ Việt Nam): nhắc vàng, vẫn cho lưu.
    var dIn = pop.querySelector('[name=d]'), past = pop.querySelector('[data-date-past]');
    var checkPast = function () {
      var today = new Date(Date.now() + 7 * 3600 * 1000).toISOString().slice(0, 10);
      var was = past.hidden;
      past.hidden = !(dIn.value && dIn.value < today);
      if (was !== past.hidden) place();
    };
    dIn.addEventListener('input', checkPast);
    dIn.addEventListener('change', checkPast);
    checkPast();
    place();
    pop.querySelector('[name=d]').focus();
    // M2-OWNER-05: nút ✕ như hộp ♫/✨ (điện thoại không có Esc; vẫn đóng được khi chạm ngoài / chạm lại ngày).
    var px = document.createElement('button');
    px.type = 'button'; px.className = 'ed-pop-x'; px.setAttribute('data-pop-x', ''); px.setAttribute('aria-label', __('Đóng')); px.textContent = '×';
    px.addEventListener('click', function () { pop.remove(); });
    pop.appendChild(px);
    pop.querySelector('[data-x]').addEventListener('click', function () { pop.remove(); });
    pop.querySelector('[data-ok]').addEventListener('click', function () {
      AC.post(url('admin/content/date'), { date: pop.querySelector('[name=d]').value, time: pop.querySelector('[name=t]').value })
        .then(function (res) {
          if (!res.ok) return fail(res);
          // Tải lại để cập nhật Âm lịch, đếm ngược, nút thêm vào lịch.
          sessionStorage.setItem('ac-toast', __('Đã lưu ngày cưới ✓'));
          location.reload();
        });
    });
  }
  // Esc đóng popup đang mở (ngày cưới, nhạc nền, hiệu ứng) — luôn chỉ có tối đa 1 popup.
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape' || !document.querySelector('.ed-pop')) return;
    closePops();
  });
  try {
    var pending = sessionStorage.getItem('ac-toast');
    if (pending) { sessionStorage.removeItem('ac-toast'); AC.toast(pending); }
  } catch (e) { /* bỏ qua */ }

  // ── 4. Địa điểm / sự kiện ─────────────────────────────────
  var evWrap = document.querySelector('[data-events]');
  if (evWrap) {
    var mapQ = function (item) {
      var a = textOf(item.querySelector('[data-ev="address"]'));
      return a || textOf(item.querySelector('[data-ev="place"]'));
    };
    var collect = function () {
      return Array.prototype.map.call(evWrap.querySelectorAll('[data-ev-item]'), function (item) {
        var o = {};
        ['title', 'place', 'address', 'time'].forEach(function (f) { o[f] = textOf(item.querySelector('[data-ev="' + f + '"]')); });
        o.map = item.querySelector('[data-ev-link]').getAttribute('data-map') || '';
        o.side = item.getAttribute('data-ev-side') || '';
        return o;
      });
    };
    var refreshItem = function (item) {
      var q = mapQ(item);
      var box = item.querySelector('.ev-map');
      var link = item.querySelector('[data-ev-link]');
      var custom = link.getAttribute('data-map');
      link.href = custom || (q ? 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(q) : '');
      link.classList.toggle('is-hidden', !link.getAttribute('href'));
      var src = q ? 'https://maps.google.com/maps?q=' + encodeURIComponent(q) + '&z=15&output=embed' : '';
      var ifr = box.querySelector('iframe');
      if (src && (!ifr || ifr.getAttribute('src') !== src)) {
        box.innerHTML = '<iframe loading="lazy" title="' + __('Bản đồ') + '" referrerpolicy="no-referrer-when-downgrade"></iframe>';
        box.firstChild.src = src;
      } else if (!src) {
        box.innerHTML = '<div class="ev-map-ph">' + __('Nhập địa chỉ để hiện bản đồ') + '</div>';
      }
    };
    /** Lỗi gắn ngay trên thẻ địa điểm gây lỗi (không chỉ toast chung). */
    var evError = function (item, msg) {
      var box = item.querySelector('.ev-card'), p = box.querySelector('.ed-ev-err');
      if (!msg) { if (p) p.remove(); return; }
      if (!p) { p = document.createElement('p'); p.className = 'ed-ev-err'; p.setAttribute('role', 'alert'); box.appendChild(p); }
      p.textContent = msg;
    };
    /** Giống Content_model::normalize_map(): thiếu https:// thì tự thêm; scheme lạ -> null (từ chối). */
    var normMap = function (v) {
      v = String(v || '').trim();
      if (!v || /^https?:\/\/\S+$/i.test(v)) return v;
      if (/^[a-z][a-z0-9+.-]*:/i.test(v)) return null;
      if (/^([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}([\/?#]\S*)?$/i.test(v)) return 'https://' + v;
      return null;
    };
    // Bản đã lưu gần nhất (để "Bỏ thay đổi" khi lưu lỗi) + khuôn thẻ gốc (dựng lại thẻ đã xóa đúng cấu trúc giao diện).
    var evSaved = collect();
    var evProto = evWrap.querySelector('[data-ev-item]');
    evProto = evProto ? evProto.cloneNode(true) : null;
    var evBox = null;   // hộp "Chưa lưu" đang hiện (không tự tắt)
    var evClearFail = function () {
      if (evBox) { evBox.remove(); evBox = null; }
      evWrap.querySelectorAll('.ev-card.ed-error').forEach(function (c) { c.classList.remove('ed-error'); });
      evWrap.classList.remove('ed-ev-unsaved');
    };
    /** Lưu địa điểm lỗi (mạng, 5xx, 401/403, 422): viền đỏ trên thẻ + "Chưa lưu: … · Thử lại · Bỏ thay đổi" (giống ô chữ). */
    var evFail = function (item, r) {
      evClearFail();
      evWrap.classList.add('ed-ev-unsaved');
      var host = item && evWrap.contains(item) ? item.querySelector('.ev-card') : null;
      if (host) host.classList.add('ed-error');
      evBox = document.createElement('div');
      evBox.className = 'ed-ev-fail';
      evBox.setAttribute('role', 'alert');
      evBox.innerHTML = '<span></span><button type="button" class="btn btn-accent btn-sm" data-ev-retry>' + __('Thử lại') + '</button>' +
        '<button type="button" class="btn btn-ghost btn-sm" data-ev-undo>' + __('Bỏ thay đổi') + '</button>';
      evBox.querySelector('span').textContent = __('Chưa lưu địa điểm: {error}', { error: r.error || __('lỗi không rõ') }) + ' ';
      if (host) host.appendChild(evBox); else evWrap.after(evBox);
      evBox.querySelector('[data-ev-retry]').addEventListener('click', function () { saveEvents('', item); });
      evBox.querySelector('[data-ev-undo]').addEventListener('click', function () { evClearFail(); renderEvents(evSaved); });
    };
    /** Dựng lại danh sách thẻ theo dữ liệu (dùng khi "Bỏ thay đổi"). */
    var makeItem = function () {
      var item;
      if (evProto) {
        item = evProto.cloneNode(true);
        var t = item.querySelector('.ed-ev-tools'); if (t) t.remove();
        var er = item.querySelector('.ed-ev-err'); if (er) er.remove();
      } else {
        item = document.createElement('article');
        item.className = 'ev';
        item.setAttribute('data-ev-item', '');
        item.innerHTML = '<div class="ev-map"></div><div class="paper ev-card"><h3 data-ev="title"></h3><p class="ev-place" data-ev="place"></p>' +
          '<p class="ev-addr" data-ev="address"></p><p class="ev-time" data-ev="time"></p>' +
          '<a class="btn btn-accent" data-ev-link data-map="" target="_blank" rel="noopener noreferrer">' + __('Chỉ đường') + '</a></div>';
      }
      item.querySelector('[data-ev="address"]').setAttribute('data-ph', __('Địa chỉ'));
      item.querySelector('[data-ev="time"]').setAttribute('data-ph', __('Thời gian'));
      item.querySelector('[data-ev="place"]').setAttribute('data-ph', __('Tên nơi tổ chức'));
      return item;
    };
    var renderEvents = function (list) {
      evWrap.querySelectorAll('[data-ev-item]').forEach(function (x) { x.remove(); });
      list.forEach(function (ev) {
        var item = makeItem();
        ['title', 'place', 'address', 'time'].forEach(function (f) { item.querySelector('[data-ev="' + f + '"]').textContent = ev[f] || ''; });
        item.querySelector('[data-ev-link]').setAttribute('data-map', ev.map || '');
        item.setAttribute('data-ev-side', ev.side || '');
        evWrap.appendChild(item);
        decorate(item);
        refreshItem(item);
      });
    };
    /** $item = thẻ vừa sửa (để gắn lỗi); xóa thẻ thì không có -> hộp lỗi hiện dưới danh sách. */
    var saveEvents = function (msg, item) {
      var sent = collect();
      return AC.post(url('admin/content/events'), { events: JSON.stringify(sent) }).then(function (r) {
        var items = evWrap.querySelectorAll('[data-ev-item]');
        if (!r.ok) {
          var bad = r.index != null && items[r.index] ? items[r.index] : item;
          if (r.index != null && items[r.index]) evError(items[r.index], r.error);
          evFail(bad, r);
          fail(r);
          return r;
        }
        evClearFail();
        items.forEach(function (it, i) {
          evError(it, '');
          // Giá trị máy chủ đã chuẩn hóa (vd thêm https://) -> lần lưu sau gửi đúng giá trị này.
          if (r.events && r.events[i]) it.querySelector('[data-ev-link]').setAttribute('data-map', r.events[i].map || '');
        });
        evSaved = collect();
        markDirty();
        applyChecklist(r.checklist);
        AC.toast(msg || __('Đã lưu ✓'));
        return r;
      });
    };
    // Đoán bên theo tên địa điểm — giống Content_model::event_side() (khi chủ nhà chưa chọn "Dành cho").
    var guessSide = function (item) {
      var t = (textOf(item.querySelector('[data-ev="title"]')) + ' ' + textOf(item.querySelector('[data-ev="place"]'))).toLowerCase();
      if (/nhà gái|nha gai|vu quy|bride/.test(t)) return 'bride';
      if (/nhà trai|nha trai|thành hôn|thanh hon|tân hôn|tan hon|groom/.test(t)) return 'groom';
      return '';
    };
    var SIDE_NAMES = { '': __('Chung hai nhà'), groom: __('Nhà trai'), bride: __('Nhà gái') };
    var syncSide = function (item) {
      var sel = item.querySelector('[data-ev-side-sel]');
      if (!sel) return;
      sel.value = item.getAttribute('data-ev-side') || '';
      sel.options[0].textContent = __('Tự nhận theo tên') + ' (' + SIDE_NAMES[guessSide(item)] + ')';
    };
    var decorate = function (item) {
      item.querySelectorAll('[data-ev]').forEach(function (f) { f.classList.add('ed-field'); });
      var tools = document.createElement('div');
      tools.className = 'ed-ev-tools';
      tools.innerHTML = '<label class="ed-ev-side">' + __('Dành cho') + ' <select data-ev-side-sel>' +
        '<option value=""></option><option value="both">' + __('Chung hai nhà') + '</option>' +
        '<option value="groom">' + __('Khách nhà trai') + '</option><option value="bride">' + __('Khách nhà gái') + '</option></select></label>' +
        '<button type="button" class="btn btn-ghost btn-sm" data-ev-map>📍 ' + __('Link bản đồ riêng') + '</button>' +
        '<button type="button" class="btn btn-danger btn-sm" data-ev-del>' + __('Xóa') + '</button>';
      item.querySelector('.ev-card').appendChild(tools);
      var sel = tools.querySelector('[data-ev-side-sel]');
      sel.title = __('Thiệp gửi khách nhà trai / nhà gái chỉ in địa điểm của bên đó và địa điểm chung.');
      sel.addEventListener('change', function () {
        item.setAttribute('data-ev-side', sel.value);
        saveEvents(__('Đã lưu ✓'), item);
      });
      syncSide(item);
    };
    evWrap.querySelectorAll('[data-ev-item]').forEach(decorate);

    evWrap.addEventListener('click', function (e) {
      if (e.target.closest('.ed-ev-side')) return;
      var f = e.target.closest('[data-ev]');
      if (f) { e.preventDefault(); editField(f); return; }
      var item = e.target.closest('[data-ev-item]');
      if (e.target.closest('[data-ev-del]')) {
        if (!window.confirm(__('Xóa địa điểm này?'))) return;
        item.remove();
        saveEvents(__('Đã xóa ✓'));
      } else if (e.target.closest('[data-ev-map]')) {
        openMapBox(item);
      } else if (e.target.closest('[data-ev-link]')) {
        // Đang sửa: bấm "Chỉ đường" mở ô sửa link bản đồ (có nút "Mở thử" để xem đúng chỗ khách sẽ tới).
        e.preventDefault();
        openMapBox(item);
      }
    });

    /** Lưu link bản đồ riêng của 1 thẻ (rỗng = tự tìm theo địa chỉ). Trả false nếu link bị từ chối ngay trên máy. */
    var saveMap = function (item, v) {
      var link = item.querySelector('[data-ev-link]');
      var old = link.getAttribute('data-map') || '';
      var nv = normMap(v);
      if (nv && nv.length > 2000) {   // giống Content_model::MAP_MAX: báo ngay, không gửi link sẽ bị từ chối
        evError(item, __('Link quá dài ({n} ký tự) — hãy dùng nút Chia sẻ của Google Maps (maps.app.goo.gl/…). Đang giữ link cũ.', { n: nv.length }));
        AC.toast(__('Link bản đồ quá dài, chưa lưu.'), 'error');
        return false;
      }
      if (nv === null) {   // không nhận: giữ link cũ, không gửi kèm các lần lưu sau
        evError(item, __('Link bản đồ không hợp lệ — hãy dán link Google Maps (vd https://maps.app.goo.gl/…). Đang giữ link cũ.'));
        AC.toast(__('Link bản đồ không hợp lệ, chưa lưu.'), 'error');
        return false;
      }
      evError(item, '');
      link.setAttribute('data-map', nv);
      refreshItem(item);
      saveEvents(nv !== v.trim() ? __('Đã lưu (tự thêm https:// cho link) ✓') : '', item).then(function (r) {
        if (r.ok || r.index == null) return;   // lỗi mạng: giữ link mới để "Thử lại"
        link.setAttribute('data-map', old);   // máy chủ từ chối link: trả lại link cũ
        refreshItem(item);
      });
      return true;
    };
    /** M1-OWNER-12: ô dán link ngay trong thẻ (thay window.prompt — điện thoại khó dán link dài, không có nút Dán). */
    var openMapBox = function (item) {
      var card = item.querySelector('.ev-card'), box = card.querySelector('.ed-ev-mapbox');
      if (box) { box.querySelector('input').focus(); return; }
      var cur = item.querySelector('[data-ev-link]').getAttribute('data-map') || '';
      box = document.createElement('form');
      box.className = 'ed-ev-mapbox';
      box.innerHTML = '<label>' + __('Link Google Maps của địa điểm này') + '<input type="url" inputmode="url" autocomplete="off" placeholder="https://maps.app.goo.gl/…"></label>' +
        '<p class="small muted">' + __('Để trống = tự tìm theo địa chỉ ở trên. Trên Google Maps bấm Chia sẻ → Sao chép link rồi dán vào đây.') + '</p>' +
        '<div class="btn-row ed-btns"><a class="btn btn-ghost btn-sm" data-map-try target="_blank" rel="noopener noreferrer">' + __('Mở thử ↗') + '</a>' +
        '<button type="button" class="btn btn-ghost btn-sm" data-map-x>' + __('Hủy') + '</button>' +
        '<button type="submit" class="btn btn-accent btn-sm">' + __('Lưu') + '</button></div>';
      var input = box.querySelector('input');
      input.value = cur;
      // "Mở thử": link đang gõ (nếu hợp lệ), không thì link khách đang được dẫn tới (tìm theo địa chỉ).
      var tryLink = box.querySelector('[data-map-try]');
      var syncTry = function () {
        var v = normMap(input.value);
        var href = v || (v === '' ? item.querySelector('[data-ev-link]').getAttribute('href') || '' : '');
        if (href) tryLink.href = href; else tryLink.removeAttribute('href');
        tryLink.classList.toggle('is-hidden', !href);
      };
      input.addEventListener('input', syncTry);
      syncTry();
      card.insertBefore(box, card.querySelector('.ed-ev-tools'));
      var close = function () { box.remove(); };
      box.querySelector('[data-map-x]').addEventListener('click', close);
      box.addEventListener('submit', function (e) {
        e.preventDefault();
        if (input.value.trim() === cur) return close();
        if (saveMap(item, input.value)) close(); else input.focus();
      });
      input.focus();
      keepAboveBar(box);
    };

    var editField = function (f) {
      if (f.isContentEditable) return;
      var original = textOf(f);
      f.contentEditable = 'true';
      f.classList.add('ed-active');
      var unbump = bumpFont(f);
      f.focus();
      var sel = window.getSelection(), range = document.createRange();
      range.selectNodeContents(f); sel.removeAllRanges(); sel.addRange(range);
      var end = function (save) {
        f.removeEventListener('keydown', key); f.removeEventListener('blur', blur);
        f.removeAttribute('contenteditable'); f.classList.remove('ed-active');
        unbump();
        f.textContent = textOf(f);
        if (!save) { f.innerText = original; return; }
        if (f.textContent === original) return;
        refreshItem(f.closest('[data-ev-item]'));
        syncSide(f.closest('[data-ev-item]'));
        saveEvents('', f.closest('[data-ev-item]'));
      };
      var key = function (e) {
        if (e.key === 'Enter') { e.preventDefault(); f.blur(); }
        if (e.key === 'Escape') { e.preventDefault(); end(false); }
      };
      var blur = function () { end(true); };
      f.addEventListener('keydown', key); f.addEventListener('blur', blur);
    };

    var add = document.createElement('button');
    add.type = 'button';
    add.className = 'btn btn-ghost ed-add';
    add.textContent = '+ ' + __('Thêm địa điểm');
    evWrap.after(add);
    add.addEventListener('click', function () {
      if (evWrap.querySelectorAll('[data-ev-item]').length >= 6) return AC.toast(__('Tối đa 6 địa điểm.'), 'error');
      var item = makeItem();
      item.querySelector('[data-ev="title"]').textContent = __('Sự kiện mới');
      ['place', 'address', 'time'].forEach(function (k) { item.querySelector('[data-ev="' + k + '"]').textContent = ''; });
      item.querySelector('[data-ev-link]').setAttribute('data-map', '');
      item.setAttribute('data-ev-side', '');
      evWrap.appendChild(item);
      decorate(item);
      refreshItem(item);
      saveEvents(__('Đã thêm địa điểm — bấm vào từng dòng để sửa'), item);
      item.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  }

  // ── 5. Album trang chủ ────────────────────────────────────
  var gal = document.querySelector('[data-home-gallery]');
  if (gal) {
    var albumId = gal.getAttribute('data-album-id');
    var tools = function (tile) {
      var t = document.createElement('span');
      t.className = 'ed-tile-tools';
      t.innerHTML = '<button type="button" data-g-replace title="' + __('Thay ảnh') + '" aria-label="' + __('Thay ảnh') + '">📷</button>' +
        '<button type="button" data-g-del title="' + __('Xóa ảnh') + '" aria-label="' + __('Xóa ảnh') + '">🗑</button>';
      tile.appendChild(t);
    };
    gal.querySelectorAll('.wd-g').forEach(tools);
    var addTile = document.createElement('button');
    addTile.type = 'button';
    addTile.className = 'wd-g ed-add-tile';
    addTile.innerHTML = '<span>＋</span>' + __('Thêm ảnh vào album');
    gal.appendChild(addTile);
    // Album trang chủ KHÔNG qua bản nháp (quyết định D13): nói rõ để chủ nhà không bất ngờ.
    var galNote = document.createElement('p');
    galNote.className = 'ed-note';
    galNote.textContent = __('Ảnh album hiện ngay cho khách — không cần bấm "Cho khách xem".');
    gal.after(galNote);

    // Chặn trình xem ảnh khi bấm nút sửa (bắt ở pha capture, trước app.js).
    document.addEventListener('click', function (e) {
      var b = e.target.closest('.ed-tile-tools button');
      if (!b) return;
      e.preventDefault(); e.stopPropagation();
      var tile = b.closest('.wd-g');
      var id = tile.getAttribute('data-photo-id');
      if (b.hasAttribute('data-g-del')) {
        if (!window.confirm(__('Xóa ảnh này khỏi album? (xóa luôn bản gốc)'))) return;
        AC.post(url('admin/photos/delete'), { ids: [id] }).then(function (r) {
          if (!r.ok) return fail(r);
          tile.remove();
          refreshChecklist();
          AC.toast(__('Đã xóa ảnh.'));
        });
      } else {
        pickFile('image/*').then(function (files) {
          if (!files.length) return;
          busy(tile, true, 0);
          upload(url('admin/content/replace_photo'), { id: id }, files[0], function (p) { busy(tile, true, p); }).then(function (r) {
            busy(tile, false);
            if (!r.ok) return fail(r);
            tile.setAttribute('data-photo-id', r.id);
            tile.href = r.medium;
            if (tile.hasAttribute('data-full')) tile.setAttribute('data-full', r.full);
            if (r.srcset) tile.setAttribute('data-srcset', r.srcset); else tile.removeAttribute('data-srcset');
            var timg = tile.querySelector('img');
            if (r.srcset_s && timg.hasAttribute('srcset')) timg.srcset = r.srcset_s; else timg.removeAttribute('srcset');
            timg.src = r.thumb;
            AC.toast(__('Đã thay ảnh ✓'));
          });
        });
      }
    }, true);

    addTile.addEventListener('click', function () {
      pickFile('image/jpeg,image/png,image/webp,image/gif', true).then(function (files) {
        if (!files.length) return;
        var oldNote = document.querySelector('.ed-persist');   // lỗi của lượt trước không đè kết quả lượt này (R2-17)
        if (oldNote) oldNote.remove();
        var chain = Promise.resolve(), okN = 0, errs = [];
        files.forEach(function (file) {
          chain = chain.then(function () {
            var tile = document.createElement('a');
            tile.className = 'wd-g';
            gal.insertBefore(tile, addTile);
            busy(tile, true, 0);
            return upload(url('admin/photos/upload'), { album_id: albumId }, file, function (p) { busy(tile, true, p); }).then(function (r) {
              busy(tile, false);
              if (!r.ok) {
                tile.remove();
                errs.push(file.name + ' (' + (r.error || __('lỗi')) + ')');
                if (isSession(r)) sessionNotice();
                return;
              }
              okN++;
              tile.href = r.photo.medium;
              tile.setAttribute('data-lb', '');
              tile.setAttribute('data-photo-id', r.photo.id);
              tile.innerHTML = '<img alt="" src="' + r.photo.thumb + '">';
              tools(tile);
            });
          });
        });
        // MỘT thông báo tổng kết khi xong hết (lỗi của ảnh trước không bị toast sau che mất).
        chain.then(function () {
          if (okN) refreshChecklist();
          if (!errs.length) return AC.toast(__('Đã thêm {ok}/{total} ảnh ✓', { ok: okN, total: files.length }));
          persistNotice(__('Đã thêm {ok}/{total} ảnh. {n} ảnh lỗi: {list}.', { ok: okN, total: files.length, n: errs.length, list: errs.join(', ') }));
        });
      });
    });
  }

  // ── 6. Giao diện ──────────────────────────────────────────
  // 10 nút cuộn ngang trên điện thoại: đưa giao diện đang dùng vào giữa tầm nhìn.
  var themeBox = bar.querySelector('.ed-themes'), themeOn = themeBox && themeBox.querySelector('.ed-theme.on');
  if (themeOn && themeBox.scrollWidth > themeBox.clientWidth) {
    themeBox.scrollLeft = themeOn.offsetLeft - (themeBox.clientWidth - themeOn.offsetWidth) / 2;
  }
  bar.addEventListener('click', function (e) {
    var b = e.target.closest('[data-theme-pick]');
    if (!b) return;
    if (b.hasAttribute('data-vip-locked')) return;   // VIP trên bản cài máy: app.js mở hộp giới thiệu, không đổi giao diện
    var t = b.getAttribute('data-theme-pick');
    var prevTheme = document.documentElement.getAttribute('data-theme');
    var prevBtn = bar.querySelector('[data-theme-pick].on');
    document.documentElement.setAttribute('data-theme', t);
    bar.querySelectorAll('[data-theme-pick]').forEach(function (x) { x.classList.toggle('on', x === b); });
    AC.post(url('admin/content/theme'), { theme: t }).then(function (r) {
      if (!r.ok) {   // chưa lưu được: trả trang + nút về giao diện cũ, không để "trông như đã đổi"
        document.documentElement.setAttribute('data-theme', prevTheme);
        bar.querySelectorAll('[data-theme-pick]').forEach(function (x) { x.classList.toggle('on', x === prevBtn); });
        if (r.vip && AC.vip) return AC.vip(b);
        return fail(r);
      }
      markDirty();
      AC.toast(__('Giao diện: {name}', { name: b.getAttribute('data-vip-name') || (b.querySelector('.ed-theme-name') || b).textContent.trim() }));
      suggestFx(t);
    });
  });

  // Hiệu ứng rơi hợp từng giao diện. Chủ nhà chưa từng tự chọn -> tự áp; đã chọn -> chỉ gợi ý kèm nút "Áp dụng".
  var FX_HINT = { serenity: 'hearts', lavender: 'petals', summer: 'leaves', thiep: 'hearts', hoangkim: 'glitter',
    songhy: 'blossom', tapchi: 'none', vuonhoa: 'petals', demsao: 'stars', datnung: 'leaves', hongphan: 'hearts',
    gatsby: 'glitter', cungdinh: 'blossom', ngoctrai: 'snow', dienanh: 'none', provence: 'petals', wabi: 'blossom',
    hongnhung: 'petals', phale: 'glitter', santorini: 'none', lucbao: 'glitter' };
  /** Hiệu ứng đang hiện trên trang (để trả về khi lưu lỗi). */
  function curFx() {
    var box = document.querySelector('.fx'), pick = bar.querySelector('[data-fx-pick]');
    return box ? box.getAttribute('data-fx') : (pick ? pick.value : '');
  }
  function showFx(v) {
    var pick = bar.querySelector('[data-fx-pick]'), box = document.querySelector('.fx');
    if (box) box.setAttribute('data-fx', v);
    if (pick) pick.value = v;
    document.querySelectorAll('[data-fx-opt]').forEach(function (x) { x.classList.toggle('on', x.getAttribute('data-fx-opt') === v); });
  }
  function setFx(v, auto) {
    var prev = curFx();
    showFx(v);
    return AC.post(url('admin/content/fx'), { fx: v, auto: auto ? '1' : '0' }).then(function (r) {
      if (!r.ok) { showFx(prev); fail(r); throw r; }
      if (!auto) bar.setAttribute('data-fx-set', '1');
      markDirty();
    });
  }
  function fxName(v) {
    var o = bar.querySelector('[data-fx-pick] option[value="' + v + '"]');
    return o ? o.textContent : v;
  }
  function suggestFx(t) {
    var want = FX_HINT[t], pick = bar.querySelector('[data-fx-pick]');
    if (!want || !pick || pick.value === want) return;
    if (bar.getAttribute('data-fx-set') !== '1') {
      setFx(want, true).then(function () { AC.toast(__('Đã đổi hiệu ứng hợp giao diện: {name}', { name: fxName(want) })); }, function () {});
      return;
    }
    var old = document.querySelector('.flash');
    if (old) old.remove();
    var d = document.createElement('div');
    d.className = 'flash flash-success flash-fx';
    d.setAttribute('role', 'status');
    d.innerHTML = '<span></span> <button type="button" class="btn btn-accent btn-sm" data-apply>' + __('Áp dụng') + '</button>' +
      '<button type="button" class="flash-x" aria-label="' + __('Đóng') + '">×</button>';
    d.querySelector('span').textContent = __('Gợi ý: hiệu ứng “{name}” hợp giao diện này.', { name: fxName(want) });
    document.body.appendChild(d);
    var timer = setTimeout(function () { d.remove(); }, 9000);
    d.querySelector('.flash-x').addEventListener('click', function () { clearTimeout(timer); d.remove(); });
    d.querySelector('[data-apply]').addEventListener('click', function () {
      clearTimeout(timer); d.remove();
      setFx(want, false).then(function () { AC.toast(__('Hiệu ứng: {name}', { name: fxName(want) })); }, function () {});
    });
  }

  // ── 6b. Thanh sửa gọn trên điện thoại (R2-11) ─────────────
  // ≤ 760px: cuộn xuống -> thanh thu còn 1 hàng nút chính (≤ 64px); cuộn lên / chạm vào thanh -> mở đầy đủ.
  // Chiều cao thanh ghi vào --ed-bar-h (toast, popup đặt ngay trên thanh); --ed-pad = chiều cao đầy đủ (đệm cuối trang).
  // M1-OWNER-04: điện thoại xoay ngang (cao ≤ 500px) cũng dùng bố cục gọn + thu gọn khi cuộn (cùng điều kiện với editor.css).
  var mobile = window.matchMedia ? window.matchMedia('(max-width: 760px), (max-height: 500px)') : { matches: false };
  var shortScreen = window.matchMedia ? window.matchMedia('(max-height: 500px)') : { matches: false };
  var rootStyle = document.documentElement.style, padMax = 0;
  var measureBar = function () {
    var h = Math.round(bar.getBoundingClientRect().height);
    rootStyle.setProperty('--ed-bar-h', h + 'px');
    if (!bar.classList.contains('ed-mini') && h > padMax) { padMax = h; rootStyle.setProperty('--ed-pad', h + 'px'); }
  };
  if (window.ResizeObserver) new ResizeObserver(measureBar).observe(bar);
  measureBar();
  window.addEventListener('resize', function () {
    padMax = 0;
    if (!mobile.matches) setMini(false);
    else if (shortScreen.matches) setMini(true);   // vừa xoay ngang: thu gọn ngay, còn chỗ xem trang
    measureBar();
  });
  function setMini(on) {
    on = !!on && mobile.matches;
    if (bar.classList.contains('ed-mini') === on) return;
    bar.classList.toggle('ed-mini', on);
    bar.setAttribute('aria-expanded', on ? 'false' : 'true');
  }
  if (shortScreen.matches) setMini(true);
  var lastY = window.scrollY;
  window.addEventListener('scroll', function () {
    var y = window.scrollY, d = y - lastY;
    if (Math.abs(d) < 4) return;
    lastY = y;
    if (!mobile.matches) return;
    var panelOpen = steps && !steps.hidden;
    if (d > 0 && y > 60 && !panelOpen) setMini(true);
    // Xoay ngang (cao ≤ 500px): cuộn lên KHÔNG mở thanh đầy đủ (175px chiếm nửa màn) — chạm vào thanh mới mở (M1-OWNER-04).
    else if (d < -12 && !shortScreen.matches) setMini(false);
  }, { passive: true });
  // Chạm vào phần trống của thanh đang thu gọn -> mở đầy đủ (nút thì vẫn làm việc của nút).
  bar.addEventListener('click', function (e) {
    if (bar.classList.contains('ed-mini') && !e.target.closest('button, a, select, input')) setMini(false);
  });
  /** Popup (✨, ♫): máy tính giữ chỗ cũ cạnh cụm nút nổi; điện thoại đặt ngay trên thanh sửa.
   *  M1-OWNER-02: hộp KHÔNG được cao quá khoảng trống giữa thanh menu trên và thanh sửa (iPhone SE: tiêu đề từng
   *  chui lên trên màn hình, đè nút ♫) — đặt max-height, phần danh sách tự cuộn (CSS .ed-pop lưới hàng minmax(0,1fr)).
   *  Mọi popup có nút ✕ (điện thoại không có Esc). */
  function placePop(pop) {
    if (!pop.querySelector('[data-pop-x]')) {
      var x = document.createElement('button');
      x.type = 'button'; x.className = 'ed-pop-x'; x.setAttribute('data-pop-x', ''); x.setAttribute('aria-label', __('Đóng'));
      x.textContent = '×';
      x.addEventListener('click', function () { closePops(); });
      pop.appendChild(x);
    }
    var bottom, top = 8;
    if (mobile.matches) {
      if (shortScreen.matches) setMini(true);   // xoay ngang: thu thanh sửa để hộp còn chỗ
      // Thanh ♫/menu nằm trên cùng (editor.css đưa .music lên top:7px): hộp bắt đầu dưới hàng nút đó.
      document.querySelectorAll('.music-btn, .wd-nav').forEach(function (b) {
        var r = b.getBoundingClientRect();
        if (r.width && r.top < 120) top = Math.max(top, r.bottom + 8);
      });
      bottom = Math.round(innerHeight - bar.getBoundingClientRect().top + 10);
      bottom = Math.min(bottom, Math.max(10, innerHeight - top - 160));   // thanh đang mở đầy đủ: hộp vẫn phải lọt màn hình
      pop.style.right = 'max(16px, env(safe-area-inset-right, 0px))';
    } else {
      // Máy tính: đặt BÊN TRÁI cụm ✨/♫ (không che chính các nút đó -> bấm ✨ rồi ♫ vẫn được).
      var left = innerWidth - 16;
      document.querySelectorAll('.music-btn, .ed-fx-btn').forEach(function (b) {
        var r = b.getBoundingClientRect();
        if (r.width) left = Math.min(left, r.left);
      });
      pop.style.right = Math.round(innerWidth - left + 12) + 'px';
      bottom = 170;
    }
    pop.style.bottom = bottom + 'px';
    pop.style.maxHeight = Math.max(160, innerHeight - bottom - top) + 'px';
  }
  /** Cuộn để $node (ô đang sửa / dòng "Quá N ký tự") nằm TRÊN thanh sửa và bàn phím ảo. */
  function keepAboveBar(node) {
    if (!node || !node.getBoundingClientRect) return;
    var vv = window.visualViewport, vh = vv ? vv.height + vv.offsetTop : innerHeight;
    var limit = Math.min(vh, bar.getBoundingClientRect().top) - 8;
    var b = node.getBoundingClientRect().bottom;
    if (b > limit) window.scrollBy(0, b - limit);
  }
  // Toast trong trang sửa: 1 dòng (cắt "…"), chạm để xem đủ (CSS: .is-editing .flash).
  document.addEventListener('click', function (e) {
    var f = e.target.closest('.flash');
    if (!f || f.classList.contains('flash-fx') || e.target.closest('button, a')) return;
    f.classList.toggle('is-open');
  });

  // ── 7. Việc cần làm (3 bước) + hướng dẫn lần đầu ─────────
  var steps = bar.querySelector('[data-ed-steps]');
  var stepsBtn = bar.querySelector('[data-ed-steps-toggle]');
  var setSteps = function (open) { steps.hidden = !open; stepsBtn.setAttribute('aria-expanded', open ? 'true' : 'false'); };
  stepsBtn.addEventListener('click', function () { setSteps(steps.hidden); });
  steps.addEventListener('click', function (e) {
    if (e.target.closest('[data-ed-go-publish]')) { e.preventDefault(); setSteps(false); publishBtn.focus(); publishBtn.classList.add('pulse'); return; }
    if (e.target.closest('a')) setSteps(false);
  });
  // M1-OWNER-02: đóng khi chạm ra ngoài bằng pointerdown (iOS không phát click tới document khi chạm vào chữ thường).
  document.addEventListener('pointerdown', function (e) {
    if (!steps.hidden && !steps.contains(e.target) && !e.target.closest('[data-ed-steps-toggle]')) setSteps(false);
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !steps.hidden) { setSteps(false); stepsBtn.focus(); } });
  // Hướng dẫn 3 bước: hiện 1 lần cho trang chưa từng cho khách xem (không che nút sửa nào sau khi đóng).
  var welcome = document.querySelector('[data-ed-welcome]');
  var showWelcome = function () { welcome.hidden = false; welcome.querySelector('[data-ed-welcome-close]').focus(); };
  if (welcome) {
    var closeWelcome = function () {
      welcome.hidden = true;
      try { localStorage.setItem('ac-welcome', '1'); } catch (e) { /* bỏ qua */ }
    };
    welcome.addEventListener('click', function (e) { if (e.target === welcome || e.target.closest('[data-ed-welcome-close]')) closeWelcome(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !welcome.hidden) closeWelcome(); });
    steps.querySelector('[data-ed-welcome-open]').addEventListener('click', function () { setSteps(false); showWelcome(); });
    var seen = null;
    try { seen = localStorage.getItem('ac-welcome'); } catch (e) { seen = null; }
    if (!seen && bar.getAttribute('data-first') === '1') showWelcome();
  }
  var fxPick = bar.querySelector('[data-fx-pick]');
  if (fxPick) fxPick.addEventListener('change', function () { pickFx(fxPick.value); });
  /** Chủ nhà tự chọn hiệu ứng (hộp chọn trên thanh sửa hoặc popup ✨ Hiệu ứng): xem trước ngay, lỗi thì trả về cũ. */
  function pickFx(v) {
    var box = document.querySelector('.fx');
    var prev = box ? box.getAttribute('data-fx') : '';
    showFx(v);
    AC.post(url('admin/content/fx'), { fx: v }).then(function (r) {
      if (!r.ok) { if (prev) showFx(prev); return fail(r); }
      bar.setAttribute('data-fx-set', '1');
      markDirty();
      AC.toast(__('Hiệu ứng: {name}', { name: fxName(v) }));
    });
  }

  // ── 8. Nhạc nền: thư viện (bài có sẵn + bài tải lên + gợi ý bài hát cưới chưa tải), chọn / nghe thử / xóa / tải lên ──
  var music = document.querySelector('[data-music]');
  var preview = new Audio();
  var UP_LABEL = '+ ' + __('Tải bài hát lên (MP3, M4A · tối đa 20 MB)'), MUSIC_MAX = 20 * 1024 * 1024;
  var openMusic = function () {
    var old = document.querySelector('.ed-pop-music');
    closePops();
    if (old) return;
    var pop = document.createElement('div');
    pop.className = 'ed-pop ed-pop-music';
    pop.innerHTML = '<p><b>' + __('Nhạc nền') + '</b> <span class="small muted">' + __('— khách bấm vào trang là nhạc phát') + '</span></p><div data-list class="ed-music-list">' + __('Đang tải…') + '</div>' +
      '<button type="button" class="btn btn-ghost btn-sm" data-m-up>' + UP_LABEL + '</button>' +
      '<p class="small muted">' + __('Chỉ tải bài bạn có quyền sử dụng. Bài "Có sẵn" là nhạc bản quyền tự do / CC BY; bài hát cưới phổ biến bạn tự tải file về máy rồi chọn.') + '</p>';
    document.body.appendChild(pop);
    placePop(pop);
    var draw = function (r) {
      if (!r.ok) return fail(r);
      var box = pop.querySelector('[data-list]');
      box.innerHTML = '';
      var rows = [{ id: '', title: __('Tắt nhạc nền'), credit: '', builtin: true }].concat(r.list);
      rows.forEach(function (m) {
        var row = document.createElement('label');
        row.className = 'ed-music-row';
        row.innerHTML = '<input type="radio" name="ed-music"><span class="ed-music-t"><b></b><small></small></span>' +
          (m.url ? '<button type="button" class="ed-music-play" title="' + __('Nghe thử') + '" aria-label="' + __('Nghe thử') + '">▶</button>' : '') +
          (!m.builtin ? '<button type="button" class="ed-music-del" title="' + __('Xóa khỏi thư viện') + '" aria-label="' + __('Xóa khỏi thư viện') + '">🗑</button>' : '');
        row.querySelector('b').textContent = m.title;
        row.querySelector('small').textContent = m.credit || (m.builtin ? (m.id ? __('Có sẵn') : '') : __('Bạn tải lên'));
        var radio = row.querySelector('input');
        radio.checked = m.id === r.current;
        radio.addEventListener('change', function () {
          AC.post(url('admin/content/music'), { action: 'select', id: m.id }).then(function (res) {
            if (!res.ok) return fail(res);
            setAudio(res.url);
            markDirty();
            AC.toast(m.id ? __('Nhạc nền: {name}', { name: m.title }) : __('Đã tắt nhạc nền'));
          });
        });
        var play = row.querySelector('.ed-music-play');
        if (play) play.addEventListener('click', function (e) {
          e.preventDefault();
          var on = preview.src === m.url && !preview.paused;
          pop.querySelectorAll('.ed-music-play').forEach(function (b) { b.textContent = '▶'; });
          if (on) { preview.pause(); return; }
          preview.src = m.url; preview.play(); play.textContent = '❚❚';
        });
        var del = row.querySelector('.ed-music-del');
        if (del) del.addEventListener('click', function (e) {
          e.preventDefault();
          if (!window.confirm(__('Xóa bài "{name}" khỏi thư viện?', { name: m.title }))) return;
          AC.post(url('admin/content/music'), { action: 'delete', id: m.id }).then(function (res) { draw(res); setAudio(res.url); markDirty(); });
        });
        box.appendChild(row);
      });
      // Gợi ý bài hát cưới phổ biến (chỉ tên — chưa có file): bấm -> chọn MP3 trên máy -> tải lên, gắn đúng tên.
      var sugs = r.suggestions || [];
      if (sugs.length) {
        var h = document.createElement('p');
        h.className = 'ed-music-sugh';
        h.innerHTML = '<b>' + __('Bài hát cưới phổ biến') + '</b><small>' + __('Bấm 1 bài rồi chọn file MP3 trên máy bạn') + '</small>';
        box.appendChild(h);
      }
      sugs.forEach(function (sg) {
        var row = document.createElement('div');
        row.className = 'ed-music-row ed-music-sug';
        row.innerHTML = '<button type="button" class="ed-music-t ed-music-sugbtn"><b></b><small></small></button>' +
          '<span class="ed-music-need">' + __('Cần tải bài') + '</span><a class="ed-music-yt" target="_blank" rel="noopener noreferrer">' + __('Nghe thử') + '</a>';
        row.querySelector('b').textContent = sg.title;
        row.querySelector('small').textContent = sg.artist;
        var yt = row.querySelector('.ed-music-yt');
        yt.href = sg.search;
        yt.title = __('Nghe thử "{name}" trên YouTube', { name: sg.name });
        yt.addEventListener('click', function (e) { e.stopPropagation(); });
        var btn = row.querySelector('button');
        btn.title = __('Chọn file MP3 "{name}" trên máy để tải lên', { name: sg.name });
        btn.addEventListener('click', function () {
          pickFile('audio/mpeg,audio/mp4,audio/x-m4a,audio/ogg,.mp3,.m4a,.ogg').then(function (files) {
            if (!files.length) return;
            if (files[0].size > MUSIC_MAX) {
              return AC.toast(__('Bài "{name}" nặng {size} MB — tối đa 20 MB.', { name: files[0].name, size: (files[0].size / 1048576).toFixed(1) }), 'error');
            }
            var need = row.querySelector('.ed-music-need');
            btn.disabled = true;
            upload(url('admin/content/music'), { __field: 'music', action: 'upload', suggest: sg.key }, files[0], function (p) {
              need.textContent = __('Đang tải… {pct}%', { pct: Math.round(p * 100) });
            }).then(function (res) {
              btn.disabled = false; need.textContent = __('Cần tải bài');
              if (!res.ok) return fail(res);
              draw(res); setAudio(res.url); markDirty(); AC.toast(__('Nhạc nền: {name}', { name: sg.name }) + ' ✓');
              var a = music.querySelector('audio');
              if (a) {   // phát ngay bài vừa tải (người dùng vừa bấm -> trình duyệt cho phát)
                preview.pause();
                var pr = a.play();
                if (pr && pr.then) pr.then(function () { music.classList.add('playing'); }).catch(function () {});
              }
            });
          });
        });
        box.appendChild(row);
      });
      // Bài đang chọn có thể nằm dưới đáy danh sách (bị cắt): cuộn tới cho thấy.
      var on = box.querySelector('input:checked');
      if (on) {
        var row = on.closest('.ed-music-row');
        box.scrollTop = Math.max(0, row.offsetTop - box.offsetTop - (box.clientHeight - row.offsetHeight) / 2);
      }
    };
    AC.post(url('admin/content/music'), { action: 'list' }).then(draw);
    pop.querySelector('[data-m-up]').addEventListener('click', function () {
      pickFile('audio/mpeg,audio/mp4,audio/x-m4a,audio/ogg,.mp3,.m4a,.ogg').then(function (files) {
        if (!files.length) return;
        var btn = pop.querySelector('[data-m-up]');
        if (files[0].size > MUSIC_MAX) {   // chặn trước khi tải: đỡ chờ cả phút rồi mới bị từ chối
          return AC.toast(__('Bài "{name}" nặng {size} MB — tối đa 20 MB.', { name: files[0].name, size: (files[0].size / 1048576).toFixed(1) }), 'error');
        }
        btn.disabled = true;
        upload(url('admin/content/music'), { __field: 'music', action: 'upload' }, files[0], function (p) {
          btn.textContent = __('Đang tải… {pct}%', { pct: Math.round(p * 100) });
        }).then(function (res) {
          btn.disabled = false; btn.textContent = UP_LABEL;
          if (!res.ok) return fail(res);
          draw(res); setAudio(res.url); markDirty(); AC.toast(__('Đã thêm và chọn bài hát ✓'));
        });
      });
    });
  };
  var setAudio = function (src) {
    var a = music.querySelector('audio');
    if (!src) { if (a) { a.pause(); a.remove(); } music.classList.remove('playing'); return; }
    if (!a) { a = document.createElement('audio'); a.loop = true; a.preload = 'none'; music.appendChild(a); }
    if (a.getAttribute('src') !== src) a.src = src;
  };
  if (music) {
    music.querySelector('[data-music-toggle]').addEventListener('click', function (e) {
      e.stopPropagation(); e.preventDefault();
      openMusic();
    }, true);
  }
  // M1-OWNER-02: chạm ra ngoài popup (♫, ✨, ngày) -> đóng. Dùng pointerdown vì iOS Safari không phát click tới
  // document khi chạm vào chữ/ảnh thường (chromium thì có) — trước đây trên iPhone chỉ thoát được bằng cách chọn 1 bài.
  document.addEventListener('pointerdown', function (e) {
    var pop = document.querySelector('.ed-pop');
    if (pop && !pop.contains(e.target) && !e.target.closest('[data-edit-date],[data-music-toggle],[data-fx-toggle]')) { pop.remove(); preview.pause(); }
  });

  // ── 8b. Nút "✨ Hiệu ứng": máy tính = nút nổi ngay trên ♫; điện thoại = nút ✨ TRONG thanh sửa (R2-11) ──
  var openFx = function (e) {
    e.preventDefault(); e.stopPropagation();
    var mine = !!document.querySelector('.ed-pop-fx');
    closePops();
    if (mine) return;
    var pop = document.createElement('div');
    pop.className = 'ed-pop ed-pop-fx';
    pop.innerHTML = '<p><b>' + __('Hiệu ứng rơi trên trang') + '</b></p><div class="ed-fx-list" role="group" aria-label="' + __('Hiệu ứng') + '"></div>';
    var list = pop.querySelector('.ed-fx-list'), cur = curFx();
    Array.prototype.forEach.call(fxPick.options, function (o) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'btn btn-ghost btn-sm' + (o.value === cur ? ' on' : '');
      b.setAttribute('data-fx-opt', o.value);
      b.setAttribute('aria-pressed', o.value === cur ? 'true' : 'false');
      b.textContent = o.textContent;
      b.addEventListener('click', function () { pickFx(o.value); });
      list.appendChild(b);
    });
    document.body.appendChild(pop);
    placePop(pop);
  };
  if (music && fxPick) {
    var fxBtn = document.createElement('button');
    fxBtn.type = 'button';
    fxBtn.className = 'ed-fx-btn';
    fxBtn.setAttribute('data-fx-toggle', '');
    fxBtn.setAttribute('aria-label', __('Chọn hiệu ứng rơi'));
    fxBtn.innerHTML = '✨<span>' + __('Hiệu ứng') + '</span>';
    music.appendChild(fxBtn);
    fxBtn.addEventListener('click', openFx);
  }
  var fxBar = bar.querySelector('.ed-fx-bar');
  if (fxBar) {
    if (fxPick) fxBar.addEventListener('click', openFx); else fxBar.remove();
  }

  // ── 9. Cho khách xem (xuất bản) -> Gửi cho khách ─────────
  // Một nút chính đổi theo trạng thái: còn thay đổi -> "Cho khách xem"; đã xong -> "Gửi cho khách".
  // Audit P0 (a): trang còn thiếu tên / ngày cưới / ảnh chính (3 việc thiết yếu trong "Việc cần làm") -> hỏi trước khi cho khách
  // xem, nêu rõ còn thiếu gì. "Vẫn cho khách xem" -> xuất bản, nhớ trong phiên (không hỏi lại mỗi lần ở tab này).
  var ESSENTIAL = ['names', 'date', 'hero'];
  var missingEssentials = function () {
    return ESSENTIAL.map(function (k) { return bar.querySelector('[data-step="' + k + '"]'); })
      .filter(function (li) { return li && !li.classList.contains('ok'); })
      .map(function (li) { return li.textContent.trim(); });
  };
  function confirmPublish(missing, go) {
    var m = document.createElement('div');
    m.className = 'ed-modal ed-confirm';
    m.setAttribute('role', 'dialog'); m.setAttribute('aria-modal', 'true'); m.setAttribute('aria-labelledby', 'edc-h');
    m.innerHTML = '<div class="ed-modal-box"><h3 id="edc-h">' + __('Trang còn thiếu') + '</h3><p>' + __('Khách sẽ thấy trang chưa có:') + '</p><ul></ul>' +
      '<p class="small muted">' + __('Bạn có thể bổ sung sau rồi bấm "Cho khách xem" lần nữa.') + '</p>' +
      '<div class="btn-row ed-btns"><button type="button" class="btn btn-ghost btn-sm" data-later>' + __('Để sau') + '</button>' +
      '<button type="button" class="btn btn-accent btn-sm" data-go>' + __('Vẫn cho khách xem') + '</button></div></div>';
    var ul = m.querySelector('ul');
    missing.forEach(function (t) { var li = document.createElement('li'); li.textContent = t; ul.appendChild(li); });
    document.body.appendChild(m);
    m.querySelector('[data-later]').focus();
    var close = function () { m.remove(); document.removeEventListener('keydown', esc); publishBtn.focus(); };
    var esc = function (e) { if (e.key === 'Escape') close(); };
    document.addEventListener('keydown', esc);
    m.addEventListener('click', function (e) {
      if (e.target === m || e.target.closest('[data-later]')) return close();
      if (e.target.closest('[data-go]')) {
        try { sessionStorage.setItem('ac-pub-ok', '1'); } catch (e2) { /* bỏ qua */ }
        close(); go();
      }
    });
  }
  publishBtn.addEventListener('click', function () {
    if (bar.getAttribute('data-unpublished') !== '1') { showShare(false); return; }
    var missing = missingEssentials(), asked = false;
    try { asked = sessionStorage.getItem('ac-pub-ok') === '1'; } catch (e) { asked = false; }
    if (missing.length && !asked && !document.querySelector('.ed-confirm')) { confirmPublish(missing, doPublish); return; }
    doPublish();
  });
  function doPublish() {
    publishBtn.disabled = true;
    AC.post(url('admin/content/publish'), {}).then(function (r) {
      publishBtn.disabled = false;
      if (!r.ok) return fail(r);
      bar.setAttribute('data-unpublished', '0');
      bar.setAttribute('data-first', '0');
      stateEl.textContent = __('Khách đang xem bản này ✓');
      publishBtn.textContent = __('Gửi cho khách');
      publishBtn.classList.remove('pulse');
      applyChecklist(r.checklist);
      showShare(true);
    });
  }

  function showShare(justPublished) {
    var openedAt = Date.now();
    var link = bar.getAttribute('data-public-url');
    var m = document.createElement('div');
    m.className = 'ed-modal';
    m.setAttribute('role', 'dialog');
    m.setAttribute('aria-modal', 'true');
    m.innerHTML = '<div class="ed-modal-box ed-share-box"><h3></h3><p class="ed-share-lead"></p>' +
      '<button class="btn btn-accent ed-share-go" type="button" data-send>' + __('Gửi cho khách') + '</button>' +
      '<p class="small muted ed-share-note">' + __('Mở Zalo, Messenger, tin nhắn… — tin mời đã soạn sẵn.') + '</p>' +
      '<div class="qr"></div>' +
      '<p class="copy-row"><input readonly data-copy-src aria-label="' + __('Link trang cưới') + '"><button class="btn btn-ghost btn-sm" type="button" data-copy>' + __('Sao chép') + '</button></p>' +
      '<div class="btn-row center-row"><button class="btn btn-ghost btn-sm" type="button" data-dl>' + __('Tải mã QR') + '</button>' +
      '<a class="btn btn-ghost btn-sm" href="' + url('admin/guests') + '">' + __('Thiệp riêng từng khách') + '</a>' +
      '<button class="btn btn-ghost btn-sm" type="button" data-close>' + __('Đóng') + '</button></div>' +
      (bar.getAttribute('data-local') === '1' ? '<p class="small notice"></p>' : '') + '</div>';
    m.querySelector('h3').textContent = justPublished ? __('Khách đã xem được trang ♡') : __('Gửi link cho khách');
    m.querySelector('.ed-share-lead').textContent = justPublished ? __('Giờ gửi link cho mọi người nhé:') : __('Khách mở link này để xem trang cưới:');
    m.querySelector('input').value = link;
    document.body.appendChild(m);
    m.querySelector('[data-send]').focus();
    var canvas = qrCanvas(link);
    if (canvas) m.querySelector('.qr').appendChild(canvas);
    // Link cho khách ở xa có thể chưa sẵn: hỏi lại tới khi có rồi thay link + QR tại chỗ.
    if (bar.getAttribute('data-local') === '1') {
      var note = m.querySelector('.notice');
      if (note) note.textContent = '⏳ ' + __('Đang tạo link cho khách ở xa… (5–15 giây). Link hiện tại chỉ mở được trong mạng nhà.');
      var tries = 0;
      var poll = function () {
        if (!document.body.contains(m)) return;
        fetch(url('admin/domain/status'), { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
          if (r.ok && r.is_public) {
            link = r.public_url;
            bar.setAttribute('data-public-url', link); bar.setAttribute('data-local', '0');
            m.querySelector('input').value = link;
            var c2 = qrCanvas(link);
            m.querySelector('.qr').innerHTML = '';
            if (c2) { m.querySelector('.qr').appendChild(c2); canvas = c2; }
            if (note) { note.className = 'small'; note.textContent = '✓ ' + __('Link cho khách ở xa đã sẵn sàng'); }
          } else if (r.mode !== 'off' && ++tries < 30) setTimeout(poll, 3000);
          else if (note) note.innerHTML = __('Chưa có link cho khách ở xa. Vào <a href="{url}">Link &amp; mã QR</a> để bật.', { url: url('admin/share') });
        }).catch(function () {});
      };
      poll();
    }
    var close = function () { m.remove(); document.removeEventListener('keydown', esc); publishBtn.focus(); };
    var esc = function (e) { if (e.key === 'Escape') close(); };
    document.addEventListener('keydown', esc);
    m.addEventListener('click', function (e) {
      // Bấm đúp "Cho khách xem": cú bấm thứ 2 rơi vào nền hộp vừa mở -> bỏ qua 400 ms đầu, không đóng ngay.
      if (e.target === m && Date.now() - openedAt < 400) return;
      if (e.target === m || e.target.closest('[data-close]')) return close();
      if (e.target.closest('[data-send]')) AC.share({ url: link, text: bar.getAttribute('data-share-text'), title: __('Gửi link trang cưới') });
      if (e.target.closest('[data-dl]') && canvas) {
        var a = document.createElement('a'); a.download = __('qr-trang-cuoi.png'); a.href = canvas.toDataURL('image/png'); a.click();
      }
    });
  }

  function qrCanvas(text) {
    if (typeof window.qrcode !== 'function') return null;
    var qr = window.qrcode(0, 'M');
    qr.addData(text, 'Byte'); qr.make();
    var n = qr.getModuleCount(), cell = 10, margin = 2, size = (n + margin * 2) * cell;
    var c = document.createElement('canvas');
    c.width = c.height = size;
    var ctx = c.getContext('2d');
    ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, size, size); ctx.fillStyle = '#231a1a';
    for (var r = 0; r < n; r++) for (var col = 0; col < n; col++) if (qr.isDark(r, col)) ctx.fillRect((col + margin) * cell, (r + margin) * cell, cell, cell);
    return c;
  }
})();
