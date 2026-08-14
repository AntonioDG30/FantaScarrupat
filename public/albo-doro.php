<?php
/**
 * public/albo-doro.php — Albo d'Oro
 */

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../config/database.php';

$db = getDB();

// ── Helper: slug sicuro per ID HTML ──────────────────────────────────────────
function albo_slug(string $s): string {
    return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($s)), '-');
}

// ── Tutti i vincitori con logo squadra ────────────────────────────────────────
$risultati = $db->query("
    SELECT
        cd.id_competizione_disputata,
        cd.anno,
        cd.nome_competizione,
        cd.vincitore,
        f.scudetto,
        f.fantallenatore
    FROM competizione_disputata cd
    JOIN competizione c ON cd.nome_competizione = c.nome_competizione
    LEFT JOIN fantasquadra f ON f.nome_fantasquadra = cd.vincitore
    WHERE cd.vincitore IS NOT NULL
    ORDER BY cd.anno DESC, cd.nome_competizione
")->fetchAll();

// ── Raggruppa per anno e per competizione ─────────────────────────────────────
$per_anno = [];
$per_comp = [];
foreach ($risultati as $r) {
    $per_anno[$r['anno']][]              = $r;
    $per_comp[$r['nome_competizione']][] = $r;
}

// Ordina le competizioni per numero di edizioni disputate (più edizioni = priorità)
// → completamente dinamico: una nuova competizione si posiziona da sola
uksort($per_comp, fn($a, $b) =>
    count($per_comp[$b]) <=> count($per_comp[$a]) ?: strcmp($a, $b)
);

// Stessa priorità per i gruppi per anno (ordine coerente tra viste)
$comp_priority = array_keys($per_comp); // già ordinato sopra
foreach ($per_anno as &$group) {
    usort($group, function ($x, $y) use ($comp_priority) {
        $ix = array_search($x['nome_competizione'], $comp_priority);
        $iy = array_search($y['nome_competizione'], $comp_priority);
        return ($ix === false ? 99 : $ix) <=> ($iy === false ? 99 : $iy);
    });
}
unset($group);

// ── Palmares per ogni competizione ────────────────────────────────────────────
$palm_stmt = $db->prepare("
    SELECT cd.vincitore, COUNT(*) AS vittorie,
           f.scudetto, f.fantallenatore,
           GROUP_CONCAT(
               CONCAT(cd.anno - 1, '/', SUBSTR(CAST(cd.anno AS CHAR), 3, 2))
               ORDER BY cd.anno
               SEPARATOR '  ·  '
           ) AS stagioni
    FROM competizione_disputata cd
    LEFT JOIN fantasquadra f ON f.nome_fantasquadra = cd.vincitore
    WHERE cd.vincitore IS NOT NULL
      AND cd.nome_competizione = ?
    GROUP BY cd.vincitore, f.scudetto, f.fantallenatore
    ORDER BY vittorie DESC
");

// ── Meta-dati per competizione: streak, edizioni, campioni distinti ──────────
$comp_meta = [];
foreach ($per_comp as $comp_name => $voci) {
    $latest = $voci[0] ?? null;
    $streak = 0;
    if ($latest) {
        foreach ($voci as $v) {
            if ($v['vincitore'] === $latest['vincitore']) $streak++;
            else break;
        }
    }
    $annos = array_column($voci, 'anno');
    $comp_meta[$comp_name] = [
        'latest'          => $latest,
        'streak'          => $streak,
        'editions'        => count($voci),
        'distinct_champs' => count(array_unique(array_column($voci, 'vincitore'))),
        'prima_anno'      => (int)min($annos),
        'max_wins'        => 1,
        'wins_by'         => [],
    ];
}

$palmares = [];
foreach (array_keys($per_comp) as $comp_name) {
    $palm_stmt->execute([$comp_name]);
    $palmares[$comp_name] = $palm_stmt->fetchAll();
}

// Seconda passata: arricchisce $comp_meta con dati palmares
foreach ($comp_meta as $cname => &$cm) {
    if (!empty($palmares[$cname])) {
        $cm['max_wins'] = (int)$palmares[$cname][0]['vittorie'];
        $cm['wins_by']  = array_column($palmares[$cname], 'vittorie', 'vincitore');
    }
}
unset($cm);

// ── Anni per team per competizione (per trofei con anno) ──────────────────────
$palmares_anni = []; // [comp_name][vincitore] => ['2016/17', '2017/18', …] ASC
foreach ($risultati as $r) {
    $palmares_anni[$r['nome_competizione']][$r['vincitore']][] =
        anno_to_stagione((int)$r['anno']);
}
foreach ($palmares_anni as &$_by_comp) {
    foreach ($_by_comp as &$_anni) { sort($_anni); }
}
unset($_by_comp, $_anni);

// ── Hall of Fame — ordinamento: totale trofei, poi per importanza ─────────────
// Priorità: 1 Serie A · 2 Champions League · 3 NBA · 4 Supercoppa · 99 altre
$hall = $db->query("
    SELECT cd.vincitore, COUNT(*) AS vittorie,
           SUM(cd.nome_competizione = 'Serie A')          AS w_seriea,
           SUM(cd.nome_competizione = 'Champions League') AS w_champ,
           SUM(cd.nome_competizione = 'NBA')              AS w_nba,
           SUM(cd.nome_competizione = 'Supercoppa')       AS w_super,
           f.scudetto, f.fantallenatore
    FROM competizione_disputata cd
    LEFT JOIN fantasquadra f ON f.nome_fantasquadra = cd.vincitore
    WHERE cd.vincitore IS NOT NULL
    GROUP BY cd.vincitore, f.scudetto, f.fantallenatore
    ORDER BY vittorie DESC, w_seriea DESC, w_champ DESC, w_nba DESC, w_super DESC
")->fetchAll();

// Mappa trofei per team → usata dal tooltip "sala trofei" (ordinata per importanza)
$_trophy_prio      = ['Serie A' => 1, 'Champions League' => 2, 'NBA' => 3, 'Supercoppa' => 4];
$hall_trophies_map = [];
foreach ($risultati as $_r) {
    if (!empty($_r['vincitore'])) {
        $hall_trophies_map[$_r['vincitore']][$_r['nome_competizione']] =
            ($hall_trophies_map[$_r['vincitore']][$_r['nome_competizione']] ?? 0) + 1;
    }
}
foreach ($hall_trophies_map as &$_comps) {
    uksort($_comps, fn($a, $b) => ($_trophy_prio[$a] ?? 99) <=> ($_trophy_prio[$b] ?? 99));
}
unset($_comps, $_r);

// ── Calcola record statistici da un array di partite ─────────────────────────
function calcola_albo_record(array $rows): array {
    if (empty($rows)) return [];

    $total          = 0;
    $gol_totali     = 0;
    $pt_sum         = 0.0;
    $pt_count       = 0;
    $pareggi        = 0;
    $vittorie_casa  = 0;
    $vittorie_trasf = 0;

    $max_pt         = 0.0;   $max_pt_team = ''; $max_pt_opp = ''; $max_pt_row = null;
    $min_pt         = PHP_INT_MAX; $min_pt_team = ''; $min_pt_row = null;
    $max_pt_match   = 0.0;   $max_pt_match_row = null;
    $max_gol_match  = 0;     $max_gol_match_row = null;
    $vittoria_max   = 0;     $vittoria_max_row = null;
    $vittoria_max_winner = ''; $vittoria_max_loser = '';

    $team_gf    = []; // goals for
    $team_ga    = []; // goals against
    $team_pt    = []; // punteggio sum
    $team_pt_c  = []; // punteggio count (non-zero matches)
    $team_wins  = []; // match wins
    $team_played= [];

    // Per record in una singola stagione: indicizzati per [$anno][$team]
    $s_gf = []; $s_ga = []; $s_wins = []; $s_played_s = [];

    foreach ($rows as $r) {
        $gc = (int)$r['gol_casa'];
        $gt = (int)$r['gol_trasferta'];
        $pc = (float)$r['punteggio_casa'];
        $pt = (float)$r['punteggio_trasferta'];
        $tc = $r['nome_fantasquadra_casa'];
        $tt = $r['nome_fantasquadra_trasferta'];

        // Skip rows with no punteggio data at all
        if ($pc === 0.0 && $pt === 0.0 && $gc === 0 && $gt === 0) continue;

        $total++;
        $gol_totali += $gc + $gt;

        // Esito
        if ($gc === $gt) $pareggi++;
        elseif ($gc > $gt) $vittorie_casa++;
        else $vittorie_trasf++;

        // Punteggio medio — solo se ha dati punteggio
        if ($pc > 0.0 || $pt > 0.0) {
            if ($pc > 0.0) { $pt_sum += $pc; $pt_count++; }
            if ($pt > 0.0) { $pt_sum += $pt; $pt_count++; }
        }

        // Max individual punteggio
        if ($pc > $max_pt) { $max_pt = $pc; $max_pt_row = $r; $max_pt_team = $tc; $max_pt_opp = $tt; }
        if ($pt > $max_pt) { $max_pt = $pt; $max_pt_row = $r; $max_pt_team = $tt; $max_pt_opp = $tc; }

        // Min individual punteggio (solo se > 0, esclude partite senza dati)
        if ($pc > 0.0 && $pc < $min_pt) { $min_pt = $pc; $min_pt_row = $r; $min_pt_team = $tc; }
        if ($pt > 0.0 && $pt < $min_pt) { $min_pt = $pt; $min_pt_row = $r; $min_pt_team = $tt; }

        // Max punteggio totale partita
        $sum_pt = $pc + $pt;
        if ($sum_pt > $max_pt_match) { $max_pt_match = $sum_pt; $max_pt_match_row = $r; }

        // Max gol in partita
        $sum_gol = $gc + $gt;
        if ($sum_gol > $max_gol_match) { $max_gol_match = $sum_gol; $max_gol_match_row = $r; }

        // Vittoria più larga
        $diff = abs($gc - $gt);
        if ($diff > $vittoria_max) {
            $vittoria_max         = $diff;
            $vittoria_max_row     = $r;
            $vittoria_max_winner  = $gc > $gt ? $tc : $tt;
            $vittoria_max_loser   = $gc > $gt ? $tt : $tc;
        }

        // Per-team aggregates
        foreach ([$tc, $tt] as $team) {
            if (!isset($team_gf[$team])) {
                $team_gf[$team] = $team_ga[$team] = $team_pt[$team] = 0;
                $team_pt_c[$team] = $team_wins[$team] = $team_played[$team] = 0;
            }
        }
        $team_gf[$tc] += $gc;   $team_ga[$tc] += $gt;
        $team_gf[$tt] += $gt;   $team_ga[$tt] += $gc;
        if ($pc > 0.0) { $team_pt[$tc] += $pc; $team_pt_c[$tc]++; }
        if ($pt > 0.0) { $team_pt[$tt] += $pt; $team_pt_c[$tt]++; }
        $team_played[$tc]++; $team_played[$tt]++;
        if ($gc > $gt) $team_wins[$tc]++;
        elseif ($gt > $gc) $team_wins[$tt]++;

        // Aggregati per stagione (per record single-season)
        $_ak = $r['anno'];
        foreach ([$tc, $tt] as $_ts) {
            if (!isset($s_gf[$_ak][$_ts])) {
                $s_gf[$_ak][$_ts] = $s_ga[$_ak][$_ts] = $s_wins[$_ak][$_ts] = $s_played_s[$_ak][$_ts] = 0;
            }
        }
        $s_gf[$_ak][$tc] += $gc; $s_gf[$_ak][$tt] += $gt;
        $s_ga[$_ak][$tc] += $gt; $s_ga[$_ak][$tt] += $gc;
        $s_played_s[$_ak][$tc]++; $s_played_s[$_ak][$tt]++;
        if ($gc > $gt) $s_wins[$_ak][$tc]++;
        elseif ($gt > $gc) $s_wins[$_ak][$tt]++;
    }

    if ($total === 0) return [];

    // Top scorer team
    $top_scorer_team = ''; $top_scorer_goals = 0;
    foreach ($team_gf as $team => $gf) {
        if ($gf > $top_scorer_goals) { $top_scorer_goals = $gf; $top_scorer_team = $team; }
    }

    // Best defense (min 4 matches played)
    $best_def_team = ''; $best_def_goals = PHP_INT_MAX;
    foreach ($team_ga as $team => $ga) {
        if (($team_played[$team] ?? 0) >= 4 && $ga < $best_def_goals) {
            $best_def_goals = $ga; $best_def_team = $team;
        }
    }

    // Most match wins
    $most_wins_team = ''; $most_wins_count = 0;
    foreach ($team_wins as $team => $w) {
        if ($w > $most_wins_count) { $most_wins_count = $w; $most_wins_team = $team; }
    }

    // Best avg punteggio (min 4 matches with non-zero punteggio)
    $avg_pt_best_team = ''; $avg_pt_best_val = 0.0;
    foreach ($team_pt as $team => $tpt) {
        $cnt = $team_pt_c[$team];
        if ($cnt >= 4) {
            $avg = $tpt / $cnt;
            if ($avg > $avg_pt_best_val) { $avg_pt_best_val = $avg; $avg_pt_best_team = $team; }
        }
    }

    // Record single-season: più vittorie / più gol / miglior difesa in una singola stagione
    $bsw_count = 0; $bsw_team = ''; $bsw_anno = '';
    foreach ($s_wins as $_ak => $teams) {
        foreach ($teams as $t => $w) {
            if ($w > $bsw_count) { $bsw_count = $w; $bsw_team = $t; $bsw_anno = $_ak; }
        }
    }

    $bsg_count = 0; $bsg_team = ''; $bsg_anno = '';
    foreach ($s_gf as $_ak => $teams) {
        foreach ($teams as $t => $gf) {
            if ($gf > $bsg_count) { $bsg_count = $gf; $bsg_team = $t; $bsg_anno = $_ak; }
        }
    }

    $bsd_goals = PHP_INT_MAX; $bsd_team = ''; $bsd_anno = '';
    foreach ($s_ga as $_ak => $teams) {
        foreach ($teams as $t => $ga) {
            if (($s_played_s[$_ak][$t] ?? 0) >= 4 && $ga < $bsd_goals) {
                $bsd_goals = $ga; $bsd_team = $t; $bsd_anno = $_ak;
            }
        }
    }

    return [
        'partite'            => $total,
        'gol_totali'         => $gol_totali,
        'avg_gol'            => round($gol_totali / $total, 2),
        'pareggi'            => $pareggi,
        'pct_pareggi'        => round($pareggi  / $total * 100, 1),
        'vittorie_casa'      => $vittorie_casa,
        'pct_casa'           => round($vittorie_casa  / $total * 100, 1),
        'vittorie_trasf'     => $vittorie_trasf,
        'pct_trasf'          => round($vittorie_trasf / $total * 100, 1),
        'avg_pt'             => $pt_count > 0 ? round($pt_sum / $pt_count, 2) : 0.0,
        'max_pt'             => $max_pt,
        'max_pt_team'        => $max_pt_team,
        'max_pt_opp'         => $max_pt_opp,
        'max_pt_row'         => $max_pt_row,
        'min_pt'             => $min_pt === PHP_INT_MAX ? 0.0 : $min_pt,
        'min_pt_team'        => $min_pt_team,
        'min_pt_row'         => $min_pt_row,
        'max_pt_match'       => $max_pt_match,
        'max_pt_match_row'   => $max_pt_match_row,
        'max_gol_match'      => $max_gol_match,
        'max_gol_match_row'  => $max_gol_match_row,
        'vittoria_max'       => $vittoria_max,
        'vittoria_max_row'   => $vittoria_max_row,
        'vittoria_max_winner'=> $vittoria_max_winner,
        'vittoria_max_loser' => $vittoria_max_loser,
        'top_scorer_team'    => $top_scorer_team,
        'top_scorer_goals'   => $top_scorer_goals,
        'best_def_team'      => $best_def_team,
        'best_def_goals'     => $best_def_goals === PHP_INT_MAX ? 0 : $best_def_goals,
        'most_wins_team'     => $most_wins_team,
        'most_wins_count'    => $most_wins_count,
        'avg_pt_best_team'        => $avg_pt_best_team,
        'avg_pt_best_val'         => round($avg_pt_best_val, 2),
        // Single-season records
        'best_season_wins_team'   => $bsw_team,
        'best_season_wins_count'  => $bsw_count,
        'best_season_wins_anno'   => $bsw_anno,
        'best_season_goals_team'  => $bsg_team,
        'best_season_goals_count' => $bsg_count,
        'best_season_goals_anno'  => $bsg_anno,
        'best_season_def_team'    => $bsd_team,
        'best_season_def_goals'   => $bsd_goals === PHP_INT_MAX ? 0 : $bsd_goals,
        'best_season_def_anno'    => $bsd_anno,
    ];
}

// ── Render HTML record stats ──────────────────────────────────────────────────
// $label: stringa visualizzata nell'eyebrow (es. 'Solo Serie A', 'Champions League')
function render_albo_record(array $s, string $label = ''): void {
    if (empty($s) || empty($s['partite'])) return;
    $eyebrow = 'Record storici';
    if ($label !== '') $eyebrow .= ' · ' . $label;
    ?>
<div class="albo-rec">
  <p class="albo-rec__eyebrow">
    <span class="material-symbols-rounded" style="font-size:13px;vertical-align:-2px">bar_chart</span>
    <?= htmlspecialchars($eyebrow) ?>
  </p>
  <div class="albo-rec-grid">

    <!-- Partite + gol -->
    <div class="albo-rec-card">
      <span class="albo-rec-card__label">Partite disputate</span>
      <span class="albo-rec-card__value"><?= number_format($s['partite']) ?></span>
      <span class="albo-rec-card__sub"><?= number_format($s['gol_totali']) ?> gol totali · media <?= $s['avg_gol'] ?> a partita</span>
    </div>

    <!-- Esiti: tutti e tre i valori in proporzione -->
    <div class="albo-rec-card albo-rec-card--outcomes">
      <span class="albo-rec-card__label">Esiti partite</span>
      <?php
      $wh = max(1, (int)round($s['pct_casa']));
      $wd = max(1, (int)round($s['pct_pareggi']));
      $wa = max(1, (int)round($s['pct_trasf']));
      ?>
      <div class="albo-rec-outcomes">
        <div class="albo-rec-outcome albo-rec-outcome--home">
          <span class="albo-rec-outcome__num"><?= $s['vittorie_casa'] ?></span>
          <span class="albo-rec-outcome__pct"><?= $s['pct_casa'] ?>%</span>
          <span class="albo-rec-outcome__lbl">Casa</span>
        </div>
        <div class="albo-rec-outcome albo-rec-outcome--draw">
          <span class="albo-rec-outcome__num"><?= $s['pareggi'] ?></span>
          <span class="albo-rec-outcome__pct"><?= $s['pct_pareggi'] ?>%</span>
          <span class="albo-rec-outcome__lbl">Pareggi</span>
        </div>
        <div class="albo-rec-outcome albo-rec-outcome--away">
          <span class="albo-rec-outcome__num"><?= $s['vittorie_trasf'] ?></span>
          <span class="albo-rec-outcome__pct"><?= $s['pct_trasf'] ?>%</span>
          <span class="albo-rec-outcome__lbl">Ospite</span>
        </div>
      </div>
      <div class="albo-rec-bar">
        <div class="albo-rec-bar__seg albo-rec-bar__seg--home" style="flex:<?= $wh ?>"></div>
        <div class="albo-rec-bar__seg albo-rec-bar__seg--draw" style="flex:<?= $wd ?>"></div>
        <div class="albo-rec-bar__seg albo-rec-bar__seg--away" style="flex:<?= $wa ?>"></div>
      </div>
    </div>

    <?php if ($s['avg_pt'] > 0): ?>
    <div class="albo-rec-card">
      <span class="albo-rec-card__label">Punteggio medio</span>
      <span class="albo-rec-card__value"><?= number_format($s['avg_pt'], 2) ?></span>
      <span class="albo-rec-card__sub">per squadra per partita</span>
    </div>
    <?php endif; ?>

    <!-- Vittorie totali (tutte le stagioni) -->
    <?php if (!empty($s['most_wins_team'])): ?>
    <div class="albo-rec-card">
      <span class="albo-rec-card__label">Più vittorie totali</span>
      <span class="albo-rec-card__value"><?= $s['most_wins_count'] ?></span>
      <span class="albo-rec-card__sub"><?= htmlspecialchars($s['most_wins_team']) ?></span>
    </div>
    <?php endif; ?>

    <!-- Più vittorie in una singola stagione -->
    <?php if (!empty($s['best_season_wins_team'])): ?>
    <div class="albo-rec-card">
      <span class="albo-rec-card__label">Più vittorie in una stagione</span>
      <span class="albo-rec-card__value"><?= $s['best_season_wins_count'] ?></span>
      <span class="albo-rec-card__sub">
        <?= htmlspecialchars($s['best_season_wins_team']) ?>
        · <?= htmlspecialchars(anno_to_stagione((int)$s['best_season_wins_anno'])) ?>
      </span>
    </div>
    <?php endif; ?>

    <!-- Più gol totali (tutte le stagioni) -->
    <?php if (!empty($s['top_scorer_team'])): ?>
    <div class="albo-rec-card">
      <span class="albo-rec-card__label">Squadra con più gol totali</span>
      <span class="albo-rec-card__value"><?= number_format($s['top_scorer_goals']) ?></span>
      <span class="albo-rec-card__sub"><?= htmlspecialchars($s['top_scorer_team']) ?></span>
    </div>
    <?php endif; ?>

    <!-- Più gol in una singola stagione -->
    <?php if (!empty($s['best_season_goals_team'])): ?>
    <div class="albo-rec-card">
      <span class="albo-rec-card__label">Più gol in una stagione</span>
      <span class="albo-rec-card__value"><?= number_format($s['best_season_goals_count']) ?></span>
      <span class="albo-rec-card__sub">
        <?= htmlspecialchars($s['best_season_goals_team']) ?>
        · <?= htmlspecialchars(anno_to_stagione((int)$s['best_season_goals_anno'])) ?>
      </span>
    </div>
    <?php endif; ?>

    <!-- Miglior difesa in una singola stagione (sostituisce quella all-time) -->
    <?php if (!empty($s['best_season_def_team'])): ?>
    <div class="albo-rec-card">
      <span class="albo-rec-card__label">Miglior difesa in una stagione</span>
      <span class="albo-rec-card__value"><?= number_format($s['best_season_def_goals']) ?></span>
      <span class="albo-rec-card__sub">
        gol subiti · <?= htmlspecialchars($s['best_season_def_team']) ?>
        · <?= htmlspecialchars(anno_to_stagione((int)$s['best_season_def_anno'])) ?>
      </span>
    </div>
    <?php endif; ?>

    <?php if ($s['max_pt'] > 0): ?>
    <div class="albo-rec-card albo-rec-card--accent">
      <span class="albo-rec-card__label">Miglior punteggio individuale</span>
      <span class="albo-rec-card__value"><?= number_format($s['max_pt'], 2) ?></span>
      <span class="albo-rec-card__sub">
        <?= htmlspecialchars($s['max_pt_team']) ?> vs <?= htmlspecialchars($s['max_pt_opp']) ?>
        <?php if (!empty($s['max_pt_row'])): ?>
        <br><?= htmlspecialchars(anno_to_stagione((int)$s['max_pt_row']['anno'])) ?>
        <?php if ($label !== ''): ?> · <?= htmlspecialchars($s['max_pt_row']['nome_competizione']) ?><?php endif; ?>
        · G<?= (int)($s['max_pt_row']['giornata'] ?? 0) ?>
        <?php endif; ?>
      </span>
    </div>
    <?php endif; ?>

    <?php if (!empty($s['avg_pt_best_team'])): ?>
    <div class="albo-rec-card albo-rec-card--accent">
      <span class="albo-rec-card__label">Miglior media punteggio</span>
      <span class="albo-rec-card__value"><?= number_format($s['avg_pt_best_val'], 2) ?></span>
      <span class="albo-rec-card__sub"><?= htmlspecialchars($s['avg_pt_best_team']) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($s['max_pt_match'] > 0 && !empty($s['max_pt_match_row'])): $r = $s['max_pt_match_row']; ?>
    <div class="albo-rec-card albo-rec-card--wide">
      <span class="albo-rec-card__label">Partita · punteggio totale più alto</span>
      <span class="albo-rec-card__value"><?= number_format($s['max_pt_match'], 2) ?> pt totali</span>
      <div class="albo-rec-match">
        <span class="albo-rec-match__team"><?= htmlspecialchars($r['nome_fantasquadra_casa']) ?></span>
        <span class="albo-rec-match__score"><?= number_format((float)$r['punteggio_casa'], 1) ?>&nbsp;·&nbsp;<?= number_format((float)$r['punteggio_trasferta'], 1) ?></span>
        <span class="albo-rec-match__team albo-rec-match__team--right"><?= htmlspecialchars($r['nome_fantasquadra_trasferta']) ?></span>
      </div>
      <span class="albo-rec-card__sub">
        <?= htmlspecialchars(anno_to_stagione((int)$r['anno'])) ?>
        <?php if ($label !== ''): ?> · <?= htmlspecialchars($r['nome_competizione']) ?><?php endif; ?>
        · G<?= (int)($r['giornata'] ?? 0) ?>
      </span>
    </div>
    <?php endif; ?>

    <?php if ($s['max_gol_match'] > 0 && !empty($s['max_gol_match_row'])): $r = $s['max_gol_match_row']; ?>
    <div class="albo-rec-card albo-rec-card--wide">
      <span class="albo-rec-card__label">Partita · più gol in assoluto</span>
      <span class="albo-rec-card__value"><?= $s['max_gol_match'] ?> gol</span>
      <div class="albo-rec-match">
        <span class="albo-rec-match__team"><?= htmlspecialchars($r['nome_fantasquadra_casa']) ?></span>
        <span class="albo-rec-match__score"><?= (int)$r['gol_casa'] ?>&nbsp;·&nbsp;<?= (int)$r['gol_trasferta'] ?></span>
        <span class="albo-rec-match__team albo-rec-match__team--right"><?= htmlspecialchars($r['nome_fantasquadra_trasferta']) ?></span>
      </div>
      <span class="albo-rec-card__sub">
        <?= htmlspecialchars(anno_to_stagione((int)$r['anno'])) ?>
        <?php if ($label !== ''): ?> · <?= htmlspecialchars($r['nome_competizione']) ?><?php endif; ?>
        · G<?= (int)($r['giornata'] ?? 0) ?>
      </span>
    </div>
    <?php endif; ?>

    <?php if ($s['vittoria_max'] > 0 && !empty($s['vittoria_max_row'])): $r = $s['vittoria_max_row']; ?>
    <div class="albo-rec-card albo-rec-card--wide">
      <span class="albo-rec-card__label">Vittoria più larga</span>
      <span class="albo-rec-card__value">+<?= $s['vittoria_max'] ?> gol</span>
      <div class="albo-rec-match">
        <span class="albo-rec-match__team"><?= htmlspecialchars($r['nome_fantasquadra_casa']) ?></span>
        <span class="albo-rec-match__score"><?= (int)$r['gol_casa'] ?>&nbsp;·&nbsp;<?= (int)$r['gol_trasferta'] ?></span>
        <span class="albo-rec-match__team albo-rec-match__team--right"><?= htmlspecialchars($r['nome_fantasquadra_trasferta']) ?></span>
      </div>
      <span class="albo-rec-card__sub">
        <?= htmlspecialchars($s['vittoria_max_winner']) ?> su <?= htmlspecialchars($s['vittoria_max_loser']) ?><br>
        <?= htmlspecialchars(anno_to_stagione((int)$r['anno'])) ?>
        <?php if ($label !== ''): ?> · <?= htmlspecialchars($r['nome_competizione']) ?><?php endif; ?>
      </span>
    </div>
    <?php endif; ?>

  </div>
</div>
    <?php
}

// ── Fetch tutte le partite con contesto competizione ─────────────────────────
$all_match_rows = $db->query("
    SELECT pa.nome_fantasquadra_casa, pa.nome_fantasquadra_trasferta,
           pa.gol_casa, pa.gol_trasferta,
           pa.punteggio_casa, pa.punteggio_trasferta,
           pa.giornata,
           cd.nome_competizione, cd.anno
    FROM partita_avvessario pa
    JOIN competizione_disputata cd
         ON pa.id_competizione_disputata = cd.id_competizione_disputata
    ORDER BY cd.nome_competizione, cd.anno, pa.giornata
")->fetchAll();

$matches_by_comp = [];
foreach ($all_match_rows as $_mr) {
    $matches_by_comp[$_mr['nome_competizione']][] = $_mr;
}

$stats_per_comp = [];
foreach ($matches_by_comp as $_cname => $_crows) {
    $stats_per_comp[$_cname] = calcola_albo_record($_crows);
}
// Record globali = solo Serie A (le altre competizioni avranno la loro sezione)
$stats_serie_a = $stats_per_comp['Serie A'] ?? [];
unset($all_match_rows, $matches_by_comp, $_mr, $_cname, $_crows);

layout_start("Albo d'Oro", 'albo-doro', [
    'description' => "L'albo d'oro di FantaScarrupat: tutti i vincitori dal 2016.",
    'breadcrumb' => [['name' => 'Home', 'url' => 'public/index.php'], ['name' => "Albo d'Oro"]],
]);
?>

<!-- ── Hero ──────────────────────────────────────────────────────────────────── -->
<section class="hero" aria-label="Albo d'oro">
  <div class="container">
    <p class="hero__eyebrow">Storia · 2016&ndash;oggi</p>
    <h1 class="hero__title">Albo d'Oro</h1>
    <p class="hero__subtitle" style="max-width:50ch">
      Ogni trofeo assegnato, ogni stagione completata.
    </p>
    <div class="hero__stats">
      <div>
        <div class="hero__stat-value"><?= count($per_anno) ?></div>
        <div class="hero__stat-label">Stagioni</div>
      </div>
      <div>
        <div class="hero__stat-value"><?= count($risultati) ?></div>
        <div class="hero__stat-label">Trofei assegnati</div>
      </div>
      <div>
        <div class="hero__stat-value"><?= count($per_comp) ?></div>
        <div class="hero__stat-label">Competizioni</div>
      </div>
    </div>
  </div>
</section>


<!-- ── Filter + Card grid ────────────────────────────────────────────────────── -->
<section class="section">
  <div class="container">

    <!-- Filter pills -->
    <div class="albo-filter" role="tablist" aria-label="Filtra per competizione">
      <button class="albo-filter__btn"
              data-view="all" role="tab" aria-selected="false">
        Tutte le stagioni
      </button>
      <?php $first_comp = true; foreach ($per_comp as $comp_name => $_): ?>
      <button class="albo-filter__btn <?= $first_comp ? 'active' : '' ?>"
              data-view="comp-<?= albo_slug($comp_name) ?>"
              role="tab" aria-selected="<?= $first_comp ? 'true' : 'false' ?>">
        <?= htmlspecialchars($comp_name) ?>
      </button>
      <?php $first_comp = false; endforeach; ?>
    </div>

    <!-- ── Vista "Tutte le stagioni": gruppi per anno ── -->
    <div class="albo-view" id="view-all" hidden>
      <?php foreach ($per_anno as $anno => $voci): ?>
      <div class="albo-year-block">
        <h2 class="albo-year-block__label">
          <?= htmlspecialchars(anno_to_stagione((int)$anno)) ?>
        </h2>
        <div class="albo-cards-grid">
          <?php foreach ($voci as $v):
            $logo_url = img_url(TEAMS_URL, $v['scudetto'] ?? '');
            $initials = strtoupper(mb_substr($v['vincitore'], 0, 2));
          ?>
          <a href="<?= BASE_PATH ?>public/dettagli-competizione.php?id=<?= $v['id_competizione_disputata'] ?>"
             class="albo-card"
             data-anno="<?= (int)$v['anno'] ?>"
             data-team="<?= htmlspecialchars($v['vincitore']) ?>">
            <div class="albo-card__header"><?= htmlspecialchars($v['nome_competizione']) ?></div>
            <div class="albo-card__img-wrap">
              <div class="albo-card__img-ph" aria-hidden="true"><?= htmlspecialchars($initials) ?></div>
              <?php if ($logo_url !== ''): ?>
              <img src="<?= $logo_url ?>"
                   alt="Stemma <?= htmlspecialchars($v['vincitore']) ?>"
                   class="albo-card__img"
                   loading="lazy"
                   onerror="this.style.display='none'">
              <?php endif; ?>
            </div>
            <div class="albo-card__body">
              <div class="albo-card__name"><?= htmlspecialchars($v['vincitore']) ?></div>
              <?php if (!empty($v['fantallenatore'])): ?>
              <div class="albo-card__coach"><?= htmlspecialchars($v['fantallenatore']) ?></div>
              <?php endif; ?>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php render_albo_record($stats_serie_a, 'Solo Serie A'); ?>
    </div><!-- /view-all -->


    <!-- ── Vista per singola competizione ── -->
    <?php $first_comp = true; foreach ($per_comp as $comp_name => $voci):
      $slug  = albo_slug($comp_name);
      $meta  = $comp_meta[$comp_name];
      $champ = $meta['latest'];
    ?>
    <div class="albo-view" id="view-comp-<?= $slug ?>" <?= $first_comp ? '' : 'hidden' ?>>

      <!-- ── Campione in carica ──────────────────────────────────── -->
      <?php if ($champ):
        $champ_logo     = img_url(TEAMS_URL, $champ['scudetto'] ?? '');
        $champ_initials = strtoupper(mb_substr($champ['vincitore'], 0, 2));
        $streak         = $meta['streak'];
      ?>
      <div class="albo-champion">
        <p class="albo-champion__eyebrow">
          <span class="material-symbols-rounded"
                style="font-size:14px;font-variation-settings:'FILL' 1">emoji_events</span>
          Campione in carica
          <?php if ($streak >= 2): ?>
          <span class="albo-champion__streak-badge"><?= $streak ?>× consecutivo</span>
          <?php endif; ?>
        </p>
        <a href="<?= BASE_PATH ?>public/dettagli-competizione.php?id=<?= $champ['id_competizione_disputata'] ?>"
           class="albo-champion__inner"
           data-anno="<?= (int)$champ['anno'] ?>">
          <div class="albo-champion__logo-wrap">
            <div class="albo-champion__logo-ph" aria-hidden="true"><?= htmlspecialchars($champ_initials) ?></div>
            <?php if ($champ_logo !== ''): ?>
            <img src="<?= $champ_logo ?>"
                 alt="Stemma <?= htmlspecialchars($champ['vincitore']) ?>"
                 class="albo-champion__logo"
                 loading="eager"
                 onerror="this.style.display='none'">
            <?php endif; ?>
          </div>
          <div class="albo-champion__info">
            <div class="albo-champion__name"><?= htmlspecialchars($champ['vincitore']) ?></div>
            <?php if (!empty($champ['fantallenatore'])): ?>
            <div class="albo-champion__coach"><?= htmlspecialchars($champ['fantallenatore']) ?></div>
            <?php endif; ?>
            <div class="albo-champion__season">
              Stagione <?= htmlspecialchars(anno_to_stagione((int)$champ['anno'])) ?>
            </div>
          </div>
          <span class="albo-champion__cta">
            Vai ai dettagli
            <span class="material-symbols-rounded" style="font-size:16px">arrow_forward</span>
          </span>
        </a>
      </div>
      <?php endif; ?>

      <!-- ── Cards: tutte le stagioni di questa competizione ──── -->
      <div class="albo-cards-grid">
        <?php
        $win_seq = [];
        foreach ($voci as $v):
          $logo_url = img_url(TEAMS_URL, $v['scudetto'] ?? '');
          $initials = strtoupper(mb_substr($v['vincitore'], 0, 2));
          $is_prima = ((int)$v['anno'] === $meta['prima_anno']);
          $total_w  = (int)($meta['wins_by'][$v['vincitore']] ?? 1);
          // voci è DESC per anno: il primo che incontriamo è la vittoria più recente
          // → parte dal totale e scala: 3ª, 2ª, 1ª; mostriamo badge solo da ×2 in su
          if (!isset($win_seq[$v['vincitore']])) { $win_seq[$v['vincitore']] = $total_w; }
          $seq_num = $win_seq[$v['vincitore']]--;
        ?>
        <a href="<?= BASE_PATH ?>public/dettagli-competizione.php?id=<?= $v['id_competizione_disputata'] ?>"
           class="albo-card"
           data-anno="<?= (int)$v['anno'] ?>"
           data-team="<?= htmlspecialchars($v['vincitore']) ?>">
          <div class="albo-card__header">
            <?= htmlspecialchars(anno_to_stagione((int)$v['anno'])) ?>
            <?php if ($is_prima): ?>
            <span class="albo-card__prima">1ª ed.</span>
            <?php endif; ?>
          </div>
          <div class="albo-card__img-wrap">
            <div class="albo-card__img-ph" aria-hidden="true"><?= htmlspecialchars($initials) ?></div>
            <?php if ($logo_url !== ''): ?>
            <img src="<?= $logo_url ?>"
                 alt="Stemma <?= htmlspecialchars($v['vincitore']) ?>"
                 class="albo-card__img"
                 loading="lazy"
                 onerror="this.style.display='none'">
            <?php endif; ?>
            <?php if ($seq_num >= 2): ?>
            <div class="albo-card__multi-badge" title="<?= $seq_num ?>ª vittoria in questa competizione">×<?= $seq_num ?></div>
            <?php endif; ?>
          </div>
          <div class="albo-card__body">
            <div class="albo-card__name"><?= htmlspecialchars($v['vincitore']) ?></div>
            <?php if (!empty($v['fantallenatore'])): ?>
            <div class="albo-card__coach"><?= htmlspecialchars($v['fantallenatore']) ?></div>
            <?php endif; ?>
          </div>
        </a>
        <?php endforeach; ?>
      </div>

      <!-- Palmares per questa competizione -->
      <?php if (!empty($palmares[$comp_name])): ?>
      <div class="albo-palmares">
        <div class="albo-palmares__header">
          <h2 class="albo-palmares__title">
            Palmares: <?= htmlspecialchars($comp_name) ?>
          </h2>
          <div class="albo-comp-stats">
            <span><?= $meta['editions'] ?> <?= $meta['editions'] === 1 ? 'edizione' : 'edizioni' ?></span>
            <span class="albo-comp-stats__sep">·</span>
            <span><?= $meta['distinct_champs'] ?> <?= $meta['distinct_champs'] === 1 ? 'campione' : 'campioni' ?> diversi</span>
          </div>
        </div>
        <div class="albo-palm2-list" role="list">
          <?php foreach ($palmares[$comp_name] as $i => $p):
            $plogo      = img_url(TEAMS_URL, $p['scudetto'] ?? '');
            $pinitials  = strtoupper(mb_substr($p['vincitore'], 0, 2));
            $is_first   = $i === 0;
            $anni_vinti = $palmares_anni[$comp_name][$p['vincitore']] ?? [];
          ?>
          <div class="albo-palm2-row <?= $is_first ? 'albo-palm2-row--gold' : '' ?>"
               role="listitem"
               data-team="<?= htmlspecialchars($p['vincitore']) ?>">

            <!-- Identità squadra -->
            <div class="albo-palm2-team">
              <?php if ($plogo !== ''): ?>
              <img src="<?= $plogo ?>"
                   alt="Stemma <?= htmlspecialchars($p['vincitore']) ?>"
                   class="albo-palm2-logo" loading="lazy"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
              <div class="albo-palm2-logo-ph" style="display:none"><?= htmlspecialchars($pinitials) ?></div>
              <?php else: ?>
              <div class="albo-palm2-logo-ph"><?= htmlspecialchars($pinitials) ?></div>
              <?php endif; ?>
              <div class="albo-palm2-team__info">
                <span class="albo-palm2-name"><?= htmlspecialchars($p['vincitore']) ?></span>
                <?php if (!empty($p['fantallenatore'])): ?>
                <span class="albo-palm2-coach"><?= htmlspecialchars($p['fantallenatore']) ?></span>
                <?php endif; ?>
              </div>
            </div>

            <!-- Una coppa per ogni vittoria, con anno sotto -->
            <div class="albo-palm2-trophies">
              <?php foreach ($anni_vinti as $stagione): ?>
              <div class="albo-palm2-trophy">
                <span class="material-symbols-rounded albo-palm2-trophy__icon">emoji_events</span>
                <span class="albo-palm2-trophy__year"><?= htmlspecialchars($stagione) ?></span>
              </div>
              <?php endforeach; ?>
            </div>

          </div><!-- /albo-palm2-row -->
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php render_albo_record($stats_per_comp[$comp_name] ?? [], $comp_name); ?>

    </div><!-- /view-comp-... -->
    <?php $first_comp = false; endforeach; ?>


  </div>
</section>


<!-- ── Hall of Fame ───────────────────────────────────────────────────────────── -->
<?php if (!empty($hall)): ?>
<section class="section section-alt" aria-labelledby="hall-title">
  <div class="container">

    <header class="pub-section-header pub-section-header--split">
      <div>
        <p class="pub-eyebrow">Statistiche globali</p>
        <h2 id="hall-title">Chi ha vinto di più</h2>
      </div>
    </header>

    <div class="albo-hall">
      <?php foreach ($hall as $i => $t):
        $hlogo    = img_url(TEAMS_URL, $t['scudetto'] ?? '');
        $hinitials = strtoupper(mb_substr($t['vincitore'], 0, 2));
        $is_top   = $i < 3;
      ?>
      <div class="albo-hall-entry <?= $i === 0 ? 'albo-hall-entry--gold' : ($i === 1 ? 'albo-hall-entry--silver' : ($i === 2 ? 'albo-hall-entry--bronze' : '')) ?>"
           data-trophies='<?= htmlspecialchars(json_encode($hall_trophies_map[$t['vincitore']] ?? []), ENT_QUOTES, 'UTF-8') ?>'>

        <div class="albo-hall-entry__rank"><?= $i + 1 ?></div>

        <?php if ($hlogo !== ''): ?>
        <img src="<?= $hlogo ?>"
             alt="Stemma <?= htmlspecialchars($t['vincitore']) ?>"
             class="albo-hall-entry__logo"
             loading="lazy"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
        <div class="albo-hall-entry__logo-ph" style="display:none">
          <?= htmlspecialchars($hinitials) ?>
        </div>
        <?php else: ?>
        <div class="albo-hall-entry__logo-ph">
          <?= htmlspecialchars($hinitials) ?>
        </div>
        <?php endif; ?>

        <div class="albo-hall-entry__body">
          <div class="albo-hall-entry__name">
            <?= htmlspecialchars($t['vincitore']) ?>
          </div>
          <div class="albo-hall-entry__wins">
            <span><?= $t['vittorie'] ?></span>
            <?= (int)$t['vittorie'] === 1 ? 'trofeo' : 'trofei' ?>
          </div>
        </div>

      </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>
<?php endif; ?>


<!-- ── Sala trofei tooltip (generato da JS al hover) ─────────────── -->
<div id="albo-trophy-room" class="albo-trophy-room" aria-hidden="true" role="tooltip"></div>

<!-- ── Toast: dati non disponibili ──────────────────────────────── -->
<div class="albo-toast" id="albo-toast" role="status" aria-live="polite" aria-hidden="true">
  <span class="material-symbols-rounded albo-toast__icon"
        style="font-variation-settings:'FILL' 1">info</span>
  <div class="albo-toast__text">
    <strong>Dati non disponibili</strong>
    I dettagli per le competizioni precedenti alla stagione 2023/24
    non sono ancora stati inseriti nel sistema.
  </div>
  <button class="albo-toast__close" aria-label="Chiudi notifica">
    <span class="material-symbols-rounded">close</span>
  </button>
</div>

<script>
(function () {
  'use strict';

  /* ── Filter pills ───────────────────────────────────────── */
  var btns  = document.querySelectorAll('.albo-filter__btn');
  var views = document.querySelectorAll('.albo-view');

  btns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      btns.forEach(function (b) {
        b.classList.remove('active');
        b.setAttribute('aria-selected', 'false');
      });
      btn.classList.add('active');
      btn.setAttribute('aria-selected', 'true');

      var target = 'view-' + btn.dataset.view;
      views.forEach(function (v) {
        v.hidden = (v.id !== target);
      });
    });
  });

  /* ── Team focus: click palmares row → highlight cards ────── */
  var focusedTeam = null;

  function applyTeamFocus(team) {
    focusedTeam = (focusedTeam === team) ? null : team;

    document.querySelectorAll('.albo-view').forEach(function (view) {
      view.classList.toggle('albo-has-focus', !!focusedTeam);
      view.querySelectorAll('.albo-card[data-team]').forEach(function (card) {
        card.classList.toggle('albo-card--team-match',
          !!focusedTeam && card.dataset.team === focusedTeam);
      });
    });

    document.querySelectorAll('.albo-palm2-row[data-team]').forEach(function (row) {
      row.classList.toggle('albo-palm2-row--team-active',
        !!focusedTeam && row.dataset.team === focusedTeam);
    });
  }

  document.querySelectorAll('.albo-palm2-row[data-team]').forEach(function (row) {
    var nameEl = row.querySelector('.albo-palm2-name');
    if (nameEl) row.title = 'Evidenzia ' + nameEl.textContent.trim();
    row.addEventListener('click', function () {
      applyTeamFocus(row.dataset.team);
    });
  });

  /* ── Toast: intercetta click su stagioni senza dati ─────── */
  var toast      = document.getElementById('albo-toast');
  var toastTimer = null;

  function showToast() {
    clearTimeout(toastTimer);
    toast.classList.add('albo-toast--visible');
    toast.setAttribute('aria-hidden', 'false');
    toastTimer = setTimeout(hideToast, 5000);
  }

  function hideToast() {
    toast.classList.remove('albo-toast--visible');
    toast.setAttribute('aria-hidden', 'true');
  }

  document.querySelectorAll(
    '.albo-card[data-anno], .albo-champion__inner[data-anno]'
  ).forEach(function (link) {
    link.addEventListener('click', function (e) {
      if (parseInt(link.dataset.anno, 10) < 2024) {
        e.preventDefault();
        showToast();
      }
    });
  });

  toast.querySelector('.albo-toast__close').addEventListener('click', hideToast);

  /* ── Sala trofei: tooltip al hover sul nome in Hall of Fame ─── */
  var trophyRoom   = document.getElementById('albo-trophy-room');
  var trophyTimer  = null;
  var TROPHY_COLORS = {
    'Serie A':          'oklch(66% 0.195 38)',
    'Champions League': 'oklch(62% 0.18 260)',
    'NBA':              'oklch(60% 0.20 50)',
    'Supercoppa':       'oklch(62% 0.16 150)'
  };

  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;')
                    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function buildTrophyHTML(data) {
    if (!data || Object.keys(data).length === 0) return '';
    var html = '<div class="albo-trophy-room__inner">';
    Object.keys(data).forEach(function (comp) {
      var n     = data[comp];
      var color = TROPHY_COLORS[comp] || 'oklch(60% 0.01 260)';
      html += '<div class="albo-trophy-room__comp">';
      html += '<span class="albo-trophy-room__comp-name">' + escHtml(comp) + '</span>';
      html += '<div class="albo-trophy-room__icons">';
      for (var i = 0; i < n; i++) {
        html += '<span class="material-symbols-rounded albo-trophy-room__icon" '
              + 'style="color:' + color + ';font-variation-settings:\'FILL\' 1">emoji_events</span>';
      }
      html += '</div>';
      html += '</div>';
    });
    html += '</div>';
    return html;
  }

  document.querySelectorAll('.albo-hall-entry[data-trophies]').forEach(function (entry) {
    var nameEl = entry.querySelector('.albo-hall-entry__name');
    if (!nameEl) return;

    nameEl.classList.add('albo-hall-entry__name--hoverable');

    nameEl.addEventListener('mouseenter', function () {
      clearTimeout(trophyTimer);
      var data = {};
      try { data = JSON.parse(entry.dataset.trophies || '{}'); } catch (e) {}
      var html = buildTrophyHTML(data);
      if (!html) return;
      trophyRoom.innerHTML = html;
      trophyRoom.classList.add('albo-trophy-room--visible');
      trophyRoom.setAttribute('aria-hidden', 'false');

      // Posiziona sopra l'elemento
      var rect = nameEl.getBoundingClientRect();
      trophyRoom.style.position = 'fixed';
      trophyRoom.style.left     = Math.max(8, rect.left) + 'px';
      trophyRoom.style.top      = rect.top + 'px';
      trophyRoom.style.transform = 'translateY(calc(-100% - 10px))';
    });

    nameEl.addEventListener('mouseleave', function () {
      trophyTimer = setTimeout(function () {
        trophyRoom.classList.remove('albo-trophy-room--visible');
        trophyRoom.setAttribute('aria-hidden', 'true');
      }, 120);
    });
  });

  trophyRoom.addEventListener('mouseenter', function () { clearTimeout(trophyTimer); });
  trophyRoom.addEventListener('mouseleave', function () {
    trophyRoom.classList.remove('albo-trophy-room--visible');
    trophyRoom.setAttribute('aria-hidden', 'true');
  });

}());
</script>

<?php layout_end(); ?>
