// Jaru Café menu: search, current-category tabs, item details sheet, back-to-top, offline cache.
(function () {
  var $ = function (s, el) { return (el || document).querySelector(s); };
  var $$ = function (s, el) { return [].slice.call((el || document).querySelectorAll(s)); };
  var calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
  var pc = matchMedia('(min-width: 1024px)'); // categories are a sidebar, not a sticky top bar

  var bar = $('.bar'), q = $('#q'), pop = $('#sig'), toTop = $('#top');
  var items = $$('.item'), sections = $$('.pop, .cat'), links = $$('.cats a');
  function pad() { document.documentElement.style.scrollPaddingTop = (pc.matches ? 16 : bar.offsetHeight + 8) + 'px'; }
  pad();

  // ---------- search: name, description, category; highlights matches ----------
  var original = new Map();
  items.forEach(function (li) { $$('h3, .desc', li).forEach(function (el) { original.set(el, el.textContent); }); });
  function esc(s) { return s.replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  function highlight(text, words) {
    if (!words.length) return esc(text);
    var re = new RegExp('(' + words.map(function (w) { return w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }).join('|') + ')', 'gi');
    return text.split(re).map(function (part, i) { return i % 2 ? '<mark>' + esc(part) + '</mark>' : esc(part); }).join('');
  }
  q.addEventListener('input', function () {
    var words = q.value.toLowerCase().trim().split(/\s+/).filter(Boolean), shown = 0;
    items.forEach(function (li) {
      var s = li.dataset.s;
      li.hidden = !words.every(function (w) { return s.indexOf(w) !== -1; });
      if (!li.hidden) shown++;
      original.forEach(function (text, el) { if (li.contains(el)) el.innerHTML = highlight(text, li.hidden ? [] : words); });
    });
    var any = false;
    $$('.cat').forEach(function (sec) { sec.hidden = !$('.item:not([hidden])', sec); any = any || !sec.hidden; });
    if (pop) pop.hidden = words.length > 0;
    $('#none').hidden = any;
    $('#count').textContent = words.length && any ? shown + (shown === 1 ? ' item found' : ' items found') : '';
    current = null; onScroll();
  });

  // ---------- highlight the category on screen ----------
  var current = null, ticking = false;
  function mark(id) {
    links.forEach(function (a) {
      var on = a.getAttribute('href') === '#' + id;
      a.classList.toggle('on', on);
      // Phones/tablets: keep the active tab visible. Scroll only the tab row (scrollIntoView would move the page).
      if (on && !pc.matches) a.parentNode.scrollTo({ left: a.offsetLeft - 16, behavior: calm ? 'auto' : 'smooth' });
    });
  }
  function onScroll() {
    ticking = false;
    var line = pc.matches ? 160 : bar.getBoundingClientRect().bottom + 24, id = '';
    sections.forEach(function (s) { if (!s.hidden && s.getBoundingClientRect().top <= line) id = s.id; });
    if (!id && sections[0] && !sections[0].hidden) id = sections[0].id;
    if (id !== current) { current = id; mark(id); }
    toTop.hidden = scrollY < 600;
  }
  addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });
  addEventListener('resize', function () { pad(); current = null; onScroll(); });
  onScroll();
  toTop.addEventListener('click', function (e) { e.preventDefault(); scrollTo({ top: 0, behavior: calm ? 'auto' : 'smooth' }); });

  // ---------- item details sheet ----------
  var sheet = $('#sheet'), openId = null;
  function fill(li) {
    var img = $('.sh-ph img', sheet), full = li.dataset.full, name = original.get($('h3', li)), d = $('.desc', li);
    $('.sh-ph', sheet).hidden = !full;
    img.src = full || '';
    img.alt = name;
    $('.sh-cat', sheet).textContent = li.dataset.cat;
    $('#sh-name').textContent = name;
    $('.sh-desc', sheet).textContent = d ? original.get(d) : '';
    var list = $('.sh-prices', sheet);
    list.innerHTML = '';
    if (!('soon' in li.dataset)) {
      $$('.price > span', li).forEach(function (p) {
        var row = document.createElement('li'), label = $('small', p);
        row.innerHTML = '<span></span><b></b>';
        row.firstChild.textContent = label ? label.textContent : 'Price';
        row.lastChild.textContent = p.textContent.replace(label ? label.textContent : '', '').trim();
        list.appendChild(row);
      });
    }
    var flag = $('.sh-flag', sheet);
    flag.textContent = 'soon' in li.dataset ? 'Coming soon' : li.classList.contains('out') ? 'Sold out today · Ubos na' : '';
    flag.className = 'sh-flag' + ('soon' in li.dataset ? ' is-soon' : '');
  }
  function openSheet(id, push) {
    var li = document.getElementById(id);
    if (!li || !li.classList.contains('item') || !sheet.showModal) return;
    fill(li);
    openId = id;
    sheet.scrollTop = 0;
    sheet.style.transform = '';
    if (!sheet.open) sheet.showModal();
    requestAnimationFrame(function () { sheet.classList.add('in'); });
    if (push) history.pushState({ sheet: id }, '', '#' + id);
  }
  function closeSheet() {
    if (!sheet.open) return;
    sheet.classList.remove('in');
    sheet.style.transform = '';
    openId = null;
    setTimeout(function () { if (!sheet.classList.contains('in')) sheet.close(); }, calm ? 0 : 280);
  }
  function dismiss() {
    // Opened by a tap: go back, so the phone's Back button history stays clean.
    if (history.state && history.state.sheet) history.back();
    else { closeSheet(); history.replaceState(null, '', location.pathname + location.search); }
  }
  document.addEventListener('click', function (ev) {
    var t = ev.target.closest('.open, .sig-card');
    if (t) openSheet(t.dataset.open || t.closest('.item').id, true);
  });
  $('.sh-close', sheet).addEventListener('click', dismiss);
  sheet.addEventListener('click', function (ev) { if (ev.target === sheet) dismiss(); }); // backdrop
  sheet.addEventListener('cancel', function (ev) { ev.preventDefault(); dismiss(); });     // Esc
  addEventListener('popstate', function () {
    var id = history.state && history.state.sheet;
    if (id) openSheet(id, false); else closeSheet();
  });
  // Shared link to one item (#i12), on load or when only the hash changes.
  function fromHash() { if (/^#i\d+$/.test(location.hash) && openId !== location.hash.slice(1)) openSheet(location.hash.slice(1), false); }
  addEventListener('hashchange', fromHash);
  fromHash();

  // Swipe down on the photo or grip bar to close (phones). Those areas have touch-action: none.
  var startY = null, dy = 0;
  sheet.addEventListener('pointerdown', function (e) {
    if (e.pointerType === 'mouse' || !e.target.closest('.sh-ph, .sh-grip') || e.target.closest('button')) return;
    startY = e.clientY; dy = 0; sheet.style.transition = 'none';
  });
  sheet.addEventListener('pointermove', function (e) {
    if (startY === null) return;
    dy = Math.max(0, e.clientY - startY);
    sheet.style.transform = 'translateY(' + dy + 'px)';
  });
  function endSwipe() {
    if (startY === null) return;
    startY = null; sheet.style.transition = '';
    if (dy > 100) dismiss(); else sheet.style.transform = '';
  }
  sheet.addEventListener('pointerup', endSwipe);
  sheet.addEventListener('pointercancel', endSwipe);

  // ---------- offline ----------
  if (!navigator.onLine) $('#offline').hidden = false;
  if ('serviceWorker' in navigator) navigator.serviceWorker.register('sw.js').catch(function () {});
})();
