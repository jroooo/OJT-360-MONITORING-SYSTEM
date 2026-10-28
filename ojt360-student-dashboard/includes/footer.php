</main>
</div><!-- /.content -->
</div><!-- /.app-shell -->

<div class="toast-stack" id="toastStack" aria-live="polite"></div>

<div class="modal-backdrop" id="logoutModal"><div class="modal modal-sm">
  <button class="modal-close" data-close-modal aria-label="Close"><?= icon('x') ?></button>
  <div class="modal-icon"><?= icon('logout') ?></div>
  <h2>Log out of OJT360?</h2><p>Your current session will be ended on this device.</p>
  <div class="modal-actions"><button class="btn secondary" data-close-modal>Stay signed in</button><a class="btn danger" href="logout.php">Logout</a></div>
</div></div>

<!-- Time In / Time Out QR scanner -->
<div class="modal-backdrop" id="scanModal" aria-hidden="true"><div class="modal modal-scan" role="dialog" aria-modal="true" aria-labelledby="scanTitle">
  <button class="modal-close" data-scan-close aria-label="Close"><?= icon('x') ?></button>
  <span class="eyebrow" id="scanEyebrow">TIME IN</span>
  <h2 id="scanTitle">Scan your company QR</h2>
  <p class="muted" id="scanHint">Allow camera access, then point it at your company's OJT360 QR code.</p>
  <div id="reader" class="qr-reader"><div class="qr-placeholder"><?= icon('qr') ?><span>Camera preview appears here</span></div></div>
  <div id="scanStatus" class="scan-status">Ready to scan.</div>
  <div class="scan-actions"><button type="button" class="btn primary" id="startScan"><?= icon('camera') ?> Start camera</button><button type="button" class="btn secondary" id="stopScan">Stop</button></div>
  <details class="manual-qr"><summary>Enter the code manually</summary>
    <div class="manual-row"><input id="manualQr" placeholder="e.g. OJT360-COMPANY-001" autocomplete="off"><button type="button" class="btn secondary" id="manualScan">Validate</button></div>
    <a class="text-link" href="qr-test.php" target="_blank" rel="noopener">Open the demo QR for testing</a>
  </details>
</div></div>

<button class="assistant-fab" id="assistantFab" aria-label="Open OJT360 Assistant" title="OJT360 Assistant"><?= logo_mark(true) ?></button>
<div class="assistant-panel" id="assistantPanel">
  <div class="assistant-head"><div class="assistant-title"><?= logo_mark(true) ?><div><strong>OJT360 Assistant</strong><span id="assistantStatus">AI Assistant</span></div></div><button class="icon-btn" id="assistantClose" aria-label="Close assistant"><?= icon('x') ?></button></div>
  <div class="assistant-messages" id="assistantMessages"><div class="assistant-bubble">Hi <?= e(explode(' ', trim($user['full_name']))[0]) ?>! Ask me anything about your OJT dashboard, your records, your journal, attendance, or general questions.</div></div>
  <div class="assistant-suggestions"><button data-assist="Summarize my OJT progress">My progress</button><button data-assist="Help me improve my weekly journal">Journal help</button><button data-assist="Explain my attendance record">Attendance</button></div>
  <form id="assistantForm" class="assistant-form"><input id="assistantInput" placeholder="Ask OJT360 Assistant..." autocomplete="off"><button aria-label="Send"><?= icon('send') ?></button></form>
</div>

<div class="modal-backdrop" id="profileCropModal" aria-hidden="true"><div class="modal modal-md" role="dialog" aria-modal="true" aria-labelledby="profileCropTitle">
  <button class="modal-close" id="profileCropClose" aria-label="Close"><?= icon('x') ?></button>
  <span class="eyebrow">PROFILE PHOTO</span><h2 id="profileCropTitle">Adjust your profile picture</h2>
  <div class="crop-stage" id="profileCropStage"><img id="profileCropImage" alt="Profile preview"><div class="crop-circle" aria-hidden="true"></div></div>
  <label class="range-row">Zoom<input id="profileCropZoom" type="range" min="1" max="3" step="0.01" value="1"></label>
  <p class="muted small">Drag the photo to position it inside the circle.</p>
  <div class="modal-actions"><button type="button" class="btn secondary" id="profileCropCancel">Cancel</button><button type="button" class="btn primary" id="profileCropSave">Save profile picture</button></div>
</div></div>

<div class="modal-backdrop" id="profilePictureViewModal" aria-hidden="true"><div class="modal modal-sm center" role="dialog" aria-modal="true">
  <button class="modal-close" id="profileViewClose" aria-label="Close"><?= icon('x') ?></button>
  <div class="view-circle"><img id="profileViewImage" src="" alt="Profile picture"><span id="profileViewInitials"><?= e(initials($user['full_name'])) ?></span></div>
  <h2><?= e($user['full_name']) ?></h2><p class="muted"><?= e($user['student_no']) ?></p>
</div></div>

<script src="assets/js/app.js?v=18"></script>
<?php foreach (($page_scripts ?? []) as $src): ?><script src="assets/js/<?= e($src) ?>?v=18"></script>
<?php endforeach; ?>
</body></html>
