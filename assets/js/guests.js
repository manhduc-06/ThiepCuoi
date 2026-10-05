/* Ảnh Cưới — Khách mời: chọn nhanh xưng hô + xem trước lời mời, "Lên danh sách nhanh", sửa danh sách xưng hô.
   Dữ liệu: <script id="sal-data"> {list: [{s, t}], common, couple, date}. Thứ tự lời mời giống máy chủ
   (Invite_model::invite_text): lời riêng -> mẫu của xưng hô -> mẫu chung. */
(function () {
  'use strict';
  var dataEl = document.getElementById('sal-data');
  if (!dataEl) return;
  var D;
  try { D = JSON.parse(dataEl.textContent); } catch (e) { return; }

  var norm = function (s) { return String(s || '').replace(/\s+/g, ' ').trim(); };
  var key = function (s) { return norm(s).toLowerCase(); };
  var find = function (sal) {
    var k = key(sal);
    if (!k) return null;
    for (var i = 0; i < D.list.length; i++) if (key(D.list[i].s) === k) return D.list[i];
    return null;
  };
  /** Mẫu áp cho xưng hô: {tpl, label} */
  var tplFor = function (sal) {
    var it = find(sal);
    return it && it.t ? { tpl: it.t, label: window.__('mẫu “{s}”', { s: it.s }) } : { tpl: D.common, label: window.__('mẫu chung') };
  };
  /** Giống Invite_model::fill_template() */
  var fill = function (tpl, sal, name) {
    sal = norm(sal);
    var lc = sal ? sal.charAt(0).toLowerCase() + sal.slice(1) : '';
    var out = String(tpl || '').split('{xung_ho}').join(lc).split('{ten}').join(norm(name))
      .split('{cap_doi}').join(D.couple || '').split('{ngay}').join(D.date || '');
    out = out.replace(/[ \t]{2,}/g, ' ').trim().replace(/ +([,.!?])/g, '$1');
    return out ? out.charAt(0).toUpperCase() + out.slice(1) : '';
  };
  /** "cô chú Lan Hùng" -> ['Cô chú', 'Lan Hùng'] theo danh sách (dài trước ngắn sau). */
  var sorted = D.list.map(function (x) { return x.s; }).sort(function (a, b) { return b.length - a.length; });
  var split = function (text) {
    text = norm(text);
    var low = text.toLowerCase();
    for (var i = 0; i < sorted.length; i++) {
      var s = sorted[i];
      if (low.indexOf(s.toLowerCase() + ' ') === 0 && text.slice(s.length).trim()) return [s, text.slice(s.length).trim()];
    }
    return ['', text];
  };
  var el = function (tag, cls, text) {
    var x = document.createElement(tag);
    if (cls) x.className = cls;
    if (text != null) x.textContent = text;
    return x;
  };

  // ── Form thêm/sửa 1 khách: nút chọn nhanh xưng hô + xem trước ──
  var initForm = function (f) {
    if (f.getAttribute('data-sal-ready')) return;
    f.setAttribute('data-sal-ready', '1');
    var salIn = f.querySelector('[data-sal-input]'), nameIn = f.querySelector('[data-name-input]');
    var own = f.querySelector('[data-own-text]'), hint = f.querySelector('[data-own-hint]');
    var prev = f.querySelector('[data-inv-preview]'), chips = f.querySelector('[data-sal-chips]');
    if (!salIn || !nameIn) return;
    var isNew = !f.hasAttribute('data-id');
    if (chips) {
      D.list.forEach(function (it) {
        var b = el('button', 'sal-chip', it.s);
        b.type = 'button';
        b.setAttribute('aria-pressed', 'false');
        b.addEventListener('click', function () {
          salIn.value = key(salIn.value) === key(it.s) ? '' : it.s;
          salIn.dispatchEvent(new Event('input', { bubbles: true }));   // gợi ý link riêng (admin.js) cập nhật theo
          if (!nameIn.value) nameIn.focus();
        });
        chips.appendChild(b);
      });
    }
    var update = function () {
      var t = tplFor(salIn.value);
      if (chips) Array.prototype.forEach.call(chips.children, function (b) {
        var on = key(b.textContent) === key(salIn.value);
        b.classList.toggle('on', on);
        b.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      if (own) own.placeholder = fill(t.tpl, salIn.value, nameIn.value || '…');
      if (hint) hint.textContent = window.__('(trống = dùng {src})', { src: t.label });
      if (!prev) return;
      var hasOwn = own && own.value.trim();
      if (isNew && !nameIn.value.trim()) { prev.hidden = true; return; }
      prev.hidden = false;
      prev.textContent = '';
      prev.appendChild(el('span', 'inv-src', window.__('Thiệp sẽ ghi · {src}', { src: hasOwn ? window.__('lời mời riêng') : t.label })));
      prev.appendChild(document.createTextNode(fill(hasOwn ? own.value : t.tpl, salIn.value, nameIn.value)));
    };
    [salIn, nameIn, own].forEach(function (x) { if (x) x.addEventListener('input', update); });
    update();
  };
  var initForms = function (root) {
    root.querySelectorAll('[data-sal-form]').forEach(function (f) {
      var d = f.closest('details:not([open])');
      if (!d) return initForm(f);
      d.addEventListener('toggle', function () { if (d.open) initForm(f); });
    });
  };
  initForms(document);
  // Form sửa khách tải khi mở (admin.js, R2-12) -> gắn nút chọn xưng hô + xem trước cho phần vừa chèn.
  document.addEventListener('ac:dom', function (e) { initForms(e.target); });

  // ── Lên danh sách nhanh ──
  var bulk = document.querySelector('.bulk');
  if (bulk) {
    var tForm = bulk.querySelector('[data-bulk-table]'), xForm = bulk.querySelector('[data-bulk-text]');
    var rows = bulk.querySelector('[data-bulk-rows]'), tpl = bulk.querySelector('[data-bulk-tpl]');
    var submit = bulk.querySelector('[data-bulk-submit]');
    bulk.querySelectorAll('[data-bulk-mode]').forEach(function (b) {
      b.addEventListener('click', function () {
        var text = b.getAttribute('data-bulk-mode') === 'text';
        bulk.querySelectorAll('[data-bulk-mode]').forEach(function (x) { x.setAttribute('aria-selected', x === b ? 'true' : 'false'); });
        tForm.hidden = text; xForm.hidden = !text;
        (text ? xForm.querySelector('textarea') : rows.querySelector('[data-bulk-name]')).focus();
      });
    });
    var rowUpdate = function (r) {
      var sal = r.querySelector('[data-bulk-sal]').value, name = r.querySelector('[data-bulk-name]').value;
      r.querySelector('[data-bulk-own]').placeholder = name.trim()
        ? fill(tplFor(sal).tpl, sal, name) : window.__('Lời mời riêng (trống = dùng mẫu)');
    };
    var count = function () {
      var n = Array.prototype.filter.call(rows.querySelectorAll('[data-bulk-name]'), function (x) { return x.value.trim(); }).length;
      submit.textContent = n ? window.__('Tạo {n} thiệp mời', { n: n }) : window.__('Tạo thiệp mời');
      return n;
    };
    var addRow = function (after) {
      var r = tpl.content.firstElementChild.cloneNode(true);
      if (after) {
        r.querySelector('select[name="rows_side[]"]').value = after.querySelector('select[name="rows_side[]"]').value;
        rows.insertBefore(r, after.nextSibling);
      } else rows.appendChild(r);
      r.querySelector('[data-bulk-name]').focus();
      return r;
    };
    bulk.querySelector('[data-bulk-add]').addEventListener('click', function () { addRow(rows.lastElementChild); });
    rows.addEventListener('input', function (e) { var r = e.target.closest('[data-bulk-row]'); if (r) rowUpdate(r); count(); });
    rows.addEventListener('change', function (e) { var r = e.target.closest('[data-bulk-row]'); if (r) rowUpdate(r); });
    rows.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter' || e.isComposing || e.target.tagName !== 'INPUT') return;
      e.preventDefault();
      var r = e.target.closest('[data-bulk-row]');
      var next = r.nextElementSibling;
      if (next && !next.querySelector('[data-bulk-name]').value) next.querySelector('[data-bulk-name]').focus();
      else addRow(r);
    });
    // Gõ "Cô chú Lan Hùng" vào ô tên -> tự chọn xưng hô "Cô chú", tên "Lan Hùng".
    rows.addEventListener('focusout', function (e) {
      if (!e.target.matches('[data-bulk-name]')) return;
      var r = e.target.closest('[data-bulk-row]'), sel = r.querySelector('[data-bulk-sal]');
      if (sel.value) return;
      var p = split(e.target.value);
      if (p[0]) { sel.value = p[0]; e.target.value = p[1]; rowUpdate(r); }
    });
    rows.addEventListener('click', function (e) {
      if (!e.target.closest('[data-bulk-del]')) return;
      var r = e.target.closest('[data-bulk-row]');
      if (rows.children.length > 1) r.remove();
      else r.querySelectorAll('input').forEach(function (x) { x.value = ''; });
      count();
    });
    // Dán cả cột tên (Excel, Google Sheets, Zalo…) vào ô Tên -> mỗi dòng 1 khách.
    // Có tab thì chia cột theo thứ tự Xưng hô / Tên / Bên; không có tab thì tự tách xưng hô.
    var setSal = function (sel, sal) {
      sal = norm(sal);
      if (!sal) { sel.value = ''; return; }
      var hit = Array.prototype.filter.call(sel.options, function (o) { return key(o.value) === key(sal); })[0];
      if (!hit) { hit = new Option(sal, sal); sel.appendChild(hit); }   // xưng hô ngoài danh sách: máy chủ vẫn nhận
      sel.value = hit.value;
    };
    // Bỏ dấu tiếng Việt để so khớp tiêu đề cột / tên bên ("Nhà Gái" = "nha gai").
    var plain = function (t) { return key(t).replace(/đ/g, 'd').normalize('NFD').replace(/[̀-ͯ]/g, ''); };
    var SIDE_WORDS = { 'nha trai': 'groom', 'ben trai': 'groom', 'trai': 'groom', 'groom': 'groom',
      'nha gai': 'bride', 'ben gai': 'bride', 'gai': 'bride', 'bride': 'bride',
      'chung': '', 'khach chung': '', 'ca hai': '', 'ca hai ben': '' };
    /** Ô là tên "bên" -> 'groom' | 'bride' | '' (chung); không phải -> null. */
    var sideOf = function (t) { var k = plain(t); return Object.prototype.hasOwnProperty.call(SIDE_WORDS, k) ? SIDE_WORDS[k] : null; };
    var HEADERS = ['stt', 'ten', 'ho ten', 'ho va ten', 'ten khach', 'khach', 'xung ho', 'sdt', 'so dien thoai', 'dien thoai',
      'dt', 'phone', 'ben', 'khach ben', 'nha', 'ghi chu', 'loi moi', 'loi moi rieng', 'name', 'so nguoi', 'so khach',
      'so luong', 'sl', 'note', 'guests', 'side', 'salutation', 'full name'];
    var isHeader = function (line) {
      var cells = line.split('\t').map(plain).filter(Boolean);
      return cells.length > 0 && cells.every(function (c) { return HEADERS.indexOf(c) > -1; });
    };
    // Số điện thoại: chỉ số / khoảng trắng / + . - ( ), có ≥ 8 chữ số. Số nhỏ (cột STT) thì bỏ qua.
    var digits = function (t) { return (String(t).match(/\d/g) || []).length; };
    var isPhone = function (t) { return /^[+\d\s.\-()]+$/.test(t) && digits(t) >= 8; };
    var isNumberOnly = function (t) { return /^[+\d\s.\-()]+$/.test(t); };
    // Xưng hô quen thuộc (giống Invite_model::SALUTATIONS) + danh sách của chủ nhà.
    var FAMILIAR = ['Ông bà', 'Cô chú', 'Anh chị', 'Gia đình', 'Vợ chồng', 'Anh', 'Chị', 'Em', 'Bạn', 'Cô', 'Chú', 'Bác', 'Ông', 'Bà',
      'Cậu', 'Mợ', 'Dì', 'Dượng', 'Thím', 'Thầy', 'Cháu'];
    var isSal = function (t) {
      var k = key(t);
      return !!k && (sorted.some(function (s) { return key(s) === k; }) || FAMILIAR.some(function (s) { return key(s) === k; }));
    };
    /** Tên dính số điện thoại ở cuối ("Chị Hà 0987654321") -> ['Chị Hà', '0987654321']. */
    var cutPhone = function (t) {
      var m = norm(t).match(/^(.*?)[\s,;:|\-–]*(\+?\d[\d .\-()]{6,}\d)$/);
      return m && digits(m[2]) >= 8 && m[1].trim() ? [m[1].trim(), m[2]] : [norm(t), ''];
    };
    /**
     * Văn bản dán từ Excel/Sheets -> mảng dòng, mỗi dòng mảng ô. Theo luật TSV của Excel (R3-15): ô bắt đầu bằng "
     * là ô có ngoặc kép, "" bên trong = ", xuống dòng/tab bên trong ngoặc thuộc về ô (đổi thành khoảng trắng).
     */
    var parseTSV = function (text) {
      var out = [], row = [], cell = '', i = 0, n = text.length, c;
      while (i < n) {
        c = text.charAt(i);
        if (cell === '' && c === '"') {
          var j = i + 1, buf = '', closed = false;
          while (j < n) {
            var d = text.charAt(j);
            if (d === '"') {
              if (text.charAt(j + 1) === '"') { buf += '"'; j += 2; continue; }
              closed = true; j++; break;
            }
            buf += d; j++;
          }
          var nx = text.charAt(j);
          if (closed && (j >= n || nx === '\t' || nx === '\n')) { cell = buf.replace(/\s+/g, ' '); i = j; continue; }
          // Không đúng dạng ô ngoặc kép (vd tên có dấu " ở đầu): coi " là chữ thường.
          cell += c; i++; continue;
        }
        if (c === '\t') { row.push(cell); cell = ''; }
        else if (c === '\n') { row.push(cell); out.push(row); row = []; cell = ''; }
        else cell += c;
        i++;
      }
      row.push(cell); out.push(row);
      return out.map(function (r) { return r.map(norm); }).filter(function (r) { return r.some(Boolean); });
    };
    /**
     * 1 dòng dán vào (mảng ô) -> {sal, name, side, phone, guests, note, noteCols}. Nhận diện TỪNG Ô theo nội dung:
     * SĐT (≥ 8 chữ số) · Bên (Nhà trai / Nhà gái / Chung) · xưng hô (chỉ khi ô đúng bằng 1 xưng hô đã biết) · Tên ·
     * số nguyên 1–20 SAU tên = Số người (trước tên = cột STT, bỏ qua) · ô chữ còn lại -> Ghi chú (nối " · ").
     * "Nguyễn Văn An ⇥ 0912345678 ⇥ Bạn đại học" -> tên + SĐT + ghi chú; "Anh Tuấn ⇥ 2" -> tối đa 2 người.
     */
    var parseLine = function (cells) {
      var o = { sal: '', name: '', side: null, phone: '', phoneCol: -1, guests: '', note: [], noteCols: [] };
      cells.forEach(function (c, ci) {
        var sd;
        if (!c) return;
        if (!o.phone && isPhone(c)) { o.phone = c; o.phoneCol = ci; }
        else if (/^\d{1,2}$/.test(c) && +c >= 1 && +c <= 20 && o.name && !o.guests) o.guests = String(+c);
        else if (isNumberOnly(c)) { /* STT / số lạ: bỏ qua, không bao giờ thành tên */ }
        else if (o.side === null && (sd = sideOf(c)) !== null) o.side = sd;
        else if (!o.sal && !o.name && isSal(c)) o.sal = c;
        else if (!o.name) o.name = c;
        else { o.note.push(c); o.noteCols.push(ci); }
      });
      if (o.name && !o.phone) { var cp = cutPhone(o.name); o.name = cp[0]; o.phone = cp[1]; }
      if (!o.sal && o.name) { var p = split(o.name); o.sal = p[0]; o.name = p[1]; }
      o.note = o.note.join(' · ').slice(0, 200);
      return o;
    };
    var fillRow = function (r, cells) {
      var o = parseLine(cells);
      setSal(r.querySelector('[data-bulk-sal]'), o.sal);
      r.querySelector('[data-bulk-name]').value = o.name;
      if (o.side !== null) r.querySelector('[data-bulk-side]').value = o.side;
      var put = function (sel, v) { var x = r.querySelector(sel); if (x) x.value = v; };
      put('[data-bulk-phone]', o.phone);
      put('[data-bulk-guests]', o.guests);
      put('[data-bulk-note]', o.note);
      var tip = [];
      if (o.phone) tip.push(window.__('SĐT: {v}', { v: o.phone }));
      if (o.guests) tip.push(window.__('Tối đa {n} người', { n: o.guests }));
      if (o.note) tip.push(window.__('Ghi chú: {v}', { v: o.note }));
      r.querySelector('[data-bulk-name]').title = tip.join(' · ');
      rowUpdate(r);
      return o;
    };
    rows.addEventListener('paste', function (e) {
      if (!e.target.matches('[data-bulk-name]')) return;
      var text = ((e.clipboardData || window.clipboardData).getData('text') || '').replace(/\r\n?/g, '\n');
      if (!/[\n\t]/.test(text.trim())) return;   // 1 dòng thường: để trình duyệt dán như bình thường
      e.preventDefault();
      var lines = parseTSV(text.replace(/^\n+|\n+$/g, ''));
      var head = lines.length && isHeader(lines[0].join('\t')) ? lines.shift() : null;
      if (!lines.length) { AC.toast(window.__('Chỉ có dòng tiêu đề, chưa có khách nào.'), 'error'); return; }
      var r = e.target.closest('[data-bulk-row]'), phones = 0, guests = 0, noteCols = {}, phoneCols = {};
      lines.forEach(function (cells, i) {
        if (i > 0) {
          var next = r.nextElementSibling;
          r = (next && !next.querySelector('[data-bulk-name]').value.trim()) ? next : addRow(r);
        }
        var o = fillRow(r, cells);
        if (o.phone) phones++;
        if (o.phoneCol > -1) phoneCols[o.phoneCol] = true;
        if (o.guests) guests++;
        o.noteCols.forEach(function (ci) { noteCols[ci] = (noteCols[ci] || 0) + 1; });
      });
      r.querySelector('[data-bulk-name]').focus();
      count();
      // Cột đưa vào Ghi chú: gọi theo tiêu đề nếu có, không thì "cột 3". R5-21: ô của cột SĐT (theo tiêu đề, hoặc cột
      // mà dòng khác có SĐT) không hợp lệ -> báo "N ô SĐT không hợp lệ…", không nói cả cột SĐT vào Ghi chú.
      var badPhones = 0, colNames = [];
      Object.keys(noteCols).forEach(function (ci) {
        var isPhoneCol = phoneCols[ci] || (head && head[ci] && /^(sdt|so dien thoai|dien thoai|dt|phone)$/.test(plain(head[ci])));
        if (isPhoneCol) badPhones += noteCols[ci];
        else colNames.push(head && head[ci] ? '“' + head[ci] + '”' : (+ci + 1));
      });
      var noteMsg = (badPhones ? ' · ' + window.__('{n} ô SĐT không hợp lệ đã chuyển vào Ghi chú', { n: badPhones }) : '') +
        (colNames.length ? ' · ' + window.__('đã đưa cột {cols} vào Ghi chú', { cols: colNames.join(', ') }) : '');
      var total = count(), max = parseInt(tForm.getAttribute('data-bulk-max'), 10) || 500;
      var lotMsg = total > max ? ' ' + window.__('Bảng có {n} dòng — sẽ tạo trong {k} lượt.', { n: total, k: Math.ceil(total / max) }) : '';
      AC.toast(window.__('Đã dán {n} dòng', { n: lines.length }) + (head ? ' ' + window.__('(bỏ dòng tiêu đề)') : '') +
        (phones ? ' · ' + window.__('{n} SĐT lưu riêng, không vào tên/link', { n: phones }) : '') +
        (guests ? ' · ' + window.__('{n} dòng có số người tối đa', { n: guests }) : '') + noteMsg + '.' + lotMsg);
    });
    var jsonIn = tForm.querySelector('[data-bulk-json]');
    var unlock = function () {
      tForm.querySelectorAll('[data-bulk-rows] input, [data-bulk-rows] select, [data-bulk-rows] button').forEach(function (x) { x.disabled = false; });
      if (jsonIn) jsonIn.disabled = true;
      submit.disabled = false;
      count();
    };
    window.addEventListener('pageshow', function (e) { if (e.persisted) unlock(); });
    var lockRows = function () {
      tForm.querySelectorAll('[data-bulk-rows] input, [data-bulk-rows] select, [data-bulk-rows] button').forEach(function (x) { x.disabled = true; });
      submit.disabled = true;
    };
    /**
     * D45 (R5-21): bảng > max dòng -> tự chia lô max dòng, gửi TUẦN TỰ (AJAX, máy chủ vẫn giới hạn max/lượt).
     * Lô lỗi -> dừng, gỡ các dòng đã tạo khỏi bảng, các dòng còn lại giữ nguyên để bấm Tạo thử lại.
     * Xong hết -> tải lại trang (máy chủ đã đặt câu báo cộng dồn mọi lượt).
     */
    var sendLots = function (data, rowEls, max) {
      var lots = [];
      for (var i = 0; i < data.length; i += max) lots.push(i);
      var created = 0, received = 0, sent = 0;
      lockRows();
      var fail = function (k, err) {
        rowEls.slice(0, sent).forEach(function (r) { r.remove(); });
        if (!rows.querySelector('[data-bulk-row]')) addRow(null);
        unlock();
        var left = data.length - sent;
        AC.toast((created ? window.__('Đã tạo {n}.', { n: created }) : window.__('Chưa tạo được lượt {k}/{m}.', { k: k + 1, m: lots.length })) + ' ' +
          window.__('{n} dòng còn lại vẫn ở bảng, bấm Tạo để thử lại.', { n: left }) + (err ? ' (' + err + ')' : ''), 'error');
      };
      var step = function (k) {
        if (k >= lots.length) { location.href = AC.baseUrl + 'admin/guests'; return; }
        var part = data.slice(lots[k], lots[k] + max);
        submit.textContent = window.__('Lượt {k}/{m} — đang tạo {n} thiệp mời…', { k: k + 1, m: lots.length, n: part.length });
        AC.post(AC.baseUrl + 'admin/guests/add', { rows_json: JSON.stringify(part), prev_created: created, prev_received: received })
          .then(function (r) {
            if (!r || !r.ok) { fail(k, r && r.error); return; }
            created += +r.created || 0;
            received += +r.received || 0;
            sent += part.length;
            step(k + 1);
          });
      };
      step(0);
    };
    tForm.addEventListener('submit', function (e) {
      rows.querySelectorAll('[data-bulk-name]').forEach(function (x) {   // áp tách xưng hô cho ô đang gõ dở
        var r = x.closest('[data-bulk-row]'), sel = r.querySelector('[data-bulk-sal]');
        if (!sel.value) { var p = split(x.value); if (p[0]) { sel.value = p[0]; x.value = p[1]; } }
      });
      var n = count(), max = parseInt(tForm.getAttribute('data-bulk-max'), 10) || 500;
      if (!n) { e.preventDefault(); rows.querySelector('[data-bulk-name]').focus(); AC.toast(window.__('Nhập tên ít nhất 1 khách.'), 'error'); return; }
      if (!jsonIn) return;
      // Gửi cả bảng trong 1 trường JSON: bảng dài (hàng trăm dòng) không bị PHP max_input_vars cắt mất khách.
      var data = [], rowEls = [];
      rows.querySelectorAll('[data-bulk-row]').forEach(function (r) {
        var o = { salutation: r.querySelector('[data-bulk-sal]').value, name: r.querySelector('[data-bulk-name]').value.trim(),
          side: r.querySelector('[data-bulk-side]').value, invite_text: r.querySelector('[data-bulk-own]').value.trim() };
        var ph = r.querySelector('[data-bulk-phone]'), gu = r.querySelector('[data-bulk-guests]'), nt = r.querySelector('[data-bulk-note]');
        if (ph && ph.value && o.name) o.phone = ph.value;
        if (gu && gu.value && o.name) o.max_guests = +gu.value;
        if (nt && nt.value && o.name) o.note = nt.value;
        if (o.name || o.salutation || o.invite_text) { data.push(o); rowEls.push(r); }
      });
      if (data.length > max) { e.preventDefault(); sendLots(data, rowEls, max); return; }
      jsonIn.value = JSON.stringify(data);
      jsonIn.disabled = false;
      tForm.querySelectorAll('[data-bulk-rows] input, [data-bulk-rows] select, [data-bulk-rows] button').forEach(function (x) { x.disabled = true; });
      submit.disabled = true;
      submit.textContent = window.__('Đang tạo {n} thiệp mời…', { n: n });
    });
  }

  // ── Sửa danh sách xưng hô & lời mời mẫu ──
  var ed = document.querySelector('[data-sal-editor]');
  if (ed) {
    var list = ed.querySelector('[data-sal-rows]'), rowTpl = ed.querySelector('[data-sal-tpl-row]');
    var sample = document.querySelector('[data-sal-sample]'), err = ed.querySelector('[data-sal-err]');
    var preview = function (li) {
      var s = li.querySelector('[data-sal-name]').value, t = li.querySelector('[data-sal-tpl]').value;
      var p = li.querySelector('[data-sal-prev]');
      p.textContent = '';
      p.appendChild(el('span', 'inv-src', t.trim() ? window.__('Xem trước') : window.__('Trống → dùng mẫu chung')));
      p.appendChild(document.createTextNode(fill(t.trim() ? t : D.common, s, sample.value || window.__('Lan'))));
    };
    var dupCheck = function () {
      var seen = {}, dup = '';
      list.querySelectorAll('[data-sal-name]').forEach(function (x) {
        var k = key(x.value);
        var bad = k && seen[k];
        x.setCustomValidity(bad ? window.__('Xưng hô này bị trùng.') : '');
        x.classList.toggle('is-bad', !!bad);
        if (bad && !dup) dup = norm(x.value);
        if (k) seen[k] = true;
      });
      err.hidden = !dup;
      err.textContent = dup ? window.__('Xưng hô "{s}" bị trùng — mỗi xưng hô chỉ một dòng.', { s: dup }) : '';
      return !dup;
    };
    var all = function () { list.querySelectorAll('[data-sal-row]').forEach(preview); };
    all();
    sample.addEventListener('input', all);
    list.addEventListener('input', function (e) {
      var li = e.target.closest('[data-sal-row]');
      if (li) preview(li);
      if (e.target.matches('[data-sal-name]')) dupCheck();
    });
    list.addEventListener('click', function (e) {
      var b = e.target.closest('button'); if (!b) return;
      var li = b.closest('[data-sal-row]');
      if (b.hasAttribute('data-sal-up') && li.previousElementSibling) list.insertBefore(li, li.previousElementSibling);
      else if (b.hasAttribute('data-sal-down') && li.nextElementSibling) list.insertBefore(li.nextElementSibling, li);
      else if (b.hasAttribute('data-sal-del')) { li.remove(); dupCheck(); return; }
      else return;
      b.focus();
    });
    ed.querySelector('[data-sal-add]').addEventListener('click', function () {
      var li = rowTpl.content.firstElementChild.cloneNode(true);
      list.appendChild(li);
      preview(li);
      li.querySelector('[data-sal-name]').focus();
    });
    // Kéo thả đổi thứ tự: chỉ bật draggable khi nắm vào tay cầm ⋮⋮ (để còn chọn chữ trong ô nhập).
    var dragging = null;
    list.addEventListener('pointerdown', function (e) {
      var g = e.target.closest('.sal-grip');
      if (g) g.closest('[data-sal-row]').draggable = true;
    });
    list.addEventListener('pointerup', function () {
      if (!dragging) list.querySelectorAll('[data-sal-row]').forEach(function (x) { x.draggable = false; });
    });
    list.addEventListener('dragstart', function (e) {
      dragging = e.target.closest('[data-sal-row]');
      if (!dragging) return;
      dragging.classList.add('is-dragging');
      e.dataTransfer.effectAllowed = 'move';
      try { e.dataTransfer.setData('text/plain', ''); } catch (x) { /* Firefox cần setData */ }
    });
    list.addEventListener('dragover', function (e) {
      if (!dragging) return;
      e.preventDefault();
      var over = e.target.closest('[data-sal-row]');
      if (!over || over === dragging) return;
      var r = over.getBoundingClientRect();
      list.insertBefore(dragging, (e.clientY - r.top) > r.height / 2 ? over.nextSibling : over);
    });
    list.addEventListener('dragend', function () {
      if (!dragging) return;
      dragging.classList.remove('is-dragging');
      dragging.draggable = false;
      dragging = null;
    });
    ed.addEventListener('submit', function (e) {
      if (!dupCheck()) { e.preventDefault(); err.scrollIntoView({ block: 'center' }); }
    });
  }
})();
