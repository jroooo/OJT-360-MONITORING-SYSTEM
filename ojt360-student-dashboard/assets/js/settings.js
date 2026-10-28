(function(){
'use strict';
const { $, $$, api, toast, applyTheme } = window.OJT;
const html = document.documentElement;
function markTheme() { const pref = html.getAttribute('data-theme-pref') || 'light'; $$('.theme-card').forEach(c => { const on = c.dataset.themePref === pref; c.classList.toggle('active', on); c.setAttribute('aria-checked', on); }); }
$$('.theme-card').forEach(c => c.onclick = () => { applyTheme(c.dataset.themePref); markTheme(); });
markTheme();
const sb = $('#sidebarToggle'), mo = $('#motionToggle');
sb.checked = !html.classList.contains('sb-collapsed');
sb.onchange = () => { html.classList.toggle('sb-collapsed', !sb.checked); try { localStorage.setItem('ojt360-sidebar', sb.checked ? 'open' : 'collapsed'); } catch (e) {} };
mo.checked = html.classList.contains('reduce-motion');
mo.onchange = () => { html.classList.toggle('reduce-motion', mo.checked); try { localStorage.setItem('ojt360-motion', mo.checked ? 'reduce' : 'full'); } catch (e) {} };
function wire(formId, action, after) {
  const f = $('#' + formId);
  f.addEventListener('submit', async e => {
    e.preventDefault(); const st = $('.form-state', f), btn = $('button.primary', f); btn.disabled = true; st.textContent = 'SavingΓÇª';
    const j = await api('api/settings.php', Object.assign(Object.fromEntries(new FormData(f)), { action }));
    btn.disabled = false; st.textContent = j.ok ? '' : j.message; toast(j.message, j.ok ? 'success' : 'error'); if (j.ok && after) after(f);
  });
}
wire('scheduleForm', 'schedule'); wire('goalForm', 'goal'); wire('passwordForm', 'password', f => f.reset());
})();
