(function(){
'use strict';
const { $ } = window.OJT;
$('#repDate')?.addEventListener('change', e => { if (e.target.value) location.href = 'reports.php?type=' + e.target.dataset.type + '&date=' + e.target.value; });
$('#printReport')?.addEventListener('click', () => {
  document.body.classList.add('printing-report');
  const done = () => { document.body.classList.remove('printing-report'); window.removeEventListener('afterprint', done); };
  window.addEventListener('afterprint', done); setTimeout(() => window.print(), 60);
});
})();
