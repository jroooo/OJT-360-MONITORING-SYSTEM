/* Final compilation: editable document, print/PDF, submit to adviser */
(function(){
'use strict';
const { $, api, toast, confirm: ask } = window.OJT;
const root = document.getElementById('compEditor'); if (!root) return;
const ed = OJTEditor.init(root, { onSave: () => save() });
let dirty = false, busy = false;
const payload = extra => { const l = ed.getLayout(); return Object.assign({ action: 'save', title: $('#compTitle').value.trim(), content: ed.getHTML(), orientation: l.orientation, page_size: l.size, margin_preset: l.margin }, extra || {}); };
async function save(silent) {
  if (busy) return false; if (ed.isEmpty()) { toast('The compilation is empty.', 'warn'); return false; }
  busy = true; ed.setSaveState('SavingΓÇª', '');
  const j = await api('api/journal_compilation.php', payload());
  busy = false;
  if (j.ok) { dirty = false; ed.setSaveState('Saved at ' + j.saved_at, 'ok'); if (!silent) toast('Final compilation saved.', 'success'); return true; }
  ed.setSaveState('Not saved', 'bad'); toast(j.message, 'error'); return false;
}
ed.onChange(() => { dirty = true; ed.setSaveState('Unsaved changes', 'warn'); });
$('#compTitle').addEventListener('input', () => { dirty = true; ed.setSaveState('Unsaved changes', 'warn'); });
$('#saveCompilation').onclick = () => save();
$('#printCompilation').onclick = () => ed.print($('#compTitle').value);
$('#pdfCompilation').onclick = () => { toast('In the print window, choose ΓÇ£Save as PDFΓÇ¥ as the destination.', 'info', 5000); setTimeout(() => ed.print($('#compTitle').value), 700); };
$('#rebuildBtn').onclick = async () => {
  if (!await ask({ title: 'Rebuild from weekly drafts?', message: 'This replaces the current text with your latest weekly drafts. Edits you made only in this compilation will be lost.', confirmText: 'Rebuild', danger: true })) return;
  const j = await api('api/journal_compilation.php', { action: 'rebuild' });
  if (j.ok) { ed.setHTML(j.html); toast('Compilation rebuilt from your drafts.', 'success'); } else toast(j.message, 'error');
};
$('#submitCompilation').onclick = async () => {
  if (ed.isEmpty()) { toast('Add your journal content before submitting.', 'warn'); return; }
  if (!await ask({ title: 'Submit to your adviser?', message: 'A copy of this final compilation will be sent to your adviser for review. You can keep editing and submit again later.', confirmText: 'Submit' })) return;
  const btn = $('#submitCompilation'); btn.disabled = true;
  const j = await api('api/journal_compilation.php', payload({ action: 'submit' }));
  btn.disabled = false;
  if (j.ok) { dirty = false; ed.setSaveState('Submitted', 'ok'); toast(j.message, 'success', 5000); setTimeout(() => location.reload(), 1600); } else toast(j.message, 'error');
};
window.addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
ed.setSaveState('Saved', 'ok');
})();
