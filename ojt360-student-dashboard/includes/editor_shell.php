<?php
/* Word-style editor markup shared by journal.php and journal_compilation.php.
   The ribbon is built by assets/js/editor.js. */
function render_editor_shell(string $id, string $html, string $orientation = 'portrait', string $size = 'A4', string $margin = 'normal'): void {
    ?>
<div class="word-app" id="<?= e($id) ?>" data-orientation="<?= e($orientation) ?>" data-size="<?= e($size) ?>" data-margin="<?= e($margin) ?>">
  <div class="ribbon" data-ribbon></div>
  <div class="desk" data-desk>
    <div class="stage" data-stage>
      <div class="paper" data-paper contenteditable="true" spellcheck="true" role="textbox" aria-multiline="true" aria-label="Document editor"><?= $html ?></div>
    </div>
    <div class="img-overlay" data-overlay hidden></div>
    <div class="drop-caret" data-dropcaret hidden></div>
  </div>
  <div class="word-status">
    <span data-words>0 words</span><span class="dot">┬╖</span><span data-pages>1 page</span><span class="dot">┬╖</span><span data-savestate class="save-state">Not saved</span>
    <div class="zoom-box"><button type="button" class="rb" data-zoom-out title="Zoom out">ΓêÆ</button><input type="range" min="40" max="200" step="5" value="100" data-zoom aria-label="Zoom"><button type="button" class="rb" data-zoom-in title="Zoom in">+</button><b data-zoom-label>100%</b><button type="button" class="link-btn" data-zoom-fit>Fit</button></div>
  </div>
</div>
    <?php
}

function render_editor_modals(): void {
    ?>
<!-- Insert link -->
<div class="modal-backdrop" id="linkModal" aria-hidden="true"><div class="modal modal-sm">
  <button class="modal-close" data-close-modal aria-label="Close"><?= icon('x') ?></button>
  <h2>Insert link</h2>
  <div class="form"><label>Web address<input id="linkUrl" placeholder="https://example.com"></label><label>Text to display <span class="muted">(optional)</span><input id="linkText" placeholder="Link text"></label></div>
  <div class="modal-actions"><button class="btn secondary" data-close-modal>Cancel</button><button class="btn primary" id="linkApply">Insert</button></div>
</div></div>

<!-- Image editor: crop, rotate, flip -->
<div class="modal-backdrop" id="imgEditorModal" aria-hidden="true" data-static><div class="modal modal-xl">
  <button class="modal-close" id="imgEdClose" aria-label="Close"><?= icon('x') ?></button>
  <span class="eyebrow">IMAGE EDITOR</span><h2>Edit picture</h2>
  <div class="imged-body">
    <div class="imged-stage" id="imgEdStage"><canvas id="imgEdCanvas"></canvas><div class="imged-crop" id="imgEdCrop"><i data-h="nw"></i><i data-h="n"></i><i data-h="ne"></i><i data-h="e"></i><i data-h="se"></i><i data-h="s"></i><i data-h="sw"></i><i data-h="w"></i></div></div>
    <div class="imged-tools">
      <label>Crop ratio<select id="imgEdRatio"><option value="free">Free</option><option value="1">Square (1:1)</option><option value="1.3333">4 : 3</option><option value="1.5">3 : 2</option><option value="1.7778">16 : 9</option><option value="0.75">3 : 4 (portrait)</option></select></label>
      <div class="imged-grid">
        <button type="button" class="btn secondary small" id="imgEdRotL"><?= icon('rotL') ?> Rotate left</button>
        <button type="button" class="btn secondary small" id="imgEdRotR"><?= icon('rotR') ?> Rotate right</button>
        <button type="button" class="btn secondary small" id="imgEdFlipH"><?= icon('flipH') ?> Flip horizontal</button>
        <button type="button" class="btn secondary small" id="imgEdFlipV"><?= icon('flipV') ?> Flip vertical</button>
        <button type="button" class="btn secondary small" id="imgEdReset"><?= icon('refresh') ?> Reset</button>
      </div>
      <p class="muted small">Drag the box to move the crop area and drag its handles to resize it. Rotate or flip first, then crop, then apply.</p>
      <div class="muted small" id="imgEdInfo"></div>
    </div>
  </div>
  <div class="modal-actions"><button class="btn secondary" id="imgEdCancel">Cancel</button><button class="btn primary" id="imgEdApply">Apply changes</button></div>
</div></div>
    <?php
}
