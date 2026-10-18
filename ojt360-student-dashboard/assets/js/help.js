(function(){
'use strict';
const { $, $$, api, toast } = window.OJT;
let cat = 'all';
function filter() {
  const q = $('#faqSearch').value.toLowerCase().trim(); let n = 0;
  $$('#faqList details').forEach(d => { const ok = (cat === 'all' || d.dataset.cat === cat) && (!q || d.textContent.toLowerCase().includes(q)); d.hidden = !ok; if (ok) n++; });
  $('#faqEmpty').hidden = n > 0;
}
$('#faqSearch').addEventListener('input', filter);
$$('.faq-tabs .tab').forEach(t => t.onclick = () => { cat = t.dataset.cat; $$('.faq-tabs .tab').forEach(x => x.classList.toggle('active', x === t)); filter(); });
$('#helpAsk').onclick = () => $('#assistantFab').click();
$('#supportForm').addEventListener('submit', async e => {
  e.preventDefault(); const f = e.target, btn = $('button.primary', f); btn.disabled = true;
  const j = await api('api/support.php', Object.fromEntries(new FormData(f)));
  btn.disabled = false; toast(j.message || (j.ok ? 'Sent.' : 'Could not send.'), j.ok ? 'success' : 'error');
  if (j.ok) { f.reset(); setTimeout(() => location.reload(), 900); }
});
})();
