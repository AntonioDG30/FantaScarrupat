<?php
/**
 * app/sorteggio.php — Ruota della Fortuna (solo admin)
 *
 * Estrae un ordine casuale fra i partecipanti con una ruota che gira.
 * Carica i fantallenatori della stagione corrente; si possono aggiungere
 * nomi manualmente. Tutto lato client, nessuna persistenza.
 */

declare(strict_types=1);

require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../auth/require_admin.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_layout.php';

$db = getDB();

// Fantallenatori della stagione corrente (partecipanti di default della ruota)
$partecipanti = $db->query("
    SELECT fantallenatore, nome_fantasquadra
      FROM fantasquadra
     WHERE flag_attuale = 1
     ORDER BY fantallenatore ASC
")->fetchAll(\PDO::FETCH_ASSOC);

$rf_list = array_map(static fn(array $r): array => [
    'name' => $r['fantallenatore'],
    'team' => $r['nome_fantasquadra'],
], $partecipanti);

app_layout_start('Ruota della Fortuna', 'admin-sorteggio');
?>

<div class="page-head">
  <div>
    <h1>Ruota della Fortuna</h1>
    <div class="page-head__sub">Estrai l'ordine di chiamata per l'asta facendo girare la ruota</div>
  </div>
</div>

<div class="rf-layout">

  <!-- ── Setup ────────────────────────────────────────────────── -->
  <div class="card rf-setup">
    <div class="card__header">
      <span class="material-symbols-rounded td-muted" aria-hidden="true">group</span>
      <div class="card__title">Partecipanti</div>
    </div>
    <div class="card__body">

      <div class="rf-controls">
        <span class="adm-label" style="margin:0">Fantallenatori attuali</span>
        <div class="rf-controls__btns">
          <button type="button" id="rf-select-all" class="btn-sm btn-secondary">
            <span class="material-symbols-rounded" style="font-size:15px" aria-hidden="true">select_all</span>
            Tutti
          </button>
          <button type="button" id="rf-deselect-all" class="btn-sm btn-secondary" disabled>
            <span class="material-symbols-rounded" style="font-size:15px" aria-hidden="true">remove_selection</span>
            Nessuno
          </button>
        </div>
      </div>

      <div id="rf-grid" class="rf-grid" role="list" aria-label="Fantallenatori disponibili"></div>

      <div class="rf-manual">
        <span class="adm-label">Aggiungi manualmente</span>
        <div class="rf-manual__row">
          <input type="text" id="rf-manual" class="adm-input" maxlength="40"
                 placeholder="Nome partecipante…" aria-label="Nome partecipante da aggiungere">
          <button type="button" id="rf-add" class="btn-sm btn-secondary">
            <span class="material-symbols-rounded" style="font-size:16px" aria-hidden="true">add</span>
            Aggiungi
          </button>
        </div>
      </div>

      <div class="rf-selected-head">
        <span class="adm-label" style="margin:0">Selezionati (<span id="rf-count">0</span>)</span>
      </div>
      <div id="rf-selected" class="rf-chips"></div>

      <div class="rf-actions">
        <button type="button" id="rf-start" class="btn-sm btn-primary" disabled>
          <span class="material-symbols-rounded" style="font-size:16px" aria-hidden="true">play_arrow</span>
          Genera ordine
        </button>
        <button type="button" id="rf-clear" class="btn-sm btn-secondary" disabled>
          <span class="material-symbols-rounded" style="font-size:16px" aria-hidden="true">clear_all</span>
          Svuota
        </button>
      </div>
    </div>
  </div>

  <!-- ── Colonna destra: ordine estratto (sopra) + ruota (sotto) ── -->
  <div class="rf-right">

    <!-- Ordine estratto: compare dopo la prima estrazione, sopra la ruota -->
    <div class="card rf-resultscard" id="rf-results-section" hidden>
      <div class="card__header">
        <span class="material-symbols-rounded td-muted" aria-hidden="true">format_list_numbered</span>
        <div class="card__title">Ordine estratto</div>
        <div class="card__action">
          <button type="button" id="rf-new" class="btn-sm btn-secondary">
            <span class="material-symbols-rounded" style="font-size:15px" aria-hidden="true">refresh</span>
            Nuovo sorteggio
          </button>
        </div>
      </div>
      <div class="card__body">
        <div id="rf-results" class="rf-results"></div>
      </div>
    </div>

    <!-- Ruota -->
    <div class="card rf-wheelcard">
      <div class="card__header">
        <span class="material-symbols-rounded td-muted" aria-hidden="true">casino</span>
        <div class="card__title">La ruota</div>
      </div>
      <div class="card__body">
        <div class="rf-wheelbox">
          <div class="rf-wheel__pointer" aria-hidden="true"></div>
          <div id="rf-wheel" class="rf-wheel">
            <div class="rf-wheel__placeholder"><span class="material-symbols-rounded" aria-hidden="true">casino</span></div>
          </div>
        </div>

        <button type="button" id="rf-spin" class="btn-sm btn-primary rf-spinbtn" disabled>
          <span class="material-symbols-rounded" style="font-size:18px" aria-hidden="true">rotate_right</span>
          Gira la ruota
        </button>

        <div id="rf-status" class="rf-status" role="status" aria-live="polite"></div>
      </div>
    </div>

  </div>
</div>

<div id="rf-toasts" class="rf-toasts" aria-live="polite"></div>

<script>
  window.RF_PARTICIPANTS = <?= json_encode($rf_list, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= BASE_PATH ?>assets/js/ruota-fortuna.js?v=<?= ASSET_VER ?>"></script>

<?php app_layout_end(); ?>
