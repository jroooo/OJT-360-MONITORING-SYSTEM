/* Attendance: calendar + history + timeline */
(function(){
'use strict';
const { $, $$, hm, hms, esc, icon, getJSON, openModal, closeModal, tracker: T, on } = window.OJT;
let month = new Date().toISOString().slice(0, 7);
const now = new Date(); const curMonth = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
month = curMonth;
let data = null, view = 'calendar';
const mins = m => { m = Math.max(0, Math.round(m)); return Math.floor(m / 60) + 'h ' + String(m % 60).padStart(2, '0') + 'm'; };
const hrs = m => (m / 60).toFixed(1) + 'h';
const shift = (m, delta) => { const [y, mo] = m.split('-').map(Number); const d = new Date(y, mo - 1 + delta, 1); return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0'); };

/* ----- live strip ----- */
function live(d) {
  const st = d.status, bar = $('#trackerCard'); bar.dataset.state = st;
  $('#attStatusText').textContent = st === 'working' ? 'Timed in · working' : st === 'break' ? 'On a break' : 'Not timed in';
  $('#attStatusSub').textContent = st === 'idle' ? 'Scan your company QR to begin.' : 'Since ' + d.session.time_in_label;
  $('#attClock').textContent = st === 'idle' ? hms(d.todayNet) : hms(d.sessionNet);
  $('#mToday').textContent = hm(d.todayNet);
  $('#mTotal').textContent = d.completed.toFixed(2) + ' h';
  $('#mRemain').textContent = Math.max(0, d.required - d.completed).toFixed(2) + ' h';
}
on('tick', live); on('state', d => { live(d); if (month === curMonth) load(); });
live(T.derive());

/* ----- data ----- */
async function load() {
  const j = await getJSON('api/attendance.php?action=month&month=' + month);
  if (!j.ok) return;
  data = j; paint();
}
function paint() {
  const [y, m] = month.split('-').map(Number);
  $('#monthTitle').textContent = new Date(y, m - 1, 1).toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
  $('#monthToday').style.visibility = month === curMonth ? 'hidden' : 'visible';
  if (view === 'calendar') paintCalendar(); else if (view === 'history') paintHistory(); else paintTimeline();
}

function paintCalendar() {
  const [y, m] = month.split('-').map(Number);
  const first = new Date(y, m - 1, 1), days = new Date(y, m, 0).getDate(), start = first.getDay();
  const prevDays = new Date(y, m - 1, 0).getDate();
  const today = data ? data.today : '';
  let html = '';
  for (let i = 0; i < start; i++) html += `<div class="cal-cell out"><span>${prevDays - start + 1 + i}</span></div>`;
  for (let d = 1; d <= days; d++) {
    const ds = `${month}-${String(d).padStart(2, '0')}`;
    const info = data && data.days[ds];
    const st = info ? info.status : '';
    const isToday = ds === today;
    let meta = '';
    if (info) {
      const parts = [];
      if (info.minutes > 0 || info.open) parts.push(`<b>${hrs(info.minutes)}</b>`);
      if (info.sessions > 0) parts.push(`<small>${info.sessions} session${info.sessions > 1 ? 's' : ''}</small>`);
      else if (info.logs > 0) parts.push(`<small>${info.logs} log${info.logs > 1 ? 's' : ''}</small>`);
      meta = `<div class="cal-meta">${parts.join('')}</div>`;
    }
    html += `<button class="cal-cell ${st ? 'st-' + st : ''} ${isToday ? 'today' : ''}" data-date="${ds}" aria-label="${ds}"><span class="num">${d}</span>${st ? `<i class="cal-dot"></i>` : ''}${meta}</button>`;
  }
  const total = start + days, tail = (7 - total % 7) % 7;
  for (let i = 1; i <= tail; i++) html += `<div class="cal-cell out"><span>${i}</span></div>`;
  $('#calGrid').innerHTML = html;
}

function paintHistory() {
  const rows = data ? data.sessions : [];
  if (!rows.length) { $('#viewHistory').innerHTML = `<div class="empty-state"><div class="empty-icon">${icon('clock')}</div><h3>No attendance records this month</h3><p>Time In and Time Out records will appear here.</p></div>`; return; }
  $('#viewHistory').innerHTML = `<div class="table-wrap"><table><thead><tr><th>Date</th><th>Company</th><th>Time in</th><th>Time out</th><th>Break</th><th>Rendered</th><th>Status</th></tr></thead><tbody>${rows.slice().reverse().map(r => `<tr><td><b>${new Date(r.date + 'T00:00:00').toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })}</b></td><td>${esc(r.company)}</td><td>${r.time_in}</td><td>${r.time_out || '—'}</td><td>${r.break_min ? mins(r.break_min) : '—'}</td><td><b>${mins(r.net_min)}</b></td><td><span class="pill ${r.open ? (r.on_break ? 'amber-pill' : 'blue-pill') : 'green-pill'}">${r.open ? (r.on_break ? 'On break' : 'Active') : 'Completed'}</span></td></tr>`).join('')}</tbody></table></div>`;
}

function paintTimeline() {
  const ev = data ? data.events : [];
  if (!ev.length) { $('#viewTimeline').innerHTML = `<div class="empty-state"><div class="empty-icon">${icon('activity')}</div><h3>Nothing on the timeline yet</h3><p>Every time in, break, and time out is listed here in order.</p></div>`; return; }
  const by = {}; ev.forEach(e => { (by[e.date] = by[e.date] || []).push(e); });
  const names = { time_in: ['Timed in', 'in', 'login'], break_start: ['Started a break', 'break', 'coffee'], break_end: ['Back from break', 'in', 'play'], time_out: ['Timed out', 'out', 'logout'] };
  $('#viewTimeline').innerHTML = `<div class="tl-days">${Object.keys(by).sort().reverse().map(d => `<div class="tl-day"><h4>${new Date(d + 'T00:00:00').toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' })}</h4><div class="tl-rail">${by[d].map(e => `<div class="tl-row ${names[e.type][1]}"><span class="tl-ic">${icon(names[e.type][2])}</span><div><b>${names[e.type][0]}</b><small>${e.time}</small></div></div>`).join('')}</div></div>`).join('')}</div>`;
}

/* ----- day modal ----- */
async function openDay(date) {
  $('#dayTitle').textContent = new Date(date + 'T00:00:00').toLocaleDateString(undefined, { weekday: 'short', month: 'long', day: 'numeric', year: 'numeric' });
  $('#dayBody').innerHTML = '<div class="empty">Loading…</div>';
  $('#dayAddLog').href = 'daily_logs.php?new=1&date=' + date; openModal('#dayModal');
  const j = await getJSON('api/attendance.php?action=day&date=' + date);
  if (!j.ok) { $('#dayBody').innerHTML = '<div class="empty">Could not load this date.</div>'; return; }
  const evNames = { time_in: 'Time in', break_start: 'Break', break_end: 'Resumed', time_out: 'Time out' };
  let h = '<section class="day-sec"><h4>Attendance</h4>';
  h += j.sessions.length ? j.sessions.map(s => `<div class="day-card"><div class="day-line"><b>${s.time_in} → ${s.time_out || '<em class="live-text">in progress</em>'}</b><span class="pill ${s.open ? 'blue-pill' : 'green-pill'}">${mins(s.net_min)} rendered</span></div><small class="muted">${esc(s.company)}${s.break_min ? ' · ' + mins(s.break_min) + ' break' : ''}</small>${s.events.length > 2 ? `<div class="day-events">${s.events.map(e => `<span>${evNames[e.type]} ${e.time}</span>`).join('')}</div>` : ''}</div>`).join('') : '<p class="muted">No attendance recorded.</p>';
  h += '</section><section class="day-sec"><h4>Daily Log</h4>';
  h += j.logs.length ? j.logs.map(l => `<div class="day-card"><div class="day-line"><b>${l.title ? esc(l.title) : '<span class="warn-text">Needs details</span>'}</b><a class="text-link" href="daily_logs.php?edit=${l.id}">${l.title ? 'Edit' : 'Add details'}</a></div>${l.description ? `<p class="day-desc">${esc(l.description).replace(/\n/g, '<br>')}</p>` : ''}<small class="muted">${l.hours.toFixed(2)} hours${l.time_in ? ' · ' + l.time_in + (l.time_out ? ' – ' + l.time_out : '') : ''}</small>${l.evidence ? `<img class="day-img" src="${l.evidence}" alt="Evidence" data-zoom>` : ''}</div>`).join('') : '<p class="muted">No daily log for this date.</p>';
  h += '</section>';
  $('#dayBody').innerHTML = h;
}
document.addEventListener('click', e => {
  const c = e.target.closest('.cal-cell[data-date]'); if (c) openDay(c.dataset.date);
  const z = e.target.closest('[data-zoom]'); if (z) { $('#lightboxImg').src = z.src; openModal('#lightbox'); }
  const t = e.target.closest('.tab[data-view]'); if (t) {
    view = t.dataset.view; $$('.tab[data-view]').forEach(x => x.classList.toggle('active', x === t));
    ['calendar', 'history', 'timeline'].forEach(v => $('#view' + v[0].toUpperCase() + v.slice(1)).hidden = v !== view); paint();
  }
});
$('#monthPrev').onclick = () => { month = shift(month, -1); load(); };
$('#monthNext').onclick = () => { month = shift(month, 1); load(); };
$('#monthToday').onclick = () => { month = curMonth; load(); };
$('#calGrid').innerHTML = ''; paintCalendar(); load();
})();
