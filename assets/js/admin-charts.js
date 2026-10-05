/* Ảnh Cưới — đồ thị trang Tổng quan. SVG thuần, không thư viện.
 * Bảng màu (đã chạy validate_palette.js, light): --series-1 #2a78d6 (tham dự / chuỗi đơn),
 * --series-2 #eb6834 (từ chối), phần còn lại (chưa trả lời) màu trung tính + nhãn trực tiếp.
 * Mỗi đồ thị: chú giải khi >= 2 chuỗi, tooltip khi rê chuột/chạm, và bảng số liệu (details). */
(function () {
  'use strict';
  var el = document.getElementById('dash-data');
  if (!el) return;
  var D = JSON.parse(el.textContent);
  var NS = 'http://www.w3.org/2000/svg';
  var __ = window.__ || function (t, v) { if (v) { for (var k in v) { t = t.split('{' + k + '}').join(v[k]); } } return t; };
  var EN = window.AC_LANG === 'en';
  var tip = document.createElement('div');
  tip.className = 'viz-tip';
  tip.hidden = true;
  document.body.appendChild(tip);

  function svg(tag, attrs, parent) {
    var n = document.createElementNS(NS, tag);
    Object.keys(attrs || {}).forEach(function (k) { n.setAttribute(k, attrs[k]); });
    if (parent) parent.appendChild(n);
    return n;
  }
  function showTip(e, html) {
    tip.innerHTML = html;
    tip.hidden = false;
    var x = (e.touches ? e.touches[0].clientX : e.clientX) + 14, y = (e.touches ? e.touches[0].clientY : e.clientY) + 14;
    var r = tip.getBoundingClientRect();
    if (x + r.width > innerWidth - 8) x -= r.width + 28;
    if (y + r.height > innerHeight - 8) y -= r.height + 28;
    tip.style.left = x + 'px'; tip.style.top = y + 'px';
  }
  function hideTip() { tip.hidden = true; }
  function hover(node, html) {
    node.addEventListener('mousemove', function (e) { showTip(e, html); });
    node.addEventListener('mouseleave', hideTip);
    node.addEventListener('touchstart', function (e) { showTip(e, html); }, { passive: true });
    node.setAttribute('data-viz-hit', '');
  }
  // M1-OWNER-09: màn cảm ứng (WebKit) — chạm chỗ khác hoặc cuộn thì ẩn tooltip (trước đây chỉ ẩn khi cuộn).
  document.addEventListener('touchstart', function (e) { if (!e.target.closest || !e.target.closest('[data-viz-hit]')) hideTip(); }, { passive: true });
  window.addEventListener('scroll', hideTip, { passive: true });
  function table(host, head, rows) {
    var d = document.createElement('details');
    d.className = 'viz-table';
    d.innerHTML = '<summary>' + __('Xem bảng số liệu') + '</summary>';
    var t = document.createElement('table');
    t.innerHTML = '<thead><tr>' + head.map(function (h) { return '<th>' + h + '</th>'; }).join('') + '</tr></thead>';
    var tb = document.createElement('tbody');
    rows.forEach(function (r) {
      var tr = document.createElement('tr');
      r.forEach(function (c) { var td = document.createElement('td'); td.textContent = c; tr.appendChild(td); });
      tb.appendChild(tr);
    });
    t.appendChild(tb);
    d.appendChild(t);
    host.appendChild(d);
  }
  function legend(host, items) {
    var l = document.createElement('div');
    l.className = 'viz-legend';
    items.forEach(function (it) {
      var s = document.createElement('span');
      s.innerHTML = '<i style="background:' + it[1] + '"></i>';
      s.appendChild(document.createTextNode(it[0]));
      l.appendChild(s);
    });
    host.appendChild(l);
  }
  function empty(host, msg) {
    var p = document.createElement('p');
    p.className = 'viz-empty';
    p.textContent = msg;
    host.appendChild(p);
  }
  var css = getComputedStyle(document.querySelector('.viz-root'));
  var C1 = css.getPropertyValue('--series-1').trim(), C2 = css.getPropertyValue('--series-2').trim(),
      CN = css.getPropertyValue('--series-rest').trim(), GRID = css.getPropertyValue('--grid').trim();
  var dayLabel = function (d) { return EN ? d.slice(5, 7) + '/' + d.slice(8, 10) : d.slice(8, 10) + '/' + d.slice(5, 7); };

  function render() {
  document.querySelectorAll('[data-viz]').forEach(function (h) { h.innerHTML = ''; });
  // ── 1. Tỉ lệ trả lời: 1 thanh 100% xếp chồng + nhãn trực tiếp ──
  (function () {
    var host = document.querySelector('[data-viz="status"]');
    var s = D.status, total = s.yes + s.no + s.pending;
    var parts = [[__('Tham dự'), s.yes, C1], [__('Từ chối{_}', { _: '' }), s.no, C2], [__('Chưa trả lời'), s.pending, CN]];
    if (!total) return empty(host, __('Chưa có giấy mời nào. Tạo giấy mời ở mục Khách mời.'));
    var W = host.clientWidth || 600, H = 56, gap = 2;
    var g = svg('svg', { viewBox: '0 0 ' + W + ' ' + H, class: 'viz-svg', role: 'img', 'aria-label': __('Tỉ lệ trả lời') }, host);
    var x = 0;
    parts.forEach(function (p, i) {
      if (!p[1]) return;
      var w = Math.max(3, p[1] / total * W - gap);
      var r = svg('rect', { x: x, y: 8, width: w, height: 40, rx: 4, fill: p[2] }, g);
      hover(r, '<b>' + p[0] + '</b><br>' + __('{n} lời mời', { n: p[1] }) + ' · ' + Math.round(p[1] / total * 100) + '%');
      x += w + gap;
    });
    var labels = document.createElement('div');
    labels.className = 'viz-direct';
    parts.forEach(function (p) {
      var s2 = document.createElement('span');
      s2.innerHTML = '<i style="background:' + p[2] + '"></i><b></b> ';
      s2.querySelector('b').textContent = p[1];
      s2.appendChild(document.createTextNode(p[0] + ' (' + Math.round(p[1] / total * 100) + '%)'));
      labels.appendChild(s2);
    });
    host.appendChild(labels);
    table(host, [__('Trả lời'), __('Số lời mời'), __('Tỉ lệ')], parts.map(function (p) { return [p[0], p[1], Math.round(p[1] / total * 100) + '%']; }));
  })();

  // ── 2. Người sẽ đến theo bên: thanh ngang, 1 chuỗi ──
  (function () {
    var host = document.querySelector('[data-viz="people"]');
    var items = D.people.filter(function (p) { return p.value > 0; });
    if (!items.length) return empty(host, __('Chưa có ai xác nhận tham dự.'));
    var max = Math.max.apply(null, items.map(function (p) { return p.value; }));
    var rowH = 34, W = host.clientWidth || 600, labelW = Math.min(170, W * 0.35), H = items.length * rowH + 8;
    var g = svg('svg', { viewBox: '0 0 ' + W + ' ' + H, class: 'viz-svg', role: 'img', 'aria-label': __('Người sẽ đến theo bên') }, host);
    items.forEach(function (p, i) {
      var y = 4 + i * rowH;
      var t = svg('text', { x: 0, y: y + 20, class: 'viz-label' }, g); t.textContent = p.label;
      // Nhãn dài hơn cột nhãn (màn hẹp): rút gọn kèm "…" để không chồng lên thanh (tên đầy đủ vẫn ở tooltip + bảng).
      if (t.getComputedTextLength && t.getComputedTextLength() > labelW - 8) {
        var full = p.label, n = full.length;
        while (n > 1 && t.getComputedTextLength() > labelW - 8) { t.textContent = full.slice(0, --n) + '…'; }
        svg('title', {}, t).textContent = full;
      }
      var w = Math.max(4, p.value / max * (W - labelW - 50));
      var r = svg('rect', { x: labelW, y: y + 6, width: w, height: 20, rx: 4, fill: C1 }, g);
      var v = svg('text', { x: labelW + w + 8, y: y + 21, class: 'viz-value' }, g); v.textContent = p.value;
      hover(r, '<b>' + p.label + '</b><br>' + __('{n} người', { n: p.value }));
    });
    table(host, [__('Bên'), __('Số người')], items.map(function (p) { return [p.label, p.value]; }));
  })();

  // ── 3–5. Cột theo ngày (có thể xếp chồng) ──
  function columns(host, series, title, unit) {
    var days = D.days, n = days.length;
    var totals = days.map(function (_, i) { return series.reduce(function (a, s) { return a + s.data[i]; }, 0); });
    if (!totals.some(Boolean)) return empty(host, __('Chưa có dữ liệu trong 30 ngày qua.'));
    var max = Math.max.apply(null, totals), step = max <= 5 ? 1 : Math.ceil(max / 4);
    var top = Math.ceil(max / step) * step;
    var W = host.clientWidth || 640, H = 200, L = 28, B = 22, T = 8, cw = (W - L) / n;
    var every = W < 420 ? 10 : 5;
    var g = svg('svg', { viewBox: '0 0 ' + W + ' ' + H, class: 'viz-svg', role: 'img', 'aria-label': title }, host);
    for (var v = 0; v <= top; v += step) {
      var y = H - B - v / top * (H - B - T);
      svg('line', { x1: L, x2: W, y1: y, y2: y, stroke: GRID, 'stroke-width': 1 }, g);
      var t = svg('text', { x: L - 6, y: y + 4, class: 'viz-axis', 'text-anchor': 'end' }, g); t.textContent = v;
    }
    days.forEach(function (d, i) {
      var x = L + i * cw + 2, base = H - B;
      series.forEach(function (s) {
        var val = s.data[i];
        if (!val) return;
        var h = val / top * (H - B - T);
        svg('rect', { x: x, y: base - h + (base === H - B ? 0 : 2), width: Math.max(2, cw - 4), height: Math.max(1, h - (base === H - B ? 0 : 2)), rx: 2, fill: s.color }, g);
        base -= h;
      });
      if ((n - 1 - i) % every === 0) {
        var lb = svg('text', { x: x + (cw - 4) / 2, y: H - 6, class: 'viz-axis', 'text-anchor': 'middle' }, g); lb.textContent = dayLabel(d);
      }
      var hit = svg('rect', { x: L + i * cw, y: T, width: cw, height: H - B - T, fill: 'transparent' }, g);
      hover(hit, '<b>' + dayLabel(d) + '</b><br>' + series.map(function (s) {
        return (series.length > 1 ? '<i style="background:' + s.color + '"></i>' + s.name + ': ' : '') + __(unit, { n: s.data[i] });
      }).join('<br>'));
    });
    if (series.length > 1) legend(host, series.map(function (s) { return [s.name, s.color]; }));
    table(host, [__('Ngày')].concat(series.map(function (s) { return s.name; })),
      days.map(function (d, i) { return [dayLabel(d)].concat(series.map(function (s) { return s.data[i]; })); }).filter(function (r, i) { return totals[i]; }));
  }
  columns(document.querySelector('[data-viz="rsvp-days"]'),
    [{ name: __('Tham dự'), data: D.rsvp_yes, color: C1 }, { name: __('Từ chối{_}', { _: '' }), data: D.rsvp_no, color: C2 }], __('Xác nhận theo ngày'), '{n} lời mời');
  columns(document.querySelector('[data-viz="wishes-days"]'), [{ name: __('Lời chúc'), data: D.wishes, color: C1 }], __('Lời chúc theo ngày'), '{n} lời chúc');
  columns(document.querySelector('[data-viz="photos-days"]'), [{ name: __('Ảnh'), data: D.photos, color: C1 }], __('Ảnh khách gửi theo ngày'), '{n} ảnh');
  }
  render();
  var lastW = innerWidth, t;
  window.addEventListener('resize', function () {
    if (innerWidth === lastW) return;
    lastW = innerWidth;
    clearTimeout(t);
    t = setTimeout(render, 150);
  });
})();
