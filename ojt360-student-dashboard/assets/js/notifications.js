(function(){
'use strict';
const { $, $$, api, toast } = window.OJT;
const badge = $('#notificationBadge');
const readAllButtons = [$('#notificationReadAll'), $('#readAll')].filter(Boolean);

function updateBadge(count) {
  if (!badge) return;
  badge.textContent = count > 9 ? '9+' : String(count);
  badge.hidden = count < 1;
  readAllButtons.forEach(button => { button.disabled = count < 1; });
}

function markVisibleRead() {
  $$('.notification-popover-item.unread, .notification-item.unread').forEach(item => {
    item.classList.remove('unread');
    $('.unread-dot', item)?.remove();
  });
  updateBadge(0);
}

readAllButtons.forEach(button => button.addEventListener('click', async () => {
  button.disabled = true;
  const j = await api('api/notifications.php', { action: 'read_all' });
  if (j.ok) {
    markVisibleRead();
    toast('All notifications marked as read.', 'success');
  } else {
    button.disabled = false;
    toast(j.message || 'Could not update.', 'error');
  }
}));

$$('.notification-item.unread').forEach(item => item.addEventListener('click', async () => {
  const j = await api('api/notifications.php', { action: 'read_one', id: item.dataset.id });
  if (!j.ok) {
    toast(j.message || 'Could not update notification.', 'error');
    return;
  }
  item.classList.remove('unread');
  $('.unread-dot', item)?.remove();
  const remaining = $$('.notification-item.unread, .notification-popover-item.unread').length;
  updateBadge(remaining);
}));

const button = $('#notificationButton');
const popover = $('#notificationPopover');
button?.addEventListener('click', () => {
  const opening = popover.hidden;
  popover.hidden = !opening;
  button.setAttribute('aria-expanded', String(opening));
});

document.addEventListener('click', event => {
  if (!popover || popover.hidden || event.target.closest('.notification-menu')) return;
  popover.hidden = true;
  button?.setAttribute('aria-expanded', 'false');
});

document.addEventListener('keydown', event => {
  if (event.key !== 'Escape' || !popover || popover.hidden) return;
  popover.hidden = true;
  button?.setAttribute('aria-expanded', 'false');
  button?.focus();
});

$$('.notification-popover-item.unread').forEach(item => item.addEventListener('click', async event => {
  event.preventDefault();
  const j = await api('api/notifications.php', { action: 'read_one', id: item.dataset.notificationId });
  if (!j.ok) {
    toast(j.message || 'Could not update notification.', 'error');
    return;
  }
  item.classList.remove('unread');
  const remaining = $$('.notification-item.unread, .notification-popover-item.unread').length;
  updateBadge(remaining);
  window.location.href = item.href;
}));
})();
