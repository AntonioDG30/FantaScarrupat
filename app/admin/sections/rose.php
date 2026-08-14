<?php
/**
 * app/admin/sections/rose.php — Gestione rose per stagione
 *
 * Step 1: selezione squadra + anno + modalità
 * Step 2: dopo scelta, mostra rosa + form aggiunta giocatore
 */

// Parametri step 2
$sel_fq      = trim($_GET['fq']       ?? '');
$sel_anno    = (int)($_GET['anno']    ?? 0);
$sel_mod     = $_GET['modalita'] ?? '';
$in_step2    = $sel_fq !== '' && $sel_anno > 0 && in_array($sel_mod, ['classic', 'mantra'], true);

// Tutte le fantasquadre
$tutte_fq = $db->query("SELECT nome_fantasquadra FROM fantasquadra ORDER BY nome_fantasquadra ASC")
              ->fetchAll(\PDO::FETCH_COLUMN);

// Per ogni squadra, in quali stagioni+modalità ha una rosa (per filtrare l'elenco).
// chiave: "anno|modalita"
$fq_seasons = [];
foreach ($db->query("SELECT DISTINCT nome_fantasquadra, anno, modalita FROM rosa") as $r) {
    $fq_seasons[$r['nome_fantasquadra']][] = $r['anno'] . '|' . $r['modalita'];
}

// Anni disponibili (fino all'anno massimo, incluse le stagioni create a mano)
$anni_rng = [];
for ($y = (int)($anno_max ?? CURRENT_ANNO); $y >= 2016; $y--) $anni_rng[] = $y;

