```php
</main>
</div>
</div>

<!-- Logout Confirmation Modal -->
<div class="modal-wrap" id="logoutModal" hidden role="dialog" aria-modal="true" aria-labelledby="logoutTitle">
  <div class="modal sm">
    <div class="modal-icon danger">
      <i class="fa-solid fa-arrow-right-from-bracket"></i>
    </div>

    <h2 id="logoutTitle">Log out of OJT360?</h2>
    <p class="muted">Your current session will be ended on this device.</p>

    <form method="post" action="logout.php" class="row-actions center">
      <?= csrf_field() ?>

      <button type="button" class="btn ghost" data-modal-close>
        Stay signed in
      </button>

      <button type="submit" class="btn danger">
        Logout
      </button>
    </form>
  </div>
</div>

<!-- Background Blur for Modal -->
<style>
  .modal-wrap:not([hidden]) {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(15, 23, 42, 0.35);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
  }

  .modal-wrap[hidden] {
    display: none !important;
  }

  .modal-wrap .modal {
    position: relative;
    z-index: 10000;
  }
</style>

<script src="assets/js/company.js"></script>

<?php
if (!isset($_SESSION['company_chatbot_csrf']) || $_SESSION['company_chatbot_csrf'] === '') {
    $_SESSION['company_chatbot_csrf'] = bin2hex(random_bytes(32));
}
?>

<button class="ai-launcher" id="aiLauncher" aria-label="Open OJT360 Assistant" aria-expanded="false" title="OJT360 Assistant">
    <span class="ai-launcher-icon">✦</span>
</button>

<section class="ai-panel" id="aiPanel" aria-label="OJT360 Assistant">
    <div class="ai-head">
        <div class="ai-title-wrap">
            <div class="ai-avatar">✦</div>
            <div>
                <div class="ai-title">OJT360 Assistant</div>
                <div class="ai-online">
                    <span class="ai-online-dot"></span>
                    <span>Online</span>
                </div>
            </div>
        </div>
        <button class="ai-close" id="aiClose" aria-label="Close assistant">&times;</button>
    </div>

    <div class="ai-body" id="aiBody" role="log" aria-live="polite" aria-relevant="additions text">
        <div class="ai-row">
            <div class="ai-mini-avatar">✦</div>
            <div class="ai-bubble">
                Hi <?= e($company['first'] ?: $company['name']) ?>! I can help with student monitoring, attendance, reports, assigned interns, and OJT dashboard guidance.
            </div>
        </div>

        <div class="ai-suggestions">
            <button class="ai-suggestion" data-question="Where can I review my assigned students?">Assigned students</button>
            <button class="ai-suggestion" data-question="How do I check today’s attendance?">Attendance</button>
            <button class="ai-suggestion" data-question="What reports are available for my company?">Reports</button>
        </div>
    </div>

    <form class="ai-foot" id="aiForm">
        <input class="ai-input" id="aiInput" autocomplete="off" placeholder="Ask OJT360...">
        <button class="ai-send" type="submit" aria-label="Send message">➤</button>
    </form>
</section>

<script>
const CHATBOT_CSRF_TOKEN = <?= json_encode($_SESSION['company_chatbot_csrf'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
</script>
<script src="chatbot.js"></script>

<?php
if (!empty($pageScript)) {
    echo $pageScript;
}
?>

</body>
</html>
```