/* OJT360 Company Portal - shared behaviour: sidebar, theme, modals, table filter */
(function () {
  var d = document, root = d.documentElement;
  var mq = window.matchMedia('(max-width: 900px)');
  var sb = d.getElementById('sidebar'), ov = d.getElementById('sbOverlay'), tg = d.getElementById('sbToggle');
  function store(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }

  function setMobile(open) {
    sb.classList.toggle('open', open); ov.hidden = !open;
    tg.setAttribute('aria-expanded', String(open));
    d.body.classList.toggle('no-scroll', open);
    if (open) { var a = sb.querySelector('a'); if (a) a.focus(); } else { tg.focus(); }
  }
  tg.addEventListener('click', function () {
    if (mq.matches) { setMobile(!sb.classList.contains('open')); return; }
    var c = root.classList.toggle('sb-collapsed');
    store('ojt360-sidebar', c ? 'collapsed' : 'open');
    tg.setAttribute('aria-expanded', String(!c));
  });
  ov.addEventListener('click', function () { setMobile(false); });
  mq.addEventListener && mq.addEventListener('change', function () { if (!mq.matches) { sb.classList.remove('open'); ov.hidden = true; d.body.classList.remove('no-scroll'); } });
  tg.setAttribute('aria-expanded', String(!root.classList.contains('sb-collapsed')));

  /* theme */
  function setTheme(t) { root.setAttribute('data-theme', t); store('ojt360-theme', t); d.querySelectorAll('[data-theme-set]').forEach(function (b) { b.classList.toggle('on', b.dataset.themeSet === t); }); }
  var tt = d.getElementById('themeToggle');
  if (tt) tt.addEventListener('click', function () { setTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'); });
  d.querySelectorAll('[data-theme-set]').forEach(function (b) { b.addEventListener('click', function () { setTheme(b.dataset.themeSet); }); });
  setTheme(root.getAttribute('data-theme') || 'light');

  /* modals */
  var last = null;
  function openModal(id) {
    var m = d.getElementById(id); if (!m) return;
    last = d.activeElement; m.hidden = false; d.body.classList.add('no-scroll');
    var f = m.querySelector('[autofocus],input:not([type=hidden]):not([readonly]),select,textarea,button'); if (f) f.focus();
  }
  function closeModal(m) {
    m.hidden = true;
    if (!d.querySelector('.modal-wrap:not([hidden])') && !sb.classList.contains('open')) d.body.classList.remove('no-scroll');
    if (last) last.focus();
  }
  d.addEventListener('click', function (e) {
    var o = e.target.closest('[data-modal-open]');
    if (o) { e.preventDefault(); openModal(o.dataset.modalOpen); return; }
    var c = e.target.closest('[data-modal-close]');
    if (c) { closeModal(c.closest('.modal-wrap')); return; }
    if (e.target.classList && e.target.classList.contains('modal-wrap')) closeModal(e.target);
  });
  d.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      var m = d.querySelector('.modal-wrap:not([hidden])');
      if (m) { closeModal(m); return; }
      if (sb.classList.contains('open')) setMobile(false);
    }
    if (e.key === 'Tab') { /* keep focus inside an open modal */
      var mm = d.querySelector('.modal-wrap:not([hidden])'); if (!mm) return;
      var f = mm.querySelectorAll('a[href],button:not([disabled]),input:not([type=hidden]):not([readonly]),select,textarea');
      if (!f.length) return; var first = f[0], lastEl = f[f.length - 1];
      if (e.shiftKey && d.activeElement === first) { e.preventDefault(); lastEl.focus(); }
      else if (!e.shiftKey && d.activeElement === lastEl) { e.preventDefault(); first.focus(); }
    }
  });

  /* live table filter: <input data-filter="#tableId" data-empty="#emptyId"> and <tr data-search="lowercase text"> */
  d.querySelectorAll('[data-filter]').forEach(function (i) {
    var t = d.querySelector(i.dataset.filter); if (!t) return;
    i.addEventListener('input', function () {
      var q = i.value.trim().toLowerCase(), n = 0;
      t.querySelectorAll('tbody tr[data-search]').forEach(function (r) { var s = r.dataset.search.indexOf(q) > -1; r.hidden = !s; if (s) n++; });
      var em = i.dataset.empty && d.querySelector(i.dataset.empty); if (em) em.hidden = n > 0;
    });
  });
  window.OJT = { openModal: openModal };
})();
