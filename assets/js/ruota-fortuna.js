/**
 * ruota-fortuna.js — Ruota della Fortuna (area admin)
 *
 * Estrae un ordine casuale fra i partecipanti con una ruota SVG che gira.
 * Tutto lato client: nessuna persistenza, nessun parametro.
 * I fantallenatori della stagione corrente arrivano da window.RF_PARTICIPANTS;
 * si possono aggiungere nomi manualmente.
 */
(function () {
  'use strict';

  var MAX = 20;

  /* Partecipanti registrati passati dal PHP: [{ name, team }] */
  var registered = Array.isArray(window.RF_PARTICIPANTS) ? window.RF_PARTICIPANTS : [];

  /* Stato */
  var selected = [];      // nomi selezionati
  var wheel    = [];      // nomi ancora sulla ruota
  var results  = [];      // { position, name }
  var rotation = 0;
  var spinning = false;

  /* DOM */
  var grid        = document.getElementById('rf-grid');
  var chips       = document.getElementById('rf-selected');
  var countEl     = document.getElementById('rf-count');
  var manualInput = document.getElementById('rf-manual');
  var addBtn      = document.getElementById('rf-add');
  var startBtn    = document.getElementById('rf-start');
  var clearBtn    = document.getElementById('rf-clear');
  var selAllBtn   = document.getElementById('rf-select-all');
  var deselAllBtn = document.getElementById('rf-deselect-all');
  var spinBtn     = document.getElementById('rf-spin');
  var wheelEl     = document.getElementById('rf-wheel');
  var statusEl    = document.getElementById('rf-status');
  var resSection  = document.getElementById('rf-results-section');
  var resList     = document.getElementById('rf-results');
  var newBtn      = document.getElementById('rf-new');
  var toastWrap   = document.getElementById('rf-toasts');

  if (!grid || !wheelEl) return;

  /* ── Toast ────────────────────────────────────────────────────── */
  function toast(message, type) {
    if (!toastWrap) return;
    var t = document.createElement('div');
    t.className = 'rf-toast rf-toast--' + (type || 'info');
    t.innerHTML = '<span class="material-symbols-rounded">' +
      (type === 'success' ? 'check_circle' : type === 'error' ? 'error' : 'info') +
      '</span><span>' + escHtml(message) + '</span>';
    toastWrap.appendChild(t);
    requestAnimationFrame(function () { t.classList.add('is-on'); });
    setTimeout(function () {
      t.classList.remove('is-on');
      setTimeout(function () { t.remove(); }, 280);
    }, 3600);
  }

  /* ── Render griglia partecipanti registrati ──────────────────── */
  function renderGrid() {
    grid.innerHTML = '';
    if (!registered.length) {
      grid.innerHTML = '<div class="rf-grid__empty">Nessun fantallenatore attivo. ' +
        'Aggiungi i partecipanti manualmente qui sotto.</div>';
      return;
    }
    registered.forEach(function (p) {
      var on = selected.indexOf(p.name) >= 0;
      var item = document.createElement('button');
      item.type = 'button';
      item.className = 'rf-user' + (on ? ' is-selected' : '');
      item.dataset.name = p.name;
      item.innerHTML =
        '<span class="rf-user__avatar">' + escHtml(p.name.charAt(0).toUpperCase()) + '</span>' +
        '<span class="rf-user__meta"><span class="rf-user__name">' + escHtml(p.name) + '</span>' +
        (p.team ? '<span class="rf-user__team">' + escHtml(p.team) + '</span>' : '') + '</span>' +
        '<span class="rf-user__check material-symbols-rounded">' + (on ? 'check_circle' : 'add_circle') + '</span>';
      item.addEventListener('click', function () { toggle(p.name); });
      grid.appendChild(item);
    });
  }

  function syncGrid() {
    grid.querySelectorAll('.rf-user').forEach(function (it) {
      var on = selected.indexOf(it.dataset.name) >= 0;
      it.classList.toggle('is-selected', on);
      var chk = it.querySelector('.rf-user__check');
      if (chk) chk.textContent = on ? 'check_circle' : 'add_circle';
    });
  }

  /* ── Selezione ────────────────────────────────────────────────── */
  function toggle(name) {
    var i = selected.indexOf(name);
    if (i >= 0) {
      selected.splice(i, 1);
    } else {
      if (selected.length >= MAX) { toast('Massimo ' + MAX + ' partecipanti', 'error'); return; }
      selected.push(name);
    }
    syncGrid();
    renderChips();
  }

  function addManual() {
    var name = (manualInput.value || '').trim();
    if (!name) { toast('Inserisci un nome', 'error'); return; }
    if (selected.indexOf(name) >= 0) { toast('Già nella lista', 'error'); return; }
    if (selected.length >= MAX) { toast('Massimo ' + MAX + ' partecipanti', 'error'); return; }
    selected.push(name);
    manualInput.value = '';
    manualInput.focus();
    syncGrid();
    renderChips();
  }

  function selectAll() {
    registered.forEach(function (p) {
      if (selected.indexOf(p.name) < 0 && selected.length < MAX) selected.push(p.name);
    });
    syncGrid();
    renderChips();
  }

  function deselectAll() {
    selected = [];
    syncGrid();
    renderChips();
  }

  function renderChips() {
    countEl.textContent = selected.length;
    if (!selected.length) {
      chips.innerHTML = '<span class="rf-empty">Nessun partecipante selezionato</span>';
    } else {
      chips.innerHTML = '';
      var regNames = registered.map(function (p) { return p.name; });
      selected.forEach(function (name) {
        var manual = regNames.indexOf(name) < 0;
        var chip = document.createElement('span');
        chip.className = 'rf-chip' + (manual ? ' rf-chip--manual' : '');
        chip.innerHTML = '<span class="material-symbols-rounded">' + (manual ? 'edit' : 'person') + '</span>' +
          escHtml(name) + '<button type="button" class="rf-chip__x" aria-label="Rimuovi">&times;</button>';
        chip.querySelector('.rf-chip__x').addEventListener('click', function () {
          selected = selected.filter(function (n) { return n !== name; });
          syncGrid();
          renderChips();
        });
        chips.appendChild(chip);
      });
    }
    startBtn.disabled  = selected.length < 2;
    clearBtn.disabled  = selected.length === 0;
    if (selAllBtn)   selAllBtn.disabled   = !registered.length || selected.length >= Math.min(registered.length, MAX);
    if (deselAllBtn) deselAllBtn.disabled = selected.length === 0;
  }

  /* ── Geometria ruota (SVG) ───────────────────────────────────── */
  var PALETTE = [
    '#38bdf8', '#a78bfa', '#f472b6', '#fbbf24',
    '#34d399', '#fb7185', '#22d3ee', '#a3e635',
    '#fb923c', '#c084fc', '#2dd4bf', '#f43f5e',
    '#818cf8', '#4ade80', '#facc15', '#e879f9'
  ];
  var ANGLE_EPS = 0.001;

  function degToRad(d) { return (d - 90) * Math.PI / 180; }
  function polar(cx, cy, r, deg) { var a = degToRad(deg); return { x: cx + r * Math.cos(a), y: cy + r * Math.sin(a) }; }
  function donut(cx, cy, ro, ri, s, e) {
    var large = (e - s) > 180 ? 1 : 0;
    var p1 = polar(cx, cy, ro, s), p2 = polar(cx, cy, ro, e), p3 = polar(cx, cy, ri, e), p4 = polar(cx, cy, ri, s);
    return 'M ' + p1.x + ' ' + p1.y + ' A ' + ro + ' ' + ro + ' 0 ' + large + ' 1 ' + p2.x + ' ' + p2.y +
           ' L ' + p3.x + ' ' + p3.y + ' A ' + ri + ' ' + ri + ' 0 ' + large + ' 0 ' + p4.x + ' ' + p4.y + ' Z';
  }
  function wrap(text, max) {
    if (text.length <= max) return [text];
    var words = text.split(/\s+/);
    if (words.length > 1) {
      var l1 = '', l2 = '';
      words.forEach(function (w) {
        if ((l1 + ' ' + w).trim().length <= max) l1 = (l1 ? l1 + ' ' : '') + w;
        else l2 = (l2 ? l2 + ' ' : '') + w;
      });
      return [l1, l2].filter(Boolean);
    }
    return [text.slice(0, max), text.slice(max)];
  }

  var SVGNS = 'http://www.w3.org/2000/svg';

  function buildWheel() {
    wheelEl.innerHTML = '';
    if (!wheel.length) return;

    var size = 520, cx = size / 2, cy = cx;
    var ro = size * 0.46, ri = size * 0.15;
    var seg = 360 / wheel.length;
    var baseFont = Math.max(11, Math.min(17, 19 - Math.ceil(wheel.length / 2)));
    var labelR = ro + 30;
    var pad = 92; // margine per le etichette esterne, così non vengono tagliate

    var svg = document.createElementNS(SVGNS, 'svg');
    svg.setAttribute('class', 'rf-wheel__svg');
    svg.setAttribute('viewBox', (-pad) + ' ' + (-pad) + ' ' + (size + 2 * pad) + ' ' + (size + 2 * pad));
    svg.style.overflow = 'visible';

    var g = document.createElementNS(SVGNS, 'g');
    g.setAttribute('id', 'rf-wheel-group');
    g.setAttribute('class', 'rf-wheel__group');

    wheel.forEach(function (name, i) {
      var start = i * seg;
      var end = (i === wheel.length - 1) ? 360 - ANGLE_EPS : (i + 1) * seg;
      var mid = start + (end - start) / 2;

      var path = document.createElementNS(SVGNS, 'path');
      path.setAttribute('class', 'rf-slice');
      path.setAttribute('d', donut(cx, cy, ro, ri, start, end));
      path.setAttribute('fill', PALETTE[i % PALETTE.length]);
      g.appendChild(path);

      var ps = polar(cx, cy, ro - 6, mid), pe = polar(cx, cy, ro + 18, mid);
      var lead = document.createElementNS(SVGNS, 'line');
      lead.setAttribute('class', 'rf-leader');
      lead.setAttribute('x1', ps.x); lead.setAttribute('y1', ps.y);
      lead.setAttribute('x2', pe.x); lead.setAttribute('y2', pe.y);
      g.appendChild(lead);

      var out = polar(cx, cy, labelR, mid);
      var label = document.createElementNS(SVGNS, 'text');
      label.setAttribute('class', 'rf-label');
      label.setAttribute('x', out.x);
      label.setAttribute('y', out.y);
      var deg = (mid % 360 + 360) % 360;
      label.setAttribute('text-anchor', (deg > 90 && deg < 270) ? 'end' : 'start');
      label.setAttribute('font-size', String(baseFont));
      label.setAttribute('dominant-baseline', 'middle');
      wrap(name, Math.max(8, Math.floor(baseFont * 0.85))).forEach(function (ln, j) {
        var ts = document.createElementNS(SVGNS, 'tspan');
        ts.setAttribute('x', out.x);
        ts.setAttribute('dy', j === 0 ? 0 : baseFont * 1.15);
        ts.textContent = ln;
        label.appendChild(ts);
      });
      g.appendChild(label);
    });

    var hub = document.createElementNS(SVGNS, 'circle');
    hub.setAttribute('class', 'rf-hub');
    hub.setAttribute('cx', cx); hub.setAttribute('cy', cy);
    hub.setAttribute('r', ri - 4);

    svg.appendChild(g);
    svg.appendChild(hub);
    wheelEl.appendChild(svg);
  }

  /* ── Avvio / spin / reset ─────────────────────────────────────── */
  function start() {
    if (selected.length < 2) { toast('Servono almeno 2 partecipanti', 'error'); return; }
    wheel = selected.slice();
    results = [];
    rotation = 0;
    buildWheel();
    spinBtn.disabled = false;
    startBtn.disabled = true;
    clearBtn.disabled = true;
    setLock(true);
    resSection.hidden = true;
    status('<strong>' + wheel.length + '</strong> partecipanti sulla ruota. Premi «Gira».');
    spinBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function spin() {
    if (spinning || !wheel.length) return;
    var group = document.getElementById('rf-wheel-group');
    if (!group) return;

    spinning = true;
    spinBtn.disabled = true;
    spinBtn.classList.add('is-spinning');
    status('<span class="rf-spinner"></span> Estrazione in corso…');

    var spins = 5 + Math.random() * 5;
    var finalAngle = Math.random() * 360;
    rotation += spins * 360 + finalAngle;
    group.style.transform = 'rotate(' + rotation + 'deg)';

    setTimeout(function () {
      var seg = 360 / wheel.length;
      var norm = (360 - (rotation % 360)) % 360;
      var idx = Math.floor(norm / seg) % wheel.length;
      var winner = wheel[idx];

      results.push({ position: results.length + 1, name: winner });
      toast(results.length + '° estratto: ' + winner, 'success');
      renderResults();   // ordine visibile e aggiornato già dalla prima estrazione

      wheel = wheel.filter(function (n) { return n !== winner; });

      spinning = false;
      spinBtn.classList.remove('is-spinning');

      if (wheel.length > 0) {
        buildWheel();
        spinBtn.disabled = false;
        status('Estratto <strong>' + winner + '</strong>. ' + wheel.length + ' rimasti. Premi «Gira».');
      } else {
        spinBtn.disabled = true;
        status('<strong style="color:var(--c-success)">Estrazione completata.</strong>');
      }
    }, 4200);
  }

  function renderResults() {
    resList.innerHTML = results.map(function (r) {
      var medal = r.position <= 3 ? ' rf-result--top' : '';
      return '<div class="rf-result' + medal + '">' +
        '<span class="rf-result__pos">' + r.position + '°</span>' +
        '<span class="rf-result__name">' + escHtml(r.name) + '</span></div>';
    }).join('');
    resSection.hidden = false;
  }

  function reset() {
    wheel = [];
    results = [];
    rotation = 0;
    spinning = false;
    wheelEl.innerHTML = '<div class="rf-wheel__placeholder"><span class="material-symbols-rounded">casino</span></div>';
    spinBtn.disabled = true;
    spinBtn.classList.remove('is-spinning');
    setLock(false);
    startBtn.disabled = selected.length < 2;
    clearBtn.disabled = selected.length === 0;
    resSection.hidden = true;
    status('Aggiungi almeno 2 partecipanti e premi «Genera ordine».');
  }

  function clearAll() {
    selected = [];
    syncGrid();
    renderChips();
    reset();
  }

  function setLock(locked) {
    grid.classList.toggle('is-locked', locked);
    manualInput.disabled = locked;
    addBtn.disabled = locked;
    if (selAllBtn)   selAllBtn.disabled   = locked || !registered.length;
    if (deselAllBtn) deselAllBtn.disabled = locked || !selected.length;
  }

  function status(html) { statusEl.innerHTML = html; }
  function escHtml(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }

  /* ── Eventi ───────────────────────────────────────────────────── */
  addBtn.addEventListener('click', addManual);
  manualInput.addEventListener('keypress', function (e) { if (e.key === 'Enter') { e.preventDefault(); addManual(); } });
  startBtn.addEventListener('click', start);
  clearBtn.addEventListener('click', clearAll);
  spinBtn.addEventListener('click', spin);
  if (newBtn)     newBtn.addEventListener('click', reset);
  if (selAllBtn)  selAllBtn.addEventListener('click', selectAll);
  if (deselAllBtn) deselAllBtn.addEventListener('click', deselectAll);

  /* ── Init ─────────────────────────────────────────────────────── */
  renderGrid();
  renderChips();
  reset();
}());
