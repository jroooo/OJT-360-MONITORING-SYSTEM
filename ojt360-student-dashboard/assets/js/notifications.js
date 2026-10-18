(function(){
'use strict';
const { $, $$, api, toast } = window.OJT;
$('#readAll')?.addEventListener('click', async () => {
  const j = await api('api/notifications.php', { action: 'read_all' });
  if (j.ok) { $$('.notification-item.unread').forEach(n => n.classList.remove('unread')); $$('.unread-dot').forEach(d => d.remove()); $('.notif-dot')?.remove(); toast('All notifications marked as read.', 'success'); } else toast(j.message || 'Could not update.', 'error');
});
$$('.notification-item.unread').forEach(n => n.addEventListener('click', async () => { await api('api/notifications.php', { action: 'read_one', id: n.dataset.id }); n.classList.remove('unread'); $('.unread-dot', n)?.remove(); }));
})();
