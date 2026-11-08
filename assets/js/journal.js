/* Weekly journal: autosave drafts using the shared editor */
(function(){
'use strict';
const { $, api, toast, confirm: ask } = window.OJT;
const root = document.getElementById('journalEditor'); if (!root) return;
const ed = OJTEditor.init(root, { onSave: () => save(false) });
let dirty = false, saving = false, timer = null;
const idEl = $('#journalId');

function payload(auto, action) {
  const l = ed.getLayout();
  return { action: action || 'save', journal_id: idEl.value || 0, week_start: $('#journalWeek').value, title: $('#journalTitle').value.trim(), content: ed.getHTML(), orientation: l.orientation, page_size: l.size, margin_preset: l.margin, autosave: auto ? 1 : '' };
}
async function save(auto) {
  if (saving) return;
  const empty = ed.isEmpty();
  if (auto && (empty || !$('#journalTitle').value.trim())) return;
  if (!auto && empty) { toast('Write something first, then save your draft.', 'warn'); return; }
  saving = true; ed.setSaveState('Saving…', '');
  const j = await api('api/journal.php', payload(auto));
  saving = false;
  if (j.ok) {
    const first = !idEl.value || idEl.value === '0';
    idEl.value = j.journal_id; dirty = false; ed.setSaveState('Saved at ' + j.saved_at, 'ok');
    if (!auto) toast('Draft saved.', 'success');
    history.replaceState(null, '', 'journal.php?id=' + j.journal_id); $('#wsTitle').textContent = 'Continue this week'; if (first) setTimeout(() => { if (!dirty) location.reload(); }, 1000);
  } else { ed.setSaveState('Not saved', 'bad'); toast(j.message, 'error'); }
}
function touch() { dirty = true; ed.setSaveState('Unsaved changes', 'warn'); clearTimeout(timer); timer = setTimeout(() => save(true), 3000); }
ed.onChange(touch);
['journalTitle', 'journalWeek'].forEach(id => $('#' + id).addEventListener('input', touch));
$('#saveDraft').onclick = () => save(false);
$('#submitWeeklyJournal').onclick = async () => {
  if (ed.isEmpty()) { toast('Write something in your weekly journal before submitting.', 'warn'); return; }
  if (!$('#journalTitle').value.trim()) { toast('Enter a week title before submitting.', 'warn'); return; }
  if (!await ask({ title: 'Submit weekly journal to your adviser?', message: 'A snapshot of this weekly journal will be sent to your adviser for review. You can edit and submit it again later.', confirmText: 'Submit' })) return;
  const btn = $('#submitWeeklyJournal'); btn.disabled = true;
  const l = ed.getLayout();
  const data = { action: 'submit', journal_id: idEl.value || 0, week_start: $('#journalWeek').value, title: $('#journalTitle').value.trim(), content: ed.getHTML(), orientation: l.orientation, page_size: l.size, margin_preset: l.margin, autosave: '' };
  const j = await api('api/journal.php', data);
  btn.disabled = false;
  if (j.ok) { dirty = false; idEl.value = j.journal_id; ed.setSaveState('Submitted to adviser', 'ok'); toast(j.message, 'success', 5000); setTimeout(() => location.reload(), 1000); }
  else toast(j.message, 'error');
};
$('#printDraft').onclick = () => ed.print($('#journalTitle').value);
$('#newDraftBtn').onclick = async () => {
  if (dirty && !await ask({ title: 'Leave this draft?', message: 'You have unsaved changes. They will be lost if you start a new week.', confirmText: 'Discard & continue', danger: true })) return;
  dirty = false; const next = new Date(); next.setDate(next.getDate() + 7 - ((next.getDay() + 6) % 7)); location.href = 'journal.php?week=' + next.toISOString().slice(0, 10);
};
document.addEventListener('click', async e => {
  const b = e.target.closest('[data-del-draft]'); if (!b) return;
  if (!await ask({ title: 'Delete this draft?', message: 'The weekly draft will be removed from your journal and from future compilations.', confirmText: 'Delete', danger: true })) return;
  const j = await api('api/journal.php', { action: 'delete', journal_id: b.dataset.delDraft });
  if (j.ok) { toast('Draft deleted.', 'success'); dirty = false; setTimeout(() => { location.href = String(idEl.value) === b.dataset.delDraft ? 'journal.php' : location.href; }, 500); } else toast(j.message, 'error');
});
window.addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
document.addEventListener('visibilitychange', () => { if (document.hidden && dirty) save(true); });
if ((idEl.value || '0') !== '0') ed.setSaveState('Saved', 'ok');
})();
