/* Ảnh Cưới — nhạc nền dùng chung cho trang cưới, album, gửi ảnh: tự phát (nếu bật trong Cài đặt), nối tiếp vị trí
   đang nghe khi chuyển trang, nút ♫ bật/tắt. Trình duyệt chặn tự phát có tiếng khi chưa có cử chỉ người dùng,
   nên khi bị chặn thì phát ở lần chạm/bấm phím đầu tiên. */
(function () {
  'use strict';
  var __ = window.__ || function (s) { return s; };

  // Nhạc nền: trình duyệt chặn tự phát, nên phát ở lần chạm đầu tiên vào trang hoặc khi bấm nút.
  var music = document.querySelector('[data-music]');
  if (music) {
    var btn = music.querySelector('[data-music-toggle]');
    var ss = function (k, v) {
      try { if (v === undefined) return sessionStorage.getItem(k); sessionStorage.setItem(k, v); } catch (e) { /* chế độ riêng tư */ }
      return null;
    };
    // Chuyển trang (album -> trang chủ) không phát lại nhạc từ đầu: nhớ vị trí đang nghe trong phiên.
    var resumed = false, pendingAt = null;   // pendingAt: vị trí cũ đang chờ tua tới (chưa tua xong)
    var resume = function (audio) {
      if (resumed) return;
      resumed = true;
      var saved = (ss('wd-music-pos') || '').split('|');
      if (saved[0] === audio.getAttribute('src') && parseFloat(saved[1]) > 0) {
        var at = parseFloat(saved[1]), done = false, timer = 0;
        var events = ['loadedmetadata', 'progress', 'canplay'];
        var finish = function () {
          if (done) return;
          done = true;
          pendingAt = null;
          clearTimeout(timer);
          events.forEach(function (ev) { audio.removeEventListener(ev, seek); });
        };
        // Chromium/Android (preload=none): gán currentTime trước khi có metadata bị bỏ qua -> phát lại từ đầu (R3-21).
        // Chỉ tua khi vị trí cũ đã nằm trong vùng tua được (`seekable`): máy chủ không hỗ trợ tải từng đoạn (Range)
        // thì Chromium không tua được — khi đó phát từ đầu như cũ, không thử mãi (thử mãi làm nhạc đứng).
        var seek = function () {
          if (done || audio.readyState < 1) return;
          var sk = audio.seekable;
          if (!(sk.length && sk.end(sk.length - 1) >= at)) return;
          try { audio.currentTime = at; } catch (e) { /* bỏ qua */ }
          finish();
        };
        events.forEach(function (ev) { audio.addEventListener(ev, seek); });
        pendingAt = at;
        timer = setTimeout(finish, 10000);
        seek();
      }
    };
    window.addEventListener('pagehide', function () {
      var audio = music.querySelector('audio');
      if (audio && !audio.paused) ss('wd-music-pos', audio.getAttribute('src') + '|' + (pendingAt !== null ? pendingAt : audio.currentTime));
    });
    // S1-DESK-06: nút ♫ công bố trạng thái cho bàn phím/đọc màn hình — aria-pressed + nhãn "Tắt nhạc" (đang phát) / "Bật nhạc".
    // Theo dõi chính <audio> (play/pause/ended) nên đúng cả khi nhạc được phát từ màn "Mở thiệp" hay cử chỉ đầu tiên.
    var setState = function (on) {
      music.classList.toggle('playing', on);
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
      btn.setAttribute('aria-label', on ? __('Tắt nhạc') : __('Bật nhạc'));
      btn.title = on ? __('Tắt nhạc') : __('Bật nhạc');
    };
    var audio0 = music.querySelector('audio');
    setState(!!audio0 && !audio0.paused);
    if (audio0) {
      audio0.addEventListener('play', function () { setState(true); });
      audio0.addEventListener('pause', function () { setState(false); });
      audio0.addEventListener('ended', function () { setState(false); });
    }
    var play = function () {
      var audio = music.querySelector('audio');
      if (!audio) return;
      resume(audio);
      audio.play().then(function () { setState(true); }).catch(function () {});
    };
    btn.addEventListener('click', function (e) {
      var audio = music.querySelector('audio');
      if (!audio || document.body.classList.contains('is-editing-music')) return;
      e.stopPropagation();
      if (audio.paused) play(); else { audio.pause(); setState(false); }
    });
    var hint = music.querySelector('[data-music-hint]');
    var cover = document.querySelector('[data-cover]');
    var draft = document.body.classList.contains('is-draft');
    // Khách tự tắt nhạc thì không tự bật lại khi chuyển trang trong phiên này.
    var muted = function () { try { return sessionStorage.getItem('wd-music-off') === '1'; } catch (e) { return false; } };
    btn.addEventListener('click', function () {
      var audio = music.querySelector('audio');
      try { if (audio) sessionStorage.setItem('wd-music-off', audio.paused ? '1' : '0'); } catch (e) { /* bỏ qua */ }
    });
    // Chạm/bấm phím đầu tiên ở bất kỳ đâu = cử chỉ người dùng -> phát nhạc (dự phòng khi không có màn "Mở thiệp").
    var gestures = ['pointerdown', 'touchend', 'keydown'];
    var first = function (e) {
      gestures.forEach(function (g) { document.removeEventListener(g, first, true); });
      if (hint) hint.hidden = true;
      // Chạm đầu tiên vào chính nút nhạc: để nút tự bật (tránh vừa phát vừa tắt).
      if (!draft && !muted() && !(e.target.closest && e.target.closest('[data-music-toggle]'))) play();
    };
    var armGestures = function () { gestures.forEach(function (g) { document.addEventListener(g, first, true); }); };
    var showHint = function () {
      if (!hint || draft) return;
      var t1 = setTimeout(function () { if (!music.classList.contains('playing')) hint.hidden = false; }, 1500);
      setTimeout(function () { hint.hidden = true; }, 11500);
      // Khách bắt đầu cuộn đọc trang -> ẩn gợi ý để không che chữ.
      var y0 = window.scrollY;
      var off = function () {
        if (Math.abs(window.scrollY - y0) < 80) return;
        clearTimeout(t1); hint.hidden = true; window.removeEventListener('scroll', off);
      };
      window.addEventListener('scroll', off, { passive: true });
    };
    var audioEl = music.querySelector('audio');
    // Màn "Mở thiệp" chỉ 1 lần mỗi phiên; không hiện khi khách vừa gửi form (có thông báo) hoặc mở thẳng tới một mục.
    var coverOk = cover && ss('wd-cover-seen') !== '1' && !document.querySelector('.flash')
      && !/^#(loi-chuc|rsvp|gallery|location)/.test(location.hash);
    var autoplay = music.getAttribute('data-autoplay') === '1';
    if (!draft && audioEl && autoplay && !muted()) {
      // Tự phát ở MỌI trang có nhạc (trang cưới, album, gửi ảnh), nối tiếp vị trí đang nghe ở trang trước.
      // Trình duyệt chặn (chưa có cử chỉ người dùng) -> lần đầu trong phiên hiện màn "Mở thiệp" (nếu trang có),
      // còn lại phát ở lần chạm đầu tiên. Không đặt preload=auto: mp3 chỉ tải khi thật sự phát (trang nhẹ trên 3G).
      resume(audioEl);
      audioEl.play().then(function () { setState(true); ss('wd-cover-seen', '1'); }).catch(function () {
        if (!coverOk) { armGestures(); showHint(); return; }
        cover.hidden = false;
        document.documentElement.classList.add('has-cover');
        var openBtn = cover.querySelector('[data-cover-open]');
        openBtn.focus({ preventScroll: true });
        // Chạm BẤT KỲ đâu trên màn che cũng mở (điện thoại xoay ngang: nút có thể nằm dưới mép màn).
        var opened = false;
        cover.addEventListener('click', function () {
          if (opened) return; opened = true;
          ss('wd-cover-seen', '1');
          play();
          cover.classList.add('is-leaving');
          document.documentElement.classList.remove('has-cover');
          setTimeout(function () { cover.hidden = true; }, 700);
        });
        armGestures();
      });
    } else {
      armGestures();
      showHint();
    }
  }
})();