if ($in_step2):
    // Trova o crea rosa
    $stmt = $db->prepare("SELECT id_rosa FROM rosa WHERE nome_fantasquadra = ? AND anno = ? AND modalita = ?");
    $stmt->execute([$sel_fq, $sel_anno, $sel_mod]);
    $rosa_id = $stmt->fetchColumn();

    // Giocatori in rosa
    $rosa_rows = [];
    if ($rosa_id) {
        $rr = $db->prepare("
            SELECT g.codice_fantacalcio, g.nome_completo, gs.squadra_reale,
                   dr.crediti_pagati
              FROM dettagli_rosa dr
              JOIN giocatore g ON g.codice_fantacalcio = dr.codice_fantacalcio
         LEFT JOIN giocatore_stagione gs ON gs.codice_fantacalcio = g.codice_fantacalcio AND gs.anno = ?
             WHERE dr.id_rosa = ?
             ORDER BY g.nome_completo ASC
        ");
        $rr->execute([$sel_anno, $rosa_id]);
        $rosa_rows = $rr->fetchAll(\PDO::FETCH_ASSOC);

        // Ruoli per ogni giocatore in rosa
        $codici = array_column($rosa_rows, 'codice_fantacalcio');
        $ruoli_rosa = [];
        if (!empty($codici)) {
            $ph  = implode(',', array_fill(0, count($codici), '?'));
            $rs2 = $db->prepare("
                SELECT gs.codice_fantacalcio, r.nome, r.tipo
                  FROM giocatore_stagione gs
                  JOIN ruoli_giocatori rg ON rg.id_giocatore_stagione = gs.id
                  JOIN ruoli r ON r.id_ruolo = rg.id_ruolo
                 WHERE gs.codice_fantacalcio IN ($ph) AND gs.anno = ?
                   AND r.tipo = ?
            ");
            $params_r = array_merge($codici, [$sel_anno, $sel_mod === 'classic' ? 'classic' : 'mantra']);
            $rs2->execute($params_r);
            foreach ($rs2->fetchAll(\PDO::FETCH_ASSOC) as $rw) {
                $ruoli_rosa[$rw['codice_fantacalcio']][] = $rw['nome'];
            }
        }
    }

    $budget_totale = array_sum(array_column($rosa_rows, 'crediti_pagati'));
endif;
?>

<!-- Pannello import CSV -->
<details style="margin-bottom:var(--sp-5)">
  <summary style="display:flex;align-items:center;gap:var(--sp-2);cursor:pointer;font-size:.9rem;font-weight:600;color:var(--c-text-muted);list-style:none;padding:var(--sp-3) 0">
    <span class="material-symbols-rounded" style="font-size:16px" aria-hidden="true">upload_file</span>
    Importa rose da CSV
  </summary>
  <div class="panel" style="margin-top:var(--sp-3)">
    <div class="panel__body">
      <p style="font-size:.8125rem;color:var(--c-text-muted);margin:0 0 var(--sp-4)">
        Formato CSV: una riga per giocatore, tre colonne:
        <code style="background:var(--c-bg-alt);padding:1px 5px;border-radius:3px">NomeFantasquadra, CodiceFantacalcio, CreditiPagati</code>.
        Nessuna intestazione. Un file può contenere rose di più squadre.
      </p>
      <?php
      // Mostra risultato import se disponibile
      if (!empty($_SESSION['import_result'])) {
          $ir = $_SESSION['import_result'];
          unset($_SESSION['import_result']);
          if (isset($ir['error'])) {
              echo '<div class="alert alert-error" role="alert" style="margin-bottom:var(--sp-4)">'
                 . htmlspecialchars($ir['error']) . '</div>';
          } elseif (isset($ir['msg'])) {
              echo '<div class="alert alert-success" role="alert" style="margin-bottom:var(--sp-4)">'
                 . htmlspecialchars($ir['msg']) . '</div>';
          }
      }
      ?>
      <form method="post"
            action="<?= BASE_PATH ?>app/admin/import_rose.php"
            enctype="multipart/form-data"
            data-proc
            data-proc-title="Importazione rose"
            data-proc-steps="Lettura del file CSV|Controllo di squadre e giocatori|Salvataggio delle rose"
            style="display:flex;gap:var(--sp-4);flex-wrap:wrap;align-items:flex-end">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

        <div class="adm-field" style="margin:0;flex:1;min-width:240px">
          <span class="adm-label">File rose</span>
          <label class="adm-file">
            <input type="file" name="file_rose" accept=".csv" class="adm-file__input" required>
            <span class="adm-file__icon material-symbols-rounded" aria-hidden="true">upload_file</span>
            <span class="adm-file__text">Scegli o trascina il file</span>
            <span class="adm-file__hint">CSV</span>
          </label>
        </div>

        <div class="adm-field" style="margin:0;min-width:140px">
          <label class="adm-label" for="rose_csv_anno">Anno</label>
          <select id="rose_csv_anno" name="anno" class="adm-input">
            <?php foreach ($anni_rng as $y): ?>
              <option value="<?= $y ?>" <?= $y === $sel_anno ? 'selected' : '' ?>>
                <?= anno_to_stagione($y) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="adm-field" style="margin:0;min-width:130px">
          <label class="adm-label" for="rose_csv_mod">Modalità</label>
          <select id="rose_csv_mod" name="modalita" class="adm-input">
            <option value="classic">Classic</option>
            <option value="mantra">Mantra</option>
          </select>
        </div>

        <div style="padding-bottom:1px">
          <button type="submit" class="btn-sm btn-primary">
            <span class="material-symbols-rounded" style="font-size:15px" aria-hidden="true">upload</span>
            Importa
          </button>
        </div>
      </form>
    </div>
  </div>
</details>

<!-- Step 1: selettore -->
<div class="panel" style="margin-bottom:var(--sp-6)">
  <div class="panel__header">
    <span class="panel__title">Seleziona rosa</span>
  </div>
  <div class="panel__body">
    <form method="get" action="" style="display:flex;gap:var(--sp-4);flex-wrap:wrap;align-items:flex-end">
      <input type="hidden" name="tab" value="rose">

      <div class="adm-field" style="margin:0;flex:1;min-width:180px">
        <label class="adm-label" for="rose_fq">Fantasquadra</label>
        <select id="rose_fq" name="fq" class="adm-input">
          <option value="">Scegli squadra…</option>
          <?php foreach ($tutte_fq as $fq): ?>
            <option value="<?= htmlspecialchars($fq) ?>"
                    data-seasons="<?= htmlspecialchars(implode(' ', $fq_seasons[$fq] ?? [])) ?>"
                    <?= $fq === $sel_fq ? 'selected' : '' ?>>
              <?= htmlspecialchars($fq) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="adm-field" style="margin:0;min-width:140px">
        <label class="adm-label" for="rose_anno">Anno</label>
        <select id="rose_anno" name="anno" class="adm-input">
          <?php foreach ($anni_rng as $y): ?>
            <option value="<?= $y ?>" <?= $y === $sel_anno ? 'selected' : '' ?>>
              <?= anno_to_stagione($y) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="adm-field" style="margin:0;min-width:130px">
        <label class="adm-label" for="rose_mod">Modalità</label>
        <select id="rose_mod" name="modalita" class="adm-input">
          <option value="classic" <?= $sel_mod === 'classic' ? 'selected' : '' ?>>Classic</option>
          <option value="mantra"  <?= $sel_mod === 'mantra'  ? 'selected' : '' ?>>Mantra</option>
        </select>
      </div>

      <div style="padding-bottom:1px">
        <button type="submit" class="btn-sm btn-primary">
          <span class="material-symbols-rounded" style="font-size:16px" aria-hidden="true">folder_open</span>
          Apri rosa
        </button>
      </div>
    </form>
  </div>
</div>

<script>
/* Filtra l'elenco squadre: mostra solo quelle con una rosa nella stagione+modalità scelta */
(function () {
  var fqSel   = document.getElementById('rose_fq');
  var annoSel = document.getElementById('rose_anno');
  var modSel  = document.getElementById('rose_mod');
  if (!fqSel || !annoSel || !modSel) return;

  function apply() {
    var key = annoSel.value + '|' + modSel.value;
    var visibili = 0;
    Array.prototype.forEach.call(fqSel.options, function (opt) {
      if (!opt.value) return; // placeholder
      var seasons = (opt.dataset.seasons || '').split(' ');
      var show = seasons.indexOf(key) >= 0;
      opt.hidden = !show;
      opt.disabled = !show;
      if (show) visibili++;
      if (!show && opt.selected) { fqSel.value = ''; }
    });
    var ph = fqSel.options[0];
    if (ph && !ph.value) {
      ph.textContent = visibili ? 'Scegli squadra…' : 'Nessuna rosa per questa stagione';
    }
  }

  annoSel.addEventListener('change', apply);
  modSel.addEventListener('change', apply);
  apply();
}());
</script>

<?php if ($in_step2): ?>

<!-- Step 2: rosa -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-4);flex-wrap:wrap;gap:var(--sp-3)">
  <div>
    <h2 style="font-family:var(--font-heading);font-size:1.35rem;font-weight:700;color:var(--c-text);margin:0 0 var(--sp-1)">
      <?= htmlspecialchars($sel_fq) ?>
    </h2>
    <div style="font-size:.875rem;color:var(--c-text-muted)">
      <?= anno_to_stagione($sel_anno) ?> &middot;
      <span style="text-transform:capitalize"><?= $sel_mod ?></span>
      &middot; <?= count($rosa_rows) ?> giocatori
      &middot; Crediti: <strong style="color:var(--c-text)"><?= $budget_totale ?></strong>
    </div>
  </div>

  <div style="display:flex;gap:var(--sp-3);align-items:center">
    <?php if ($rosa_id): ?>
    <a href="<?= BASE_PATH ?>api/admin/rose.php?action=export&id_rosa=<?= $rosa_id ?>"
       class="btn-sm btn-secondary" style="font-size:.75rem">
      <span class="material-symbols-rounded" style="font-size:14px" aria-hidden="true">download</span>
      Esporta CSV
    </a>
    <?php else: ?>
    <form method="post" action="<?= BASE_PATH ?>api/admin/rose.php">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
      <input type="hidden" name="action"          value="crea_rosa">
      <input type="hidden" name="nome_fantasquadra" value="<?= htmlspecialchars($sel_fq) ?>">
      <input type="hidden" name="anno"            value="<?= $sel_anno ?>">
      <input type="hidden" name="modalita"        value="<?= $sel_mod ?>">
      <button type="submit" class="btn-sm btn-primary">
        <span class="material-symbols-rounded" style="font-size:16px" aria-hidden="true">add</span>
        Crea rosa
      </button>
    </form>
    <?php endif; ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:var(--sp-6);align-items:start" id="rose-grid">

  <!-- Tabella rosa corrente -->
  <div class="panel">
    <div class="panel__body--flush">
      <table class="adm-table" aria-label="Rosa">
        <thead>
          <tr>
            <th>Giocatore</th>
            <th>Squadra</th>
            <th>Ruolo<?= $sel_mod === 'mantra' ? 'i' : '' ?></th>
            <th style="width:70px;text-align:right">Crediti</th>
            <th style="width:50px"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rosa_rows as $rg):
              $ruoli_g = $ruoli_rosa[$rg['codice_fantacalcio']] ?? [];
          ?>
          <tr>
            <td style="font-weight:500"><?= htmlspecialchars($rg['nome_completo']) ?></td>
            <td style="color:var(--c-text-muted);font-size:.875rem"><?= htmlspecialchars($rg['squadra_reale'] ?? '') ?></td>
            <td>
              <div style="display:flex;flex-wrap:wrap;gap:3px">
                <?php foreach ($ruoli_g as $r): ?>
                  <span class="rb <?= rb_class($r) ?>"><?= htmlspecialchars($r) ?></span>
                <?php endforeach; ?>
                <?php if (empty($ruoli_g)): ?>
                  <span style="color:var(--c-text-faint);font-size:.8rem">-</span>
                <?php endif; ?>
              </div>
            </td>
            <td style="text-align:right;font-family:var(--font-heading);font-weight:700">
              <?= (int)$rg['crediti_pagati'] ?>
            </td>
            <td>
              <?php if ($rosa_id): ?>
              <form method="post" action="<?= BASE_PATH ?>api/admin/rose.php" style="margin:0">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="action"   value="rimuovi">
                <input type="hidden" name="id_rosa"  value="<?= $rosa_id ?>">
                <input type="hidden" name="codice"   value="<?= (int)$rg['codice_fantacalcio'] ?>">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                <button type="submit" class="btn-sm" style="padding:2px 6px;background:transparent;border:1px solid var(--c-border);border-radius:var(--r-sm);cursor:pointer;color:var(--c-danger)" title="Rimuovi">
                  <span class="material-symbols-rounded" style="font-size:14px" aria-hidden="true">close</span>
                </button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($rosa_rows)): ?>
          <tr><td colspan="5" style="text-align:center;color:var(--c-text-muted);padding:var(--sp-8)">
            Rosa vuota. <?= !$rosa_id ? 'Prima crea la rosa con il pulsante in alto.' : 'Aggiungi giocatori dal pannello.' ?>
          </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pannello aggiungi giocatore -->
  <?php if ($rosa_id): ?>
  <div class="panel">
    <div class="panel__header">
      <span class="panel__title">Aggiungi giocatore</span>
    </div>
    <div class="panel__body">
      <form method="post" action="<?= BASE_PATH ?>api/admin/rose.php" id="form-add-gioc">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="action"   value="aggiungi">
        <input type="hidden" name="id_rosa"  value="<?= $rosa_id ?>">
        <input type="hidden" name="anno_sel" value="<?= $sel_anno ?>">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
        <input type="hidden" id="codice_sel" name="codice_fantacalcio" value="">

        <div class="adm-field">
          <label class="adm-label" for="gioc-search">Cerca giocatore</label>
          <input type="text" id="gioc-search" class="adm-input" autocomplete="off"
                 placeholder="Digita nome..." aria-label="Cerca giocatore">
          <div id="gioc-results"
               style="display:none;position:absolute;z-index:100;
                      background:var(--c-bg-card);border:1px solid var(--c-border-strong);
                      border-radius:var(--r-md);box-shadow:0 4px 16px oklch(0% 0 0 / .4);
                      max-height:220px;overflow-y:auto;min-width:260px;width:100%">
          </div>
        </div>

        <div class="adm-field">
          <label class="adm-label" for="gioc-preview">Giocatore selezionato</label>
          <div id="gioc-preview"
               style="padding:var(--sp-3) var(--sp-4);background:var(--c-bg-alt);
                      border:1px solid var(--c-border);border-radius:var(--r-md);
                      font-size:.875rem;color:var(--c-text-faint);min-height:42px">
            Nessuno selezionato
          </div>
        </div>

        <div class="adm-field">
          <label class="adm-label" for="crediti">Crediti pagati</label>
          <input type="number" id="crediti" name="crediti_pagati"
                 class="adm-input" min="0" max="500" value="1" required>
        </div>

        <button type="submit" id="btn-add-gioc" class="btn-sm btn-primary" style="width:100%;justify-content:center" disabled>
          <span class="material-symbols-rounded" style="font-size:16px" aria-hidden="true">add</span>
          Aggiungi alla rosa
        </button>
      </form>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /rose-grid -->

