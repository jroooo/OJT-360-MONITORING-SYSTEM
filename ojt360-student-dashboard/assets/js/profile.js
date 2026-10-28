(function(){
'use strict';
const { $, api, toast, C } = window.OJT;
$('#profileForm').addEventListener('submit', async e => {
  e.preventDefault(); const f = e.target, st = $('#profileState'), btn = $('button.primary', f); btn.disabled = true; st.textContent = 'SavingΓÇª';
  const j = await api('api/profile.php', Object.fromEntries(new FormData(f)));
  btn.disabled = false; st.textContent = ''; toast(j.message || (j.ok ? 'Saved.' : 'Could not save.'), j.ok ? 'success' : 'error');
  if (j.ok && j.full_name) { const h = $('.profile-id h1'); if (h) h.textContent = j.full_name; OJT.C.userName = j.full_name; }
});
})();
