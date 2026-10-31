/* Attendance: calendar + history + timeline */
(function(){
'use strict';
const { $, $$, hm, hms, esc, icon, getJSON, openModal, closeModal, toast, tracker: T, on } = window.OJT;
let month = new Date().toISOString().slice(0, 7);
const now = new Date(); const curMonth = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
month = curMonth;
let data = null, view = 'calendar', loadSequence = 0, daySequence = 0;
const mins = m => { m = Math.max(0, Math.round(m)); return Math.floor(m / 60) + 'h ' + String(m % 60).padStart(2, '0') + 'm'; };
const hrs = m => (m / 60).toFixed(1) + 'h';
const shift = (m, delta) => { const [y, mo] = m.split('-').map(Number); const d = new Date(y, mo - 1 + delta, 1); return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0'); };

/* ----- live strip ----- */
function live(d) {
  const st = d.status, bar = $('#trackerCard'); bar.dataset.state = st;
  const shift = d.session ? (d.session.slot === 'afternoon' ? 'Afternoon' : 'Morning') : '';
  $('#attStatusText').textContent = st === 'working' ? shift + ' shift · active' : st === 'scheduled' ? shift + ' shift · scheduled' : 'Not timed in';
  $('#attClock').textContent = st === 'idle' ? hms(d.todayNet) : hms(d.sessionElapsed);
  $('#attClock').style.color = d.timerState === 'warning' ? 'var(--amber)' : d.timerState === 'overdue' ? 'var(--red)' : '';
  $('#mToday').textContent = hm(d.todayNet) + ' / 8h';
  $('#attStatusSub').textContent = st === 'idle'
    ? 'Scan your company QR to begin.'
    : st === 'scheduled'
      ? 'Your actual Time In was ' + d.session.time_in_label + '. Daily requirement: 8 hours.'
      : shift + ' shift: ' + hm(d.sessionNet) + ' credited (4h maximum). Daily credit: ' + hm(d.todayNet) + ' of 8h. Your actual checkout time is recorded when you Time Out.';
  $('#mTotal').textContent = d.completed.toFixed(2) + ' h';
  $('#mRemain').textContent = Math.max(0, d.required - d.completed).toFixed(2) + ' h';
}
on('tick', live); on('state', d => { live(d); if (month === curMonth) load(); });
live(T.derive());

/* ----- data ----- */
async function load() {
  const requestedMonth = month, sequence = ++loadSequence;
  const j = await getJSON('api/attendance.php?action=month&month=' + requestedMonth);
  if (sequence !== loadSequence || requestedMonth !== month) return;
  if (!j.ok) { toast(j.message || j.error || 'Could not load attendance for this month.', 'error'); return; }
  const warning = $('#attendanceWarning');
  warning.hidden = !j.status_warnings?.length;
  warning.textContent = (j.status_warnings || []).join(' ');
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
      if (info.status === 'absent') parts.push('<small>Absent</small>');
      else if (info.status === 'incomplete') parts.push('<small>Incomplete</small>');
      meta = `<div class="cal-meta">${parts.join('')}</div>`;
    }
    html += `<button class="cal-cell ${st ? 'st-' + st : ''} ${isToday ? 'today' : ''}" data-date="${ds}" aria-label="${ds}"><span class="num">${d}</span>${st ? `<i class="cal-dot"></i>` : ''}${meta}</button>`;
  }
  const total = start + days, tail = (7 - total % 7) % 7;
  for (let i = 1; i <= tail; i++) html += `<div class="cal-cell out"><span>${i}</span></div>`;
  $('#calGrid').innerHTML = html;
}

function paintHistory() {
  const rows = data ? data.sessions.map(session => ({
    ...session,
    status: data.days[session.date]?.status || 'attendance'
  })).concat(Object.entries(data.days || {}).filter(([, day]) => day.status === 'absent').map(([date, day]) => ({
    date, company: '', time_in: '—', time_out: null, net_min: 0, open: false, status: day.status, reason: day.reason
  }))).sort((a, b) => a.date.localeCompare(b.date)) : [];
  if (!rows.length) { $('#viewHistory').innerHTML = `<div class="empty-state"><div class="empty-icon">${icon('clock')}</div><h3>No attendance records this month</h3><p>Time In and Time Out records will appear here.</p></div>`; return; }
  $('#viewHistory').innerHTML = `<div class="table-wrap"><table><thead><tr><th>Date</th><th>Company</th><th>Time in</th><th>Time out</th><th>Rendered</th><th>Status</th></tr></thead><tbody>${rows.slice().reverse().map(r => {
    const statusLabels = { absent: 'Absent', incomplete: 'Incomplete', progress: 'In progress', complete: 'Complete', attendance: 'Attendance' };
    const statusClasses = { absent: 'red-pill', incomplete: 'amber-pill', progress: 'blue-pill', complete: 'green-pill', attendance: 'blue-pill' };
    const checkoutDate = r.time_out_date && r.time_out_date !== r.date
      ? `<small class="muted">${new Date(r.time_out_date + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}</small>`
      : '';
    const label = r.status === 'absent' && r.absence_status === 'Excused'
      ? 'Absent · Excused'
      : statusLabels[r.status] || 'Attendance';
    const rendered = r.status === 'absent' ? '—' : mins(r.net_min);
    return `<tr><td><b>${new Date(r.date + 'T00:00:00').toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })}</b></td><td>${esc(r.company || '—')}</td><td>${esc(r.time_in)}</td><td>${r.time_out ? `${esc(r.time_out)}${checkoutDate}` : '—'}</td><td><b>${rendered}</b></td><td><span class="pill ${statusClasses[r.status] || 'blue-pill'}">${label}</span>${r.reason ? `<br><small>${esc(r.reason)}</small>` : ''}</td></tr>`;
  }).join('')}</tbody></table></div>`;
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
  const sequence = ++daySequence;
  $('#dayTitle').textContent = new Date(date + 'T00:00:00').toLocaleDateString(undefined, { weekday: 'short', month: 'long', day: 'numeric', year: 'numeric' });
  $('#dayBody').innerHTML = '<div class="empty">Loading…</div>';
  openModal('#dayModal');
  const j = await getJSON('api/attendance.php?action=day&date=' + date);
  if (sequence !== daySequence) return;
  if (!j.ok) { $('#dayBody').innerHTML = '<div class="empty">Could not load this date.</div>'; return; }
  const evNames = { time_in: 'Time in', break_start: 'Break', break_end: 'Resumed', time_out: 'Time out' };
  const dateLabel = value => new Date(value + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
  const statusLabels = { absent: 'Absent', incomplete: 'Incomplete — under 8 hours', progress: 'In progress', complete: 'Complete — 8 hours reached', attendance: 'Attendance recorded' };
  let h = '';
  if (j.day_status) {
    const statusClass = j.day_status.status === 'absent' ? 'red-pill' : j.day_status.status === 'incomplete' ? 'amber-pill' : j.day_status.status === 'complete' ? 'green-pill' : 'blue-pill';
    const label = j.day_status.status === 'absent' && j.day_status.absence_status === 'Excused'
      ? 'Absent · Excused'
      : statusLabels[j.day_status.status] || 'Attendance recorded';
    h += `<div class="day-status"><span class="pill ${statusClass}">${label}</span><b>${Number(j.day_status.hours).toFixed(2)} / 8.00 hours</b>${j.day_status.reason ? `<small>${esc(j.day_status.reason)}</small>` : ''}</div>`;
  }
  h += '<section class="day-sec"><h4>Attendance</h4>';
  h += j.sessions.length ? j.sessions.map(s => {
    const timeIn = `${s.time_in_date && s.time_in_date !== date ? `<small class="muted">${dateLabel(s.time_in_date)} · </small>` : ''}${esc(s.time_in)}`;
    const timeOut = s.time_out
      ? `${s.time_out_date && s.time_out_date !== date ? `<small class="muted">${dateLabel(s.time_out_date)} · </small>` : ''}${esc(s.time_out)}`
      : '<em class="live-text">in progress</em>';
    const events = s.events.filter(e => evNames[e.type]).map(e => `<span>${evNames[e.type]} ${esc(e.time)}</span>`).join('');
    return `<div class="day-card"><div class="day-line"><b>${timeIn} → ${timeOut}</b><span class="pill ${s.open ? 'blue-pill' : 'green-pill'}">${mins(s.net_min)} rendered</span></div><small class="muted">${esc(s.company)}</small>${s.events.length > 2 ? `<div class="day-events">${events}</div>` : ''}</div>`;
  }).join('') : `<p class="muted">${j.day_status?.status === 'absent' ? 'No attendance was recorded for this workday.' : 'No attendance recorded.'}</p>`;
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
