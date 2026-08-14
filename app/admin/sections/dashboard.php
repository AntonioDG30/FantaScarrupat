<?php
/**
 * app/admin/sections/dashboard.php — Panoramica sistema
 * Variabili disponibili: $db, $tab, $flash
 */

// Contatori
$n_users = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$n_gioc  = (int)$db->query("SELECT COUNT(DISTINCT codice_fantacalcio) FROM giocatore_stagione WHERE anno = " . CURRENT_ANNO)->fetchColumn();
$n_comp  = (int)$db->query("SELECT COUNT(*) FROM competizione_disputata")->fetchColumn();
$n_part  = (int)$db->query("SELECT COUNT(*) FROM partita_avvessario")->fetchColumn()
         + (int)$db->query("SELECT COUNT(*) FROM partita_solitaria")->fetchColumn();
$n_rose  = (int)$db->query("SELECT COUNT(*) FROM rosa")->fetchColumn();

// Ultime 8 edizioni
$ultime = $db->query("
    SELECT nome_competizione, anno, vincitore
      FROM competizione_disputata
     ORDER BY anno DESC, id_competizione_disputata DESC
     LIMIT 8
")->fetchAll(\PDO::FETCH_ASSOC);

// Partecipanti attivi
$fq_attivi = $db->query("
    SELECT nome_fantasquadra, fantallenatore, immagine_fantallenatore
      FROM fantasquadra
     WHERE flag_attuale = 1
     ORDER BY nome_fantasquadra ASC
")->fetchAll(\PDO::FETCH_ASSOC);
?>

<div style="margin-bottom:var(--sp-8)">
  <h2 style="font-family:var(--font-heading);font-size:1.35rem;font-weight:700;color:var(--c-text);margin:0 0 var(--sp-1)">
    Panoramica sistema
  </h2>
  <p style="font-size:.875rem;color:var(--c-text-muted);margin:0">
    Stagione corrente: <strong style="color:var(--c-text)"><?= anno_to_stagione(CURRENT_ANNO) ?></strong>
  </p>
</div>

<!-- Stat row -->
<div class="stat-row" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));margin-bottom:var(--sp-8)">
  <div class="stat-block">
    <div class="stat-block__label">Utenti</div>
    <div class="stat-block__value"><?= $n_users ?></div>
  </div>
  <div class="stat-block">
    <div class="stat-block__label">Giocatori <?= anno_to_stagione(CURRENT_ANNO) ?></div>
    <div class="stat-block__value accent"><?= $n_gioc ?></div>
  </div>
  <div class="stat-block">
    <div class="stat-block__label">Edizioni competizioni</div>
    <div class="stat-block__value"><?= $n_comp ?></div>
  </div>
  <div class="stat-block">
    <div class="stat-block__label">Partite registrate</div>
    <div class="stat-block__value"><?= $n_part ?></div>
  </div>
  <div class="stat-block">
    <div class="stat-block__label">Rose stagionali</div>
    <div class="stat-block__value"><?= $n_rose ?></div>
  </div>
</div>

<!-- Grid: ultime edizioni + partecipanti attivi -->
<div style="display:grid;grid-template-columns:1fr 280px;gap:var(--sp-6);align-items:start" id="dash-grid">

  <!-- Ultime edizioni -->
  <div class="panel">
    <div class="panel__header">
      <span class="panel__title">
        <span class="material-symbols-rounded" style="font-size:15px;vertical-align:-3px;margin-right:var(--sp-2)" aria-hidden="true">trophy</span>
        Ultime edizioni registrate
      </span>
      <a href="?tab=competizioni" class="btn-sm btn-secondary" style="font-size:.75rem">
        Gestisci
        <span class="material-symbols-rounded" style="font-size:14px" aria-hidden="true">arrow_forward</span>
      </a>
    </div>
    <div class="panel__body--flush">
      <table class="adm-table" aria-label="Ultime edizioni">
        <thead>
          <tr>
            <th>Anno</th>
            <th>Competizione</th>
            <th>Vincitore</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ultime as $c): ?>
          <tr>
            <td style="color:var(--c-text-muted);font-size:.875rem"><?= (int)$c['anno'] ?></td>
            <td style="font-weight:500"><?= htmlspecialchars($c['nome_competizione']) ?></td>
            <td>
              <?php if ($c['vincitore']): ?>
                <span style="color:var(--c-gold);font-weight:500">
                  <span class="material-symbols-rounded" style="font-size:13px;vertical-align:-2px;font-variation-settings:'FILL' 1" aria-hidden="true">emoji_events</span>
                  <?= htmlspecialchars($c['vincitore']) ?>
                </span>
              <?php else: ?>
                <span style="color:var(--c-text-faint);font-size:.875rem">In corso</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($ultime)): ?>
          <tr><td colspan="3" style="text-align:center;color:var(--c-text-muted);padding:var(--sp-8)">Nessun dato.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Partecipanti attivi -->
  <div class="panel">
    <div class="panel__header">
      <span class="panel__title">
        <span class="material-symbols-rounded" style="font-size:15px;vertical-align:-3px;margin-right:var(--sp-2)" aria-hidden="true">groups</span>
        Partecipanti attivi
      </span>
      <a href="?tab=partecipanti" class="btn-sm btn-secondary" style="font-size:.75rem">
        Gestisci <span class="material-symbols-rounded" style="font-size:14px" aria-hidden="true">arrow_forward</span>
      </a>
    </div>
    <div class="activity-feed panel__body--flush">
      <?php foreach ($fq_attivi as $fq): ?>
        <div class="activity-item">
          <div class="activity-item__icon"
               style="background:oklch(from var(--c-accent) l c h / .12);color:var(--c-accent);font-weight:700;font-family:var(--font-heading);font-size:.875rem">
            <?= strtoupper(substr($fq['fantallenatore'], 0, 2)) ?>
          </div>
          <div class="activity-item__content">
            <div class="activity-item__text" style="font-size:.875rem"><?= htmlspecialchars($fq['nome_fantasquadra']) ?></div>
            <div class="activity-item__time"><?= htmlspecialchars($fq['fantallenatore']) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($fq_attivi)): ?>
        <div style="padding:var(--sp-6);text-align:center;color:var(--c-text-muted);font-size:.875rem">Nessuna squadra attiva.</div>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Strumenti admin -->
<div class="panel" style="margin-top:var(--sp-6)">
  <div class="panel__header">
    <span class="panel__title">
      <span class="material-symbols-rounded" style="font-size:15px;vertical-align:-3px;margin-right:var(--sp-2)" aria-hidden="true">build</span>
      Strumenti
    </span>
  </div>
  <div class="panel__body">
    <div style="display:flex;gap:var(--sp-4);flex-wrap:wrap;align-items:center">
      <form method="post" action="<?= BASE_PATH ?>app/admin/export_db.php" style="display:inline">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <button type="submit" class="btn-sm btn-secondary">
          <span class="material-symbols-rounded" style="font-size:15px" aria-hidden="true">database</span>
          Esporta database SQL
        </button>
      </form>
      <span style="font-size:.8125rem;color:var(--c-text-muted)">
        Scarica un dump completo del database (tutte le tabelle).
      </span>
    </div>
  </div>
</div>

<style>
@media (max-width:900px) { #dash-grid { grid-template-columns:1fr !important; } }
</style>
