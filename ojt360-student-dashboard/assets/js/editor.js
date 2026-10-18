/* OJT360 Word-style editor
   Ribbon (Home / Insert / Layout / Picture), real page layout, pictures that can be
   resized, dragged anywhere in the text, wrapped, cropped, rotated and flipped. */
(function(){
'use strict';
const OJT = window.OJT;
const { $, $$, esc, api, toast, openModal, closeModal } = OJT;
const PAGE = { A4: [794, 1123], Letter: [816, 1056] };
const MARGINS = { normal: 96, narrow: 48, wide: 144 };
const FONTS = ['Arial', 'Calibri', 'Cambria', 'Georgia', 'Times New Roman', 'Verdana', 'Tahoma', 'Trebuchet MS', 'Courier New', 'Palatino Linotype'];
const SIZES = [8, 9, 10, 11, 12, 14, 16, 18, 20, 24, 28, 32, 36, 48];
const clamp = (v, a, b) => Math.max(a, Math.min(b, v));

/* tiny svg icon set for the ribbon */
const G = {
  undo: '<path d="M9 14L4 9l5-5M4 9h10a6 6 0 0 1 0 12h-3"/>', redo: '<path d="M15 14l5-5-5-5M20 9H10a6 6 0 0 0 0 12h3"/>',
  ul: '<path d="M9 6h12M9 12h12M9 18h12"/><circle cx="4.5" cy="6" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="18" r="1"/>',
  ol: '<path d="M10 6h11M10 12h11M10 18h11M4 4v4M3 8h2M3 13h2l-2 3h2M3 19.5h2a1 1 0 0 1 0 2H3.5"/>',
  outdent: '<path d="M21 6H11M21 12H11M21 18H11M7 8l-4 4 4 4"/>', indent: '<path d="M21 6H11M21 12H11M21 18H11M3 8l4 4-4 4"/>',
  left: '<path d="M3 6h18M3 12h12M3 18h16"/>', center: '<path d="M3 6h18M6 12h12M4 18h16"/>', right: '<path d="M3 6h18M9 12h12M5 18h16"/>', justify: '<path d="M3 6h18M3 12h18M3 18h18"/>',
  image: '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="M21 15l-5-5L5 21"/>', link: '<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/>',
  table: '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18M15 3v18"/>', hr: '<path d="M3 12h18"/>', pbreak: '<path d="M4 8V4h16v4M4 16v4h16v-4M2 12h4M10 12h4M18 12h4"/>',
  cal: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>', clear: '<path d="M4 7h12M9 7v12M5 20l14-14M14 20h6"/>',
  portrait: '<rect x="6" y="3" width="12" height="18" rx="1.5"/>', landscape: '<rect x="3" y="6" width="18" height="12" rx="1.5"/>',
  inline: '<path d="M3 6h5M16 6h5M3 12h18M3 18h8"/><rect x="9" y="4" width="6" height="5" rx="1"/>', wl: '<rect x="3" y="4" width="8" height="7" rx="1"/><path d="M14 5h7M14 9h7M3 15h18M3 19h14"/>', wr: '<rect x="13" y="4" width="8" height="7" rx="1"/><path d="M3 5h7M3 9h7M3 15h18M3 19h14"/>',
  tb: '<path d="M3 4h18M3 20h18"/><rect x="8" y="8" width="8" height="8" rx="1"/>', behind: '<path d="M3 7h18M3 12h18M3 17h18" opacity=".6"/><rect x="8" y="5" width="9" height="14" rx="1" opacity=".35" fill="currentColor"/>', front: '<path d="M3 7h18M3 12h18M3 17h18" opacity=".45"/><rect x="8" y="6" width="9" height="12" rx="1" fill="currentColor" opacity=".25"/><rect x="8" y="6" width="9" height="12" rx="1"/>',
  crop: '<path d="M6 2v14a2 2 0 0 0 2 2h14M2 6h14a2 2 0 0 1 2 2v14"/>', trash: '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>',
  reset: '<path d="M23 4v6h-6M20.5 15a9 9 0 1 1-2.1-9.4L23 10"/>'
};
const svg = k => `<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${G[k]}</svg>`;
const rb = (cmd, inner, title, extra = '') => `<button type="button" class="rb" data-cmd="${cmd}" title="${title}" aria-label="${title}" ${extra}>${inner}</button>`;

function ribbonHTML() {
  return `
  <div class="rb-tabs" role="tablist">
    <button type="button" class="rb-tab active" data-tab="home" role="tab">Home</button>
    <button type="button" class="rb-tab" data-tab="insert" role="tab">Insert</button>
    <button type="button" class="rb-tab" data-tab="layout" role="tab">Layout</button>
    <button type="button" class="rb-tab rb-tab-ctx" data-tab="picture" role="tab" hidden>Picture Format</button>
  </div>
  <div class="rb-body">
    <div class="rb-panel active" data-panel="home">
      <div class="rb-group"><div class="rb-row">${rb('undo', svg('undo'), 'Undo (Ctrl+Z)')}${rb('redo', svg('redo'), 'Redo (Ctrl+Y)')}</div><span class="rb-name">Undo</span></div>
      <div class="rb-group"><div class="rb-row"><select class="rb-sel" data-font title="Font">${FONTS.map(f => `<option value="${f}">${f}</option>`).join('')}</select><select class="rb-sel narrow" data-size title="Font size">${SIZES.map(s => `<option value="${s}" ${s === 12 ? 'selected' : ''}>${s}</option>`).join('')}</select></div>
        <div class="rb-row">${rb('bold', '<b>B</b>', 'Bold (Ctrl+B)')}${rb('italic', '<i>I</i>', 'Italic (Ctrl+I)')}${rb('underline', '<u>U</u>', 'Underline (Ctrl+U)')}${rb('strikeThrough', '<s>S</s>', 'Strikethrough')}${rb('subscript', 'x<sub>2</sub>', 'Subscript')}${rb('superscript', 'x<sup>2</sup>', 'Superscript')}
        <label class="rb color-btn" title="Text color"><span class="a">A</span><i data-swatch="fore" style="background:#111"></i><input type="color" data-color="foreColor" value="#111111"></label><label class="rb color-btn" title="Highlight color"><span class="a hl">ab</span><i data-swatch="hilite" style="background:#fde047"></i><input type="color" data-color="hiliteColor" value="#fde047"></label>${rb('removeFormat', svg('clear'), 'Clear formatting')}</div><span class="rb-name">Font</span></div>
      <div class="rb-group"><div class="rb-row">${rb('insertUnorderedList', svg('ul'), 'Bullets')}${rb('insertOrderedList', svg('ol'), 'Numbering')}${rb('outdent', svg('outdent'), 'Decrease indent')}${rb('indent', svg('indent'), 'Increase indent')}</div>
        <div class="rb-row">${rb('justifyLeft', svg('left'), 'Align left')}${rb('justifyCenter', svg('center'), 'Center')}${rb('justifyRight', svg('right'), 'Align right')}${rb('justifyFull', svg('justify'), 'Justify')}<select class="rb-sel narrow" data-spacing title="Line spacing"><option value="">Spacing</option><option value="1">1.0</option><option value="1.15">1.15</option><option value="1.5">1.5</option><option value="2">2.0</option></select></div><span class="rb-name">Paragraph</span></div>
      <div class="rb-group"><div class="rb-row"><select class="rb-sel wide" data-block title="Paragraph style"><option value="p">Paragraph</option><option value="h1">Title</option><option value="h2">Heading 1</option><option value="h3">Heading 2</option></select></div><span class="rb-name">Styles</span></div>
    </div>
    <div class="rb-panel" data-panel="insert">
      <div class="rb-group"><div class="rb-row">${rb('picture', svg('image') + '<span>Picture</span>', 'Insert a picture', 'data-big')}${rb('link', svg('link') + '<span>Link</span>', 'Insert link', 'data-big')}<div class="rb-pop"><button type="button" class="rb" data-big data-cmd="table" title="Insert table">${svg('table')}<span>Table</span></button><div class="table-pick" data-tablepick hidden></div></div>${rb('hr', svg('hr') + '<span>Line</span>', 'Horizontal line', 'data-big')}${rb('pagebreak', svg('pbreak') + '<span>Page break</span>', 'Page break', 'data-big')}${rb('date', svg('cal') + '<span>Date</span>', 'Insert today\'s date', 'data-big')}</div><span class="rb-name">Insert</span></div>
      <input type="file" data-file accept="image/png,image/jpeg,image/webp,image/gif" hidden>
    </div>
    <div class="rb-panel" data-panel="layout">
      <div class="rb-group"><div class="rb-row">${rb('portrait', svg('portrait') + '<span>Portrait</span>', 'Portrait page', 'data-big data-orient="portrait"')}${rb('landscape', svg('landscape') + '<span>Landscape</span>', 'Landscape page', 'data-big data-orient="landscape"')}</div><span class="rb-name">Orientation</span></div>
      <div class="rb-group"><div class="rb-row"><label class="rb-field">Page size<select class="rb-sel" data-pagesize><option value="A4">A4 (210 × 297 mm)</option><option value="Letter">Letter (8.5 × 11 in)</option></select></label><label class="rb-field">Margins<select class="rb-sel" data-margin><option value="normal">Normal (1 in)</option><option value="narrow">Narrow (0.5 in)</option><option value="wide">Wide (1.5 in)</option></select></label></div><span class="rb-name">Page setup</span></div>
    </div>
    <div class="rb-panel" data-panel="picture">
      <div class="rb-group"><div class="rb-row">${rb('wrap:inline', svg('inline') + '<span>Inline</span>', 'In line with text', 'data-big')}${rb('wrap:left', svg('wl') + '<span>Square left</span>', 'Picture on the left, text wraps on the right', 'data-big')}${rb('wrap:right', svg('wr') + '<span>Square right</span>', 'Picture on the right, text wraps on the left', 'data-big')}${rb('wrap:block', svg('tb') + '<span>Top &amp; bottom</span>', 'Top and bottom', 'data-big')}${rb('wrap:behind', svg('behind') + '<span>Behind text</span>', 'Behind text (Alt+click to select it later)', 'data-big')}${rb('wrap:front', svg('front') + '<span>In front</span>', 'In front of text', 'data-big')}</div><span class="rb-name">Wrap text</span></div>
      <div class="rb-group"><div class="rb-row">${rb('palign:left', svg('left'), 'Align picture left')}${rb('palign:center', svg('center'), 'Center picture')}${rb('palign:right', svg('right'), 'Align picture right')}</div><span class="rb-name">Position</span></div>
      <div class="rb-group"><div class="rb-row"><label class="rb-field">Width (px)<input type="number" class="rb-num" data-picw min="40" max="1400" step="10"></label>${rb('pic:reset', svg('reset') + '<span>Reset size</span>', 'Reset picture size', 'data-big')}</div><span class="rb-name">Size</span></div>
      <div class="rb-group"><div class="rb-row">${rb('pic:edit', svg('crop') + '<span>Crop &amp; rotate</span>', 'Crop, rotate and flip', 'data-big')}${rb('pic:delete', svg('trash') + '<span>Delete</span>', 'Delete picture', 'data-big')}</div><span class="rb-name">Edit</span></div>
    </div>
  </div>`;
}

function init(root, opts = {}) {
  const ribbon = $('[data-ribbon]', root), desk = $('[data-desk]', root), stage = $('[data-stage]', root), paper = $('[data-paper]', root), ov = $('[data-overlay]', root), caret = $('[data-dropcaret]', root);
  ribbon.innerHTML = ribbonHTML();
  const state = { zoom: 1, orientation: root.dataset.orientation || 'portrait', size: root.dataset.size || 'A4', margin: root.dataset.margin || 'normal', img: null, range: null, tab: 'home', changeCbs: [], hist: [], redo: [], last: '', restoring: false, histTimer: null, fitOnce: true };
  document.execCommand('defaultParagraphSeparator', false, 'p');

  /* ---------- layout (page size / margins / orientation) ---------- */
  function pageDims() { const [w, h] = PAGE[state.size] || PAGE.A4; return state.orientation === 'landscape' ? [h, w] : [w, h]; }
  function applyLayout() {
    const [w, h] = pageDims(), pad = MARGINS[state.margin] || 96;
    root.dataset.orientation = state.orientation; root.dataset.size = state.size; root.dataset.margin = state.margin;
    paper.style.setProperty('--page-w', w + 'px'); paper.style.setProperty('--page-h', h + 'px'); paper.style.setProperty('--pad', pad + 'px');
    $$('[data-orient]', ribbon).forEach(b => b.classList.toggle('active', b.dataset.orient === state.orientation));
    $('[data-pagesize]', ribbon).value = state.size; $('[data-margin]', ribbon).value = state.margin;
    applyZoom(state.zoom); stats(); placeOverlay();
  }
  function applyZoom(z) {
    state.zoom = clamp(z, 0.4, 2);
    stage.style.zoom = state.zoom;
    $('[data-zoom]', root).value = Math.round(state.zoom * 100); $('[data-zoom-label]', root).textContent = Math.round(state.zoom * 100) + '%';
    placeOverlay();
  }
  function fit() { const [w] = pageDims(); const avail = desk.clientWidth - 24; applyZoom(Math.min(1, avail / w)); }
  $('[data-zoom]', root).addEventListener('input', e => applyZoom(e.target.value / 100));
  $('[data-zoom-in]', root).onclick = () => applyZoom(state.zoom + 0.1);
  $('[data-zoom-out]', root).onclick = () => applyZoom(state.zoom - 0.1);
  $('[data-zoom-fit]', root).onclick = fit;
  window.addEventListener('resize', () => { placeOverlay(); });

  /* ---------- selection helpers ---------- */
  document.addEventListener('selectionchange', () => {
    const s = window.getSelection();
    if (s && s.rangeCount && paper.contains(s.anchorNode)) { state.range = s.getRangeAt(0).cloneRange(); syncToolbar(); }
  });
  function restoreRange() {
    paper.focus({ preventScroll: true });
    if (state.range) { const s = window.getSelection(); s.removeAllRanges(); s.addRange(state.range); }
  }
  function endRange() { const r = document.createRange(); r.selectNodeContents(paper); r.collapse(false); state.range = r; }
  ribbon.addEventListener('mousedown', e => { if (!e.target.closest('select, input')) e.preventDefault(); });

  /* ---------- history ---------- */
  state.last = paper.innerHTML;
  function snapshot() { if (state.restoring) return; const h = paper.innerHTML; if (h === state.last) return; state.hist.push(state.last); if (state.hist.length > 100) state.hist.shift(); state.redo = []; state.last = h; }
  function changed(immediate) {
    fixSizes(); stats(); placeOverlay();
    clearTimeout(state.histTimer);
    if (immediate) snapshot(); else state.histTimer = setTimeout(snapshot, 400);
    state.changeCbs.forEach(fn => fn());
  }
  function setHTML(html, quiet) { state.restoring = true; paper.innerHTML = html && html.trim() ? html : '<p><br></p>'; state.last = paper.innerHTML; state.restoring = false; selectImage(null); stats(); if (!quiet) state.changeCbs.forEach(fn => fn()); }
  function undo() { clearTimeout(state.histTimer); snapshot(); if (!state.hist.length) return; state.redo.push(paper.innerHTML); const h = state.hist.pop(); state.restoring = true; paper.innerHTML = h; state.last = h; state.restoring = false; selectImage(null); endRange(); stats(); state.changeCbs.forEach(fn => fn()); }
  function redo() { if (!state.redo.length) return; state.hist.push(paper.innerHTML); const h = state.redo.pop(); state.restoring = true; paper.innerHTML = h; state.last = h; state.restoring = false; selectImage(null); endRange(); stats(); state.changeCbs.forEach(fn => fn()); }

  /* ---------- text commands ---------- */
  function exec(cmd, val) { restoreRange(); document.execCommand('styleWithCSS', false, true); document.execCommand(cmd, false, val == null ? null : val); changed(true); syncToolbar(); }
  function fixSizes() {
    $$('font[size="7"], span[style*="xxx-large"]', paper).forEach(n => {
      const px = n.dataset.pt || state.pendingPt || 12; const sp = document.createElement('span'); sp.style.fontSize = px + 'pt';
      while (n.firstChild) sp.appendChild(n.firstChild); n.replaceWith(sp);
    });
  }
  function setSize(pt) { state.pendingPt = pt; restoreRange(); document.execCommand('styleWithCSS', false, true); document.execCommand('fontSize', false, '7'); fixSizes(); changed(true); }
  function blocksInRange() {
    restoreRange(); const s = window.getSelection(); if (!s.rangeCount) return [];
    const r = s.getRangeAt(0), out = new Set(), sel = 'p,h1,h2,h3,li,blockquote,div:not(.paper)';
    const climb = n => { while (n && n !== paper && !(n.nodeType === 1 && n.matches(sel))) n = n.parentNode; return n && n !== paper ? n : null; };
    const a = climb(r.startContainer), b = climb(r.endContainer); if (a) out.add(a); if (b) out.add(b);
    $$(sel, paper).forEach(el => { if (r.intersectsNode(el)) out.add(el); });
    return [...out];
  }
  function lineSpacing(v) { if (!v) return; blocksInRange().forEach(el => { el.style.lineHeight = v; }); changed(true); }
  function formatBlock(tag) { restoreRange(); document.execCommand('formatBlock', false, tag); changed(true); syncToolbar(); }
  function syncToolbar() {
    if (document.activeElement && document.activeElement.closest && document.activeElement.closest('.ribbon') && document.activeElement.tagName !== 'BODY') { /* keep select values while interacting */ }
    ['bold', 'italic', 'underline', 'strikeThrough', 'subscript', 'superscript', 'insertUnorderedList', 'insertOrderedList', 'justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'].forEach(c => {
      const b = $(`[data-cmd="${c}"]`, ribbon); if (!b) return; let on = false; try { on = document.queryCommandState(c); } catch (e) {} b.classList.toggle('on', !!on);
    });
    const s = window.getSelection(); if (!s.rangeCount || !paper.contains(s.anchorNode)) return;
    let n = s.anchorNode.nodeType === 1 ? s.anchorNode : s.anchorNode.parentElement; if (!n) return;
    const cs = getComputedStyle(n); const fam = cs.fontFamily.split(',')[0].replace(/["']/g, '').trim();
    const f = $('[data-font]', ribbon); if ([...f.options].some(o => o.value === fam)) f.value = fam;
    const pt = Math.round(parseFloat(cs.fontSize) * 72 / 96); const sz = $('[data-size]', ribbon); if ([...sz.options].some(o => Number(o.value) === pt)) sz.value = pt;
    const blk = n.closest('h1,h2,h3,p'); $('[data-block]', ribbon).value = blk ? blk.tagName.toLowerCase() : 'p';
  }

  /* ---------- ribbon wiring ---------- */
  function showTab(t) { state.tab = t; $$('.rb-tab', ribbon).forEach(b => b.classList.toggle('active', b.dataset.tab === t)); $$('.rb-panel', ribbon).forEach(p => p.classList.toggle('active', p.dataset.panel === t)); }
  ribbon.addEventListener('click', e => {
    const tab = e.target.closest('.rb-tab'); if (tab) { showTab(tab.dataset.tab); return; }
    const o = e.target.closest('[data-orient]'); if (o) { state.orientation = o.dataset.orient; applyLayout(); changed(true); return; }
    const b = e.target.closest('[data-cmd]'); if (!b) return;
    const c = b.dataset.cmd;
    if (c === 'undo') return undo(); if (c === 'redo') return redo();
    if (c === 'picture') return $('[data-file]', ribbon).click();
    if (c === 'link') return openLink();
    if (c === 'table') { const p = $('[data-tablepick]', ribbon); p.hidden = !p.hidden; if (!p.hidden) buildTablePick(p); return; }
    if (c === 'hr') return exec('insertHorizontalRule');
    if (c === 'pagebreak') return insertHTML('<div class="page-break" style="page-break-after:always"></div><p><br></p>');
    if (c === 'date') return insertHTML(esc(new Date().toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' })));
    if (c === 'indent') return exec('indent'); if (c === 'outdent') return exec('outdent');
    if (c.startsWith('wrap:')) return state.img && applyWrap(state.img, c.slice(5));
    if (c.startsWith('palign:')) return state.img && alignPic(state.img, c.slice(7));
    if (c === 'pic:reset') return state.img && resetSize(state.img);
    if (c === 'pic:edit') return state.img && openImageEditor(state.img);
    if (c === 'pic:delete') return deleteImage();
    exec(c);
  });
  document.addEventListener('click', e => { if (!e.target.closest('.rb-pop')) { const p = $('[data-tablepick]', ribbon); if (p) p.hidden = true; } });
  $('[data-font]', ribbon).addEventListener('change', e => exec('fontName', e.target.value));
  $('[data-size]', ribbon).addEventListener('change', e => setSize(Number(e.target.value)));
  $('[data-block]', ribbon).addEventListener('change', e => formatBlock(e.target.value));
  $('[data-spacing]', ribbon).addEventListener('change', e => { lineSpacing(e.target.value); e.target.value = ''; });
  $$('[data-color]', ribbon).forEach(inp => inp.addEventListener('input', () => { const sw = inp.parentElement.querySelector('i'); sw.style.background = inp.value; exec(inp.dataset.color, inp.value); }));
  $('[data-pagesize]', ribbon).addEventListener('change', e => { state.size = e.target.value; applyLayout(); changed(true); });
  $('[data-margin]', ribbon).addEventListener('change', e => { state.margin = e.target.value; applyLayout(); changed(true); });

  function buildTablePick(p) {
    p.innerHTML = '<div class="tp-grid">' + Array.from({ length: 36 }, (_, i) => `<i data-r="${Math.floor(i / 6) + 1}" data-c="${i % 6 + 1}"></i>`).join('') + '</div><div class="tp-label">Select table size</div>';
    p.onmouseover = e => { const t = e.target.closest('i'); if (!t) return; const r = +t.dataset.r, c = +t.dataset.c; $$('i', p).forEach(x => x.classList.toggle('on', +x.dataset.r <= r && +x.dataset.c <= c)); $('.tp-label', p).textContent = r + ' × ' + c + ' table'; };
    p.onclick = e => { const t = e.target.closest('i'); if (!t) return; p.hidden = true; insertTable(+t.dataset.r, +t.dataset.c); };
  }
  function insertTable(r, c) {
    let h = '<table style="border-collapse:collapse;width:100%"><tbody>';
    for (let i = 0; i < r; i++) { h += '<tr>'; for (let j = 0; j < c; j++) h += '<td style="border:1px solid #9aa7b4;padding:6px">' + (i === 0 ? '' : '') + '<br></td>'; h += '</tr>'; }
    insertHTML(h + '</tbody></table><p><br></p>');
  }
  function insertHTML(html) { restoreRange(); document.execCommand('insertHTML', false, html); changed(true); }

  /* ---------- link ---------- */
  function openLink() {
    const s = window.getSelection(); $('#linkText').value = state.range && !state.range.collapsed ? state.range.toString() : ''; $('#linkUrl').value = '';
    openModal('#linkModal'); setTimeout(() => $('#linkUrl').focus(), 100);
  }
  if (!root.dataset.linkBound) {
    root.dataset.linkBound = '1';
    $('#linkApply').onclick = () => {
      let url = $('#linkUrl').value.trim(); if (!url) return;
      if (!/^(https?:\/\/|mailto:)/i.test(url)) url = 'https://' + url;
      const text = $('#linkText').value.trim(); closeModal('#linkModal'); restoreRange();
      if (state.range && !state.range.collapsed) document.execCommand('createLink', false, url);
      else document.execCommand('insertHTML', false, `<a href="${esc(url)}">${esc(text || url)}</a>&nbsp;`);
      changed(true);
    };
  }

  /* ---------- pictures ---------- */
  async function upload(file) {
    if (!/^image\/(jpeg|png|webp|gif)$/.test(file.type)) { toast('Use a JPG, PNG, WEBP, or GIF picture.', 'error'); return null; }
    if (file.size > 8 * 1024 * 1024) { toast('The picture must be 8 MB or smaller.', 'error'); return null; }
    setSaveState('Uploading picture…', '');
    const fd = new FormData(); fd.append('image', file, file.name || 'picture.png');
    const j = await api('api/journal_image.php', fd);
    if (!j.ok) { toast(j.message || 'Upload failed.', 'error'); return null; }
    return j;
  }
  async function insertPicture(file) {
    const j = await upload(file); if (!j) return;
    const img = document.createElement('img'); img.src = j.url; img.alt = (file.name || 'picture').replace(/\.[^.]+$/, '');
    img.className = 'jimg jimg-inline'; img.dataset.wrap = 'inline'; img.style.display = 'inline-block'; img.style.verticalAlign = 'bottom'; img.style.margin = '0 4px';
    await new Promise(res => { img.onload = res; img.onerror = res; img.src = j.url; });
    const max = Math.min(480, pageDims()[0] - 2 * (MARGINS[state.margin] || 96));
    img.style.width = Math.min(img.naturalWidth || max, max) + 'px';
    restoreRange();
    const s = window.getSelection();
    if (s.rangeCount && paper.contains(s.anchorNode)) { const r = s.getRangeAt(0); r.collapse(false); r.insertNode(img); r.setStartAfter(img); r.collapse(true); s.removeAllRanges(); s.addRange(r); }
    else { const p = document.createElement('p'); p.appendChild(img); paper.appendChild(p); }
    changed(true); setSaveState('Picture added', 'ok'); selectImage(img);
  }
  $('[data-file]', ribbon).addEventListener('change', e => { const f = e.target.files[0]; e.target.value = ''; if (f) insertPicture(f); });
  paper.addEventListener('paste', e => {
    const items = [...(e.clipboardData ? e.clipboardData.items : [])];
    const img = items.find(i => i.kind === 'file' && i.type.startsWith('image/'));
    if (img) { e.preventDefault(); insertPicture(img.getAsFile()); return; }
    e.preventDefault();
    const text = e.clipboardData.getData('text/plain'); document.execCommand('insertText', false, text);
  });
  paper.addEventListener('dragover', e => { if ([...(e.dataTransfer ? e.dataTransfer.types : [])].includes('Files')) e.preventDefault(); });
  paper.addEventListener('drop', e => {
    const f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
    if (f && f.type.startsWith('image/')) { e.preventDefault(); const r = caretFromPoint(e.clientX, e.clientY); if (r && paper.contains(r.startContainer)) state.range = r; insertPicture(f); }
  });
  paper.addEventListener('dragstart', e => { if (e.target.tagName === 'IMG') e.preventDefault(); });

  function resetPos(img) {
    ['float', 'position', 'left', 'top', 'zIndex', 'display', 'margin', 'verticalAlign'].forEach(p => { img.style[p] = ''; });
    img.classList.remove('jimg-inline', 'jimg-left', 'jimg-right', 'jimg-block', 'jimg-front', 'jimg-behind');
  }
  function applyWrap(img, mode) {
    const wasAbs = img.style.position === 'absolute';
    let abs = null;
    if ((mode === 'front' || mode === 'behind')) {
      if (wasAbs) abs = { left: parseFloat(img.style.left) || 0, top: parseFloat(img.style.top) || 0 };
      else { const r = img.getBoundingClientRect(), p = paper.getBoundingClientRect(); abs = { left: (r.left - p.left) / state.zoom, top: (r.top - p.top) / state.zoom }; }
    }
    resetPos(img); img.dataset.wrap = mode; img.classList.add('jimg');
    if (mode === 'inline') { img.classList.add('jimg-inline'); img.style.display = 'inline-block'; img.style.verticalAlign = 'bottom'; img.style.margin = '0 4px'; }
    else if (mode === 'left') { img.classList.add('jimg-left'); img.style.float = 'left'; img.style.margin = '4px 18px 10px 0'; }
    else if (mode === 'right') { img.classList.add('jimg-right'); img.style.float = 'right'; img.style.margin = '4px 0 10px 18px'; }
    else if (mode === 'block') { img.classList.add('jimg-block'); img.style.display = 'block'; img.style.margin = '12px auto'; }
    else { img.classList.add(mode === 'front' ? 'jimg-front' : 'jimg-behind'); img.style.position = 'absolute'; img.style.left = Math.round(abs.left) + 'px'; img.style.top = Math.round(abs.top) + 'px'; img.style.zIndex = mode === 'front' ? '5' : '-1'; }
    changed(true); selectImage(img);
  }
  function alignPic(img, a) {
    if (img.dataset.wrap !== 'block') { applyWrap(img, 'block'); }
    img.style.margin = a === 'left' ? '12px auto 12px 0' : a === 'right' ? '12px 0 12px auto' : '12px auto'; changed(true); placeOverlay();
  }
  function resetSize(img) { img.style.height = ''; img.style.width = Math.min(img.naturalWidth, 480) + 'px'; changed(true); placeOverlay(); }
  function deleteImage() { const img = state.img; if (!img) return; selectImage(null); const p = img.parentNode; img.remove(); if (p && p !== paper && !p.textContent.trim() && !p.querySelector('img,br')) p.innerHTML = '<br>'; changed(true); }

  /* ---------- selection overlay (handles, size badge, quick bar) ---------- */
  ov.innerHTML = `<div class="ov-badge" data-badge>0 px</div><div class="ov-bar" data-bar>${[['inline', 'inline', 'In line with text'], ['left', 'wl', 'Square – picture left'], ['right', 'wr', 'Square – picture right'], ['block', 'tb', 'Top and bottom'], ['behind', 'behind', 'Behind text'], ['front', 'front', 'In front of text']].map(([m, ic, t]) => `<button type="button" data-w="${m}" title="${t}" aria-label="${t}">${svg(ic)}</button>`).join('')}<span class="sep"></span><button type="button" data-edit title="Crop, rotate, flip" aria-label="Crop and rotate">${svg('crop')}</button><button type="button" data-del title="Delete picture" aria-label="Delete picture">${svg('trash')}</button></div>${['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w'].map(h => `<i class="ov-h ov-${h}" data-h="${h}"></i>`).join('')}`;
  ov.addEventListener('mousedown', e => { if (e.target.closest('.ov-bar')) e.preventDefault(); });
  $('[data-bar]', ov).addEventListener('click', e => {
    const w = e.target.closest('[data-w]'); if (w && state.img) return applyWrap(state.img, w.dataset.w);
    if (e.target.closest('[data-edit]') && state.img) return openImageEditor(state.img);
    if (e.target.closest('[data-del]')) deleteImage();
  });
  function selectImage(img) {
    if (state.img && state.img !== img) state.img.classList.remove('is-selected');
    state.img = img;
    const tab = $('.rb-tab-ctx', ribbon);
    if (img) { img.classList.add('is-selected'); tab.hidden = false; if (state.tab !== 'picture') { state.prevTab = state.tab; showTab('picture'); } placeOverlay(); }
    else { ov.hidden = true; tab.hidden = true; if (state.tab === 'picture') showTab(state.prevTab && state.prevTab !== 'picture' ? state.prevTab : 'home'); }
  }
  function placeOverlay() {
    const img = state.img; if (!img || !img.isConnected) { if (img) { state.img = null; } ov.hidden = true; return; }
    const r = img.getBoundingClientRect(), d = desk.getBoundingClientRect();
    ov.hidden = false; ov.style.left = (r.left - d.left + desk.scrollLeft) + 'px'; ov.style.top = (r.top - d.top + desk.scrollTop) + 'px'; ov.style.width = r.width + 'px'; ov.style.height = r.height + 'px';
    ov.classList.toggle('bar-below', r.top - d.top < 56);
    $('[data-badge]', ov).textContent = Math.round(img.offsetWidth) + ' px';
    const w = $('[data-picw]', ribbon); if (w && document.activeElement !== w) w.value = Math.round(img.offsetWidth);
    $$('[data-w]', ov).forEach(b => b.classList.toggle('on', b.dataset.w === (img.dataset.wrap || 'inline')));
    $$('[data-cmd^="wrap:"]', ribbon).forEach(b => b.classList.toggle('on', b.dataset.cmd === 'wrap:' + (img.dataset.wrap || 'inline')));
  }
  $('[data-picw]', ribbon).addEventListener('input', e => { if (!state.img) return; const v = clamp(Number(e.target.value) || 0, 40, 1600); state.img.style.width = v + 'px'; state.img.style.height = ''; placeOverlay(); changed(false); });
  desk.addEventListener('scroll', placeOverlay, { passive: true }); window.addEventListener('scroll', placeOverlay, { passive: true });

  /* resize */
  let rs = null;
  ov.addEventListener('pointerdown', e => {
    const h = e.target.closest('.ov-h'); if (!h || !state.img) return;
    e.preventDefault(); e.stopPropagation(); h.setPointerCapture(e.pointerId);
    rs = { h: h.dataset.h, x: e.clientX, y: e.clientY, w: state.img.offsetWidth, hgt: state.img.offsetHeight, id: e.pointerId };
  });
  ov.addEventListener('pointermove', e => {
    if (!rs || !state.img) return; const img = state.img, z = state.zoom, dx = (e.clientX - rs.x) / z, dy = (e.clientY - rs.y) / z;
    const corner = rs.h.length === 2, maxW = pageDims()[0] - 20;
    if (corner) { let nw = rs.w + (rs.h.includes('e') ? dx : -dx); nw = clamp(nw, 40, maxW); img.style.width = Math.round(nw) + 'px'; img.style.height = 'auto'; }
    else if (rs.h === 'e' || rs.h === 'w') { img.style.width = Math.round(clamp(rs.w + (rs.h === 'e' ? dx : -dx), 40, maxW)) + 'px'; img.style.height = rs.hgt + 'px'; }
    else { img.style.width = rs.w + 'px'; img.style.height = Math.round(clamp(rs.hgt + (rs.h === 's' ? dy : -dy), 30, 2000)) + 'px'; }
    placeOverlay();
  });
  const endResize = () => { if (rs) { rs = null; changed(true); } };
  ov.addEventListener('pointerup', endResize); ov.addEventListener('pointercancel', endResize);

  /* select / drag-to-move */
  function caretFromPoint(x, y) {
    if (document.caretPositionFromPoint) { const p = document.caretPositionFromPoint(x, y); if (!p) return null; const r = document.createRange(); r.setStart(p.offsetNode, p.offset); r.collapse(true); return r; }
    return document.caretRangeFromPoint ? document.caretRangeFromPoint(x, y) : null;
  }
  let drag = null;
  paper.addEventListener('pointerdown', e => {
    if (e.button !== 0) return;
    let img = e.target.closest && e.target.closest('img.jimg');
    if (!img && e.altKey) img = document.elementsFromPoint(e.clientX, e.clientY).find(n => n.matches && n.matches('img.jimg'));
    if (!img) { selectImage(null); return; }
    e.preventDefault(); paper.focus({ preventScroll: true });
    selectImage(img);
    drag = { img, x: e.clientX, y: e.clientY, moved: false, abs: img.style.position === 'absolute', l: parseFloat(img.style.left) || 0, t: parseFloat(img.style.top) || 0, range: null };
  });
  window.addEventListener('pointermove', e => {
    if (!drag) return;
    const dx = e.clientX - drag.x, dy = e.clientY - drag.y;
    if (!drag.moved && Math.hypot(dx, dy) < 5) return;
    drag.moved = true; drag.img.classList.add('is-dragging');
    if (drag.abs) {
      const [pw] = pageDims(); drag.img.style.left = Math.round(clamp(drag.l + dx / state.zoom, -20, paper.scrollWidth - 30)) + 'px'; drag.img.style.top = Math.round(clamp(drag.t + dy / state.zoom, -20, paper.scrollHeight - 30)) + 'px'; placeOverlay();
    } else {
      const r = caretFromPoint(e.clientX, e.clientY);
      if (r && paper.contains(r.startContainer) && !drag.img.contains(r.startContainer)) {
        drag.range = r; let rect = r.getClientRects()[0];
        if (!rect) { const el = r.startContainer.nodeType === 1 ? r.startContainer : r.startContainer.parentElement; const b = el.getBoundingClientRect(); rect = { left: b.left, top: b.top, height: 20 }; }
        const dd = desk.getBoundingClientRect(); caret.hidden = false; caret.style.left = (rect.left - dd.left + desk.scrollLeft) + 'px'; caret.style.top = (rect.top - dd.top + desk.scrollTop) + 'px'; caret.style.height = Math.max(16, rect.height) + 'px';
      }
    }
  });
  window.addEventListener('pointerup', () => {
    if (!drag) return; const d = drag; drag = null; caret.hidden = true; d.img.classList.remove('is-dragging');
    if (d.moved) {
      if (!d.abs && d.range) { d.range.insertNode(d.img); }
      changed(true); selectImage(d.img);
    }
  });

  /* keyboard */
  paper.addEventListener('keydown', e => {
    const mod = e.ctrlKey || e.metaKey, k = e.key.toLowerCase();
    if (mod && k === 'z') { e.preventDefault(); e.shiftKey ? redo() : undo(); return; }
    if (mod && k === 'y') { e.preventDefault(); redo(); return; }
    if (mod && k === 's') { e.preventDefault(); opts.onSave && opts.onSave(); return; }
    if (mod && k === 'k') { e.preventDefault(); openLink(); return; }
    if (state.img && (e.key === 'Delete' || e.key === 'Backspace') && window.getSelection().isCollapsed) { e.preventDefault(); deleteImage(); return; }
    if (e.key === 'Escape') selectImage(null);
  });
  paper.addEventListener('input', () => changed(false));
  paper.addEventListener('keyup', syncToolbar); paper.addEventListener('mouseup', syncToolbar);
  paper.addEventListener('blur', () => { /* keep range */ });

  /* ---------- image editor (crop / rotate / flip) ---------- */
  const E = { img: null, work: null, orig: null, view: 1, rect: null, drag: null, ratio: 0 };
  const stageEl = $('#imgEdStage'), cvs = $('#imgEdCanvas'), box = $('#imgEdCrop');
  function drawWork() {
    const w = E.work; const maxW = Math.min(640, stageEl.parentElement.clientWidth - 280 || 640), maxH = 420;
    E.view = Math.min(1, maxW / w.width, maxH / w.height);
    cvs.width = Math.round(w.width * E.view); cvs.height = Math.round(w.height * E.view);
    cvs.getContext('2d').drawImage(w, 0, 0, cvs.width, cvs.height);
    stageEl.style.width = cvs.width + 'px'; stageEl.style.height = cvs.height + 'px'; paintBox();
  }
  function paintBox() { const r = E.rect, k = E.view; box.style.left = r.x * k + 'px'; box.style.top = r.y * k + 'px'; box.style.width = r.w * k + 'px'; box.style.height = r.h * k + 'px'; $('#imgEdInfo').textContent = `Crop size: ${Math.round(r.w)} × ${Math.round(r.h)} px`; }
  function fullRect() { E.rect = { x: 0, y: 0, w: E.work.width, h: E.work.height }; if (E.ratio) fitRatio(); }
  function fitRatio() { const w = E.work.width, h = E.work.height; let nw = w, nh = w / E.ratio; if (nh > h) { nh = h; nw = h * E.ratio; } E.rect = { x: (w - nw) / 2, y: (h - nh) / 2, w: nw, h: nh }; }
  function transform(kind) {
    const s = E.work, c = document.createElement('canvas'), q = kind === 'rotL' || kind === 'rotR';
    c.width = q ? s.height : s.width; c.height = q ? s.width : s.height; const x = c.getContext('2d');
    if (kind === 'rotR') { x.translate(c.width, 0); x.rotate(Math.PI / 2); } else if (kind === 'rotL') { x.translate(0, c.height); x.rotate(-Math.PI / 2); } else if (kind === 'flipH') { x.translate(c.width, 0); x.scale(-1, 1); } else { x.translate(0, c.height); x.scale(1, -1); }
    x.drawImage(s, 0, 0); E.work = c; fullRect(); drawWork();
  }
  function openImageEditor(img) {
    E.img = img; E.ratio = 0; $('#imgEdRatio').value = 'free';
    const im = new Image(); im.crossOrigin = 'anonymous';
    im.onload = () => {
      const k = Math.min(1, 2400 / Math.max(im.naturalWidth, im.naturalHeight)); const c = document.createElement('canvas'); c.width = Math.round(im.naturalWidth * k); c.height = Math.round(im.naturalHeight * k);
      c.getContext('2d').drawImage(im, 0, 0, c.width, c.height); E.orig = c; E.work = c; openModal('#imgEditorModal'); setTimeout(() => { fullRect(); drawWork(); }, 60);
    };
    im.onerror = () => toast('That picture could not be opened for editing.', 'error'); im.src = img.src;
  }
  $('#imgEdRotL').onclick = () => transform('rotL'); $('#imgEdRotR').onclick = () => transform('rotR'); $('#imgEdFlipH').onclick = () => transform('flipH'); $('#imgEdFlipV').onclick = () => transform('flipV');
  $('#imgEdReset').onclick = () => { E.work = E.orig; fullRect(); drawWork(); };
  $('#imgEdRatio').onchange = e => { E.ratio = e.target.value === 'free' ? 0 : parseFloat(e.target.value); if (E.ratio) fitRatio(); else fullRect(); paintBox(); };
  const closeEd = () => closeModal('#imgEditorModal'); $('#imgEdClose').onclick = closeEd; $('#imgEdCancel').onclick = closeEd;
  box.addEventListener('pointerdown', e => {
    e.preventDefault(); box.setPointerCapture(e.pointerId);
    const h = e.target.dataset ? e.target.dataset.h : null;
    E.drag = { h: h || 'move', x: e.clientX, y: e.clientY, r: { ...E.rect } };
  });
  box.addEventListener('pointermove', e => {
    if (!E.drag) return; const k = E.view, d = E.drag, W = E.work.width, H = E.work.height, min = 24;
    const dx = (e.clientX - d.x) / k, dy = (e.clientY - d.y) / k, r = d.r;
    if (d.h === 'move') { E.rect = { x: clamp(r.x + dx, 0, W - r.w), y: clamp(r.y + dy, 0, H - r.h), w: r.w, h: r.h }; paintBox(); return; }
    let x1 = r.x, y1 = r.y, x2 = r.x + r.w, y2 = r.y + r.h;
    if (d.h.includes('w')) x1 = clamp(r.x + dx, 0, x2 - min); if (d.h.includes('e')) x2 = clamp(r.x + r.w + dx, x1 + min, W);
    if (d.h.includes('n')) y1 = clamp(r.y + dy, 0, y2 - min); if (d.h.includes('s')) y2 = clamp(r.y + r.h + dy, y1 + min, H);
    if (E.ratio) {
      let nw = x2 - x1, nh = y2 - y1;
      if (d.h === 'n' || d.h === 's') { nw = nh * E.ratio; if (x1 + nw > W) { nw = W - x1; nh = nw / E.ratio; } x2 = x1 + nw; if (d.h === 'n') y1 = y2 - nh; else y2 = y1 + nh; }
      else { nh = nw / E.ratio; if (d.h.includes('n')) y1 = y2 - nh; else y2 = y1 + nh; if (y1 < 0) { y1 = 0; nh = y2 - y1; nw = nh * E.ratio; if (d.h.includes('w')) x1 = x2 - nw; else x2 = x1 + nw; } if (y2 > H) { y2 = H; nh = y2 - y1; nw = nh * E.ratio; if (d.h.includes('w')) x1 = x2 - nw; else x2 = x1 + nw; } }
    }
    E.rect = { x: x1, y: y1, w: x2 - x1, h: y2 - y1 }; paintBox();
  });
  const endCrop = () => { E.drag = null; }; box.addEventListener('pointerup', endCrop); box.addEventListener('pointercancel', endCrop);
  $('#imgEdApply').onclick = async () => {
    const r = E.rect, out = document.createElement('canvas'); const k = Math.min(1, 2000 / Math.max(r.w, r.h)); out.width = Math.round(r.w * k); out.height = Math.round(r.h * k);
    out.getContext('2d').drawImage(E.work, r.x, r.y, r.w, r.h, 0, 0, out.width, out.height);
    const png = /\.png($|\?)/i.test(E.img.src); const btn = $('#imgEdApply'); btn.disabled = true; btn.textContent = 'Applying…';
    out.toBlob(async blob => {
      const j = await upload(new File([blob], 'edited.' + (png ? 'png' : 'jpg'), { type: png ? 'image/png' : 'image/jpeg' }));
      btn.disabled = false; btn.textContent = 'Apply changes';
      if (!j) return;
      const img = E.img; const w = img.offsetWidth; img.style.height = ''; img.src = j.url; img.style.width = Math.max(60, w) + 'px';
      closeEd(); changed(true); setTimeout(placeOverlay, 120); toast('Picture updated.', 'success');
    }, png ? 'image/png' : 'image/jpeg', 0.92);
  };

  /* ---------- status ---------- */
  function stats() {
    const t = (paper.innerText || '').replace(/\s+/g, ' ').trim(); const words = t ? t.split(' ').length : 0;
    $('[data-words]', root).textContent = words + (words === 1 ? ' word' : ' words');
    const pages = Math.max(1, Math.ceil(paper.offsetHeight / pageDims()[1] - 0.02)); $('[data-pages]', root).textContent = pages + (pages === 1 ? ' page' : ' pages');
  }
  function setSaveState(text, cls) { const s = $('[data-savestate]', root); s.textContent = text; s.className = 'save-state ' + (cls || ''); }
  new ResizeObserver(() => { stats(); placeOverlay(); }).observe(paper);

  /* ---------- printing ---------- */
  function print(title) {
    selectImage(null);
    const old = document.getElementById('printArea'); if (old) old.remove();
    const area = document.createElement('div'); area.id = 'printArea'; area.className = 'paper print-paper';
    const pad = MARGINS[state.margin] || 96; area.style.setProperty('--pad', pad + 'px'); area.innerHTML = paper.innerHTML;
    $$('.is-selected,.is-dragging', area).forEach(n => n.classList.remove('is-selected', 'is-dragging'));
    document.body.appendChild(area);
    let st = document.getElementById('pageStyle'); if (!st) { st = document.createElement('style'); st.id = 'pageStyle'; document.head.appendChild(st); }
    st.textContent = `@page{size:${state.size} ${state.orientation};margin:${pad}px}`;
    const prevTitle = document.title; if (title) document.title = title;
    document.body.classList.add('printing-doc');
    const done = () => { document.body.classList.remove('printing-doc'); area.remove(); document.title = prevTitle; window.removeEventListener('afterprint', done); };
    window.addEventListener('afterprint', done); setTimeout(() => window.print(), 80);
  }

  /* ---------- public API ---------- */
  applyLayout(); stats();
  setTimeout(() => { if (desk.clientWidth && desk.clientWidth < pageDims()[0] + 30) fit(); }, 60);
  const api_ = {
    getHTML() { selectImage(null); $$('.is-selected,.is-dragging', paper).forEach(n => n.classList.remove('is-selected', 'is-dragging')); return paper.innerHTML; },
    setHTML, getLayout: () => ({ orientation: state.orientation, size: state.size, margin: state.margin }),
    setLayout(o) { if (o.orientation) state.orientation = o.orientation; if (o.size) state.size = o.size; if (o.margin) state.margin = o.margin; applyLayout(); },
    isEmpty() { const t = (paper.innerText || '').replace(/\s+/g, '').trim(); return !t && !paper.querySelector('img,table,hr'); },
    onChange(fn) { state.changeCbs.push(fn); }, setSaveState, print, focus() { paper.focus(); }, fit, paper
  };
  return api_;
}
window.OJTEditor = { init };
})();
