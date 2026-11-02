/* Dashboard: live timer, flowing wave, sparklines, progress ring */
(function(){
'use strict';
const { $, $$, hms, hm, tracker: T, on, icon, esc } = window.OJT;
const card = $('#trackerCard'); if (!card) return;
const dashboardData = window.OJT_DASH || { completed: 0, required: 480, todaySeconds: 0, lastTimeIn: null, lastTimeOut: null };

/* ---------- live text ---------- */
const el = id => document.getElementById(id);
function render(d) {
  const st = d.status;
  card.dataset.state = st;
  const evs = d.events || [];
  const firstIn = evs.find(e => e.type === 'time_in');
  const lastOut = [...evs].reverse().find(e => e.type === 'time_out');
  const shift = d.session ? (d.session.slot === 'afternoon' ? 'Afternoon' : 'Morning') : '';
  const sessionCredit = d.session ? Math.min(4 * 3600, Math.floor(d.sessionElapsed / 3600) * 3600) : 0;
  const todayCredit = Math.min(8 * 3600, dashboardData.todaySeconds + sessionCredit);
  const completedHours = Math.min(dashboardData.required, dashboardData.completed + sessionCredit / 3600);
  const liveClock = el('liveClock');
  liveClock.textContent = st === 'idle' ? hms(todayCredit) : hms(d.sessionElapsed);
  liveClock.style.color = d.timerState === 'warning' ? 'var(--amber)' : d.timerState === 'overdue' ? 'var(--red)' : '';
  el('liveClockLabel').textContent = st === 'idle'
    ? 'Credited today'
    : d.timerState === 'overdue' ? 'Over 4-hour shift limit - live timer continues'
      : d.timerState === 'warning' ? 'Approaching 4-hour shift limit'
        : st === 'scheduled' ? 'Attendance timer will begin at Time In' : 'Elapsed attendance time';
  const dailyCredit = hm(todayCredit) + ' / 8h';
  el('trackerSub').textContent = st === 'idle'
    ? 'Daily requirement: 8 hours. Credited today: ' + dailyCredit + '. Only full hours count toward rendered credit.'
    : st === 'scheduled'
      ? shift + ' shift starts at ' + d.session.time_in_label + '. Daily requirement: 8 hours.'
      : shift + ' shift: ' + hm(sessionCredit) + ' credited (4h maximum). Daily credit: ' + dailyCredit + '. Elapsed time continues live; only full hours count toward rendered credit.';
  el('tmIn').textContent = st !== 'idle' ? d.session.time_in_label : (firstIn ? (firstIn.actual_label || firstIn.label) : (dashboardData.lastTimeIn || '--'));
  el('tmOut').textContent = lastOut ? (lastOut.actual_label || lastOut.label) : (st === 'idle' ? (dashboardData.lastTimeOut || '--') : '--');
  el('tmToday').textContent = dailyCredit;
  const vs = el('vizState');
  vs.className = 'viz-state ' + st;
  vs.querySelector('em').textContent = st === 'working' ? 'Live' : st === 'scheduled' ? 'Scheduled' : 'Idle';
  el('vizNote').textContent = st === 'idle'
    ? dashboardData.lastTimeIn
      ? 'Last recorded Time In: ' + dashboardData.lastTimeIn + (dashboardData.lastTimeOut ? ' · Time Out: ' + dashboardData.lastTimeOut : '') + '. Daily requirement: 4 hours in the morning and 4 in the afternoon.'
      : 'Daily requirement: 4 hours in the morning and 4 in the afternoon.'
    : st === 'scheduled'
      ? 'Your timer starts from the actual scan time.'
      : d.timerState === 'overdue'
        ? 'The timer continues; credited hours remain capped at 4 for this shift.'
        : 'Live elapsed time is shown; credited time is capped at 4 hours per shift.';
  // progress
  const pct = dashboardData.required > 0 ? Math.min(100, completedHours / dashboardData.required * 100) : 0;
  const shown = pct >= 100 ? 100 : Math.round(pct * 10) / 10;
  el('statRendered').textContent = completedHours.toFixed(0);
  el('statRemaining').textContent = Math.max(0, dashboardData.required - completedHours).toFixed(0) + ' hours remaining';
  el('progressRing').style.setProperty('--progress', pct.toFixed(2) + '%');
  el('ringPct').textContent = shown + '%'; el('pctPill').textContent = shown + '%'; el('goalPct').textContent = shown + '%';
  el('goalFill').style.width = pct.toFixed(2) + '%';
  el('goalDone').textContent = completedHours.toFixed(0) + ' rendered';
  el('metaDone').textContent = completedHours.toFixed(0) + ' hrs';
}
let lastEventsKey = '';
function renderTimeline(d) {
  const evs = d.events || [];
  const key = JSON.stringify(evs); if (key === lastEventsKey) return; lastEventsKey = key;
  const names = { time_in: ['Time in', 'in'], break_start: ['Break started', 'break'], break_end: ['Back to work', 'in'], time_out: ['Time out', 'out'] };
  el('timelineMini').innerHTML = evs.length ? evs.map(e => `<div class="tl-item ${names[e.type][1]}"><i></i><b>${esc(e.actual_label || e.label)}</b><span>${names[e.type][0]}</span></div>`).join('') : '<div class="tl-empty">Today\'s timeline will appear here.</div>';
}
on('tick', d => render(d));
on('state', d => { render(d); renderTimeline(d); });
const d0 = T.derive(); render(d0); renderTimeline(d0);

/* ---------- flowing wave ---------- */
const cv = el('waveCanvas'), ctx = cv.getContext('2d');
let W = 0, H = 0, dpr = Math.min(2, window.devicePixelRatio || 1), phase = 0, amp = 0.06, last = performance.now();
const colors = {};
function readColors() {
  const cs = getComputedStyle(document.documentElement);
  colors.primary = cs.getPropertyValue('--primary').trim() || '#0ea5e9';
  colors.green = cs.getPropertyValue('--green').trim() || '#16a34a';
  colors.amber = cs.getPropertyValue('--amber').trim() || '#f59e0b';
  colors.line = cs.getPropertyValue('--line-2').trim() || '#cbd9e6';
}
readColors(); on('theme', () => setTimeout(readColors, 60));
function resize() { const r = cv.getBoundingClientRect(); W = Math.max(200, r.width); H = r.height || 170; cv.width = W * dpr; cv.height = H * dpr; ctx.setTransform(dpr, 0, 0, dpr, 0, 0); }
resize(); window.addEventListener('resize', resize);
const hex2rgba = (c, a) => { c = c.trim(); if (c[0] === '#') { if (c.length === 4) c = '#' + c[1] + c[1] + c[2] + c[2] + c[3] + c[3]; const n = parseInt(c.slice(1), 16); return `rgba(${n >> 16},${n >> 8 & 255},${n & 255},${a})`; } return c.replace(/rgb\((.*)\)/, `rgba($1,${a})`); };
function frame(now) {
  requestAnimationFrame(frame);
  if (document.hidden) { last = now; return; }
  const dt = Math.min(0.05, (now - last) / 1000); last = now;
  const st = T.derive().status;
  const targetAmp = st === 'working' ? 0.34 : st === 'break' ? 0.10 : 0.035;
  const speed = st === 'working' ? 2.4 : st === 'break' ? 0.7 : 0.35;
  amp += (targetAmp - amp) * Math.min(1, dt * 3);
  phase += dt * speed;
  const base = st === 'idle' ? colors.line : st === 'break' ? colors.amber : colors.primary;
  ctx.clearRect(0, 0, W, H);
  // faint grid
  ctx.strokeStyle = hex2rgba(colors.line, 0.35); ctx.lineWidth = 1; ctx.beginPath();
  for (let y = H * 0.2; y < H; y += H * 0.2) { ctx.moveTo(0, y); ctx.lineTo(W, y); } ctx.stroke();
  const layers = [{ k: 1, o: 0, a: 0.30, f: 0.9 }, { k: 0.72, o: 1.7, a: 0.22, f: 1.25 }, { k: 0.5, o: 3.1, a: 0.16, f: 1.7 }];
  layers.forEach((L, i) => {
    ctx.beginPath();
    for (let x = 0; x <= W; x += 3) {
      const t = x / W;
      const env = Math.sin(Math.PI * t) * 0.55 + 0.45;
      const y = H * 0.55 + Math.sin(t * 6.2 * L.f + phase * (1 + i * 0.25) + L.o) * H * amp * L.k * env + Math.sin(t * 13 + phase * 1.7 + L.o) * H * amp * 0.18 * L.k;
      x === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
    }
    ctx.lineTo(W, H); ctx.lineTo(0, H); ctx.closePath();
    const g = ctx.createLinearGradient(0, 0, 0, H); g.addColorStop(0, hex2rgba(base, L.a)); g.addColorStop(1, hex2rgba(base, 0.01));
    ctx.fillStyle = g; ctx.fill();
    if (i === 0) { ctx.lineWidth = 2.4; ctx.strokeStyle = hex2rgba(base, st === 'idle' ? 0.9 : 1); ctx.stroke(); }
  });
  // moving "now" pulse at the right edge when working
  if (st === 'working') {
    const t = 0.97, y = H * 0.55 + Math.sin(t * 6.2 * 0.9 + phase) * H * amp * (Math.sin(Math.PI * t) * 0.55 + 0.45) + Math.sin(t * 13 + phase * 1.7) * H * amp * 0.18;
    ctx.beginPath(); ctx.arc(W * t, y, 5 + Math.sin(phase * 4) * 1.2, 0, 7); ctx.fillStyle = hex2rgba(base, 0.25); ctx.fill();
    ctx.beginPath(); ctx.arc(W * t, y, 3.2, 0, 7); ctx.fillStyle = base; ctx.fill();
  }
}
requestAnimationFrame(frame);

/* ---------- sparklines ---------- */
function drawSpark(svg) {
  let data; try { data = JSON.parse(svg.dataset.series || '[]'); } catch (e) { data = []; }
  const series = Array.isArray(data) && data.length
    ? data.map(v => Number.isFinite(Number(v)) ? Math.max(0, Number(v)) : 0)
    : Array(14).fill(0);
  const has = series.some(v => v > 0);
  const n = series.length;
  const max = Math.max(...series, 1);
  const pts = series.map((v, i) => [i / Math.max(1, n - 1) * 160, 62 - (v / max) * 50]);
  let dstr = `M${pts[0][0]},${pts[0][1]}`;
  for (let i = 1; i < pts.length; i++) { const [x0, y0] = pts[i - 1], [x1, y1] = pts[i], cx = (x0 + x1) / 2; dstr += ` C${cx},${y0} ${cx},${y1} ${x1},${y1}`; }
  const id = 'sg' + Math.random().toString(36).slice(2, 7);
  svg.innerHTML = `<defs><linearGradient id="${id}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="var(--spark)" stop-opacity=".55"/><stop offset="1" stop-color="var(--spark)" stop-opacity="0"/></linearGradient></defs><path d="${dstr} L160,70 L0,70 Z" fill="url(#${id})" opacity="${has ? 1 : .55}"/><path d="${dstr}" fill="none" stroke="var(--spark)" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linecap="round" opacity="${has ? 1 : .5}"/>`;
  svg.style.setProperty('--spark', `var(--${svg.dataset.color || 'primary'})`);
}
$$('.spark').forEach(drawSpark);
})();
