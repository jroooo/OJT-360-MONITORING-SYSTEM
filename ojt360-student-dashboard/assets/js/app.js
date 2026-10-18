/* OJT360 core: theme, shell, toasts, modals, profile photo, assistant, time tracker. */
(function(){
'use strict';
const C = window.OJT360 || {};
const $ = (s, p = document) => p.querySelector(s);
const $$ = (s, p = document) => [...p.querySelectorAll(s)];
const pad = n => String(n).padStart(2, '0');
const hms = sec => { sec = Math.max(0, Math.floor(sec)); return pad(Math.floor(sec / 3600)) + ':' + pad(Math.floor(sec % 3600 / 60)) + ':' + pad(sec % 60); };
const hm = sec => { sec = Math.max(0, Math.floor(sec)); return Math.floor(sec / 3600) + 'h ' + pad(Math.floor(sec % 3600 / 60)) + 'm'; };
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const icon = (name, cls = '') => `<svg class="ic ${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${(C.icons || {})[name] || ''}</svg>`;

/* ---------- tiny event bus ---------- */
const bus = {}; 
const on = (ev, fn) => { (bus[ev] = bus[ev] || []).push(fn); };
const emit = (ev, data) => (bus[ev] || []).forEach(fn => { try { fn(data); } catch (e) { console.error(e); } });

/* ---------- API helper ---------- */
async function api(url, data, opts = {}) {
  const fd = data instanceof FormData ? data : new FormData();
  if (!(data instanceof FormData) && data) Object.entries(data).forEach(([k, v]) => fd.append(k, v));
  if (!fd.has('csrf')) fd.append('csrf', C.csrf);
  let res, json;
  try {
    res = await fetch(url, { method: 'POST', body: fd, cache: 'no-store', credentials: 'same-origin' });
    const text = await res.text();
    try { json = JSON.parse(text); } catch (e) { json = { ok: false, message: 'The server returned an unexpected response.' }; console.error(text); }
  } catch (e) { json = { ok: false, message: 'Could not reach the server. Check that Apache and MySQL are running.' }; }
  if (!json.message && json.error) json.message = json.error;
  return json;
}
async function getJSON(url) {
  try { const r = await fetch(url, { cache: 'no-store', credentials: 'same-origin' }); return await r.json(); }
  catch (e) { return { ok: false, message: 'Could not load data.' }; }
}

/* ---------- toast / modal / confirm ---------- */
function toast(message, type = 'info', ms = 3800) {
  const stack = $('#toastStack'); if (!stack) return;
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.innerHTML = icon(type === 'success' ? 'check' : type === 'error' ? 'alert' : type === 'warn' ? 'alert' : 'bell') + '<span>' + esc(message) + '</span>';
  stack.appendChild(t);
  setTimeout(() => { t.classList.add('out'); setTimeout(() => t.remove(), 260); }, ms);
}
function lockScroll() { document.body.style.overflow = $$('.modal-backdrop.show').length ? 'hidden' : ''; }
function openModal(el) { if (typeof el === 'string') el = $(el); if (!el) return; el.classList.add('show'); el.setAttribute('aria-hidden', 'false'); lockScroll(); }
function closeModal(el) { if (typeof el === 'string') el = $(el); if (!el) return; el.classList.remove('show'); el.setAttribute('aria-hidden', 'true'); lockScroll(); emit('modal:close', el); }
document.addEventListener('click', e => {
  const bd = e.target.classList && e.target.classList.contains('modal-backdrop') ? e.target : null;
  if (bd && !bd.hasAttribute('data-static')) closeModal(bd);
  const cl = e.target.closest && e.target.closest('[data-close-modal]');
  if (cl) closeModal(cl.closest('.modal-backdrop'));
});
function confirmBox({ title = 'Are you sure?', message = '', confirmText = 'Confirm', danger = false } = {}) {
  return new Promise(resolve => {
    const bd = document.createElement('div');
    bd.className = 'modal-backdrop';
    bd.innerHTML = `<div class="modal modal-sm center"><div class="modal-icon" style="${danger ? '' : 'background:var(--primary-soft);color:var(--primary-600)'}">${icon(danger ? 'trash' : 'help')}</div><h2>${esc(title)}</h2><p class="muted">${esc(message)}</p><div class="modal-actions" style="justify-content:center"><button class="btn secondary" data-no>Cancel</button><button class="btn ${danger ? 'danger' : 'primary'}" data-yes>${esc(confirmText)}</button></div></div>`;
    document.body.appendChild(bd);
    requestAnimationFrame(() => bd.classList.add('show')); lockScroll();
    const done = v => { bd.classList.remove('show'); setTimeout(() => { bd.remove(); lockScroll(); }, 230); resolve(v); };
    bd.querySelector('[data-yes]').onclick = () => done(true);
    bd.querySelector('[data-no]').onclick = () => done(false);
    bd.addEventListener('click', e => { if (e.target === bd) done(false); });
  });
}
document.addEventListener('keydown', e => {
  if (e.key !== 'Escape') return;
  const open = $$('.modal-backdrop.show').pop();
  if (open && !open.hasAttribute('data-static')) closeModal(open);
});

/* ---------- theme ---------- */
const mq = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
function applyTheme(pref, animate = true) {
  const dark = pref === 'dark' || (pref === 'system' && mq && mq.matches);
  const h = document.documentElement;
  if (animate) { h.classList.add('theme-switching'); setTimeout(() => h.classList.remove('theme-switching'), 500); }
  h.setAttribute('data-theme', dark ? 'dark' : 'light');
  h.setAttribute('data-theme-pref', pref);
  try { localStorage.setItem('ojt360-theme', pref); localStorage.setItem('ojt360-dark', dark ? '1' : '0'); } catch (e) {}
  emit('theme', dark ? 'dark' : 'light');
}
if (mq && mq.addEventListener) mq.addEventListener('change', () => { if (document.documentElement.getAttribute('data-theme-pref') === 'system') applyTheme('system'); });
$('#themeQuick')?.addEventListener('click', () => applyTheme(document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'));

/* ---------- sidebar ---------- */
const sidebar = $('#sidebar'), backdrop = $('#sidebarBackdrop');
const desktop = () => window.matchMedia('(min-width:1025px)').matches;
function setSidebarCollapsed(v) {
  document.documentElement.classList.toggle('sb-collapsed', v);
  try { localStorage.setItem('ojt360-sidebar', v ? 'collapsed' : 'open'); } catch (e) {}
}
function closeDrawer() { sidebar?.classList.remove('open'); backdrop?.classList.remove('show'); }
$('#menuBtn')?.addEventListener('click', () => {
  if (desktop()) setSidebarCollapsed(!document.documentElement.classList.contains('sb-collapsed'));
  else { const o = !sidebar.classList.contains('open'); sidebar.classList.toggle('open', o); backdrop.classList.toggle('show', o); }
});
backdrop?.addEventListener('click', closeDrawer);
$$('.sidebar a').forEach(a => a.addEventListener('click', () => { if (!desktop()) closeDrawer(); }));
$$('[data-logout]').forEach(b => b.addEventListener('click', () => openModal('#logoutModal')));

/* ---------- online pill ---------- */
const net = $('#netPill');
function updateNet() { if (!net) return; const on = navigator.onLine; net.classList.toggle('offline', !on); $('span', net).textContent = on ? 'Online' : 'Offline'; }
window.addEventListener('online', updateNet); window.addEventListener('offline', updateNet); updateNet();

/* ---------- profile photo (menu, crop, view, delete) ---------- */
const avatarEls = () => $$('.mini-card .avatar, [data-avatar], [data-avatar-xl]');
function setAvatar(url) {
  C.profileImage = url || '';
  const initials = (C.userName || 'U').trim().split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase();
  avatarEls().forEach(el => { el.innerHTML = url ? `<img src="${esc(url)}" alt="Profile picture">` : esc(initials); });
  const vi = $('#profileViewImage'), vs = $('#profileViewInitials');
  if (vi) { vi.style.display = url ? 'block' : 'none'; if (url) vi.src = url; }
  if (vs) vs.style.display = url ? 'none' : 'block';
  emit('avatar', url);
}
const pm = $('#profileMenuButton'), pd = $('#profileDropdown');
pm?.addEventListener('click', e => { e.stopPropagation(); const s = !pd.classList.contains('show'); pd.classList.toggle('show', s); pm.setAttribute('aria-expanded', s); });
pd?.addEventListener('click', e => e.stopPropagation());
document.addEventListener('click', () => { pd?.classList.remove('show'); $$('.avatar-menu.show').forEach(m => m.classList.remove('show')); });

const crop = { file: null, img: $('#profileCropImage'), stage: $('#profileCropStage'), zoom: $('#profileCropZoom'), x: 0, y: 0, scale: 1, base: 1, ready: false, drag: null };
function cropLayout() {
  if (!crop.ready) return;
  const S = crop.stage.clientWidth, nw = crop.img.naturalWidth, nh = crop.img.naturalHeight;
  const sc = crop.base * crop.scale, dw = nw * sc, dh = nh * sc;
  crop.x = Math.max(-(dw - S) / 2, Math.min((dw - S) / 2, crop.x));
  crop.y = Math.max(-(dh - S) / 2, Math.min((dh - S) / 2, crop.y));
  crop.img.style.transform = `translate(-50%,-50%) translate(${crop.x}px,${crop.y}px) scale(${sc})`;
}
function openCrop(file) {
  if (!file) return;
  if (!/^image\/(jpeg|png|webp)$/.test(file.type)) { toast('Only JPG, PNG, or WEBP images are allowed.', 'error'); return; }
  if (file.size > 8 * 1024 * 1024) { toast('Please choose an image under 8 MB.', 'error'); return; }
  crop.file = file; crop.scale = 1; crop.x = crop.y = 0; crop.ready = false; crop.zoom.value = 1;
  const url = URL.createObjectURL(file);
  crop.img.onload = () => { const S = crop.stage.clientWidth; crop.base = Math.max(S / crop.img.naturalWidth, S / crop.img.naturalHeight); crop.ready = true; cropLayout(); URL.revokeObjectURL(url); };
  crop.img.src = url; openModal('#profileCropModal');
}
crop.zoom?.addEventListener('input', () => { crop.scale = parseFloat(crop.zoom.value); cropLayout(); });
crop.stage?.addEventListener('pointerdown', e => { if (!crop.ready) return; crop.drag = { x: e.clientX - crop.x, y: e.clientY - crop.y }; crop.stage.setPointerCapture(e.pointerId); });
crop.stage?.addEventListener('pointermove', e => { if (!crop.drag) return; crop.x = e.clientX - crop.drag.x; crop.y = e.clientY - crop.drag.y; cropLayout(); });
['pointerup', 'pointercancel'].forEach(ev => crop.stage?.addEventListener(ev, () => { crop.drag = null; }));
crop.stage?.addEventListener('wheel', e => { e.preventDefault(); crop.scale = Math.min(3, Math.max(1, crop.scale - e.deltaY * 0.0015)); crop.zoom.value = crop.scale; cropLayout(); }, { passive: false });
const closeCrop = () => { closeModal('#profileCropModal'); crop.ready = false; crop.file = null; };
$('#profileCropCancel')?.addEventListener('click', closeCrop);
$('#profileCropClose')?.addEventListener('click', closeCrop);
$('#profileCropSave')?.addEventListener('click', async () => {
  if (!crop.ready) return;
  const S = crop.stage.clientWidth, sc = crop.base * crop.scale, dw = crop.img.naturalWidth * sc, dh = crop.img.naturalHeight * sc;
  const sx = (dw / 2 - S / 2 - crop.x) / sc, sy = (dh / 2 - S / 2 - crop.y) / sc, sw = S / sc;
  const cv = document.createElement('canvas'); cv.width = cv.height = 640;
  const ctx = cv.getContext('2d'); ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, 640, 640);
  ctx.drawImage(crop.img, sx, sy, sw, sw, 0, 0, 640, 640);
  const btn = $('#profileCropSave'); btn.disabled = true; btn.textContent = 'Saving…';
  cv.toBlob(async blob => {
    const fd = new FormData(); fd.append('profile_picture', blob, 'profile.jpg');
    const j = await api('api/profile_picture.php', fd);
    btn.disabled = false; btn.textContent = 'Save profile picture';
    if (j.ok) { setAvatar(j.url); closeCrop(); toast('Profile picture updated.', 'success'); } else toast(j.message || 'Upload failed.', 'error');
  }, 'image/jpeg', 0.92);
});
function bindPhotoInput(input) { input?.addEventListener('change', () => { const f = input.files && input.files[0]; if (f) openCrop(f); input.value = ''; }); }
bindPhotoInput($('#headerProfileInput'));
$('#headerUploadProfile')?.addEventListener('click', () => $('#headerProfileInput').click());
async function deletePhoto() {
  if (!C.profileImage) { toast('You have no profile picture to delete.', 'warn'); return; }
  if (!await confirmBox({ title: 'Delete profile photo?', message: 'Your initials will be shown instead.', confirmText: 'Delete', danger: true })) return;
  const j = await api('api/profile_picture.php', { action: 'delete' });
  if (j.ok) { setAvatar(''); toast('Profile photo removed.', 'success'); } else toast(j.message || 'Could not delete the photo.', 'error');
}
$('#headerDeleteProfile')?.addEventListener('click', deletePhoto);
function viewPhoto() { setAvatar(C.profileImage); openModal('#profilePictureViewModal'); }
$('#profileViewClose')?.addEventListener('click', () => closeModal('#profilePictureViewModal'));
// page-level avatar menu (profile.php)
$$('[data-avatar-menu]').forEach(btn => {
  const menu = $('#' + btn.dataset.avatarMenu);
  btn.addEventListener('click', e => { e.stopPropagation(); menu.classList.toggle('show'); });
  menu.addEventListener('click', e => e.stopPropagation());
});
$$('[data-photo-action]').forEach(b => b.addEventListener('click', () => {
  $$('.avatar-menu.show').forEach(m => m.classList.remove('show'));
  const a = b.dataset.photoAction;
  if (a === 'view') viewPhoto(); else if (a === 'upload') $('#profilePageInput')?.click(); else if (a === 'delete') deletePhoto();
}));
bindPhotoInput($('#profilePageInput'));
setAvatar(C.profileImage || '');

/* ---------- assistant ---------- */
(function(){
  const fab = $('#assistantFab'), panel = $('#assistantPanel'), box = $('#assistantMessages'), form = $('#assistantForm'), input = $('#assistantInput'), status = $('#assistantStatus');
  if (!fab) return;
  let loaded = false, busy = false; const greeting = box.innerHTML;
  const add = (text, who) => { const d = document.createElement('div'); d.className = who === 'user' ? 'user-bubble' : 'assistant-bubble'; d.textContent = text; box.appendChild(d); box.scrollTop = box.scrollHeight; return d; };
  async function history() { if (loaded) return; const j = await getJSON('api/assistant.php?action=history'); box.innerHTML = ''; if (j.ok && j.history && j.history.length) j.history.forEach(m => add(m.message, m.role)); else box.innerHTML = greeting; loaded = true; }
  async function ask(text) {
    if (busy) return; busy = true; add(text, 'user'); const t = add('Thinking…', 'assistant'); status.textContent = 'AI Assistant · thinking';
    const j = await api('api/assistant.php', { message: text });
    t.textContent = j.ok ? (j.reply || 'No response returned.') : (j.error || j.message || 'I could not process that request.');
    status.textContent = j.ok ? (j.mode === 'gemini' ? 'AI Assistant · online' : 'AI Assistant · setup mode') : 'AI Assistant · error';
    busy = false; box.scrollTop = box.scrollHeight;
  }
  fab.addEventListener('click', async () => { panel.classList.toggle('show'); if (panel.classList.contains('show')) { await history(); input.focus(); } });
  $('#assistantClose').addEventListener('click', () => panel.classList.remove('show'));
  form.addEventListener('submit', e => { e.preventDefault(); const v = input.value.trim(); if (v) { input.value = ''; ask(v); } });
  $$('[data-assist]').forEach(b => b.addEventListener('click', () => ask(b.dataset.assist)));
})();

/* =====================================================================
   Time tracker: one state machine shared by every page
   ===================================================================== */
const T = {
  state: C.tracker || { status: 'idle', session: null, today: { net_seconds: 0, break_seconds: 0 }, completed_hours: 0, required_hours: 480, events: [], server_now: Math.floor(Date.now() / 1000) },
  offset: 0,
  mode: 'timein',
  now() { return Date.now() / 1000 + this.offset; },
  set(state) { this.state = state; this.offset = state.server_now - Date.now() / 1000; emit('state', this.derive()); this.paintButtons(); },
  derive() {
    const s = this.state, now = this.now();
    const d = { status: s.status, session: s.session, sessionTotal: 0, sessionBreak: 0, sessionNet: 0, todayNet: s.today.net_seconds, todayBreak: s.today.break_seconds, required: s.required_hours, events: s.events || [] };
    if (s.session) {
      const total = Math.max(0, now - s.session.time_in_ts);
      let brk = s.session.break_seconds + (s.session.break_started_ts ? Math.max(0, now - s.session.break_started_ts) : 0);
      brk = Math.min(brk, total);
      d.sessionTotal = total; d.sessionBreak = brk; d.sessionNet = Math.max(0, total - brk);
      d.todayNet += d.sessionNet; d.todayBreak += brk;
    }
    d.completed = Math.min(d.required, s.completed_hours + d.sessionNet / 3600);
    d.pct = d.required > 0 ? Math.min(100, d.completed / d.required * 100) : 0;
    return d;
  },
  paintButtons() {
    const st = this.state.status;
    $$('[data-track="timein"]').forEach(b => { b.disabled = st !== 'idle'; });
    $$('[data-track="timeout"]').forEach(b => { b.disabled = st === 'idle'; });
    $$('[data-track="break"]').forEach(b => {
      b.disabled = st === 'idle';
      const lbl = $('.lbl', b), ic = $('.btn-ic', b);
      if (lbl) lbl.textContent = st === 'break' ? 'Resume Work' : 'Take a Break';
      if (ic) ic.innerHTML = icon(st === 'break' ? 'play' : 'coffee');
      b.classList.toggle('is-resume', st === 'break');
    });
    document.body.dataset.track = st;
  },
  async refresh() { const j = await getJSON('api/attendance.php?action=state'); if (j.ok) this.set(j.state); return j; },
  async act(action, extra = {}) {
    const j = await api('api/attendance.php', Object.assign({ action }, extra));
    if (j.state) this.set(j.state);
    return j;
  },
  async breakToggle() {
    const j = await this.act(this.state.status === 'break' ? 'break_end' : 'break_start');
    toast(j.message || (j.ok ? 'Updated.' : 'Could not update.'), j.ok ? 'success' : 'error');
    return j;
  }
};
T.offset = T.state.server_now - Date.now() / 1000;
OJT_expose();

/* chip in the top bar, updated every second */
function tick() {
  const d = T.derive(); const chip = $('#trackChip');
  if (chip) {
    chip.hidden = d.status === 'idle';
    chip.classList.toggle('is-break', d.status === 'break');
    $('[data-chip-label]', chip).textContent = d.status === 'break' ? 'On break' : 'Working';
    $('[data-chip-time]', chip).textContent = hms(d.status === 'break' && T.state.session?.break_started_ts ? T.now() - T.state.session.break_started_ts : d.sessionNet);
  }
  emit('tick', d);
}
setInterval(tick, 1000);

/* ----- QR scanner modal ----- */
const scan = { qr: null, busy: false };
function setScanStatus(msg, cls = '') { const el = $('#scanStatus'); if (!el) return; el.textContent = msg; el.className = 'scan-status ' + cls; }
function loadQrLib() {
  if (window.Html5Qrcode) return Promise.resolve();
  return new Promise((resolve, reject) => {
    const urls = ['https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js', 'https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js'];
    const tryNext = i => { if (i >= urls.length) return reject(new Error('QR library failed to load')); const s = document.createElement('script'); s.src = urls[i]; s.onload = resolve; s.onerror = () => tryNext(i + 1); document.head.appendChild(s); };
    tryNext(0);
  });
}
async function stopCamera() { if (scan.qr) { try { await scan.qr.stop(); } catch (e) {} try { scan.qr.clear(); } catch (e) {} scan.qr = null; } }
async function startCamera() {
  if (scan.qr) return;
  setScanStatus('Starting camera…');
  try { await loadQrLib(); } catch (e) { setScanStatus('The QR library could not load (offline?). Use the manual code box below.', 'bad'); return; }
  $('#reader').innerHTML = '';
  scan.qr = new Html5Qrcode('reader');
  try {
    await scan.qr.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 230, height: 230 } }, async text => { await stopCamera(); setScanStatus('QR detected. Validating…'); await submitQr(text); });
    setScanStatus('Camera active. Point it at the company QR code.');
  } catch (e) { scan.qr = null; setScanStatus('Camera could not start. Allow camera access, or enter the code manually below.', 'bad'); }
}
async function submitQr(value) {
  if (scan.busy) return; scan.busy = true; setScanStatus('Validating on the server…');
  const j = await T.act('scan', { qr_value: value });
  scan.busy = false;
  if (j.ok) {
    setScanStatus(j.message, 'ok'); toast(j.message, 'success');
    setTimeout(() => { closeScan(); if (j.action === 'timeout') emit('timeout', j); else emit('timein', j); }, 650);
  } else { setScanStatus(j.message || 'That QR code was not accepted.', 'bad'); }
}
function openScan(mode) {
  T.mode = mode;
  const isIn = mode === 'timein';
  $('#scanEyebrow').textContent = isIn ? 'TIME IN' : 'TIME OUT';
  $('#scanTitle').textContent = isIn ? 'Scan your company QR to start' : 'Scan your company QR to finish';
  $('#scanHint').textContent = isIn ? 'Your timer starts as soon as the code is validated.' : 'Your hours are saved and today\'s daily log opens for your details.';
  $('#manualQr').value = ''; $('#reader').innerHTML = '<div class="qr-placeholder">' + icon('qr') + '<span>Camera preview appears here</span></div>';
  setScanStatus('Ready to scan.'); openModal('#scanModal'); startCamera();
}
async function closeScan() { await stopCamera(); closeModal('#scanModal'); }
$$('[data-scan-close]').forEach(b => b.addEventListener('click', closeScan));
on('modal:close', el => { if (el && el.id === 'scanModal') stopCamera(); });
$('#startScan')?.addEventListener('click', startCamera);
$('#stopScan')?.addEventListener('click', async () => { await stopCamera(); setScanStatus('Scanner stopped.'); });
$('#manualScan')?.addEventListener('click', () => { const v = $('#manualQr').value.trim(); if (v) { stopCamera(); submitQr(v); } else setScanStatus('Type or paste a code first.', 'bad'); });
$('#manualQr')?.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); $('#manualScan').click(); } });

