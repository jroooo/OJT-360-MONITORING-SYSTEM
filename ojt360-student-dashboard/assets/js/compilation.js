/* Final compilation: editable document, print/PDF, submit to adviser */
(function(){
'use strict';
const { $, api, toast, confirm: ask } = window.OJT;
const root = document.getElementById('compEditor'); if (!root) return;
const ed = OJTEditor.init(root, { onSave: () => save() });
let dirty = false, busy = false, revision = 0;
const payload = extra => { const l = ed.getLayout(); return Object.assign({ action: 'save', title: $('#compTitle').value.trim(), content: ed.getHTML(), orientation: l.orientation, page_size: l.size, margin_preset: l.margin }, extra || {}); };
async function save(silent) {
  if (busy) return false; if (ed.isEmpty()) { toast('The compilation is empty.', 'warn'); return false; }
  const savedRevision = revision;
  busy = true; ed.setSaveState('Saving…', '');
  const j = await api('api/journal_compilation.php', payload());
  busy = false;
  if (j.ok) {
    dirty = revision !== savedRevision;
    ed.setSaveState(dirty ? 'Unsaved changes' : 'Saved at ' + j.saved_at, dirty ? 'warn' : 'ok');
    if (dirty) return false;
    if (!silent) toast('Final compilation saved.', 'success');
    return true;
  }
  ed.setSaveState('Not saved', 'bad'); toast(j.message, 'error'); return false;
}
ed.onChange(() => { revision++; dirty = true; ed.setSaveState('Unsaved changes', 'warn'); });
$('#compTitle').addEventListener('input', () => { revision++; dirty = true; ed.setSaveState('Unsaved changes', 'warn'); });
$('#saveCompilation').onclick = () => save();
$('#printCompilation').onclick = () => ed.print($('#compTitle').value);
$('#pdfCompilation').onclick = () => { toast('In the print window, choose “Save as PDF” as the destination.', 'info', 5000); setTimeout(() => ed.print($('#compTitle').value), 700); };
$('#rebuildBtn').onclick = async () => {
  if (!await ask({ title: 'Rebuild from weekly drafts?', message: 'This replaces the current text with your latest weekly drafts. Edits you made only in this compilation will be lost.', confirmText: 'Rebuild', danger: true })) return;
  const j = await api('api/journal_compilation.php', { action: 'rebuild' });
  if (j.ok) { ed.setHTML(j.html); toast('Compilation rebuilt from your drafts.', 'success'); } else toast(j.message, 'error');
};
$('#submitCompilation').onclick = async () => {
  if (ed.isEmpty()) { toast('Add your journal content before submitting.', 'warn'); return; }
  if (!await ask({ title: 'Submit to your adviser?', message: 'A PDF copy of this final compilation will be sent to your adviser for review. You can keep editing and submit again later.', confirmText: 'Submit' })) return;
  const submittedRevision = revision;
  const btn = $('#submitCompilation'); btn.disabled = true;
  const label = btn.textContent;
  btn.textContent = 'Creating PDF…';
  try {
    const data = payload({ action: 'submit' });
    const pdf = await ed.createPdfBlob(data.content);
    data.pdf = new File([pdf], 'final-journal-compilation.pdf', { type: 'application/pdf' });
    btn.textContent = 'Sending PDF…';
    const j = await api('api/journal_compilation.php', data);
    if (j.ok) {
      dirty = revision !== submittedRevision;
      ed.setSaveState(dirty ? 'Submitted; newer changes unsaved' : 'Submitted', dirty ? 'warn' : 'ok');
      toast(j.message, 'success', 5000);
      if (!dirty) setTimeout(() => location.reload(), 1600);
    } else toast(j.message, 'error');
  } catch (error) {
    toast(error.message || 'The compilation PDF could not be submitted. Please try again.', 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = label;
  }
};
window.addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
ed.setSaveState('Saved', 'ok');
})();
