<?php
/**
 * app/dashboard.php — Schermata principale area privata
 */

declare(strict_types=1);

require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_layout.php';

$fq      = $_SESSION['nome_fantasquadra'] ?? '';
$db      = getDB();
$stagione = anno_to_stagione(CURRENT_ANNO);

// ── Stat: trofei ─────────────────────────────────────────────────────
$stmt = $db->prepare("SELECT COUNT(*) FROM competizione_disputata WHERE vincitore = ?");
$stmt->execute([$fq]);
$trofei = (int)$stmt->fetchColumn();

// ── Stat: partite giocate ─────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT COUNT(*) FROM partita_avvessario
     WHERE nome_fantasquadra_casa = ? OR nome_fantasquadra_trasferta = ?
");
$stmt->execute([$fq, $fq]);
$partite = (int)$stmt->fetchColumn();

// ── Stat: vittorie ────────────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT COUNT(*) FROM partita_avvessario
     WHERE (nome_fantasquadra_casa = ?      AND gol_casa      > gol_trasferta)
        OR (nome_fantasquadra_trasferta = ? AND gol_trasferta > gol_casa)
");
$stmt->execute([$fq, $fq]);
$vittorie = (int)$stmt->fetchColumn();

// ── Competizioni stagione corrente ────────────────────────────────────
$stmt = $db->prepare("
    SELECT nome_competizione, vincitore
      FROM competizione_disputata
     WHERE anno = ?
     ORDER BY id_competizione_disputata ASC
");
$stmt->execute([CURRENT_ANNO]);
$comp_anno = $stmt->fetchAll(\PDO::FETCH_ASSOC);

// ── Ultima competizione vinta ─────────────────────────────────────────
$stmt = $db->prepare("
    SELECT nome_competizione, anno
      FROM competizione_disputata
     WHERE vincitore = ?
     ORDER BY anno DESC, id_competizione_disputata DESC
     LIMIT 1
");
$stmt->execute([$fq]);
$ultima = $stmt->fetch(\PDO::FETCH_ASSOC);

app_layout_start('Dashboard', 'dashboard');
?>

<!-- ── Page head ──────────────────────────────────────────────── -->
<div class="page-head">
  <div>
    <h1>Ciao, <?= htmlspecialchars($_SESSION['username'] ?? '') ?></h1>
    <div class="page-head__sub">
      <?= htmlspecialchars($fq) ?> &middot; Stagione <?= htmlspecialchars($stagione) ?>
    </div>
  </div>
</div>

<!-- ── Stat grid ──────────────────────────────────────────────── -->
<div class="stat-grid">

  <div class="card">
    <div class="card__body">
      <div class="stat">
        <div class="stat__label">Trofei vinti</div>
        <div class="stat__value stat__value--gold"><?= $trofei ?></div>
        <div class="stat__delta">in tutta la storia</div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card__body">
      <div class="stat">
        <div class="stat__label">Partite giocate</div>
        <div class="stat__value"><?= $partite ?></div>
        <div class="stat__delta">testa a testa</div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card__body">
      <div class="stat">
        <div class="stat__label">Vittorie</div>
        <div class="stat__value stat__value--success"><?= $vittorie ?></div>
        <?php if ($partite > 0): ?>
        <div class="stat__delta stat__delta--pos">
          <span class="material-symbols-rounded" style="font-size:14px">trending_up</span>
          <?= round($vittorie / $partite * 100) ?>% di win rate
        </div>
        <?php else: ?>
        <div class="stat__delta">nessuna ancora</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card__body">
      <div class="stat">
        <div class="stat__label">Stagione</div>
        <div class="stat__value stat__value--accent"><?= htmlspecialchars($stagione) ?></div>
        <div class="stat__delta">in corso</div>
      </div>
    </div>
  </div>

</div>

<!-- ── Griglia principale ──────────────────────────────────────── -->
<div class="grid-2" id="dash-grid">

  <!-- Colonna sinistra: competizioni -->
  <div class="card">
    <div class="card__header">
      <span class="material-symbols-rounded td-muted" aria-hidden="true">emoji_events</span>
      <div>
        <div class="card__title">Competizioni <?= htmlspecialchars($stagione) ?></div>
        <div class="card__sub">Anno <?= CURRENT_ANNO ?></div>
      </div>
    </div>

    <?php if (empty($comp_anno)): ?>
      <div class="empty">
        <span class="material-symbols-rounded" aria-hidden="true">hourglass_empty</span>
        <div>Nessuna competizione ancora registrata per questa stagione.</div>
      </div>
    <?php else: ?>
      <div class="activity-feed card__body--flush">
        <?php foreach ($comp_anno as $c):
            $is_mine = ($c['vincitore'] === $fq);
        ?>
          <div class="activity-item">
            <div class="activity-item__icon"
                 <?= $is_mine ? 'style="background:var(--c-gold-dim);color:var(--c-gold)"' : '' ?>>
              <span class="material-symbols-rounded"
                    style="font-variation-settings:'FILL' <?= $c['vincitore'] ? '1' : '0' ?>"
                    aria-hidden="true">emoji_events</span>
            </div>
            <div class="activity-item__content">
              <div class="activity-item__text">
                <strong><?= htmlspecialchars($c['nome_competizione']) ?></strong>
              </div>
              <div class="activity-item__time">
                <?php if ($c['vincitore']): ?>
                  Vincitore:
                  <span style="color:<?= $is_mine ? 'var(--c-gold)' : 'var(--c-text)' ?>;font-weight:<?= $is_mine ? '600' : '400' ?>">
                    <?= htmlspecialchars($c['vincitore']) ?>
                  </span>
                  <?php if ($is_mine): ?>
                    <span class="material-symbols-rounded"
                          style="font-size:12px;color:var(--c-gold);vertical-align:-1px;font-variation-settings:'FILL' 1"
                          aria-hidden="true">star</span>
                  <?php endif; ?>
                <?php else: ?>
                  <span style="color:var(--c-text-dim)">In corso</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Colonna destra: accesso rapido + ultima vittoria -->
  <div class="col">

    <?php if ($ultima): ?>
    <div class="card">
      <div class="card__body" style="text-align:center;padding:var(--sp-6) var(--sp-5)">
        <span class="material-symbols-rounded"
              style="font-size:40px;color:var(--c-gold);display:block;margin-bottom:var(--sp-3);font-variation-settings:'FILL' 1"
              aria-hidden="true">emoji_events</span>
        <div style="font-family:var(--font-heading);font-size:1.125rem;font-weight:800;color:var(--c-text);line-height:1.2;margin-bottom:var(--sp-1)">
          <?= htmlspecialchars($ultima['nome_competizione']) ?>
        </div>
        <div style="font-size:0.8125rem;color:var(--c-text-muted)">
          Ultima vittoria &middot; <?= (int)$ultima['anno'] ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Accesso rapido -->
    <div class="card">
      <div class="card__header">
        <span class="material-symbols-rounded td-muted" aria-hidden="true">bolt</span>
        <div class="card__title">Accesso rapido</div>
      </div>
      <div class="card__body">
        <div class="quick-grid">
          <a href="<?= BASE_PATH ?>app/hall-of-fame.php" class="quick-item">
            <span class="material-symbols-rounded" aria-hidden="true">military_tech</span>
            <div>
              <div class="quick-item__label">Hall of Fame</div>
              <div class="quick-item__desc">Trofei e classifiche</div>
            </div>
          </a>
          <a href="<?= BASE_PATH ?>app/sorteggio.php" class="quick-item">
            <span class="material-symbols-rounded" aria-hidden="true">casino</span>
            <div>
              <div class="quick-item__label">Sorteggio asta</div>
              <div class="quick-item__desc">Ordine di chiamata</div>
            </div>
          </a>
          <a href="<?= BASE_PATH ?>app/profilo.php" class="quick-item">
            <span class="material-symbols-rounded" aria-hidden="true">manage_accounts</span>
            <div>
              <div class="quick-item__label">Profilo</div>
              <div class="quick-item__desc">Account e preferenze</div>
            </div>
          </a>
          <?php if ($_SESSION['flag_admin'] ?? false): ?>
          <a href="<?= BASE_PATH ?>app/admin/index.php" class="quick-item">
            <span class="material-symbols-rounded" aria-hidden="true">admin_panel_settings</span>
            <div>
              <div class="quick-item__label">Admin</div>
              <div class="quick-item__desc">Gestione lega</div>
            </div>
          </a>
          <?php else: ?>
          <div class="quick-item" style="cursor:default;pointer-events:none;opacity:.45">
            <span class="material-symbols-rounded" aria-hidden="true">list_alt</span>
            <div>
              <div class="quick-item__label">La mia rosa</div>
              <div class="quick-item__desc">Prossimamente</div>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</div>

<style>
@media (max-width: 900px) {
  #dash-grid { grid-template-columns: 1fr !important; }
}
</style>

<?php app_layout_end(); ?>
