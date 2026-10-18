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
if (!empty($pageScript)) {
    echo $pageScript;
}
?>

</body>
</html>
```