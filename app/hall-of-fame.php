<?php
/**
 * app/hall-of-fame.php — Classifica storica e statistiche personali
 */

declare(strict_types=1);

require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_layout.php';

$fq = $_SESSION['nome_fantasquadra'] ?? '';
$db = getDB();

// ── Classifica globale per trofei ─────────────────────────────────────
$ranking = $db->query("
    SELECT vincitore,
           COUNT(*) AS trofei
      FROM competizione_disputata
     WHERE vincitore IS NOT NULL
     GROUP BY vincitore
     ORDER BY trofei DESC, vincitore ASC
")->fetchAll(\PDO::FETCH_ASSOC);

// ── Trofei per tipo di competizione (utente corrente) ─────────────────
$stmt = $db->prepare("
    SELECT nome_competizione, COUNT(*) AS vittorie
      FROM competizione_disputata
     WHERE vincitore = ?
     GROUP BY nome_competizione
     ORDER BY vittorie DESC, nome_competizione ASC
");
$stmt->execute([$fq]);
$miei_trofei = $stmt->fetchAll(\PDO::FETCH_ASSOC);

// ── Record W/L/D ──────────────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT
        SUM(CASE
              WHEN nome_fantasquadra_casa = ?      AND gol_casa      > gol_trasferta THEN 1
              WHEN nome_fantasquadra_trasferta = ? AND gol_trasferta > gol_casa      THEN 1
              ELSE 0
            END) AS vittorie,
        SUM(CASE
              WHEN nome_fantasquadra_casa = ?      AND gol_casa      < gol_trasferta THEN 1
              WHEN nome_fantasquadra_trasferta = ? AND gol_trasferta < gol_casa      THEN 1
              ELSE 0
            END) AS sconfitte,
        SUM(CASE WHEN gol_casa = gol_trasferta THEN 1 ELSE 0 END) AS pareggi,
        COUNT(*) AS partite
      FROM partita_avvessario
     WHERE nome_fantasquadra_casa = ? OR nome_fantasquadra_trasferta = ?
");
$stmt->execute([$fq, $fq, $fq, $fq, $fq, $fq]);
$record = $stmt->fetch(\PDO::FETCH_ASSOC);

$w = (int)($record['vittorie']  ?? 0);
$l = (int)($record['sconfitte'] ?? 0);
$d = (int)($record['pareggi']   ?? 0);
$p = (int)($record['partite']   ?? 0);

// ── Top 3 per podio (bozza: 2°/1°/3°) ────────────────────────────────
$top3  = array_slice($ranking, 0, 3);
$resto = array_slice($ranking, 3);

// Riordina per podio: slot 0=2°, slot 1=1°, slot 2=3°
$podio = [null, null, null];
if (isset($top3[0])) $podio[1] = $top3[0]; // 1° al centro
if (isset($top3[1])) $podio[0] = $top3[1]; // 2° a sinistra
if (isset($top3[2])) $podio[2] = $top3[2]; // 3° a destra

app_layout_start('Hall of Fame', 'hall-of-fame');
?>

<!-- ── Page head ──────────────────────────────────────────────── -->
<div class="page-head">
  <div>
    <h1>Hall of Fame</h1>
    <div class="page-head__sub">Classifica storica per titoli vinti dalla fondazione della lega.</div>
  </div>
</div>

<!-- ── Podio top 3 ────────────────────────────────────────────── -->
<?php if (count($top3) >= 1): ?>
<section aria-label="Podio" style="margin-bottom:var(--sp-8)">
  <div class="podium">

    <?php
    // Slot 0 = 2°, Slot 1 = 1°, Slot 2 = 3°
    $slot_config = [
        0 => ['pos' => 2, 'col_class' => 'podium__col--2', 'rank_label' => '2'],
        1 => ['pos' => 1, 'col_class' => 'podium__col--1', 'rank_label' => '1'],
        2 => ['pos' => 3, 'col_class' => 'podium__col--3', 'rank_label' => '3'],
    ];
    foreach ($slot_config as $slot => $cfg):
        $item = $podio[$slot];
        if (!$item): ?>
          <div class="podium__col <?= $cfg['col_class'] ?>" style="opacity:.25">
            <div class="podium__rank td-dim"><?= $cfg['rank_label'] ?></div>
          </div>
        <?php continue; endif;
        $is_me = ($item['vincitore'] === $fq);
        $trofei_n = (int)$item['trofei'];
    ?>
      <div class="podium__col <?= $cfg['col_class'] ?><?= $is_me ? ' podium__is-me' : '' ?>"
           role="article"
           aria-label="<?= $cfg['pos'] ?>° posto: <?= htmlspecialchars($item['vincitore']) ?>">
        <div class="podium__rank"><?= $cfg['rank_label'] ?></div>
        <?php if ($cfg['pos'] === 1): ?>
          <span class="material-symbols-rounded"
                style="font-size:42px;color:var(--c-gold);font-variation-settings:'FILL' 1"
                aria-hidden="true">emoji_events</span>
        <?php else: ?>
          <span class="material-symbols-rounded"
                style="font-size:28px;color:var(--c-text-dim)"
                aria-hidden="true">military_tech</span>
        <?php endif; ?>
        <div class="podium__name"><?= htmlspecialchars($item['vincitore']) ?></div>
        <?php if ($is_me): ?>
          <span class="pill pill--accent" style="font-size:.65rem">tu</span>
        <?php endif; ?>
        <div class="podium__trofei">
          <?= $trofei_n ?> <?= $trofei_n === 1 ? 'titolo' : 'titoli' ?>
        </div>
      </div>
    <?php endforeach; ?>

  </div>
</section>
<?php endif; ?>

<!-- ── Griglia: classifica + stats ────────────────────────────── -->
<div class="grid-2" id="hof-grid">

  <!-- Classifica completa -->
  <div class="card">
    <div class="card__header">
      <span class="material-symbols-rounded td-muted" aria-hidden="true">leaderboard</span>
      <div class="card__title">Classifica completa</div>
    </div>
    <div class="card__body--flush">
      <table class="app-table" aria-label="Classifica storica per trofei">
        <thead>
          <tr>
            <th style="width:44px">#</th>
            <th>Squadra</th>
            <th class="td-right" style="padding-right:var(--sp-6)">Titoli</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ranking as $i => $row):
              $pos   = $i + 1;
              $is_me = ($row['vincitore'] === $fq);
          ?>
          <tr class="<?= $is_me ? 'is-self' : '' ?>">
            <td style="padding-left:var(--sp-5)">
              <span class="td-mono"
                    style="color:<?= $pos <= 3 ? 'var(--c-gold)' : 'var(--c-text-dim)' ?>;font-family:var(--font-heading);font-weight:700;font-size:<?= $pos <= 3 ? '1.1rem' : '.875rem' ?>">
                <?= $pos ?>
              </span>
            </td>
            <td>
              <span style="color:<?= $is_me ? 'var(--c-accent)' : 'var(--c-text)' ?>;font-weight:<?= $is_me ? '600' : '400' ?>">
                <?= htmlspecialchars($row['vincitore']) ?>
              </span>
              <?php if ($is_me): ?><span class="pill pill--accent" style="margin-left:var(--sp-2)">tu</span><?php endif; ?>
            </td>
            <td class="td-right td-num" style="padding-right:var(--sp-6)">
              <span style="font-family:var(--font-heading);font-size:1.2rem;font-weight:900;color:<?= (int)$row['trofei'] > 0 ? 'var(--c-gold)' : 'var(--c-text-dim)' ?>">
                <?= (int)$row['trofei'] ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($ranking)): ?>
          <tr>
            <td colspan="3" class="td-center td-muted" style="padding:var(--sp-8)">
              Nessun dato disponibile.
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Stats personali -->
  <div class="col">

    <!-- Record W/L/D -->
    <div class="card">
      <div class="card__header">
        <span class="material-symbols-rounded td-muted" aria-hidden="true">person</span>
        <div class="card__title">Il tuo record</div>
      </div>
      <div class="card__body">
        <div class="grid-3" style="text-align:center;gap:var(--sp-3)">
          <div>
            <div style="font-family:var(--font-heading);font-size:2rem;font-weight:900;color:var(--c-success);line-height:1"><?= $w ?></div>
            <div style="font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--c-text-muted);margin-top:var(--sp-2)">Vittorie</div>
          </div>
          <div>
            <div style="font-family:var(--font-heading);font-size:2rem;font-weight:900;color:var(--c-text-muted);line-height:1"><?= $d ?></div>
            <div style="font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--c-text-muted);margin-top:var(--sp-2)">Pareggi</div>
          </div>
          <div>
            <div style="font-family:var(--font-heading);font-size:2rem;font-weight:900;color:var(--c-danger);line-height:1"><?= $l ?></div>
            <div style="font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--c-text-muted);margin-top:var(--sp-2)">Sconfitte</div>
          </div>
        </div>
        <?php if ($p > 0):
            $wp = round($w / $p * 100);
            $dp = round($d / $p * 100);
            $lp = 100 - $wp - $dp;
        ?>
        <div style="margin-top:var(--sp-5);padding-top:var(--sp-4);border-top:1px solid var(--c-border)">
          <div style="height:6px;border-radius:var(--r-pill);background:var(--c-border);overflow:hidden;display:flex">
            <?php if ($wp > 0): ?><div style="width:<?= $wp ?>%;background:var(--c-success)"></div><?php endif; ?>
            <?php if ($dp > 0): ?><div style="width:<?= $dp ?>%;background:var(--c-text-dim)"></div><?php endif; ?>
            <?php if ($lp > 0): ?><div style="width:<?= $lp ?>%;background:var(--c-danger)"></div><?php endif; ?>
          </div>
          <div style="display:flex;justify-content:space-between;margin-top:var(--sp-2);font-size:.75rem;color:var(--c-text-dim)">
            <span><?= $wp ?>% vittorie</span>
            <span><?= $p ?> partite giocate</span>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Trofei per competizione -->
    <?php if (!empty($miei_trofei)): ?>
    <div class="card">
      <div class="card__header">
        <span class="material-symbols-rounded" style="color:var(--c-gold)" aria-hidden="true">emoji_events</span>
        <div>
          <div class="card__title">I tuoi titoli</div>
          <div class="card__sub"><?= array_sum(array_column($miei_trofei, 'vittorie')) ?> in totale</div>
        </div>
      </div>
      <div class="activity-feed card__body--flush">
        <?php foreach ($miei_trofei as $t): ?>
          <div class="activity-item">
            <div class="activity-item__icon"
                 style="background:var(--c-gold-dim);color:var(--c-gold)">
              <span class="material-symbols-rounded"
                    style="font-variation-settings:'FILL' 1"
                    aria-hidden="true">emoji_events</span>
            </div>
            <div class="activity-item__content">
              <div class="activity-item__text"><?= htmlspecialchars($t['nome_competizione']) ?></div>
              <div class="activity-item__time">
                <?= (int)$t['vittorie'] ?> <?= (int)$t['vittorie'] === 1 ? 'vittoria' : 'vittorie' ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php else: ?>
    <div class="card">
      <div class="empty" style="padding:var(--sp-10) var(--sp-6)">
        <span class="material-symbols-rounded" aria-hidden="true">emoji_events</span>
        <div>Nessun titolo ancora.<br>Il primo arriverà.</div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<style>
@media (max-width: 880px) { #hof-grid { grid-template-columns: 1fr !important; } }
</style>

<?php app_layout_end(); ?>