<style>
@media (max-width:960px) { #rose-grid { grid-template-columns:1fr !important; } }

#gioc-results .gioc-item {
  padding:var(--sp-3) var(--sp-4);cursor:pointer;font-size:.875rem;
  border-bottom:1px solid var(--c-border);
  transition:background .1s;
}
#gioc-results .gioc-item:last-child { border-bottom:none; }
#gioc-results .gioc-item:hover { background:oklch(from var(--c-accent) l c h / .08); }
</style>

<script>
(function() {
  var searchInput = document.getElementById('gioc-search');
  var resultsEl   = document.getElementById('gioc-results');
  var previewEl   = document.getElementById('gioc-preview');
  var codiceInput = document.getElementById('codice_sel');
  var btnAdd      = document.getElementById('btn-add-gioc');
  var timer = null;
  var anno  = '<?= $sel_anno ?>';
  var modalita = '<?= $sel_mod ?>';

  if (!searchInput) return;

  searchInput.addEventListener('input', function() {
    clearTimeout(timer);
    var q = this.value.trim();
    if (q.length < 2) { resultsEl.style.display = 'none'; return; }
    timer = setTimeout(function() { doSearch(q); }, 250);
  });

  function doSearch(q) {
    fetch(window.BASE_PATH + 'api/admin/giocatori_search.php?q=' + encodeURIComponent(q) + '&anno=' + anno, {
      credentials: 'same-origin'
    }).then(function(r) { return r.json(); })
    .then(function(data) {
      if (!Array.isArray(data) || data.length === 0) {
        resultsEl.innerHTML = '<div style="padding:var(--sp-4);color:var(--c-text-muted);font-size:.875rem">Nessun risultato.</div>';
        resultsEl.style.display = 'block';
        return;
      }
      resultsEl.innerHTML = '';
      data.forEach(function(g) {
        var roles = modalita === 'mantra' ? g.ruoli_mantra.join('; ') : (g.ruolo_classic || '-');
        var div = document.createElement('div');
        div.className = 'gioc-item';
        div.innerHTML =
          '<strong>' + escHtml(g.nome_completo) + '</strong>' +
          '<span style="color:var(--c-text-muted);font-size:.8rem;margin-left:6px">' + escHtml(g.squadra_reale) + '</span>' +
          '<span style="float:right;font-size:.75rem;color:var(--c-text-faint)">' + escHtml(roles) + '</span>';
        div.addEventListener('click', function() {
          codiceInput.value = g.codice_fantacalcio;
          searchInput.value = g.nome_completo;
          previewEl.innerHTML =
            '<strong>' + escHtml(g.nome_completo) + '</strong>' +
            ' <span style="color:var(--c-text-muted)">' + escHtml(g.squadra_reale) + '</span>' +
            ' <span style="color:var(--c-text-faint);font-size:.8rem">' + escHtml(roles) + '</span>';
          previewEl.style.color = 'var(--c-text)';
          btnAdd.disabled = false;
          resultsEl.style.display = 'none';
        });
        resultsEl.appendChild(div);
      });
      resultsEl.style.display = 'block';
    }).catch(function() {});
  }

  document.addEventListener('click', function(e) {
    if (!resultsEl.contains(e.target) && e.target !== searchInput) {
      resultsEl.style.display = 'none';
    }
  });

  // Position results under search input
  var wrapper = searchInput.parentElement;
  wrapper.style.position = 'relative';
  resultsEl.style.top = '100%';
  resultsEl.style.left = '0';

  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }
}());
</script>