document.addEventListener('click', async e => {
  const b = e.target.closest('[data-track]'); if (!b || b.disabled) return;
  const a = b.dataset.track;
  if (a === 'timein') openScan('timein');
  else if (a === 'timeout') {
    if (T.state.status === 'break') toast('Your break will be ended automatically when you time out.', 'info');
    openScan('timeout');
  } else if (a === 'break') { b.disabled = true; await T.breakToggle(); T.paintButtons(); }
});
// after Time Out, open the daily log form so the student can describe the work
on('timeout', j => { if (j.log_id) { toast('Add what you worked on in today\'s daily log.', 'info', 2600); setTimeout(() => { location.href = 'daily_logs.php?edit=' + j.log_id; }, 900); } else location.reload(); });
on('timein', () => { if (!/(index|attendance)\.php$|\/$/.test(location.pathname)) location.href = 'index.php'; else location.reload(); });

function OJT_expose() {
  window.OJT = { C, $, $$, pad, hms, hm, esc, icon, api, getJSON, toast, openModal, closeModal, confirm: confirmBox, on, emit, tracker: T, applyTheme, setAvatar, openScan, stopCamera };
}
OJT_expose();
T.paintButtons();
tick();
if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) document.documentElement.classList.add('reduce-motion');
document.addEventListener('visibilitychange', () => { if (!document.hidden) T.refresh(); });
})();
