// Jaru admin: confirm deletes, drag to reorder, square crop before upload, QR cards + PNG.
(function () {
  // "Are you sure?" on delete forms.
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (ev) { if (!confirm(f.dataset.confirm)) ev.preventDefault(); });
  });

  // ---------- drag to reorder (pointer events, works with touch) ----------
  var csrf = document.getElementById('csrf');
  document.querySelectorAll('[data-sort]').forEach(function (list) {
    list.addEventListener('pointerdown', function (ev) {
      var handle = ev.target.closest('.handle');
      var el = handle && handle.closest('[data-id]');
      if (!el || el.parentElement !== list) return;
      ev.preventDefault();
      var before = order(list);
      el.classList.add('dragging');

      // Listen on document: moving the row in the DOM drops pointer capture on the handle.
      function move(e) {
        var after = [].slice.call(list.children).filter(function (c) { return c !== el && c.dataset.id; })
          .find(function (c) { var r = c.getBoundingClientRect(); return e.clientY < r.top + r.height / 2; }) || null;
        if (el.nextElementSibling !== after) list.insertBefore(el, after);
        if (e.clientY < 70) window.scrollBy(0, -14);
        else if (e.clientY > window.innerHeight - 70) window.scrollBy(0, 14);
      }
      function up() {
        document.removeEventListener('pointermove', move);
        document.removeEventListener('pointerup', up);
        document.removeEventListener('pointercancel', up);
        el.classList.remove('dragging');
        if (order(list).join() !== before.join()) save(list);
      }
      document.addEventListener('pointermove', move);
      document.addEventListener('pointerup', up);
      document.addEventListener('pointercancel', up);
    });
  });

  function order(list) {
    return [].slice.call(list.children).filter(function (c) { return c.dataset.id; }).map(function (c) { return c.dataset.id; });
  }

  function save(list) {
    var fd = new FormData();
    fd.append('a', 'reorder');
    fd.append('type', list.dataset.sort);
    fd.append('csrf', csrf.value);
    order(list).forEach(function (id) { fd.append('ids[]', id); });
    list.classList.add('saving');
    fetch('actions.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { if (!r.ok) throw new Error(); list.classList.remove('saving'); })
      .catch(function () { alert('Could not save the new order. The page will reload.'); location.reload(); });
  }

  // ---------- photo: crop to square / shrink on the phone before upload ----------
  var input = document.getElementById('photo');
  if (input && window.DataTransfer && HTMLCanvasElement.prototype.toBlob) {
    var square = document.getElementById('square');
    var preview = document.getElementById('crop-preview');
    var original = null;

    function process() {
      if (!original) return;
      var img = new Image();
      img.onload = function () {
        var w = img.naturalWidth, h = img.naturalHeight, sx = 0, sy = 0, sw = w, sh = h;
        if (square.checked) { sw = sh = Math.min(w, h); sx = (w - sw) / 2; sy = (h - sh) / 2; } // ponytail: center crop only, add drag-to-position if owners ask
        var scale = Math.min(1, 1600 / Math.max(sw, sh));
        var c = document.createElement('canvas');
        c.width = Math.round(sw * scale); c.height = Math.round(sh * scale);
        var ctx = c.getContext('2d');
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
        ctx.drawImage(img, sx, sy, sw, sh, 0, 0, c.width, c.height);
        URL.revokeObjectURL(img.src);
        c.toBlob(function (blob) {
          if (!blob) return;
          var dt = new DataTransfer();
          dt.items.add(new File([blob], original.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }));
          input.files = dt.files;
          preview.src = c.toDataURL('image/jpeg', 0.6);
          preview.hidden = false;
        }, 'image/jpeg', 0.88);
      };
      img.src = URL.createObjectURL(original);
    }
    input.addEventListener('change', function () {
      // Setting input.files from code does not fire "change", so this is always the user's pick.
      var f = input.files[0];
      original = f && /^image\/(jpeg|png|webp)$/.test(f.type) ? f : null;
      preview.hidden = true;
      process();
    });
    square.addEventListener('change', process);
  }

  // ---------- QR cards ----------
  var sheet = document.getElementById('sheet');
  if (sheet && window.qrcode) {
    var INK = '#2b1d0b', PANEL = '#fffaf0';
    // Level H (30% recovery) so the logo covering the middle doesn't stop phones reading it.
    var qr = qrcode(0, 'H');
    qr.addData(sheet.dataset.url);
    qr.make();
    var n = qr.getModuleCount(), d = '';
    for (var r = 0; r < n; r++) for (var c = 0; c < n; c++) if (qr.isDark(r, c)) d += 'M' + c + ' ' + r + 'h1v1h-1z';
    var q = n + 4; // 2-module quiet zone; the cream panel padding adds the rest
    var svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-2 -2 ' + q + ' ' + q + '" shape-rendering="crispEdges" role="img" aria-label="QR code to ' +
      sheet.dataset.url.replace(/[<>"&]/g, '') + '"><rect x="-2" y="-2" width="' + q + '" height="' + q + '" fill="' + PANEL + '"/><path fill="' + INK + '" d="' + d + '"/></svg>';
    sheet.querySelectorAll('.qr').forEach(function (el) { el.innerHTML = svg; });

    document.getElementById('print').addEventListener('click', function () { window.print(); });

    function load(src) {
      return new Promise(function (ok) { var i = new Image(); i.onload = function () { ok(i); }; i.onerror = function () { ok(null); }; i.src = src; });
    }
    function roundRect(x, X, Y, w, h, rad) {
      x.beginPath(); x.moveTo(X + rad, Y); x.arcTo(X + w, Y, X + w, Y + h, rad); x.arcTo(X + w, Y + h, X, Y + h, rad);
      x.arcTo(X, Y + h, X, Y, rad); x.arcTo(X, Y, X + w, Y, rad); x.closePath();
    }

    // High-resolution PNG (1800 x 2600) for print shops, same design as the cards.
    document.getElementById('png').addEventListener('click', function () {
      var W = 1800, H = 2600;
      var fonts = ['700 150px Caveat', '600 120px Fraunces', '700 84px "Nunito Sans"'];
      Promise.all([load(sheet.dataset.chalk), load(sheet.dataset.logo || ''), document.fonts ? Promise.all(fonts.map(function (f) { return document.fonts.load(f); })).catch(function () {}) : null])
        .then(function (res) {
          var chalk = res[0], logo = res[1];
          var cv = document.createElement('canvas'); cv.width = W; cv.height = H;
          var x = cv.getContext('2d');
          x.fillStyle = '#37230a'; x.fillRect(0, 0, W, H);
          if (chalk) { // cover-fit the chalk art, dimmed like the website
            var s = Math.max(W / chalk.width, H / chalk.height), cw = chalk.width * s, ch = chalk.height * s;
            x.globalAlpha = .35; x.drawImage(chalk, (W - cw) / 2, (H - ch) / 2, cw, ch); x.globalAlpha = 1;
          }
          x.textAlign = 'center';
          x.fillStyle = '#e3c98f'; x.font = '700 150px Caveat, cursive';
          x.fillText(sheet.dataset.name, W / 2, 330);

          var P = 1300, px = (W - P) / 2, py = 430, pad = 50, cell = Math.floor((P - pad * 2) / q), ox = px + (P - cell * q) / 2, oy = py + (P - cell * q) / 2;
          x.save(); x.shadowColor = 'rgba(0,0,0,.45)'; x.shadowBlur = 60; x.shadowOffsetY = 20;
          x.fillStyle = PANEL; roundRect(x, px, py, P, P, 70); x.fill(); x.restore();
          x.fillStyle = INK;
          for (var r = 0; r < n; r++) for (var c = 0; c < n; c++) if (qr.isDark(r, c)) x.fillRect(ox + (c + 2) * cell, oy + (r + 2) * cell, cell, cell);
          if (logo) {
            var L = Math.round(cell * n * .22), cx = W / 2, cy = py + P / 2;
            x.fillStyle = PANEL; x.beginPath(); x.arc(cx, cy, L / 2 + 22, 0, Math.PI * 2); x.fill();
            x.save(); x.beginPath(); x.arc(cx, cy, L / 2, 0, Math.PI * 2); x.clip();
            x.fillStyle = '#fff'; x.fillRect(cx - L / 2, cy - L / 2, L, L);
            x.drawImage(logo, cx - L / 2, cy - L / 2, L, L); x.restore();
          }
          x.fillStyle = '#f3ead6'; x.font = '600 120px Fraunces, Georgia, serif';
          x.fillText('Scan to see our menu', W / 2, py + P + 220);
          x.fillStyle = '#e3c98f'; x.font = '700 84px "Nunito Sans", system-ui, sans-serif';
          x.fillText(sheet.dataset.short, W / 2, py + P + 350);
          cv.toBlob(function (b) {
            var a = document.createElement('a');
            a.href = URL.createObjectURL(b);
            a.download = 'jaru-menu-qr.png';
            document.body.appendChild(a); a.click(); a.remove();
            setTimeout(function () { URL.revokeObjectURL(a.href); }, 5000);
          }, 'image/png');
        });
    });
  }
})();