<?php else: ?>
  <div style="text-align:center;padding:var(--sp-12) var(--sp-8);color:var(--c-text-muted)">
    <span class="material-symbols-rounded" style="font-size:48px;display:block;margin-bottom:var(--sp-4);opacity:.3" aria-hidden="true">folder_special</span>
    Seleziona una fantasquadra per gestire la sua rosa.
  </div>

  <!-- Statistiche spesa per anno -->
  <?php
  $spesa_anno = $db->query("
      SELECT r.anno,
             COUNT(DISTINCT r.id_rosa)   AS n_rose,
             SUM(dr.crediti_pagati)       AS totale_crediti,
             ROUND(AVG(dr.crediti_pagati),1) AS media_per_giocatore
        FROM rosa r
        JOIN dettagli_rosa dr ON dr.id_rosa = r.id_rosa
       GROUP BY r.anno
       ORDER BY r.anno DESC
       LIMIT 10
  ")->fetchAll(\PDO::FETCH_ASSOC);
  ?>
  <?php if (!empty($spesa_anno)): ?>
  <div class="panel" style="margin-top:var(--sp-6)">
    <div class="panel__header">
      <span class="panel__title">
        <span class="material-symbols-rounded" style="font-size:15px;vertical-align:-3px;margin-right:var(--sp-2)" aria-hidden="true">bar_chart</span>
        Spesa per stagione
      </span>
    </div>
    <div class="panel__body--flush">
      <table class="adm-table">
        <thead>
          <tr>
            <th>Stagione</th>
            <th style="text-align:right">Rose</th>
            <th style="text-align:right">Crediti totali</th>
            <th style="text-align:right">Media per giocatore</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($spesa_anno as $sa): ?>
          <tr>
            <td style="font-weight:500"><?= anno_to_stagione((int)$sa['anno']) ?></td>
            <td style="text-align:right;color:var(--c-text-muted)"><?= (int)$sa['n_rose'] ?></td>
            <td style="text-align:right;font-family:var(--font-heading);font-weight:600"><?= number_format((float)$sa['totale_crediti']) ?></td>
            <td style="text-align:right;color:var(--c-text-muted)"><?= number_format((float)$sa['media_per_giocatore'],1) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

<?php endif; ?>
