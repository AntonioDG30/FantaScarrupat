<?php
/**
 * app/admin/sections/giocatori.php — Gestione giocatori + import XLSX
 */

// Parametri filtro
$anno_sel  = (int)($_GET['anno']   ?? CURRENT_ANNO);
$q_nome    = trim($_GET['q']       ?? '');
$q_ruolo   = trim($_GET['ruolo']   ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$per_page  = 50;

// Anni disponibili (da giocatore_stagione)
$anni_disp = $db->query("SELECT DISTINCT anno FROM giocatore_stagione ORDER BY anno DESC")
              ->fetchAll(\PDO::FETCH_COLUMN);
if (empty($anni_disp)) $anni_disp = [CURRENT_ANNO];

// Ruoli classic disponibili
$ruoli_classic = ['P', 'D', 'C', 'A'];

// ── Query giocatori ───────────────────────────────────────────────────
$where  = ['gs.anno = ?'];
$params = [$anno_sel];

if ($q_nome !== '') {
    $where[]  = 'g.nome_completo LIKE ?';
    $params[] = '%' . $q_nome . '%';
}
if ($q_ruolo !== '') {
    $where[]  = 'EXISTS (SELECT 1 FROM ruoli_giocatori rg2 JOIN ruoli r2 ON rg2.id_ruolo = r2.id_ruolo WHERE rg2.id_giocatore_stagione = gs.id AND r2.nome = ? AND r2.tipo = \'classic\')';
    $params[] = $q_ruolo;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

// Count
$cnt_stmt = $db->prepare("
    SELECT COUNT(DISTINCT g.codice_fantacalcio)
      FROM giocatore g
      JOIN giocatore_stagione gs ON gs.codice_fantacalcio = g.codice_fantacalcio
      $where_sql
");
$cnt_stmt->execute($params);
$total = (int)$cnt_stmt->fetchColumn();
$pages = max(1, (int)ceil($total / $per_page));
$page  = min($page, $pages);

// Data
$offset   = ($page - 1) * $per_page;
$params_p = array_merge($params, [$per_page, $offset]);
$gioc_stmt = $db->prepare("
    SELECT g.codice_fantacalcio, g.nome_completo, gs.squadra_reale, gs.id AS gs_id,
           (SELECT COUNT(*) FROM dettagli_rosa dr WHERE dr.codice_fantacalcio = g.codice_fantacalcio) AS utilizzi,
           (SELECT ROUND(AVG(dr2.crediti_pagati),1) FROM dettagli_rosa dr2 WHERE dr2.codice_fantacalcio = g.codice_fantacalcio) AS media_crediti
      FROM giocatore g
      JOIN giocatore_stagione gs ON gs.codice_fantacalcio = g.codice_fantacalcio
      $where_sql
     ORDER BY g.nome_completo ASC
     LIMIT ? OFFSET ?
");
$gioc_stmt->execute($params_p);
$giocatori = $gioc_stmt->fetchAll(\PDO::FETCH_ASSOC);

// Pre-fetch ruoli per i giocatori restituiti (1 query batch)
$gs_ids = array_column($giocatori, 'gs_id');
$ruoli_map = []; // gs_id → [classic: 'P'|null, mantra: ['Dc','M',...]]
if (!empty($gs_ids)) {
    $ph = implode(',', array_fill(0, count($gs_ids), '?'));
    $rr = $db->prepare("
        SELECT rg.id_giocatore_stagione, r.nome, r.tipo
          FROM ruoli_giocatori rg
          JOIN ruoli r ON r.id_ruolo = rg.id_ruolo
         WHERE rg.id_giocatore_stagione IN ($ph)
    ");
    $rr->execute($gs_ids);
    foreach ($rr->fetchAll(\PDO::FETCH_ASSOC) as $row) {
        $id = $row['id_giocatore_stagione'];
        if ($row['tipo'] === 'classic') {
            $ruoli_map[$id]['classic'] = $row['nome'];
        } else {
            $ruoli_map[$id]['mantra'][] = $row['nome'];
        }
    }
}

// ── Import XLSX result flash ──────────────────────────────────────────
$import_result = $_SESSION['import_result'] ?? null;
if ($import_result) unset($_SESSION['import_result']);

// Base URL per paginazione / filtri
$base_url = '?tab=giocatori&anno=' . $anno_sel
    . ($q_nome  !== '' ? '&q='     . urlencode($q_nome)  : '')
    . ($q_ruolo !== '' ? '&ruolo=' . urlencode($q_ruolo) : '');
?>

<!-- ── Import XLSX ────────────────────────────────────────────────── -->
<div class="panel" style="margin-bottom:var(--sp-6)">
  <details>
    <summary style="display:flex;align-items:center;gap:var(--sp-3);cursor:pointer;padding:var(--sp-4) var(--sp-5);list-style:none">
      <span class="material-symbols-rounded" style="font-size:18px;color:var(--c-accent)" aria-hidden="true">upload_file</span>
      <span style="font-weight:600;color:var(--c-text)">Import giocatori da XLSX</span>
      <span style="font-size:.8125rem;color:var(--c-text-muted);margin-left:var(--sp-2)">Fantacalcio Quotazioni</span>
    </summary>
    <div style="padding:var(--sp-5) var(--sp-5) var(--sp-6);border-top:1px solid var(--c-border)">

      <?php if ($import_result): ?>
        <div class="alert alert-<?= isset($import_result['error']) ? 'error' : 'success' ?>" role="alert">
          <span class="material-symbols-rounded" style="font-size:18px;flex-shrink:0" aria-hidden="true">
            <?= isset($import_result['error']) ? 'error' : 'check_circle' ?>
          </span>
          <div>
            <?php if (isset($import_result['error'])): ?>
              <?= htmlspecialchars($import_result['error']) ?>
            <?php else: ?>
              Import completato:
              <strong><?= (int)$import_result['upserted'] ?></strong> giocatori inseriti/aggiornati,
              <strong><?= (int)$import_result['errors'] ?></strong> errori.
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <form method="post"
            action="<?= BASE_PATH ?>app/admin/import_listone.php"
            enctype="multipart/form-data"
            data-proc
            data-proc-title="Importazione listone"
            data-proc-steps="Lettura del file XLSX|Riconoscimento di giocatori e ruoli|Salvataggio nel database"
            style="display:flex;align-items:flex-end;gap:var(--sp-4);flex-wrap:wrap">
        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="redirect_tab" value="giocatori">

        <div class="adm-field" style="margin:0;flex:1;min-width:260px">
          <span class="adm-label">File listone</span>
          <label class="adm-file">
            <input type="file" name="xlsx_file" accept=".xlsx" class="adm-file__input" required>
            <span class="adm-file__icon material-symbols-rounded" aria-hidden="true">upload_file</span>
            <span class="adm-file__text">Scegli o trascina il file</span>
            <span class="adm-file__hint">XLSX</span>
          </label>
        </div>

        <div class="adm-field" style="margin:0;min-width:160px">
          <label class="adm-label" for="import_anno">Stagione (anno)</label>
          <select id="import_anno" name="anno" class="adm-input">
            <?php
            $cur = (int)($anno_max ?? CURRENT_ANNO);
            for ($y = $cur; $y >= 2016; $y--): ?>
              <option value="<?= $y ?>" <?= $y === CURRENT_ANNO ? 'selected' : '' ?>>
                <?= anno_to_stagione($y) ?>
              </option>
            <?php endfor; ?>
          </select>
        </div>

        <div style="padding-bottom:1px">
          <button type="submit" class="btn-sm btn-primary">
            <span class="material-symbols-rounded" style="font-size:16px" aria-hidden="true">upload</span>
            Importa
          </button>
        </div>
      </form>
      <p style="font-size:.8rem;color:var(--c-text-faint);margin:.75rem 0 0">
        Colonne richieste: <code style="font-size:.8rem;background:oklch(100% 0 0 / .06);padding:1px 5px;border-radius:3px">Id</code>,
        <code style="font-size:.8rem;background:oklch(100% 0 0 / .06);padding:1px 5px;border-radius:3px">Nome</code>,
        <code style="font-size:.8rem;background:oklch(100% 0 0 / .06);padding:1px 5px;border-radius:3px">Squadra</code>,
        <code style="font-size:.8rem;background:oklch(100% 0 0 / .06);padding:1px 5px;border-radius:3px">R</code> (classic),
        <code style="font-size:.8rem;background:oklch(100% 0 0 / .06);padding:1px 5px;border-radius:3px">RM</code> (mantra, punto e virgola).
      </p>
    </div>
  </details>
</div>

<!-- ── Filtri e tabella ────────────────────────────────────────────── -->
<div class="admin-toolbar">
  <form method="get" action="" style="display:contents">
    <input type="hidden" name="tab" value="giocatori">

    <div class="admin-toolbar__search">
      <div style="position:relative">
        <span class="material-symbols-rounded"
              style="position:absolute;left:var(--sp-3);top:50%;transform:translateY(-50%);font-size:18px;color:var(--c-text-faint);pointer-events:none"
              aria-hidden="true">search</span>
        <input type="text" name="q" value="<?= htmlspecialchars($q_nome) ?>"
               placeholder="Cerca giocatore..."
               class="adm-input" style="padding-left:var(--sp-10)" aria-label="Cerca giocatore">
      </div>
    </div>

    <select name="anno" class="adm-input" style="width:auto;min-width:140px" aria-label="Stagione">
      <?php foreach ($anni_disp as $y): ?>
        <option value="<?= (int)$y ?>" <?= (int)$y === $anno_sel ? 'selected' : '' ?>>
          <?= anno_to_stagione((int)$y) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="ruolo" class="adm-input" style="width:auto;min-width:120px" aria-label="Ruolo classic">
      <option value="">Tutti i ruoli</option>
      <?php foreach ($ruoli_classic as $r): ?>
        <option value="<?= $r ?>" <?= $q_ruolo === $r ? 'selected' : '' ?>><?= $r ?></option>
      <?php endforeach; ?>
    </select>

    <div class="admin-toolbar__actions">
      <button type="submit" class="btn-sm btn-primary">
        <span class="material-symbols-rounded" style="font-size:15px" aria-hidden="true">filter_alt</span>
        Filtra
      </button>
      <?php if ($q_nome !== '' || $q_ruolo !== ''): ?>
        <a href="?tab=giocatori&anno=<?= $anno_sel ?>" class="btn-sm btn-secondary">Reset</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- Count -->
<div style="font-size:.8125rem;color:var(--c-text-muted);margin-bottom:var(--sp-4)">
  <?= $total ?> giocatori
  <?= $total > 0 ? '&middot; pagina ' . $page . ' di ' . $pages : '' ?>
</div>

<div class="panel">
  <div class="panel__body--flush">
    <table class="adm-table" aria-label="Elenco giocatori">
      <thead>
        <tr>
          <th style="width:40px">#</th>
          <th>Nome</th>
          <th>Squadra</th>
          <th>Ruolo classic</th>
          <th>Ruoli Mantra</th>
          <th style="width:60px;text-align:right" title="Volte acquistato nelle rose">Acquisti</th>
          <th style="width:70px;text-align:right" title="Crediti medi pagati">Cred. medi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($giocatori as $i => $g):
            $gs_id      = $g['gs_id'];
            $classic    = $ruoli_map[$gs_id]['classic'] ?? null;
            $mantra     = $ruoli_map[$gs_id]['mantra']  ?? [];
            $utilizzi   = (int)($g['utilizzi'] ?? 0);
            $media_cred = $g['media_crediti'] !== null ? (float)$g['media_crediti'] : null;
        ?>
          <tr>
            <td style="color:var(--c-text-faint);font-size:.8rem"><?= $g['codice_fantacalcio'] ?></td>
            <td style="font-weight:500"><?= htmlspecialchars($g['nome_completo']) ?></td>
            <td style="color:var(--c-text-muted)"><?= htmlspecialchars($g['squadra_reale']) ?></td>
            <td>
              <?php if ($classic): ?>
                <span class="rb rb-<?= htmlspecialchars($classic) ?>"><?= htmlspecialchars($classic) ?></span>
              <?php else: ?>
                <span style="color:var(--c-text-faint);font-size:.8rem">-</span>
              <?php endif; ?>
            </td>
            <td style="display:flex;flex-wrap:wrap;gap:4px;padding-top:6px;padding-bottom:6px">
              <?php foreach ($mantra as $mr): ?>
                <span class="rb <?= rb_class($mr) ?>"><?= htmlspecialchars($mr) ?></span>
              <?php endforeach; ?>
              <?php if (empty($mantra)): ?>
                <span style="color:var(--c-text-faint);font-size:.8rem">-</span>
              <?php endif; ?>
            </td>
            <td style="text-align:right;font-family:var(--font-heading);font-size:.875rem;color:<?= $utilizzi > 0 ? 'var(--c-text)' : 'var(--c-text-faint)' ?>">
              <?= $utilizzi > 0 ? $utilizzi : '-' ?>
            </td>
            <td style="text-align:right;font-family:var(--font-heading);font-size:.875rem;color:<?= $media_cred !== null ? 'var(--c-text)' : 'var(--c-text-faint)' ?>">
              <?= $media_cred !== null ? number_format($media_cred, 1) : '-' ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($giocatori)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--c-text-muted);padding:var(--sp-10)">
            Nessun giocatore trovato per la stagione <?= anno_to_stagione($anno_sel) ?>.
          </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Paginazione -->
<?php if ($pages > 1): ?>
<div style="display:flex;gap:var(--sp-2);margin-top:var(--sp-5);justify-content:center;flex-wrap:wrap">
  <?php for ($p = 1; $p <= $pages; $p++): ?>
    <a href="<?= $base_url ?>&page=<?= $p ?>"
       class="btn-sm <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>"
       <?= $p === $page ? 'aria-current="page"' : '' ?>>
      <?= $p ?>
    </a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<?php
// Distribuzione ruoli per la stagione selezionata
$dist_ruoli = $db->prepare("
    SELECT r.nome AS ruolo, COUNT(DISTINCT gs.codice_fantacalcio) AS n
      FROM giocatore_stagione gs
      JOIN ruoli_giocatori rg ON rg.id_giocatore_stagione = gs.id
      JOIN ruoli r ON r.id_ruolo = rg.id_ruolo
     WHERE gs.anno = ? AND r.tipo = 'classic'
     GROUP BY r.nome
     ORDER BY FIELD(r.nome, 'P', 'D', 'C', 'A'), r.nome
");
$dist_ruoli->execute([$anno_sel]);
$dist = $dist_ruoli->fetchAll(\PDO::FETCH_ASSOC);
?>
<?php if (!empty($dist)): ?>
<div class="stat-row" style="margin-top:var(--sp-6)">
  <?php foreach ($dist as $d): ?>
  <div class="stat-block">
    <div class="stat-block__label">Ruolo <?= htmlspecialchars($d['ruolo']) ?></div>
    <div class="stat-block__value <?= $d['ruolo'] === 'A' ? 'accent' : '' ?>"><?= (int)$d['n'] ?></div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
