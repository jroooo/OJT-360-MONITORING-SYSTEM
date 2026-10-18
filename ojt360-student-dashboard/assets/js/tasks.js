(function(){
'use strict';
const { $, $$, api, toast, openModal, closeModal, confirm: ask } = window.OJT;
const labels = { pending: ['Not started', 'amber-pill'], in_progress: ['In progress', 'blue-pill'], completed: ['Completed', 'green-pill'] };
function applyFilter(f) {
  $$('.task-tabs .tab').forEach(t => t.classList.toggle('active', t.dataset.filter === f));
  $$('#taskList .task-card').forEach(c => { const s = c.dataset.status; c.hidden = f === 'open' ? s === 'completed' : f === 'completed' ? s !== 'completed' : false; });
}
$$('.task-tabs .tab').forEach(t => t.onclick = () => applyFilter(t.dataset.filter));
async function save(card, status, comment) {
  const j = await api('api/tasks.php', { action: 'update', task_id: card.dataset.task, status, comment });
  if (j.ok) {
    card.dataset.status = j.status; card.classList.toggle('task-done', j.status === 'completed'); $('.task-check', card).classList.toggle('checked', j.status === 'completed');
    const p = $('[data-pill]', card), l = labels[j.status]; p.textContent = l[0]; p.className = 'pill ' + l[1];
    const sel = $('[data-status-sel]', card); if (sel) sel.value = j.status;
    toast(j.message, 'success');
  } else toast(j.message, 'error');
  return j;
}
document.addEventListener('click', async e => {
  const sv = e.target.closest('[data-save]'); if (sv) { const card = sv.closest('.task-card'); sv.disabled = true; await save(card, $('[data-status-sel]', card).value, $('[data-comment]', card).value); sv.disabled = false; return; }
  const tg = e.target.closest('[data-toggle]'); if (tg && !tg.disabled) { const card = tg.closest('.task-card'); const next = card.dataset.status === 'completed' ? 'pending' : 'completed'; await save(card, next, $('[data-comment]', card)?.value || ''); return; }
  const del = e.target.closest('[data-delete]'); if (del) {
    if (!await ask({ title: 'Delete this task?', message: 'This removes your personal task.', confirmText: 'Delete', danger: true })) return;
    const j = await api('api/tasks.php', { action: 'delete', task_id: del.dataset.delete }); if (j.ok) { toast(j.message, 'success'); setTimeout(() => location.reload(), 400); } else toast(j.message, 'error');
  }
});
$('#addTaskBtn').onclick = () => { $('#taskForm').reset(); openModal('#taskModal'); setTimeout(() => $('#taskForm [name=title]').focus(), 100); };
$('#taskForm').addEventListener('submit', async e => {
  e.preventDefault(); const j = await api('api/tasks.php', Object.assign(Object.fromEntries(new FormData(e.target)), { action: 'create' }));
  if (j.ok) { toast(j.message, 'success'); closeModal('#taskModal'); setTimeout(() => location.reload(), 400); } else toast(j.message, 'error');
});
})();
