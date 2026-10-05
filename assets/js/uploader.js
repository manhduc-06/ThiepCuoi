/* Ảnh Cưới — hàng đợi tải ảnh cho khách mời ([data-guest-upload]) và chủ nhà ([data-owner-upload]).
 *
 * - Gửi TỪNG ảnh một request (XHR để có tiến độ): không chạm post_max_size, rớt mạng chỉ mất 1 ảnh.
 * - Khách mời: trình duyệt thu nhỏ ảnh về cạnh dài GUEST_MAX_PX trước khi gửi — nhẹ cho 4G ở tiệc
 *   và cho máy nhà. Chủ nhà: gửi nguyên bản gốc.
 * - Ảnh lỗi có nút "Thử lại"; đang tải mà đóng trang thì trình duyệt hỏi lại.
 */
(function () {
  'use strict';
  var __ = window.__ || function (s) { return s; };   // i18n: chuỗi Việt -> tiếng Anh (application/language/en/ui_js_*.php)
  if (window.ACUploader) return;

  var GUEST_MAX_PX = 3000;
  var GUEST_QUALITY = 0.88;

  function fmtSize(b) {
    if (b < 1024) return b + ' B';                         // file lỗi vài byte: ghi đúng số byte, không làm tròn thành "1 KB"
    return b > 1048576 ? (b / 1048576).toFixed(1).replace('.', (window.AC_LANG === 'vi' || window.AC_LANG === 'fr') ? ',' : '.') + ' MB' : Math.round(b / 1024) + ' KB';
  }

  /** Thay ô thu nhỏ bằng biểu tượng ⚠ (file không phải ảnh / hỏng: không để hình ảnh vỡ). */
  function badThumb(li) {
    var old = li.querySelector('.up-thumb');
    if (!old || old.tagName !== 'IMG') return;
    var w = document.createElement('span');
    w.className = 'up-thumb up-thumb-bad';
    w.setAttribute('aria-hidden', 'true');
    w.textContent = '⚠';
    w.style.display = 'grid'; w.style.placeItems = 'center'; w.style.fontSize = '22px'; w.style.color = '#b3261e';
    if (old.src.indexOf('blob:') === 0) URL.revokeObjectURL(old.src);
    old.parentNode.replaceChild(w, old);
  }

  /** Thu nhỏ ảnh (giữ đúng chiều xoay EXIF — trình duyệt hiện đại tự áp khi vẽ <img>). */
  function shrink(file) {
    return new Promise(function (resolve) {
      if (!/^image\/(jpeg|png|webp)$/.test(file.type)) return resolve(file);
      var url = URL.createObjectURL(file);
      var im = new Image();
      im.onload = function () {
        var w = im.naturalWidth, h = im.naturalHeight, r = Math.min(1, GUEST_MAX_PX / Math.max(w, h));
        if (r === 1 && file.size < 4 * 1048576) { URL.revokeObjectURL(url); return resolve(file); }
        var c = document.createElement('canvas');
        c.width = Math.round(w * r); c.height = Math.round(h * r);
        var ctx = c.getContext('2d');
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
        ctx.drawImage(im, 0, 0, c.width, c.height);
        URL.revokeObjectURL(url);
        c.toBlob(function (blob) {
          if (!blob || blob.size >= file.size) return resolve(file);
          resolve(new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }));
        }, 'image/jpeg', GUEST_QUALITY);
      };
      im.onerror = function () { URL.revokeObjectURL(url); resolve(file); };
      im.src = url;
    });
  }

  function Uploader(root, opts) {
    this.root = root;
    this.opts = opts;
    this.list = root.querySelector('[data-list]');
    this.summary = root.querySelector('[data-summary]');
    this.queue = [];
    this.active = 0;
    this.stats = { total: 0, ok: 0, fail: 0, retry: 0 };
    var self = this;
    var input = root.querySelector('[data-files]');
    var drop = root.querySelector('[data-drop]');
    input.addEventListener('change', function () { self.add(input.files); input.value = ''; });
    ['dragenter', 'dragover'].forEach(function (ev) {
      drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-over'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      drop.addEventListener(ev, function () { drop.classList.remove('is-over'); });
    });
    drop.addEventListener('drop', function (e) { e.preventDefault(); self.add(e.dataTransfer.files); });
    window.addEventListener('beforeunload', function (e) {
      if (self.active || self.queue.length) { e.preventDefault(); e.returnValue = ''; }
    });
  }

  Uploader.prototype.add = function (files) {
    var self = this, skipped = 0;
    Array.prototype.forEach.call(files, function (f) {
      var li = document.createElement('li');
      li.className = 'up-item';
      li.innerHTML = '<img class="up-thumb" alt=""><div><div class="up-name"></div><div class="up-bar"><i></i></div></div><span class="up-state">' + __('Chờ…') + '</span>';
      li.querySelector('.up-name').textContent = f.name + ' · ' + fmtSize(f.size);
      self.list.insertBefore(li, self.list.firstChild);
      // S1-DESK-07: file không phải ảnh (.txt, .pdf, .mp4…) kéo-thả vào: không bỏ qua im lặng — dòng ⚠ trong danh sách + toast,
      // không tính vào tổng ảnh gửi.
      if (f.type && f.type.indexOf('image/') !== 0) {
        li.classList.add('fail');
        badThumb(li);
        li.querySelector('.up-bar').remove();
        li.querySelector('.up-state').textContent = __('Không phải ảnh, đã bỏ qua');
        skipped++;
        return;
      }
      var job = { file: f, li: li };
      if (f.size < 15 * 1048576 && /^image\/(jpeg|png|webp|gif)$/.test(f.type)) {
        var u = URL.createObjectURL(f), th = li.querySelector('.up-thumb');
        th.onload = function () { URL.revokeObjectURL(u); };
        th.onerror = function () { badThumb(li); };
        th.src = u;
      }
      self.queue.push(job);
      self.stats.total++;
    });
    if (skipped && window.AC && window.AC.toast) window.AC.toast(__('Chỉ nhận ảnh JPG/PNG/WebP'), 'error');
    this.render();
    this.pump();
  };

  Uploader.prototype.render = function () {
    var s = this.stats, left = s.total - s.ok - s.fail, tail = this.opts.doneText ? this.opts.doneText(s) : '';
    this.summary.hidden = s.total === 0;
    this.summary.textContent = left > 0
      ? __('Đang gửi {i} / {n} ảnh…', { i: s.ok + s.fail + 1, n: s.total })
      : __('Xong: {n} ảnh', { n: s.ok }) + (s.fail ? ' · ' + __('{n} ảnh lỗi', { n: s.fail }) + (s.retry ? ' ' + __('(bấm Thử lại ở ảnh lỗi mạng)') : '') : '')
        + (tail ? ' — ' + tail : '');
  };

  Uploader.prototype.pump = function () {
    while (this.active < this.opts.concurrency && this.queue.length) {
      this.send(this.queue.shift());
    }
  };

  Uploader.prototype.send = function (job) {
    var self = this, li = job.li, bar = li.querySelector('.up-bar i'), state = li.querySelector('.up-state');
    this.active++;
    li.className = 'up-item';
    bar.style.width = '0';
    state.textContent = this.opts.shrink ? __('Đang nén…') : '0%';
    var prep = this.opts.shrink ? shrink(job.file) : Promise.resolve(job.file);

    prep.then(function (file) {
      if (file.size > self.opts.maxBytes) {
        return finish(false, __('Quá {size}', { size: fmtSize(self.opts.maxBytes) }), false);
      }
      var fd = new FormData();
      fd.append('photo', file, file.name);
      var extra = self.opts.fields();
      Object.keys(extra).forEach(function (k) { fd.append(k, extra[k]); });
      fd.append(window.AC.csrfName, window.AC.csrfHash);
      var xhr = new XMLHttpRequest();
      xhr.open('POST', self.opts.endpoint);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.upload.onprogress = function (e) {
        if (!e.lengthComputable) return;
        var p = Math.round(e.loaded / e.total * 100);
        bar.style.width = p + '%';
        state.textContent = p < 100 ? p + '%' : __('Đang xử lý…');
      };
      xhr.onload = function () {
        var res = null;
        try { res = JSON.parse(xhr.responseText); } catch (e) { /* không phải JSON */ }
        if (res && res.ok) return finish(true, res);
        // 403 không phải JSON = token CSRF hết hạn: lấy token mới rồi gửi lại 1 lần (như AC.post, R2-08b).
        if (xhr.status === 403 && !res && !job.csrfRetry && window.AC.refreshCsrf) {
          job.csrfRetry = true;
          self.active--;
          return window.AC.refreshCsrf().then(function () { self.send(job); });
        }
        // 413 (S1-SEC-01): index.php trả JSON {code:'too_large', max_mb} trước cả CSRF -> câu dịch được theo ngôn ngữ trang.
        var msg = res && res.code === 'too_large' && res.max_mb ? __('Ảnh quá lớn so với giới hạn máy chủ (tối đa {max} MB)', { max: res.max_mb })
          : res && res.error ? res.error
          : xhr.status === 403 ? __('Trang đã mở quá lâu, hãy tải lại trang')
          : xhr.status === 413 ? __('Ảnh quá lớn so với máy chủ')
          : __('Lỗi máy chủ {code}', { code: xhr.status });
        // Lỗi định dạng/kích thước (4xx) gửi lại vẫn lỗi y như cũ -> không có nút Thử lại.
        finish(false, msg, xhr.status >= 500 || xhr.status === 0 || xhr.status === 429 || xhr.status === 408);
      };
      xhr.onerror = function () { finish(false, __('Mất mạng'), true); };
      xhr.send(fd);
    });

    function finish(ok, info, retryable) {
      self.active--;
      if (ok) {
        self.stats.ok++;
        li.classList.add('ok');
        bar.style.width = '100%';
        state.textContent = self.opts.okText(info);
        if (self.opts.onDone) self.opts.onDone(info);
      } else {
        self.stats.fail++;
        li.classList.add('fail');
        if (retryable !== true) badThumb(li);           // lỗi 4xx: file hỏng/không nhận -> ⚠ thay cho thu nhỏ
        bar.style.width = '100%';
        state.textContent = info + ' ';
        if (retryable === true) {
          self.stats.retry++;
          var b = document.createElement('button');
          b.type = 'button'; b.className = 'up-retry'; b.textContent = __('Thử lại');
          b.addEventListener('click', function () { self.stats.fail--; self.stats.retry--; b.remove(); self.queue.push(job); self.render(); self.pump(); });
          state.appendChild(b);
        }
      }
      self.render();
      if (retryable === true) { setTimeout(function () { self.pump(); }, 1500); } else { self.pump(); }
    }
  };

  window.ACUploader = Uploader;

  // ── Khách mời ───────────────────────────────────────────────
  var g = document.querySelector('[data-guest-upload]');
  if (g) {
    new Uploader(g, {
      endpoint: g.getAttribute('data-endpoint'),
      maxBytes: parseInt(g.getAttribute('data-max-mb'), 10) * 1048576,
      concurrency: 2,
      shrink: true,
      fields: function () {
        return { album: g.getAttribute('data-album'), guest_name: g.elements.guest_name.value, guest_message: g.elements.guest_message.value };
      },
      okText: function (res) { return res.pending ? __('Đã gửi ✓') : __('Đã đăng ✓'); },
      // loop t1: có duyệt -> nói rõ ảnh chưa vào album ngay (khách khỏi vào album tìm không thấy).
      doneText: function (s) { return s.ok ? (g.getAttribute('data-approval') === '1' ? __('cảm ơn bạn rất nhiều ♡ Cô dâu chú rể xem qua rồi mới đưa vào album nhé.') : __('cảm ơn bạn rất nhiều ♡')) : ''; }
    });
  }

  // ── Chủ nhà (trang chi tiết album) ─────────────────────────
  var o = document.querySelector('[data-owner-upload]');
  if (o) {
    var grid = document.querySelector('[data-photo-grid]');
    new Uploader(o, {
      endpoint: o.getAttribute('data-endpoint'),
      maxBytes: parseInt(o.getAttribute('data-max-mb'), 10) * 1048576,
      concurrency: 2,
      shrink: false,
      fields: function () { return { album_id: o.getAttribute('data-album-id') }; },
      okText: function () { return __('Xong ✓'); },
      onDone: function (res) {
        var empty = document.querySelector('[data-empty]');
        if (empty) empty.remove();
        if (grid && window.ACAdmin) grid.appendChild(window.ACAdmin.photoTile(res.photo));
      }
    });
  }
})();
