/* Daily logs: filters, pager, modal form with live rendered-hours */
(function(){
'use strict';
const { $, $$, esc, icon, api, toast, openModal, closeModal, confirm: ask, on } = window.OJT;
const LOGS = window.OJT_LOGS || [];
let page = 1, onlyIncomplete = false;
const hoursText = h => h.toFixed(2) + ' h';
const t12 = t => { if (!t) return ''; const [h, m] = t.split(':').map(Number); return (h % 12 || 12) + ':' + String(m).padStart(2, '0') + ' ' + (h < 12 ? 'AM' : 'PM'); };

function card(l) {
  const d = new Date(l.date + 'T00:00:00');
  const time = l.time_in ? `<span class="chip-time">${icon('clock')} ${t12(l.time_in)} ΓÇô ${l.time_out ? t12(l.time_out) : '<em class="live-text">in progress</em>'}</span>` : '';
  const brk = l.break ? `<span class="chip-time">${icon('coffee')} ${l.break} min break</span>` : '';
  const auto = l.auto ? '<span class="pill gray-pill">Auto from attendance</span>' : '';
  return `<article class="log-card ${l.incomplete ? 'is-incomplete' : ''}" data-id="${l.id}">
    <div class="log-date"><b>${String(d.getDate()).padStart(2, '0')}</b><span>${d.toLocaleDateString(undefined, { month: 'short' })} ${d.getFullYear()}</span></div>
    <div class="log-main">
      <div class="log-title-row"><h3>${l.title ? esc(l.title) : 'Add what you worked on'}</h3>${l.incomplete ? '<span class="pill amber-pill">Needs details</span>' : ''}${auto}</div>
      <div class="log-chips">${time}${brk}</div>
      <p>${l.description ? esc(l.description).replace(/\n/g, '<br>') : '<span class="muted">No description yet. Click Edit to describe your tasks for this day.</span>'}</p>
      <small class="muted">Recorded ${esc(l.created)}</small>
    </div>
    ${l.evidence ? `<button class="evidence-thumb" type="button" data-zoom="${l.evidence}" aria-label="View photo evidence"><img src="${l.evidence}" alt="Evidence photo" loading="lazy"></button>` : ''}
    <div class="log-right"><span class="pill blue-pill hours-pill">${hoursText(l.hours)}</span>
      <div class="log-actions"><button class="btn small secondary" data-edit="${l.id}">${icon('edit')} ${l.incomplete ? 'Add details' : 'Edit'}</button><button class="btn small danger-soft" data-del="${l.id}" aria-label="Delete">${icon('trash')}</button></div></div>
  </article>`;
}

function filtered() {
  const s = $('#logSearch').value.toLowerCase().trim(), f = $('#logFrom').value, t = $('#logTo').value, sort = $('#logSort').value;
  let a = LOGS.filter(l => (!s || (l.title + ' ' + l.description).toLowerCase().includes(s)) && (!f || l.date >= f) && (!t || l.date <= t) && (!onlyIncomplete || l.incomplete));
  a.sort((x, y) => sort === 'oldest' ? x.date.localeCompare(y.date) || x.id - y.id : sort === 'hours' ? y.hours - x.hours : y.date.localeCompare(x.date) || y.id - x.id);
  return a;
}
function render() {
  const a = filtered(), per = Number($('#logPerPage').value), pages = Math.max(1, Math.ceil(a.length / per));
  page = Math.min(page, pages);
  const slice = a.slice((page - 1) * per, page * per);
  $('#logCount').textContent = a.length + (a.length === 1 ? ' entry' : ' entries');
  $('#logList').innerHTML = slice.length ? slice.map(card).join('') : `<div class="empty-state"><div class="empty-icon">${icon('logs')}</div><h3>${LOGS.length ? 'No entries found' : 'No daily entries yet'}</h3><p>${LOGS.length ? 'Try changing or clearing your filters.' : 'Time in from the dashboard and your first log appears automatically, or click New Entry.'}</p></div>`;
  $('#logPager').innerHTML = pages > 1 ? `<button class="btn small secondary" ${page <= 1 ? 'disabled' : ''} data-page="${page - 1}">${icon('chevL')} Previous</button><span>Page ${page} of ${pages}</span><button class="btn small secondary" ${page >= pages ? 'disabled' : ''} data-page="${page + 1}">Next ${icon('chevR')}</button>` : '';
  $('#chipIncomplete').classList.toggle('active', onlyIncomplete);
}
['logSearch', 'logFrom', 'logTo', 'logSort', 'logPerPage'].forEach(id => $('#' + id).addEventListener('input', () => { page = 1; render(); }));
$('#chipIncomplete').onclick = () => { onlyIncomplete = !onlyIncomplete; page = 1; render(); };
$('#clearLogFilters').onclick = () => { ['logSearch', 'logFrom', 'logTo'].forEach(id => $('#' + id).value = ''); $('#logSort').value = 'newest'; $('#logPerPage').value = '10'; onlyIncomplete = false; page = 1; render(); };
document.addEventListener('click', async e => {
  const p = e.target.closest('[data-page]'); if (p) { page = Number(p.dataset.page); render(); window.scrollTo({ top: 0, behavior: 'smooth' }); }
  const ed = e.target.closest('[data-edit]'); if (ed) openForm(LOGS.find(l => l.id === Number(ed.dataset.edit)));
  const z = e.target.closest('[data-zoom]'); if (z) { $('#lightboxImg').src = z.dataset.zoom; openModal('#lightbox'); }
  const del = e.target.closest('[data-del]'); if (del) {
    if (!await ask({ title: 'Delete this entry?', message: 'This removes the daily log and its photo. Your attendance hours are not affected.', confirmText: 'Delete', danger: true })) return;
    const j = await api('api/daily_logs.php', { action: 'delete', id: del.dataset.del });
    if (j.ok) { toast('Entry deleted.', 'success'); const i = LOGS.findIndex(l => l.id === Number(del.dataset.del)); if (i > -1) LOGS.splice(i, 1); setTimeout(() => location.reload(), 500); } else toast(j.message, 'error');
  }
});

/* ----- form ----- */
const form = $('#logForm');
const val = id => $('#' + id).value;
function calc() {
  const a = val('logIn'), b = val('logOut'), br = Number(val('logBreak') || 0);
  let txt = '0.00 hrs';
  if (a && b) { const [ah, am] = a.split(':').map(Number), [bh, bm] = b.split(':').map(Number); const net = (bh * 60 + bm) - (ah * 60 + am) - br; txt = net > 0 ? (net / 60).toFixed(2) + ' hrs' : '0.00 hrs'; $('#renderedBox').classList.toggle('bad', net <= 0); }
  else $('#renderedBox').classList.remove('bad');
  $('#renderedVal').textContent = txt;
}
['logIn', 'logOut', 'logBreak'].forEach(id => $('#' + id).addEventListener('input', calc));
$$('[data-now]').forEach(b => b.onclick = () => { const n = new Date(); $('#' + b.dataset.now).value = String(n.getHours()).padStart(2, '0') + ':' + String(n.getMinutes()).padStart(2, '0'); calc(); });
function setPreview(src) { $('#photoPreview').hidden = !src; if (src) $('#photoPreviewImg').src = src; }
$('#logEvidence').addEventListener('change', e => {
  const f = e.target.files[0]; if (!f) return;
  if (f.size > 5 * 1024 * 1024) { toast('The photo must be 5 MB or smaller.', 'error'); e.target.value = ''; return; }
  $('#removeEvidence').value = ''; setPreview(URL.createObjectURL(f));
});
$('#removePhoto').onclick = () => { $('#logEvidence').value = ''; $('#removeEvidence').value = '1'; setPreview(''); };

function openForm(l, defaults = {}) {
  form.reset();
  $('#logModalTitle').textContent = l ? (l.incomplete ? 'Add details' : 'Edit Entry') : 'New Entry';
  $('input[name=action]', form).value = l ? 'update' : 'create';
  $('#logId').value = l ? l.id : ''; $('#removeEvidence').value = '';
  $('#logDate').value = l ? l.date : (defaults.date || window.OJT_LOG_TODAY);
  $('#logTitle').value = l ? l.title : ''; $('#logDescription').value = l ? l.description : '';
  $('#logIn').value = l ? l.time_in : ''; $('#logOut').value = l ? l.time_out : ''; $('#logBreak').value = l ? l.break : 0;
  const auto = !!(l && l.auto);
  ['logIn', 'logOut', 'logBreak', 'logDate'].forEach(id => $('#' + id).disabled = auto);
  $$('[data-now]').forEach(b => b.hidden = auto); $('#autoNote').hidden = !auto;
  if (auto) $('#renderedVal').textContent = l.hours.toFixed(2) + ' hrs'; else calc();
  setPreview(l && l.evidence ? l.evidence : ''); $('#logFormState').textContent = '';
  openModal('#logModal'); setTimeout(() => $('#logTitle').focus(), 120);
}
$('#newLogBtn').onclick = () => openForm(null);
const closeForm = () => closeModal('#logModal');
$('#logModalClose').onclick = closeForm; $('#logCancel').onclick = closeForm;
form.addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData(form);
  // disabled fields are not posted; add the date back for auto logs (ignored server side)
  const btn = $('#logSave'); btn.disabled = true; $('#logFormState').textContent = 'SavingΓÇª';
  const j = await api('api/daily_logs.php', fd);
  btn.disabled = false; $('#logFormState').textContent = '';
  if (j.ok) { toast(j.message, 'success'); closeForm(); setTimeout(() => { location.href = 'daily_logs.php'; }, 450); } else { $('#logFormState').textContent = j.message; toast(j.message, 'error'); }
});

render();
const qs = new URLSearchParams(location.search);
if (qs.get('filter') === 'incomplete') { onlyIncomplete = true; render(); }
if (qs.get('edit')) { const l = LOGS.find(x => x.id === Number(qs.get('edit'))); if (l) openForm(l); }
else if (qs.get('new')) openForm(null, { date: qs.get('date') });
})();
