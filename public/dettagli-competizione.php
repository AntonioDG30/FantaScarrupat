<?php
/**
 * public/dettagli-competizione.php — Dettagli di una competizione disputata
 * URL: ?id=<id_competizione_disputata>
 */

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/admin/lib/tiebreak.php'; // logica unica supplementari/rigori (sola lettura qui)

$db = getDB();

// ── Validazione input ─────────────────────────────────────────────────────────
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    http_response_code(404);
    layout_start('Competizione non trovata', '');
    echo '<section class="section"><div class="container"><div class="alert alert-danger">'
       . '<span class="material-symbols-rounded">error</span>'
       . 'Competizione non trovata. <a href="' . BASE_PATH . 'public/albo-doro.php">Torna all\'albo d\'oro.</a>'
       . '</div></div></section>';
    layout_end();
    exit;
}

// ── Dati competizione ─────────────────────────────────────────────────────────
$comp = $db->prepare("
    SELECT cd.id_competizione_disputata, cd.anno, cd.nome_competizione, cd.vincitore,
           c.tipologia
    FROM competizione_disputata cd
    JOIN competizione c ON cd.nome_competizione = c.nome_competizione
    WHERE cd.id_competizione_disputata = ?
");
$comp->execute([$id]);
$competizione = $comp->fetch();

if (!$competizione) {
    http_response_code(404);
    layout_start('Competizione non trovata', '');
    echo '<section class="section"><div class="container"><div class="alert alert-danger">'
       . '<span class="material-symbols-rounded">error</span>'
       . 'Competizione non trovata. <a href="' . BASE_PATH . 'public/albo-doro.php">Torna all\'albo d\'oro.</a>'
       . '</div></div></section>';
    layout_end();
    exit;
}

$stagione           = anno_to_stagione((int)$competizione['anno']);
$dati_non_disponibili = ((int)$competizione['anno'] < 2024);
$title    = htmlspecialchars($competizione['nome_competizione']) . ' ' . $stagione;
$_comp_anno = (int)$competizione['anno'];

/**
 * Restituisce il nome della squadra come link alla rosa di quell'anno.
 * @param string $nome   nome_fantasquadra
 * @param string $class  classe CSS aggiuntiva (opzionale)
 */
function tlink(string $nome, string $class = ''): string {
    global $_comp_anno;
    $url = BASE_PATH . 'public/dettagli-rosa.php?'
         . http_build_query(['squadra' => $nome, 'anno' => $_comp_anno]);
    $c = 'det-team-link' . ($class !== '' ? ' ' . $class : '');
    return '<a href="' . htmlspecialchars($url) . '" class="' . $c . '">'
         . htmlspecialchars($nome) . '</a>';
}

// ── Partite avversario ────────────────────────────────────────────────────────
$partite = $db->prepare("
    SELECT id_partita, nome_fantasquadra_casa, nome_fantasquadra_trasferta,
           gol_casa, gol_trasferta, punteggio_casa, punteggio_trasferta,
           giornata, tipologia, girone,
           supplementari, rigori, gol_rigori_casa, gol_rigori_trasferta
    FROM partita_avvessario
    WHERE id_competizione_disputata = ?
    ORDER BY giornata ASC, id_partita ASC
");
$partite->execute([$id]);
$matches = $partite->fetchAll();

// Formazioni disponibili per questa edizione → pulsante "vedi formazioni" sui match
$form_keys = [];
$fk = $db->prepare("SELECT giornata, tipologia, nome_fantasquadra FROM formazione WHERE id_competizione_disputata = ?");
$fk->execute([$id]);
foreach ($fk->fetchAll() as $fkr) {
    $form_keys[$fkr['giornata'] . '|' . $fkr['tipologia'] . '|' . $fkr['nome_fantasquadra']] = true;
}
function ha_formazioni(array $p, array $form_keys): bool {
    $k = ($p['giornata'] ?? '') . '|' . ($p['tipologia'] ?? '') . '|';
    return isset($form_keys[$k . ($p['nome_fantasquadra_casa'] ?? '')])
        && isset($form_keys[$k . ($p['nome_fantasquadra_trasferta'] ?? '')]);
}

/**
 * Riga tie-break per una card partita (.bracket-match): mostra l'esito dopo i
 * supplementari (ed eventuali rigori) mappato sul casa/trasferta della partita.
 * Vuota se la partita non è la gara decisiva di una sfida andata ai supplementari.
 */
function tiebreak_line(array $p, array $leg_tiebreak): string {
    $pid = (int)($p['id_partita'] ?? 0);
    if (!isset($leg_tiebreak[$pid])) return '';
    $e   = $leg_tiebreak[$pid];
    $aIsCasa = ($p['nome_fantasquadra_casa'] === $e['teamA']);
    $isRig = ($e['metodo'] === 'rigori' && $e['rigA'] !== null);
    if ($isRig) {
        $numA = (int)$e['rigA']; $numB = (int)$e['rigB'];
        $badge = 'dcr'; $mod = ' bracket-match__tb--rig';
        $title = 'Risultato ai rigori';
    } else {
        $numA = (int)$e['aggA'] + (int)$e['suppA'];
        $numB = (int)$e['aggB'] + (int)$e['suppB'];
        $badge = 'dts'; $mod = '';
        $title = 'Risultato dopo i supplementari';
    }
    // casa = riga in alto, trasferta = riga in basso (allineate alle due squadre)
    $numC = $aIsCasa ? $numA : $numB;
    $numT = $aIsCasa ? $numB : $numA;
    return '<div class="bracket-match__tb' . $mod . '" title="' . $title . ': ' . $numC . '-' . $numT . '">'
         . '<span class="bracket-match__tb-score">' . $numC . '</span>'
         . '<span class="bracket-match__tb-badge">' . $badge . '</span>'
         . '<span class="bracket-match__tb-score">' . $numT . '</span>'
         . '</div>';
}

// ── Partita solitaria (classifiche tipo F1/NBA) ───────────────────────────────
$solitarie_stmt = $db->prepare("
    SELECT nome_fantasquadra, punteggio, giornata
    FROM partita_solitaria
    WHERE id_competizione_disputata = ?
    ORDER BY giornata ASC, punteggio DESC
");
$solitarie_stmt->execute([$id]);
$solitarie = $solitarie_stmt->fetchAll();

// Raggruppa partite per tipologia/fase poi per giornata
$fasi = [];
foreach ($matches as $m) {
    $fase    = $m['tipologia'] ?? 'Partite';
    $giornata = $m['giornata'];
    $fasi[$fase][$giornata][] = $m;
}

// Ordine fasi: prima i gironi, poi le fasi eliminatorie in ordine logico
$fase_order = ['Calendario' => 0, 'Fase a Gironi' => 1, 'Ottavi' => 2, 'Quarti' => 3, 'Semifinali' => 4, 'Finale' => 5];
uksort($fasi, fn($a, $b) => ($fase_order[$a] ?? 99) - ($fase_order[$b] ?? 99));

// Range giornate per lo slider (solo 'A Calendario')
$_cal_g       = !empty($fasi['Calendario']) ? array_keys($fasi['Calendario']) : [];
$min_giornata = $_cal_g ? (int)min($_cal_g) : 1;
$max_giornata = $_cal_g ? (int)max($_cal_g) : 38;
unset($_cal_g);

// Raggruppa partite gironi per girone
$gironi = [];
if (isset($fasi['Fase a Gironi'])) {
    foreach ($fasi['Fase a Gironi'] as $giornata => $partite_g) {
        foreach ($partite_g as $p) {
            $g = $p['girone'] ?? 'A';
            $gironi[$g][$giornata][] = $p;
        }
    }
    ksort($gironi);
}

// Range giornate per slider gironi
$_gir_all = [];
foreach ($gironi as $_giornate) {
    foreach (array_keys($_giornate) as $_g) {
        $_gir_all[] = (int)$_g;
    }
}
$min_giornata_gir = $_gir_all ? min($_gir_all) : 1;
$max_giornata_gir = $_gir_all ? max($_gir_all) : 10;
unset($_gir_all);

// ── Classifica gironi ─────────────────────────────────────────────────────────
function calcola_classifica(array $partite_girone): array
{
    $cls = [];
    foreach ($partite_girone as $giornata => $ps) {
        foreach ($ps as $p) {
            foreach (['casa', 'trasferta'] as $lato) {
                $nome = $p['nome_fantasquadra_' . $lato];
                if (!isset($cls[$nome])) {
                    $cls[$nome] = ['nome' => $nome, 'g' => 0, 'v' => 0, 'n' => 0, 'p' => 0,
                                   'gf' => 0, 'gs' => 0, 'pt' => 0, 'punteggio' => 0];
                }
            }
            $cls[$p['nome_fantasquadra_casa']]['g']++;
            $cls[$p['nome_fantasquadra_trasferta']]['g']++;
            $cls[$p['nome_fantasquadra_casa']]['gf']       += $p['gol_casa'];
            $cls[$p['nome_fantasquadra_casa']]['gs']       += $p['gol_trasferta'];
            $cls[$p['nome_fantasquadra_trasferta']]['gf']  += $p['gol_trasferta'];
            $cls[$p['nome_fantasquadra_trasferta']]['gs']  += $p['gol_casa'];
            $cls[$p['nome_fantasquadra_casa']]['punteggio']       += $p['punteggio_casa'];
            $cls[$p['nome_fantasquadra_trasferta']]['punteggio']  += $p['punteggio_trasferta'];

            if ($p['gol_casa'] > $p['gol_trasferta']) {
                $cls[$p['nome_fantasquadra_casa']]['v']++;
                $cls[$p['nome_fantasquadra_casa']]['pt'] += 3;
                $cls[$p['nome_fantasquadra_trasferta']]['p']++;
            } elseif ($p['gol_casa'] < $p['gol_trasferta']) {
                $cls[$p['nome_fantasquadra_trasferta']]['v']++;
                $cls[$p['nome_fantasquadra_trasferta']]['pt'] += 3;
                $cls[$p['nome_fantasquadra_casa']]['p']++;
            } else {
                $cls[$p['nome_fantasquadra_casa']]['n']++;
                $cls[$p['nome_fantasquadra_casa']]['pt']++;
                $cls[$p['nome_fantasquadra_trasferta']]['n']++;
                $cls[$p['nome_fantasquadra_trasferta']]['pt']++;
            }
        }
    }
    usort($cls, fn($a, $b) =>
        $b['pt'] <=> $a['pt']
        ?: $b['punteggio'] <=> $a['punteggio']
        ?: ($b['gf'] - $b['gs']) <=> ($a['gf'] - $a['gs'])
        ?: $b['gf'] <=> $a['gf']
    );
    return array_values($cls);
}

// ── Raggruppa eliminatorie per sfida (andata + ritorno) ──────────────────────
/**
 * Dato un array giornata→partite, restituisce array di sfide.
 * Ogni sfida: ['andata' => partita|null, 'ritorno' => partita|null]
 * Determina andata/ritorno ordinando per numero di giornata crescente.
 */
function raggruppa_eliminatorie(array $giornate): array
{
    $sfide_map = [];
    foreach ($giornate as $giornata => $ps) {
        foreach ($ps as $p) {
            $teams = [$p['nome_fantasquadra_casa'], $p['nome_fantasquadra_trasferta']];
            sort($teams);
            $key = $teams[0] . '|' . $teams[1];
            $sfide_map[$key][] = $p;
        }
    }
    $sfide = [];
    foreach ($sfide_map as $ps) {
        usort($ps, fn($a, $b) => (int)$a['giornata'] <=> (int)$b['giornata']);
        $sfide[] = [
            'andata'  => $ps[0] ?? null,
            'ritorno' => $ps[1] ?? null,
        ];
    }
    return $sfide;
}

// ── Classifica calendario (Serie A) ──────────────────────────────────────────
function calcola_classifica_calendario(array $partite_arr): array
{
    $cls = [];
    foreach ($partite_arr as $giornata => $ps) {
        foreach ($ps as $p) {
            foreach (['casa', 'trasferta'] as $lato) {
                $nome = $p['nome_fantasquadra_' . $lato];
                if (!isset($cls[$nome])) {
                    $cls[$nome] = ['nome' => $nome, 'g' => 0, 'v' => 0, 'n' => 0, 'p' => 0,
                                   'gf' => 0, 'gs' => 0, 'pt' => 0, 'punteggio' => 0];
                }
            }
            $cls[$p['nome_fantasquadra_casa']]['g']++;
            $cls[$p['nome_fantasquadra_trasferta']]['g']++;
            $cls[$p['nome_fantasquadra_casa']]['gf']       += $p['gol_casa'];
            $cls[$p['nome_fantasquadra_casa']]['gs']       += $p['gol_trasferta'];
            $cls[$p['nome_fantasquadra_trasferta']]['gf']  += $p['gol_trasferta'];
            $cls[$p['nome_fantasquadra_trasferta']]['gs']  += $p['gol_casa'];
            $cls[$p['nome_fantasquadra_casa']]['punteggio']       += $p['punteggio_casa'];
            $cls[$p['nome_fantasquadra_trasferta']]['punteggio']  += $p['punteggio_trasferta'];

            if ($p['gol_casa'] > $p['gol_trasferta']) {
                $cls[$p['nome_fantasquadra_casa']]['v']++;
                $cls[$p['nome_fantasquadra_casa']]['pt'] += 3;
                $cls[$p['nome_fantasquadra_trasferta']]['p']++;
            } elseif ($p['gol_casa'] < $p['gol_trasferta']) {
                $cls[$p['nome_fantasquadra_trasferta']]['v']++;
                $cls[$p['nome_fantasquadra_trasferta']]['pt'] += 3;
                $cls[$p['nome_fantasquadra_casa']]['p']++;
            } else {
                $cls[$p['nome_fantasquadra_casa']]['n']++;
                $cls[$p['nome_fantasquadra_casa']]['pt']++;
                $cls[$p['nome_fantasquadra_trasferta']]['n']++;
                $cls[$p['nome_fantasquadra_trasferta']]['pt']++;
            }
        }
    }
    usort($cls, fn($a, $b) =>
        $b['pt'] <=> $a['pt']
        ?: $b['punteggio'] <=> $a['punteggio']
        ?: ($b['gf'] - $b['gs']) <=> ($a['gf'] - $a['gs'])
        ?: $b['gf'] <=> $a['gf']
    );
    return array_values($cls);
}

// ── Statistiche della competizione ───────────────────────────────────────────
$stats_comp = [];
if (!empty($matches)) {
    $best_match_pt   = null; // partita col punteggio totale più alto
    $best_match_gol  = null; // partita con più gol
    $best_pt_single  = null; // miglior punteggio individuale (casa o tra)
    $best_pt_single_team = '';
    $max_pt_sum      = -1;
    $max_gols        = -1;
    $max_single_pt   = -1;
    $team_gf         = []; // gol fatti per squadra
    $team_gs         = []; // gol subiti per squadra
    $team_pt         = []; // punteggio totale per squadra
    $team_g          = []; // partite giocate

    foreach ($matches as $m) {
        $tc = $m['nome_fantasquadra_casa'];
        $tt = $m['nome_fantasquadra_trasferta'];
        $gc = (int)$m['gol_casa'];
        $gt = (int)$m['gol_trasferta'];
        $pc = (float)$m['punteggio_casa'];
        $pt = (float)$m['punteggio_trasferta'];

        foreach ([$tc, $tt] as $t) {
            if (!isset($team_gf[$t])) { $team_gf[$t] = $team_gs[$t] = $team_pt[$t] = $team_g[$t] = 0; }
        }
        $team_gf[$tc] += $gc; $team_gs[$tc] += $gt; $team_pt[$tc] += $pc; $team_g[$tc]++;
        $team_gf[$tt] += $gt; $team_gs[$tt] += $gc; $team_pt[$tt] += $pt; $team_g[$tt]++;

        $pt_sum = $pc + $pt;
        if ($pt_sum > $max_pt_sum) { $max_pt_sum = $pt_sum; $best_match_pt = $m; }

        $gol_sum = $gc + $gt;
        if ($gol_sum > $max_gols) { $max_gols = $gol_sum; $best_match_gol = $m; }

        if ($pc > $max_single_pt) { $max_single_pt = $pc; $best_pt_single = $m; $best_pt_single_team = $tc; }
        if ($pt > $max_single_pt) { $max_single_pt = $pt; $best_pt_single = $m; $best_pt_single_team = $tt; }
    }

    arsort($team_gf);
    arsort($team_pt);
    asort($team_gs);

    $stats_comp = [
        'best_match_pt'        => $best_match_pt,
        'best_match_pt_sum'    => $max_pt_sum,
        'best_match_gol'       => $best_match_gol,
        'best_match_gol_sum'   => $max_gols,
        'best_pt_single'       => $best_pt_single,
        'best_pt_single_val'   => $max_single_pt,
        'best_pt_single_team'  => $best_pt_single_team,
        'top_scorer_team'      => array_key_first($team_gf),
        'top_scorer_gf'        => reset($team_gf),
        'best_defense_team'    => array_key_first($team_gs),
        'best_defense_gs'      => reset($team_gs),
        'top_punteggio_team'   => array_key_first($team_pt),
        'top_punteggio_val'    => reset($team_pt),
        'total_matches'        => count($matches),
        'total_goals'          => array_sum($team_gf) / 2,
        'avg_goals_per_match'  => count($matches) > 0
            ? round(array_sum($team_gf) / count($matches), 2)
            : 0,
    ];
}

// ── Tabellone: dati eliminatorie (disponibile per tutte le tipologie) ─────────
$bracket_rounds    = [];
$bracket_all_teams = [];
$leg_tiebreak      = []; // id_partita della gara decisiva → esito (per la riga tie-break sulle card)
$fase_elim_order   = ['Ottavi' => 0, 'Quarti' => 1, 'Semifinali' => 2, 'Finale' => 3];

foreach ($fasi as $_fase => $_giornate) {
    if ($_fase === 'Fase a Gironi' || !isset($fase_elim_order[$_fase])) continue;
    $_sfide_r = raggruppa_eliminatorie($_giornate);
    $_round   = [];
    foreach ($_sfide_r as $_s) {
        $_and = $_s['andata'];
        $_rit = $_s['ritorno'];
        if (!$_and) continue;
        $_ta = $_and['nome_fantasquadra_casa'];
        $_tb = $_and['nome_fantasquadra_trasferta'];
        $bracket_all_teams[] = $_ta;
        $bracket_all_teams[] = $_tb;

        // Esito completo: aggregato regolamentari → supplementari → rigori (logica unica).
        // I supplementari sono ricalcolati dalle formazioni della gara di ritorno.
        $_e = fs_risolvi_sfida($db, $id, $_and, $_rit);
        $_round[] = [
            'ta'     => $_ta,
            'tb'     => $_tb,
            'ga'     => $_e['aggA'],
            'gb'     => $_e['aggB'],
            'winner' => $_e['winner'],   // null se non determinabile → look-ahead più sotto
            'metodo' => $_e['metodo'],   // regolamentari|supplementari|rigori|pareggio
            'suppA'  => $_e['suppA'],
            'suppB'  => $_e['suppB'],
            'totA'   => $_e['aggA'] + (int)$_e['suppA'],
            'totB'   => $_e['aggB'] + (int)$_e['suppB'],
            'rigA'   => $_e['rigA'],
            'rigB'   => $_e['rigB'],
            'supp'   => in_array($_e['metodo'], ['supplementari', 'rigori'], true),
            'rig'    => $_e['metodo'] === 'rigori',
        ];
        // Mappa la gara decisiva → esito, per la riga tie-break sulle card partita
        if ($_e['metodo'] === 'supplementari' || $_e['metodo'] === 'rigori') {
            $leg_tiebreak[(int)$_e['leg']['id_partita']] = $_e;
        }
    }
    if (!empty($_round)) $bracket_rounds[$_fase] = $_round;
}
uksort($bracket_rounds, fn($a, $b) => ($fase_elim_order[$a] ?? 99) <=> ($fase_elim_order[$b] ?? 99));

$bracket_logo = [];
$_bteams = array_unique($bracket_all_teams);
if (!empty($_bteams)) {
    $_ph = implode(',', array_fill(0, count($_bteams), '?'));
    $_ls = $db->prepare("SELECT nome_fantasquadra, scudetto FROM fantasquadra WHERE nome_fantasquadra IN ($_ph)");
    $_ls->execute($_bteams);
    foreach ($_ls->fetchAll() as $_lr) {
        $bracket_logo[$_lr['nome_fantasquadra']] = $_lr['scudetto'] ?? '';
    }
}

$last_bracket_round = !empty($bracket_rounds) ? array_key_last($bracket_rounds) : null;

// ── Look-ahead: risolvi i pareggi guardando chi appare nel round successivo ──
if (!empty($bracket_rounds)) {
    $_br_keys  = array_keys($bracket_rounds);
    $_br_count = count($_br_keys);

    // Per ogni round tranne l'ultimo: cerca il vincitore nel round successivo
    for ($_la_i = 0; $_la_i < $_br_count - 1; $_la_i++) {
        $_this_r = $_br_keys[$_la_i];
        $_next_r = $_br_keys[$_la_i + 1];
        // Raccoglie tutti i team presenti nel round successivo
        $_next_teams = [];
        foreach ($bracket_rounds[$_next_r] as $_nm) {
            $_next_teams[$_nm['ta']] = true;
            $_next_teams[$_nm['tb']] = true;
        }
        foreach ($bracket_rounds[$_this_r] as &$_bm_la) {
            if ($_bm_la['winner'] === null) {
                if (isset($_next_teams[$_bm_la['ta']])) {
                    $_bm_la['winner'] = $_bm_la['ta'];
                } elseif (isset($_next_teams[$_bm_la['tb']])) {
                    $_bm_la['winner'] = $_bm_la['tb'];
                }
            }
        }
        unset($_bm_la);
    }
    // Per la finale: usa il vincitore ufficiale della competizione
    if (!empty($competizione['vincitore'])) {
        foreach ($bracket_rounds[$last_bracket_round] as &$_fm_la) {
            if ($_fm_la['winner'] === null) {
                if ($_fm_la['ta'] === $competizione['vincitore']) {
                    $_fm_la['winner'] = $_fm_la['ta'];
                } elseif ($_fm_la['tb'] === $competizione['vincitore']) {
                    $_fm_la['winner'] = $_fm_la['tb'];
                }
            }
        }
        unset($_fm_la);
    }
    unset($_br_keys, $_br_count, $_la_i, $_this_r, $_next_r, $_next_teams, $_nm);
}

// Set di tutti i team che hanno vinto almeno un round (per i badge "avanza")
$bracket_advanced = [];
foreach ($bracket_rounds as $_r_adv) {
    foreach ($_r_adv as $_m_adv) {
        if ($_m_adv['winner'] !== null) {
            $bracket_advanced[$_m_adv['winner']] = true;
        }
    }
}
unset($_r_adv, $_m_adv);

// ── Geometria bracket: match_h=108px, inner_gap=16px ─────────────────────────
// offset è cumulativo: ogni round si sposta di (match_h + gap_precedente) / 2
// rispetto al round precedente, in modo che le colonne si allineino al centro.
$_bk_h    = 108; // altezza card partita (px)
$_bk_g    = 16;  // gap iniziale tra partite (px)
$_bk_off  = 0.0; // offset cumulativo corrente
$bk_gaps        = [];
$bk_connector_h = [];
$bk_offsets     = [];
$_bk_rnames = array_keys($bracket_rounds);
for ($_ri = 0; $_ri < count($_bk_rnames); $_ri++) {
    $_rn = $_bk_rnames[$_ri];
    $bk_gaps[$_rn]        = $_bk_g;
    $bk_connector_h[$_rn] = 2 * $_bk_h + $_bk_g;
    $bk_offsets[$_rn]     = (int)$_bk_off;
    $_bk_off += ($_bk_h + $_bk_g) / 2; // accumula per il prossimo round
    $_bk_g    = $_bk_h + 2 * $_bk_g;
}
unset($_bk_h, $_bk_g, $_bk_off, $_bk_rnames, $_ri, $_rn);

// ── SEO / GEO: descrizione dinamica + dati strutturati SportsEvent ──
$_seo_nome = (string)$competizione['nome_competizione'];
$_seo_vinc = (string)($competizione['vincitore'] ?? '');
$_seo_desc = $_seo_nome . ' ' . $stagione . ' di FantaScarrupat: classifica, tabellone a eliminazione e calendario completo'
           . ($_seo_vinc !== '' ? '. Vincitore: ' . $_seo_vinc . '.' : '.');
$_seo_event = [
    '@type'  => 'SportsEvent',
    'name'   => $_seo_nome . ' ' . $stagione . ' · FantaScarrupat',
    'sport'  => 'Fantacalcio',
    'description' => $_seo_desc,
    'startDate'   => sprintf('%d-09-01', (int)$competizione['anno'] - 1),
    'endDate'     => sprintf('%d-06-30', (int)$competizione['anno']),
    'eventStatus' => 'https://schema.org/EventScheduled',
    'eventAttendanceMode' => 'https://schema.org/OnlineEventAttendanceMode',
    'location'  => ['@type' => 'VirtualLocation', 'url' => fs_url('public/index.php')],
    'organizer' => ['@id' => fs_url() . '#organization'],
];
layout_start($title, '', [
    'description' => $_seo_desc,
    'og_type'     => 'article',
    'robots'      => $dati_non_disponibili ? 'noindex, follow' : 'index, follow, max-image-preview:large, max-snippet:-1',
    'breadcrumb'  => [
        ['name' => 'Home',         'url' => 'public/index.php'],
        ['name' => "Albo d'Oro",   'url' => 'public/albo-doro.php'],
        ['name' => $_seo_nome . ' ' . $stagione],
    ],
    'jsonld'      => [$_seo_event],
]);
?>

<?php if ($dati_non_disponibili): ?>
<!-- ── Banner dati non disponibili ──────────────────────────────────────────── -->
<div class="det-unavailable-banner" role="alert">
  <span class="material-symbols-rounded det-unavailable-banner__icon">info</span>
  <div class="det-unavailable-banner__text">
    <strong>Dati non disponibili</strong>
    I dettagli delle competizioni precedenti alla stagione 2023/24 non sono stati ancora inseriti nel sistema.
  </div>
  <a href="<?= BASE_PATH ?>public/albo-doro.php" class="det-unavailable-banner__link">
    Torna all'albo d'oro
    <span class="material-symbols-rounded" style="font-size:14px">arrow_forward</span>
  </a>
</div>
<?php endif; ?>

<!-- ── Hero ──────────────────────────────────────────────────────────────────── -->
<section class="hero" aria-label="Competizione">
  <div class="container">

    <p class="hero__eyebrow">
      <a href="<?= BASE_PATH ?>public/albo-doro.php"
         style="color:var(--c-accent);text-decoration:none">Albo d'Oro</a>
      &nbsp;/&nbsp; <?= htmlspecialchars($competizione['nome_competizione']) ?>
    </p>

    <h1 class="hero__title">
      <?= htmlspecialchars($competizione['nome_competizione']) ?>
    </h1>

    <p class="hero__subtitle">
      Stagione <?= $stagione ?>
      &nbsp;&middot;&nbsp;
      <?= htmlspecialchars($competizione['tipologia']) ?>
    </p>

    <?php if ($competizione['vincitore']): ?>
    <div style="display:inline-flex;align-items:center;gap:var(--sp-3);
                margin-top:var(--sp-6);
                padding:var(--sp-3) var(--sp-5);
                background:oklch(76% 0.162 76 / 0.15);
                border:1px solid oklch(76% 0.162 76 / 0.35);
                border-radius:var(--r-pill)">
      <span class="material-symbols-rounded"
            style="font-size:20px;font-variation-settings:'FILL' 1;color:var(--c-gold)">
        emoji_events
      </span>
      <span style="color:var(--c-gold);font-weight:700;font-size:1rem">
        <?= htmlspecialchars($competizione['vincitore']) ?>
      </span>
    </div>
    <?php endif; ?>

  </div>
</section>

<!-- ── Contenuto ─────────────────────────────────────────────────────────────── -->
<section class="section">
  <div class="container">
  <?php $calendario_after = ''; // ordine pagina: classifica → tabellone → calendario (qui sotto) ?>

  <?php if (empty($matches) && empty($solitarie)): ?>
    <!-- Nessun dato -->
    <div style="text-align:center;padding:var(--sp-16) 0;color:var(--c-text-muted)">
      <span class="material-symbols-rounded"
            style="font-size:48px;display:block;margin-bottom:var(--sp-4);opacity:0.4">
        info
      </span>
      <p>I dettagli di questa competizione non sono ancora disponibili.</p>
    </div>

  <?php elseif ($competizione['tipologia'] === 'A Calendario'): ?>
    <!-- ── Serie A: classifica + risultati ── -->
    <?php
    $classifica = calcola_classifica_calendario($fasi['Calendario'] ?? []);
    ?>

    <!-- Dati partite per lo slider giornate (JS) -->
    <script>
    var DET_MATCHES = <?= json_encode(array_map(fn($m) => [
        'casa' => $m['nome_fantasquadra_casa'],
        'tra'  => $m['nome_fantasquadra_trasferta'],
        'gc'   => (int)$m['gol_casa'],
        'gt'   => (int)$m['gol_trasferta'],
        'pc'   => (float)$m['punteggio_casa'],
        'pt'   => (float)$m['punteggio_trasferta'],
        'g'    => (int)$m['giornata'],
    ], $matches), JSON_UNESCAPED_UNICODE) ?>;
    var DET_VINCITORE = <?= json_encode($competizione['vincitore']) ?>;
    var DET_MIN_G = <?= $min_giornata ?>;
    var DET_MAX_G = <?= $max_giornata ?>;
    </script>

    <!-- ── Slider giornate ─────────────────────────────────────── -->
    <?php if ($max_giornata > $min_giornata): ?>
    <div class="det-range-slider" id="det-range-slider">
      <div class="det-range-slider__header">
        <span class="det-range-slider__label">
          <span class="material-symbols-rounded" style="font-size:15px;vertical-align:-3px">tune</span>
          Filtra giornate
        </span>
        <span class="det-range-slider__display" id="det-range-display">Tutte le giornate</span>
        <button class="det-range-slider__reset" id="det-range-reset"
                style="visibility:hidden">Azzera</button>
      </div>
      <div class="det-range-slider__track-wrap">
        <div class="det-range-slider__fill" id="det-range-fill"></div>
        <input type="range" class="det-range-slider__thumb" id="det-range-min"
               min="<?= $min_giornata ?>" max="<?= $max_giornata ?>"
               value="<?= $min_giornata ?>" step="1" aria-label="Giornata minima">
        <input type="range" class="det-range-slider__thumb" id="det-range-max"
               min="<?= $min_giornata ?>" max="<?= $max_giornata ?>"
               value="<?= $max_giornata ?>" step="1" aria-label="Giornata massima">
      </div>
      <div class="det-range-slider__endpoints">
        <span>G.&thinsp;<?= $min_giornata ?></span>
        <span>G.&thinsp;<?= $max_giornata ?></span>
      </div>
    </div>
    <?php endif; ?>

    <h2 style="margin-bottom:var(--sp-6)">Classifica finale</h2>

    <?php
      $_pt_vals = array_column($classifica, 'punteggio');
      $_pt_max  = !empty($_pt_vals) ? max($_pt_vals) : 0;
      $_pt_min  = !empty($_pt_vals) ? min($_pt_vals) : 0;
      $_pt_many = count($classifica) > 1;
    ?>
    <div class="table-scroll" style="margin-bottom:var(--sp-12)">
      <table class="fs-table">
        <thead>
          <tr>
            <th style="width:44px">#</th>
            <th>Squadra</th>
            <th style="text-align:center">G</th>
            <th style="text-align:center">V</th>
            <th style="text-align:center">N</th>
            <th style="text-align:center">P</th>
            <th style="text-align:center">GF</th>
            <th style="text-align:center">GS</th>
            <th style="text-align:right">Punti</th>
            <th style="text-align:right" title="Punteggio totale, spareggio a pari punti">Pt tot</th>
          </tr>
        </thead>
        <tbody id="standings-tbody">
          <?php foreach ($classifica as $pos => $sq):
            $rank = $pos + 1;
            $_ptc = '';
            if ($_pt_many) {
                if ($sq['punteggio'] >= $_pt_max)      $_ptc = 'color:var(--c-success)';
                elseif ($sq['punteggio'] <= $_pt_min)  $_ptc = 'color:var(--c-danger)';
            }
          ?>
          <tr <?= ($sq['nome'] === $competizione['vincitore']) ? 'style="background:oklch(76% 0.162 76 / 0.06)"' : '' ?>>
            <td class="rank-cell rank-<?= min($rank, 3) ?>"><?= $rank ?></td>
            <td style="font-weight:<?= $sq['nome'] === $competizione['vincitore'] ? '700' : '400' ?>">
              <?= tlink($sq['nome']) ?>
              <?php if ($sq['nome'] === $competizione['vincitore']): ?>
                <span class="material-symbols-rounded"
                      style="font-size:14px;vertical-align:-2px;
                             color:var(--c-gold);font-variation-settings:'FILL' 1">
                  emoji_events
                </span>
              <?php endif; ?>
            </td>
            <td style="text-align:center;color:var(--c-text-muted)"><?= $sq['g'] ?></td>
            <td style="text-align:center;color:var(--c-success)"><?= $sq['v'] ?></td>
            <td style="text-align:center;color:var(--c-text-muted)"><?= $sq['n'] ?></td>
            <td style="text-align:center;color:var(--c-danger)"><?= $sq['p'] ?></td>
            <td style="text-align:center"><?= $sq['gf'] ?></td>
            <td style="text-align:center"><?= $sq['gs'] ?></td>
            <td style="text-align:right;font-weight:700"><?= $sq['pt'] ?></td>
            <td style="text-align:right;font-weight:600;<?= $_ptc ?>"><?= number_format($sq['punteggio'], 2) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Risultati per giornata -->
    <h2 style="margin-bottom:var(--sp-6)">Risultati</h2>

    <?php foreach (($fasi['Calendario'] ?? []) as $giornata => $ps): ?>
    <div class="det-matchday" data-giornata="<?= $giornata ?>" style="margin-bottom:var(--sp-8)">
      <h3 style="font-size:1rem;color:var(--c-text-muted);
                 font-family:var(--font-body);font-weight:600;
                 text-transform:uppercase;letter-spacing:0.06em;
                 margin-bottom:var(--sp-4)">
        Giornata <?= $giornata ?>
      </h3>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:var(--sp-3)">
        <?php foreach ($ps as $p): ?>
        <div class="bracket-match<?= ha_formazioni($p, $form_keys) ? ' js-vedi-form' : '' ?><?= isset($leg_tiebreak[(int)($p['id_partita'] ?? 0)]) ? ' has-tb' : '' ?>" data-partita="<?= (int)($p['id_partita'] ?? 0) ?>">
          <?php
          $casa_vince = $p['gol_casa'] > $p['gol_trasferta'];
          $tras_vince = $p['gol_trasferta'] > $p['gol_casa'];
          ?>
          <div class="bracket-team <?= $casa_vince ? 'winner' : '' ?>">
            <span class="bracket-team__name"><?= tlink($p['nome_fantasquadra_casa']) ?></span>
            <span class="bracket-team__score"><?= $p['gol_casa'] ?></span>
            <span style="font-size:0.75rem;color:var(--c-text-muted);min-width:50px;text-align:right">
              <?= number_format($p['punteggio_casa'], 1) ?>
            </span>
          </div>
          <div class="bracket-team <?= $tras_vince ? 'winner' : '' ?>">
            <span class="bracket-team__name"><?= tlink($p['nome_fantasquadra_trasferta']) ?></span>
            <span class="bracket-team__score"><?= $p['gol_trasferta'] ?></span>
            <span style="font-size:0.75rem;color:var(--c-text-muted);min-width:50px;text-align:right">
              <?= number_format($p['punteggio_trasferta'], 1) ?>
            </span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>

  <?php elseif ($competizione['tipologia'] === 'A Gruppi'): ?>
    <!-- ── Champions League: gironi + eliminatorie ── -->

    <?php if (!empty($gironi)): ?>

    <!-- Dati gironi per slider JS -->
    <?php
    $gir_matches_json = [];
    foreach ($gironi as $g_nome => $giornate) {
        foreach ($giornate as $gno => $ps) {
            foreach ($ps as $p) {
                $gir_matches_json[] = [
                    'gn'   => $g_nome,
                    'g'    => (int)$p['giornata'],
                    'casa' => $p['nome_fantasquadra_casa'],
                    'tra'  => $p['nome_fantasquadra_trasferta'],
                    'gc'   => (int)$p['gol_casa'],
                    'gt'   => (int)$p['gol_trasferta'],
                ];
            }
        }
    }
    ?>
    <script>
    var GIRONE_MATCHES = <?= json_encode($gir_matches_json, JSON_UNESCAPED_UNICODE) ?>;
    var GIRONE_MIN_G   = <?= $min_giornata_gir ?>;
    var GIRONE_MAX_G   = <?= $max_giornata_gir ?>;
    var GIRONE_NAMES   = <?= json_encode(array_keys($gironi), JSON_UNESCAPED_UNICODE) ?>;
    </script>

    <h2 style="margin-bottom:var(--sp-6)">Classifiche gironi</h2>

    <div style="display:grid;grid-template-columns:minmax(0,1fr);
                gap:var(--sp-8);margin-bottom:var(--sp-12)">
      <?php foreach ($gironi as $g_nome => $giornate):
        $cls_girone = calcola_classifica($giornate);
      ?>
      <div style="min-width:0">
        <h3 style="font-size:1rem;text-transform:uppercase;letter-spacing:0.08em;
                   color:var(--c-text-muted);font-family:var(--font-body);font-weight:600;
                   margin-bottom:var(--sp-4)">Girone <?= htmlspecialchars($g_nome) ?></h3>

        <!-- Classifica girone -->
        <?php
          $_gpt_vals = array_column($cls_girone, 'punteggio');
          $_gpt_max  = !empty($_gpt_vals) ? max($_gpt_vals) : 0;
          $_gpt_min  = !empty($_gpt_vals) ? min($_gpt_vals) : 0;
          $_gpt_many = count($cls_girone) > 1;
        ?>
        <div class="table-scroll" style="margin-bottom:var(--sp-6)">
          <table class="fs-table">
            <thead>
              <tr>
                <th>#</th><th>Squadra</th>
                <th style="text-align:center">G</th><th style="text-align:center">V</th>
                <th style="text-align:center">N</th><th style="text-align:center">P</th>
                <th style="text-align:center">GF</th><th style="text-align:center">GS</th>
                <th style="text-align:right">Pt</th>
                <th style="text-align:right" title="Punteggio totale, spareggio a pari punti">Pt tot</th>
              </tr>
            </thead>
            <tbody id="standings-girone-<?= htmlspecialchars($g_nome) ?>">
              <?php foreach ($cls_girone as $pos => $sq):
                $_gptc = '';
                if ($_gpt_many) {
                    if ($sq['punteggio'] >= $_gpt_max)     $_gptc = 'color:var(--c-success)';
                    elseif ($sq['punteggio'] <= $_gpt_min) $_gptc = 'color:var(--c-danger)';
                }
              ?>
              <?php $_gwin = ($sq['nome'] === $competizione['vincitore']); ?>
              <tr<?= $_gwin ? ' style="background:oklch(76% 0.162 76 / 0.08)"' : ($pos === 0 ? ' style="background:oklch(63% 0.150 145 / 0.06)"' : '') ?>>
                <td class="rank-cell"><?= $pos + 1 ?></td>
                <td style="font-weight:<?= ($_gwin || $pos === 0) ? '600' : '400' ?>">
                  <?= tlink($sq['nome']) ?>
                  <?php if ($_gwin): ?>
                  <span class="material-symbols-rounded" style="font-size:14px;vertical-align:-2px;color:var(--c-gold);font-variation-settings:'FILL' 1" title="Vincitore">emoji_events</span>
                  <?php endif; ?>
                </td>
                <td style="text-align:center;color:var(--c-text-muted)"><?= $sq['g'] ?></td>
                <td style="text-align:center;color:var(--c-success)"><?= $sq['v'] ?></td>
                <td style="text-align:center;color:var(--c-text-muted)"><?= $sq['n'] ?></td>
                <td style="text-align:center;color:var(--c-danger)"><?= $sq['p'] ?></td>
                <td style="text-align:center"><?= $sq['gf'] ?></td>
                <td style="text-align:center"><?= $sq['gs'] ?></td>
                <td style="text-align:right;font-weight:700"><?= $sq['pt'] ?></td>
                <td style="text-align:right;font-weight:600;<?= $_gptc ?>"><?= number_format($sq['punteggio'], 2) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php ob_start(); // calendario completo: gironi + eliminatorie, dopo il tabellone ?>

    <?php if (!empty($gironi)): ?>
    <h2 style="margin-bottom:var(--sp-6)">Fase a gironi</h2>

    <?php if ($max_giornata_gir > $min_giornata_gir): ?>
    <div class="det-range-slider" id="det-gj-range-slider" style="margin-bottom:var(--sp-8)">
      <div class="det-range-slider__header">
        <span class="det-range-slider__label">
          <span class="material-symbols-rounded" style="font-size:15px;vertical-align:-3px">tune</span>
          Filtra giornate gironi
        </span>
        <span class="det-range-slider__display" id="det-gj-range-display">Tutte le giornate</span>
        <button class="det-range-slider__reset" id="det-gj-range-reset"
                style="visibility:hidden">Azzera</button>
      </div>
      <div class="det-range-slider__track-wrap">
        <div class="det-range-slider__fill" id="det-gj-range-fill"></div>
        <input type="range" class="det-range-slider__thumb" id="det-gj-range-min"
               min="<?= $min_giornata_gir ?>" max="<?= $max_giornata_gir ?>"
               value="<?= $min_giornata_gir ?>" step="1" aria-label="Giornata minima">
        <input type="range" class="det-range-slider__thumb" id="det-gj-range-max"
               min="<?= $min_giornata_gir ?>" max="<?= $max_giornata_gir ?>"
               value="<?= $max_giornata_gir ?>" step="1" aria-label="Giornata massima">
      </div>
      <div class="det-range-slider__endpoints">
        <span>G.&thinsp;<?= $min_giornata_gir ?></span>
        <span>G.&thinsp;<?= $max_giornata_gir ?></span>
      </div>
    </div>
    <?php endif; ?>

    <?php foreach ($gironi as $g_nome => $giornate): ?>
    <h3 style="font-size:0.875rem;color:var(--c-text-muted);font-family:var(--font-body);
               font-weight:700;text-transform:uppercase;letter-spacing:0.08em;
               margin:var(--sp-6) 0 var(--sp-4)">Girone <?= htmlspecialchars($g_nome) ?></h3>
    <?php foreach ($giornate as $giornata => $ps): ?>
    <div class="det-girone-matchday" data-giornata="<?= (int)$giornata ?>" style="margin-bottom:var(--sp-6)">
      <div style="font-size:0.75rem;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;color:var(--c-text-faint);margin-bottom:var(--sp-2)">
        Giornata <?= $giornata ?>
      </div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:var(--sp-3)">
        <?php foreach ($ps as $p):
          $c_v = $p['gol_casa'] > $p['gol_trasferta'];
          $t_v = $p['gol_trasferta'] > $p['gol_casa'];
        ?>
        <div class="bracket-match<?= ha_formazioni($p, $form_keys) ? ' js-vedi-form' : '' ?><?= isset($leg_tiebreak[(int)($p['id_partita'] ?? 0)]) ? ' has-tb' : '' ?>" data-partita="<?= (int)($p['id_partita'] ?? 0) ?>">
          <div class="bracket-team <?= $c_v ? 'winner' : '' ?>">
            <span class="bracket-team__name"><?= tlink($p['nome_fantasquadra_casa']) ?></span>
            <span class="bracket-team__score"><?= $p['gol_casa'] ?></span>
          </div>
          <div class="bracket-team <?= $t_v ? 'winner' : '' ?>">
            <span class="bracket-team__name"><?= tlink($p['nome_fantasquadra_trasferta']) ?></span>
            <span class="bracket-team__score"><?= $p['gol_trasferta'] ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php // ── Eliminatorie ── ?>
    <?php foreach ($fasi as $fase => $giornate):
      if ($fase === 'Fase a Gironi') continue;
    ?>
    <h2 style="margin-bottom:var(--sp-6)"><?= htmlspecialchars($fase) ?></h2>
    <?php foreach ($giornate as $giornata => $ps): ?>
    <div style="margin-bottom:var(--sp-8)">
      <?php if (count($giornate) > 1): ?>
      <h3 style="font-size:0.875rem;color:var(--c-text-muted);font-family:var(--font-body);font-weight:600;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:var(--sp-4)">
        Giornata <?= $giornata ?>
      </h3>
      <?php endif; ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:var(--sp-3)">
        <?php foreach ($ps as $p):
          $c_v = $p['gol_casa'] > $p['gol_trasferta'];
          $t_v = $p['gol_trasferta'] > $p['gol_casa'];
        ?>
        <div class="bracket-match<?= ha_formazioni($p, $form_keys) ? ' js-vedi-form' : '' ?><?= isset($leg_tiebreak[(int)($p['id_partita'] ?? 0)]) ? ' has-tb' : '' ?>" data-partita="<?= (int)($p['id_partita'] ?? 0) ?>">
          <div class="bracket-team <?= $c_v ? 'winner' : '' ?>">
            <span class="bracket-team__name"><?= tlink($p['nome_fantasquadra_casa']) ?></span>
            <span class="bracket-team__score"><?= $p['gol_casa'] ?></span>
            <span style="font-size:0.8125rem;color:var(--c-text-muted);min-width:44px;text-align:right"><?= number_format($p['punteggio_casa'], 1) ?></span>
          </div>
          <div class="bracket-team <?= $t_v ? 'winner' : '' ?>">
            <span class="bracket-team__name"><?= tlink($p['nome_fantasquadra_trasferta']) ?></span>
            <span class="bracket-team__score"><?= $p['gol_trasferta'] ?></span>
            <span style="font-size:0.8125rem;color:var(--c-text-muted);min-width:44px;text-align:right"><?= number_format($p['punteggio_trasferta'], 1) ?></span>
          </div>
          <?= tiebreak_line($p, $leg_tiebreak) ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endforeach; ?>
    <?php $calendario_after = ob_get_clean(); ?>

  <?php else: ?>
    <!-- ── Formato generico: classifica (se round-robin) + calendario ── -->
    <?php
    $cls_generic = !empty($fasi['Calendario']) ? calcola_classifica_calendario($fasi['Calendario']) : [];
    if (!empty($cls_generic)):
      $_ggv = array_column($cls_generic, 'punteggio');
      $_ggmax = $_ggv ? max($_ggv) : 0; $_ggmin = $_ggv ? min($_ggv) : 0; $_ggmany = count($cls_generic) > 1;
    ?>
    <h2 style="margin-bottom:var(--sp-6)">Classifica</h2>
    <div class="table-scroll" style="margin-bottom:var(--sp-12)">
      <table class="fs-table">
        <thead><tr>
          <th style="width:44px">#</th><th>Squadra</th>
          <th style="text-align:center">G</th><th style="text-align:center">V</th>
          <th style="text-align:center">N</th><th style="text-align:center">P</th>
          <th style="text-align:center">GF</th><th style="text-align:center">GS</th>
          <th style="text-align:right">Punti</th>
          <th style="text-align:right" title="Punteggio totale, spareggio a pari punti">Pt tot</th>
        </tr></thead>
        <tbody>
          <?php foreach ($cls_generic as $pos => $sq): $rank = $pos + 1; $_ggc = '';
            if ($_ggmany) { if ($sq['punteggio'] >= $_ggmax) $_ggc = 'color:var(--c-success)'; elseif ($sq['punteggio'] <= $_ggmin) $_ggc = 'color:var(--c-danger)'; }
          ?>
          <tr<?= $sq['nome'] === $competizione['vincitore'] ? ' style="background:oklch(76% 0.162 76 / 0.06)"' : '' ?>>
            <td class="rank-cell rank-<?= min($rank, 3) ?>"><?= $rank ?></td>
            <td style="font-weight:<?= $sq['nome'] === $competizione['vincitore'] ? '700' : '400' ?>"><?= tlink($sq['nome']) ?>
              <?php if ($sq['nome'] === $competizione['vincitore']): ?>
              <span class="material-symbols-rounded" style="font-size:14px;vertical-align:-2px;color:var(--c-gold);font-variation-settings:'FILL' 1" title="Vincitore">emoji_events</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center;color:var(--c-text-muted)"><?= $sq['g'] ?></td>
            <td style="text-align:center;color:var(--c-success)"><?= $sq['v'] ?></td>
            <td style="text-align:center;color:var(--c-text-muted)"><?= $sq['n'] ?></td>
            <td style="text-align:center;color:var(--c-danger)"><?= $sq['p'] ?></td>
            <td style="text-align:center"><?= $sq['gf'] ?></td>
            <td style="text-align:center"><?= $sq['gs'] ?></td>
            <td style="text-align:right;font-weight:700"><?= $sq['pt'] ?></td>
            <td style="text-align:right;font-weight:600;<?= $_ggc ?>"><?= number_format($sq['punteggio'], 2) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
    <?php ob_start(); // calendario partite: mostrato dopo il tabellone ?>
    <?php foreach ($fasi as $fase => $giornate): ?>

    <h2 style="margin-bottom:var(--sp-6)"><?= htmlspecialchars($fase) ?></h2>

    <?php foreach ($giornate as $giornata => $ps): ?>
    <div style="margin-bottom:var(--sp-8)">
      <?php if (count($giornate) > 1): ?>
      <h3 style="font-size:0.875rem;color:var(--c-text-muted);font-family:var(--font-body);
                 font-weight:600;text-transform:uppercase;letter-spacing:0.06em;
                 margin-bottom:var(--sp-4)">
        Giornata <?= $giornata ?>
      </h3>
      <?php endif; ?>

      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:var(--sp-3)">
        <?php foreach ($ps as $p):
          $c_v = $p['gol_casa'] > $p['gol_trasferta'];
          $t_v = $p['gol_trasferta'] > $p['gol_casa'];
        ?>
        <div class="bracket-match<?= ha_formazioni($p, $form_keys) ? ' js-vedi-form' : '' ?><?= isset($leg_tiebreak[(int)($p['id_partita'] ?? 0)]) ? ' has-tb' : '' ?>" data-partita="<?= (int)($p['id_partita'] ?? 0) ?>">
          <div class="bracket-team <?= $c_v ? 'winner' : '' ?>">
            <span class="bracket-team__name"><?= tlink($p['nome_fantasquadra_casa']) ?></span>
            <span class="bracket-team__score"><?= $p['gol_casa'] ?></span>
            <span style="font-size:0.8125rem;color:var(--c-text-muted);min-width:44px;text-align:right">
              <?= number_format($p['punteggio_casa'], 1) ?>
            </span>
          </div>
          <div class="bracket-team <?= $t_v ? 'winner' : '' ?>">
            <span class="bracket-team__name"><?= tlink($p['nome_fantasquadra_trasferta']) ?></span>
            <span class="bracket-team__score"><?= $p['gol_trasferta'] ?></span>
            <span style="font-size:0.8125rem;color:var(--c-text-muted);min-width:44px;text-align:right">
              <?= number_format($p['punteggio_trasferta'], 1) ?>
            </span>
          </div>
          <?= tiebreak_line($p, $leg_tiebreak) ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <?php endforeach; ?>
    <?php $calendario_after = ob_get_clean(); ?>
  <?php endif; ?>

  <?php if (!empty($bracket_rounds)): ?>
  <!-- ── Tabellone ─────────────────────────────────────────────────────────── -->
  <div style="margin-top:var(--sp-12);padding-top:var(--sp-8);border-top:1px solid var(--c-border)">
    <p class="det-section-eyebrow">
      <span class="material-symbols-rounded" style="font-size:13px;vertical-align:-2px">account_tree</span>
      <?= htmlspecialchars($competizione['nome_competizione']) ?> <?= $stagione ?>
    </p>
    <h2 style="margin:var(--sp-1) 0 var(--sp-8)">Tabellone</h2>
  </div>

  <div class="det-bracket-wrap">
    <div class="det-bracket">
      <?php foreach ($bracket_rounds as $_fname => $_sfide_b):
        $_is_last = ($_fname === $last_bracket_round);
        $_gap     = $bk_gaps[$_fname]        ?? 16;
        $_conn_h  = $bk_connector_h[$_fname] ?? 124;
        $_offset  = $bk_offsets[$_fname]     ?? 0;
        $_n_pairs = (int)ceil(count($_sfide_b) / 2);
      ?>
      <section class="det-br-round">
        <div class="det-br-round__label"><?= htmlspecialchars($_fname) ?></div>
        <div class="det-br-winners" style="padding-top:<?= $_offset ?>px">
          <div class="det-br-matchups">
            <?php foreach ($_sfide_b as $_mi => $_sb):
              $_logo_a = img_url(TEAMS_URL, $bracket_logo[$_sb['ta']] ?? '');
              $_logo_b = img_url(TEAMS_URL, $bracket_logo[$_sb['tb']] ?? '');
              $_init_a = strtoupper(mb_substr($_sb['ta'], 0, 2));
              $_init_b = strtoupper(mb_substr($_sb['tb'], 0, 2));
              $_win_a  = $_sb['winner'] === $_sb['ta'];
              $_win_b  = $_sb['winner'] === $_sb['tb'];
            ?>
            <div class="det-br-match<?= $_is_last ? ' det-br-match--champion' : '' ?>"
                 <?= $_mi > 0 ? 'style="margin-top:'.$_gap.'px"' : '' ?>>

              <div class="det-br-team <?= $_win_a ? 'det-br-team--win' : ($_sb['winner'] ? 'det-br-team--lose' : '') ?>">
                <?php if ($_logo_a !== ''): ?>
                <img src="<?= $_logo_a ?>" alt="" class="det-br-team__logo"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                <div class="det-br-team__logo-ph" style="display:none"><?= htmlspecialchars($_init_a) ?></div>
                <?php else: ?>
                <div class="det-br-team__logo-ph"><?= htmlspecialchars($_init_a) ?></div>
                <?php endif; ?>
                <span class="det-br-team__name"><?= tlink($_sb['ta']) ?></span>
                <span class="det-br-team__score"><?= $_sb['ga'] ?></span>
                <?php if ($_is_last && $_win_a): ?>
                <span class="material-symbols-rounded det-br-team__trophy">emoji_events</span>
                <?php endif; ?>
              </div>

              <div class="det-br-team <?= $_win_b ? 'det-br-team--win' : ($_sb['winner'] ? 'det-br-team--lose' : '') ?>">
                <?php if ($_logo_b !== ''): ?>
                <img src="<?= $_logo_b ?>" alt="" class="det-br-team__logo"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                <div class="det-br-team__logo-ph" style="display:none"><?= htmlspecialchars($_init_b) ?></div>
                <?php else: ?>
                <div class="det-br-team__logo-ph"><?= htmlspecialchars($_init_b) ?></div>
                <?php endif; ?>
                <span class="det-br-team__name"><?= tlink($_sb['tb']) ?></span>
                <span class="det-br-team__score"><?= $_sb['gb'] ?></span>
                <?php if ($_is_last && $_win_b): ?>
                <span class="material-symbols-rounded det-br-team__trophy">emoji_events</span>
                <?php endif; ?>
              </div>

              <?php if ($_sb['metodo'] === 'supplementari' || $_sb['metodo'] === 'rigori'): ?>
              <div class="det-br-tiebreak">
                <!-- I punteggi sulle squadre sono i regolamentari; qui l'esito dopo supplementari (ed eventuali rigori) -->
                <span class="det-br-tiebreak__badge det-br-tiebreak__badge--supp">d.t.s.</span>
                <span class="det-br-tiebreak__score"><?= $_sb['totA'] ?>&thinsp;&ndash;&thinsp;<?= $_sb['totB'] ?></span>
                <?php if ($_sb['metodo'] === 'rigori'): ?>
                <span class="det-br-tiebreak__badge det-br-tiebreak__badge--rig">d.c.r.</span>
                <?php if ($_sb['rigA'] !== null): ?>
                <span class="det-br-tiebreak__score"><?= $_sb['rigA'] ?>&thinsp;&ndash;&thinsp;<?= $_sb['rigB'] ?></span>
                <?php endif; ?>
                <?php endif; ?>
              </div>
              <?php endif; ?>

            </div>
            <?php endforeach; // matches in round ?>
          </div><!-- /matchups -->

          <?php if (!$_is_last): ?>
          <div class="det-br-connectors" style="gap:<?= $_gap ?>px">
            <?php for ($_pi = 0; $_pi < $_n_pairs; $_pi++): ?>
            <div class="det-br-conn-pair">
              <div class="det-br-merger" style="height:<?= $_conn_h ?>px"></div>
              <div class="det-br-line"></div>
            </div>
            <?php endfor; ?>
          </div><!-- /connectors -->
          <?php endif; ?>

        </div><!-- /winners -->
      </section>
      <?php endforeach; // rounds ?>
    </div><!-- /det-bracket -->
  </div><!-- /det-bracket-wrap -->
  <?php endif; // bracket ?>

  <?= $calendario_after ?>

  <!-- ── Statistiche della competizione ── -->
  <?php if (!empty($stats_comp)): ?>
  <div class="det-stats">
    <div class="det-stats__title">
      <span class="material-symbols-rounded" style="font-size:13px;vertical-align:-2px">bar_chart</span>
      Statistiche &mdash; <?= htmlspecialchars($competizione['nome_competizione']) ?> <?= $stagione ?>
    </div>
    <div class="det-stats-grid">

      <!-- Numeri di sintesi -->
      <div class="det-stat-card">
        <span class="det-stat-card__label">Partite disputate</span>
        <span class="det-stat-card__value"><?= $stats_comp['total_matches'] ?></span>
        <span class="det-stat-card__sub"><?= number_format($stats_comp['avg_goals_per_match'], 2) ?> gol medi a partita</span>
      </div>

      <div class="det-stat-card">
        <span class="det-stat-card__label">Gol totali</span>
        <span class="det-stat-card__value"><?= (int)$stats_comp['total_goals'] ?></span>
      </div>

      <!-- Miglior punteggio individuale -->
      <?php if ($stats_comp['best_pt_single']): $bps = $stats_comp['best_pt_single']; ?>
      <div class="det-stat-card det-stat-card--accent">
        <span class="det-stat-card__label">Miglior punteggio</span>
        <span class="det-stat-card__value"><?= number_format($stats_comp['best_pt_single_val'], 1) ?></span>
        <span class="det-stat-card__sub">
          <?= htmlspecialchars($stats_comp['best_pt_single_team']) ?><br>
          vs <?= htmlspecialchars(
              $stats_comp['best_pt_single_team'] === $bps['nome_fantasquadra_casa']
              ? $bps['nome_fantasquadra_trasferta']
              : $bps['nome_fantasquadra_casa']
          ) ?>, G.<?= $bps['giornata'] ?>
        </span>
      </div>
      <?php endif; ?>

      <!-- Squadra più prolifica -->
      <?php if ($stats_comp['top_scorer_team']): ?>
      <div class="det-stat-card">
        <span class="det-stat-card__label">Più gol fatti</span>
        <span class="det-stat-card__value"><?= $stats_comp['top_scorer_gf'] ?></span>
        <span class="det-stat-card__sub"><?= htmlspecialchars($stats_comp['top_scorer_team']) ?></span>
      </div>
      <?php endif; ?>

      <!-- Miglior difesa -->
      <?php if ($stats_comp['best_defense_team']): ?>
      <div class="det-stat-card">
        <span class="det-stat-card__label">Miglior difesa</span>
        <span class="det-stat-card__value"><?= $stats_comp['best_defense_gs'] ?></span>
        <span class="det-stat-card__sub"><?= htmlspecialchars($stats_comp['best_defense_team']) ?> &mdash; gol subiti</span>
      </div>
      <?php endif; ?>

      <!-- Miglior punteggio totale squadra -->
      <?php if ($stats_comp['top_punteggio_team']): ?>
      <div class="det-stat-card">
        <span class="det-stat-card__label">Punteggio totale</span>
        <span class="det-stat-card__value"><?= number_format($stats_comp['top_punteggio_val'], 1) ?></span>
        <span class="det-stat-card__sub"><?= htmlspecialchars($stats_comp['top_punteggio_team']) ?></span>
      </div>
      <?php endif; ?>

      <!-- Partita più spettacolare (punteggio) -->
      <?php if ($stats_comp['best_match_pt']): $bmp = $stats_comp['best_match_pt']; ?>
      <div class="det-stat-match">
        <div class="det-stat-match__label">Partita con punteggio più alto</div>
        <div class="det-stat-match__body">
          <span class="det-stat-match__team"><?= htmlspecialchars($bmp['nome_fantasquadra_casa']) ?></span>
          <span class="det-stat-match__score">
            <?= number_format((float)$bmp['punteggio_casa'], 1) ?>&thinsp;+&thinsp;<?= number_format((float)$bmp['punteggio_trasferta'], 1) ?>
            <span style="font-size:0.75rem;color:var(--c-text-faint);font-weight:400;margin-left:4px">(<?= number_format($stats_comp['best_match_pt_sum'], 1) ?>)</span>
          </span>
          <span class="det-stat-match__team det-stat-match__team--right"><?= htmlspecialchars($bmp['nome_fantasquadra_trasferta']) ?></span>
        </div>
      </div>
      <?php endif; ?>

      <!-- Partita con più gol -->
      <?php if ($stats_comp['best_match_gol'] && $stats_comp['best_match_gol_sum'] > 0): $bmg = $stats_comp['best_match_gol']; ?>
      <div class="det-stat-match">
        <div class="det-stat-match__label">Partita con più gol &mdash; <?= $stats_comp['best_match_gol_sum'] ?> reti</div>
        <div class="det-stat-match__body">
          <span class="det-stat-match__team"><?= htmlspecialchars($bmg['nome_fantasquadra_casa']) ?></span>
          <span class="det-stat-match__score"><?= $bmg['gol_casa'] ?>&thinsp;&ndash;&thinsp;<?= $bmg['gol_trasferta'] ?></span>
          <span class="det-stat-match__team det-stat-match__team--right"><?= htmlspecialchars($bmg['nome_fantasquadra_trasferta']) ?></span>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
  <?php endif; ?>

  </div>
</section>

<!-- ── Nav: altre edizioni ───────────────────────────────────────────────────── -->
<?php
$altre_edizioni = $db->prepare("
    SELECT id_competizione_disputata, anno
    FROM competizione_disputata
    WHERE nome_competizione = ? AND id_competizione_disputata <> ?
    ORDER BY anno DESC
");
$altre_edizioni->execute([$competizione['nome_competizione'], $id]);
$altre = $altre_edizioni->fetchAll();
?>

<?php if (!empty($altre)): ?>
<section class="section section-alt">
  <div class="container">
    <h3 style="margin-bottom:var(--sp-5)">
      Altre edizioni: <?= htmlspecialchars($competizione['nome_competizione']) ?>
    </h3>
    <div style="display:flex;flex-wrap:wrap;gap:var(--sp-3)">
      <?php foreach ($altre as $a): ?>
      <a href="<?= BASE_PATH ?>public/dettagli-competizione.php?id=<?= $a['id_competizione_disputata'] ?>"
         class="btn btn-outline btn-sm">
        <?= anno_to_stagione((int)$a['anno']) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<script>
(function () {
  'use strict';

  /* Slider giornate — solo attivo per 'A Calendario' */
  if (typeof DET_MATCHES === 'undefined') return;

  var thumbMin = document.getElementById('det-range-min');
  var thumbMax = document.getElementById('det-range-max');
  var fill     = document.getElementById('det-range-fill');
  var display  = document.getElementById('det-range-display');
  var resetBtn = document.getElementById('det-range-reset');
  if (!thumbMin || !thumbMax) return;

  var minG = DET_MIN_G;
  var maxG = DET_MAX_G;
  var curMin = minG;
  var curMax = maxG;

  /* ── Aggiorna la barra colorata tra i due thumb ─────────── */
  function updateFill() {
    var range = maxG - minG || 1;
    var p1 = ((curMin - minG) / range * 100).toFixed(1);
    var p2 = ((curMax - minG) / range * 100).toFixed(1);
    fill.style.setProperty('--p1', p1 + '%');
    fill.style.setProperty('--p2', p2 + '%');
  }

  /* ── Aggiorna etichetta range ───────────────────────────── */
  function updateDisplay() {
    if (curMin === minG && curMax === maxG) {
      display.textContent = 'Tutte le giornate';
    } else {
      display.textContent = 'G. ' + curMin + '–G. ' + curMax;
    }
    resetBtn.style.visibility = (curMin === minG && curMax === maxG) ? 'hidden' : 'visible';
  }

  /* ── Ricalcola classifica da partite filtrate ───────────── */
  function recalc(filtered) {
    var cls = {};
    filtered.forEach(function (m) {
      [m.casa, m.tra].forEach(function (t) {
        if (!cls[t]) cls[t] = {nome: t, g: 0, v: 0, n: 0, p: 0, gf: 0, gs: 0, pt: 0, pt_tot: 0};
      });
      cls[m.casa].g++; cls[m.tra].g++;
      cls[m.casa].gf += m.gc; cls[m.casa].gs += m.gt;
      cls[m.tra].gf  += m.gt; cls[m.tra].gs  += m.gc;
      cls[m.casa].pt_tot += m.pc; cls[m.tra].pt_tot += m.pt;
      if (m.gc > m.gt) {
        cls[m.casa].v++; cls[m.casa].pt += 3; cls[m.tra].p++;
      } else if (m.gt > m.gc) {
        cls[m.tra].v++; cls[m.tra].pt += 3; cls[m.casa].p++;
      } else {
        cls[m.casa].n++; cls[m.casa].pt++;
        cls[m.tra].n++;  cls[m.tra].pt++;
      }
    });
    return Object.values(cls).sort(function (a, b) {
      return (b.pt - a.pt) || (b.pt_tot - a.pt_tot) || ((b.gf - b.gs) - (a.gf - a.gs)) || (b.gf - a.gf);
    });
  }

  /* ── Escape HTML ─────────────────────────────────────────── */
  function esc(s) {
    return String(s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  /* ── Ridisegna la tbody classifica ──────────────────────── */
  function renderStandings(sorted) {
    var tbody = document.getElementById('standings-tbody');
    if (!tbody) return;
    var many   = sorted.length > 1;
    var maxPt  = many ? sorted.reduce(function(a, s) { return Math.max(a, s.pt_tot); }, -Infinity) : 0;
    var minPt  = many ? sorted.reduce(function(a, s) { return Math.min(a, s.pt_tot); }, Infinity)  : 0;
    var html = '';
    sorted.forEach(function (sq, i) {
      var rank = i + 1;
      var isW  = sq.nome === DET_VINCITORE;
      var bg   = isW ? ' style="background:oklch(76% 0.162 76 / 0.06)"' : '';
      var fw   = isW ? '700' : '400';
      var ico  = isW
        ? '<span class="material-symbols-rounded" style="font-size:14px;vertical-align:-2px;' +
          'color:var(--c-gold);font-variation-settings:\'FILL\' 1">emoji_events</span>'
        : '';
      var rkClass = rank <= 3 ? 'rank-cell rank-' + rank : 'rank-cell';
      var ptColor = many
        ? (sq.pt_tot >= maxPt ? 'color:var(--c-success)' : (sq.pt_tot <= minPt ? 'color:var(--c-danger)' : ''))
        : '';
      html += '<tr' + bg + '>';
      html += '<td class="' + rkClass + '">' + rank + '</td>';
      html += '<td><span style="font-weight:' + fw + '">' + esc(sq.nome) + '</span>' + ico + '</td>';
      html += '<td style="text-align:center;color:var(--c-text-muted)">' + sq.g + '</td>';
      html += '<td style="text-align:center;color:var(--c-success)">' + sq.v + '</td>';
      html += '<td style="text-align:center;color:var(--c-text-muted)">' + sq.n + '</td>';
      html += '<td style="text-align:center;color:var(--c-danger)">' + sq.p + '</td>';
      html += '<td style="text-align:center">' + sq.gf + '</td>';
      html += '<td style="text-align:center">' + sq.gs + '</td>';
      html += '<td style="text-align:right;font-weight:700">' + sq.pt + '</td>';
      html += '<td style="text-align:right;font-weight:600;' + ptColor + '">' + sq.pt_tot.toFixed(2) + '</td>';
      html += '</tr>';
    });
    tbody.innerHTML = html;
  }

  /* ── Applica filtro ─────────────────────────────────────── */
  function applyFilter() {
    var filtered = DET_MATCHES.filter(function (m) {
      return m.g >= curMin && m.g <= curMax;
    });
    renderStandings(recalc(filtered));
    document.querySelectorAll('.det-matchday[data-giornata]').forEach(function (el) {
      var g = parseInt(el.dataset.giornata, 10);
      el.hidden = (g < curMin || g > curMax);
    });
    updateDisplay();
    updateFill();
  }

  thumbMin.addEventListener('input', function () {
    curMin = Math.min(parseInt(thumbMin.value, 10), curMax);
    thumbMin.value = curMin;
    applyFilter();
  });

  thumbMax.addEventListener('input', function () {
    curMax = Math.max(parseInt(thumbMax.value, 10), curMin);
    thumbMax.value = curMax;
    applyFilter();
  });

  resetBtn.addEventListener('click', function () {
    curMin = minG; curMax = maxG;
    thumbMin.value = minG; thumbMax.value = maxG;
    applyFilter();
  });

  /* Stato iniziale */
  updateFill();

}());

/* ── Slider gironi — solo per 'A Gruppi' ─────────────────────── */
(typeof GIRONE_MATCHES !== 'undefined') && (function () {
  'use strict';

  var thumbMin = document.getElementById('det-gj-range-min');
  var thumbMax = document.getElementById('det-gj-range-max');
  var fill     = document.getElementById('det-gj-range-fill');
  var display  = document.getElementById('det-gj-range-display');
  var resetBtn = document.getElementById('det-gj-range-reset');
  if (!thumbMin || !thumbMax) return;

  var minG   = GIRONE_MIN_G;
  var maxG   = GIRONE_MAX_G;
  var curMin = minG;
  var curMax = maxG;

  function updateFill() {
    var range = maxG - minG || 1;
    var p1 = ((curMin - minG) / range * 100).toFixed(1);
    var p2 = ((curMax - minG) / range * 100).toFixed(1);
    fill.style.setProperty('--p1', p1 + '%');
    fill.style.setProperty('--p2', p2 + '%');
  }

  function updateDisplay() {
    if (curMin === minG && curMax === maxG) {
      display.textContent = 'Tutte le giornate';
    } else {
      display.textContent = 'G. ' + curMin + '-G. ' + curMax;
    }
    resetBtn.style.visibility = (curMin === minG && curMax === maxG) ? 'hidden' : 'visible';
  }

  function recalcGirone(matches) {
    var cls = {};
    matches.forEach(function (m) {
      [m.casa, m.tra].forEach(function (t) {
        if (!cls[t]) cls[t] = {nome: t, g: 0, v: 0, n: 0, p: 0, gf: 0, gs: 0, pt: 0, pt_tot: 0};
      });
      cls[m.casa].g++; cls[m.tra].g++;
      cls[m.casa].gf += m.gc; cls[m.casa].gs += m.gt;
      cls[m.tra].gf  += m.gt; cls[m.tra].gs  += m.gc;
      cls[m.casa].pt_tot += m.pc; cls[m.tra].pt_tot += m.pt;
      if (m.gc > m.gt) {
        cls[m.casa].v++; cls[m.casa].pt += 3; cls[m.tra].p++;
      } else if (m.gt > m.gc) {
        cls[m.tra].v++; cls[m.tra].pt += 3; cls[m.casa].p++;
      } else {
        cls[m.casa].n++; cls[m.casa].pt++;
        cls[m.tra].n++;  cls[m.tra].pt++;
      }
    });
    return Object.values(cls).sort(function (a, b) {
      return (b.pt - a.pt) || (b.pt_tot - a.pt_tot) || ((b.gf - b.gs) - (a.gf - a.gs)) || (b.gf - a.gf);
    });
  }

  function esc(s) {
    return String(s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function renderGirone(name, sorted) {
    var tbody = document.getElementById('standings-girone-' + name);
    if (!tbody) return;
    var many  = sorted.length > 1;
    var maxPt = many ? sorted.reduce(function(a, s) { return Math.max(a, s.pt_tot); }, -Infinity) : 0;
    var minPt = many ? sorted.reduce(function(a, s) { return Math.min(a, s.pt_tot); }, Infinity)  : 0;
    var html = '';
    sorted.forEach(function (sq, i) {
      var isFirst = (i === 0);
      var bg = isFirst ? ' style="background:oklch(63% 0.150 145 / 0.06)"' : '';
      var fw = isFirst ? '600' : '400';
      var ptColor = many
        ? (sq.pt_tot >= maxPt ? 'color:var(--c-success)' : (sq.pt_tot <= minPt ? 'color:var(--c-danger)' : ''))
        : '';
      html += '<tr' + bg + '>';
      html += '<td class="rank-cell">' + (i + 1) + '</td>';
      html += '<td style="font-weight:' + fw + '">' + esc(sq.nome) + '</td>';
      html += '<td style="text-align:center;color:var(--c-text-muted)">' + sq.g + '</td>';
      html += '<td style="text-align:center;color:var(--c-success)">' + sq.v + '</td>';
      html += '<td style="text-align:center;color:var(--c-text-muted)">' + sq.n + '</td>';
      html += '<td style="text-align:center;color:var(--c-danger)">' + sq.p + '</td>';
      html += '<td style="text-align:right;font-weight:700">' + sq.pt + '</td>';
      html += '<td style="text-align:right;font-weight:600;' + ptColor + '">' + sq.pt_tot.toFixed(2) + '</td>';
      html += '</tr>';
    });
    tbody.innerHTML = html;
  }

  function applyGironeFilter() {
    var filtered = GIRONE_MATCHES.filter(function (m) {
      return m.g >= curMin && m.g <= curMax;
    });
    /* Raggruppa per girone e ricalcola ogni classifica */
    var byGirone = {};
    filtered.forEach(function (m) {
      if (!byGirone[m.gn]) byGirone[m.gn] = [];
      byGirone[m.gn].push(m);
    });
    GIRONE_NAMES.forEach(function (name) {
      renderGirone(name, recalcGirone(byGirone[name] || []));
    });
    /* Mostra/nascondi giornate */
    document.querySelectorAll('.det-girone-matchday[data-giornata]').forEach(function (el) {
      var g = parseInt(el.dataset.giornata, 10);
      el.hidden = (g < curMin || g > curMax);
    });
    updateDisplay();
    updateFill();
  }

  thumbMin.addEventListener('input', function () {
    curMin = Math.min(parseInt(thumbMin.value, 10), curMax);
    thumbMin.value = curMin;
    applyGironeFilter();
  });

  thumbMax.addEventListener('input', function () {
    curMax = Math.max(parseInt(thumbMax.value, 10), curMin);
    thumbMax.value = curMax;
    applyGironeFilter();
  });

  resetBtn.addEventListener('click', function () {
    curMin = minG; curMax = maxG;
    thumbMin.value = minG; thumbMax.value = maxG;
    applyGironeFilter();
  });

  updateFill();

}());
</script>

<!-- ── Formazioni schierate: stili, modale, logica ──────────────────────────── -->
<style>
.bracket-match.js-vedi-form{cursor:pointer;position:relative;transition:box-shadow .15s ease}
.bracket-match.js-vedi-form:hover{box-shadow:0 0 0 2px var(--c-accent,#4f46e5)}
.bracket-match.js-vedi-form::after{content:'formazioni';position:absolute;top:3px;right:6px;font-size:.55rem;text-transform:uppercase;letter-spacing:.04em;color:var(--c-accent,#4f46e5);opacity:.5;pointer-events:none}
.bracket-match.js-vedi-form:hover::after{opacity:1}
.fmz-modal{position:fixed;inset:0;z-index:1000;display:flex;align-items:center;justify-content:center;padding:16px}
.fmz-modal[hidden]{display:none}
.fmz-modal__backdrop{position:absolute;inset:0;background:rgba(0,0,0,.55)}
.fmz-modal__panel{position:relative;background:var(--c-bg,#fff);color:var(--c-text,#111);border:1px solid var(--c-border,#ddd);border-radius:var(--r-lg,12px);max-width:800px;width:100%;max-height:88vh;overflow:auto;padding:24px;box-shadow:0 20px 60px rgba(0,0,0,.35)}
.fmz-modal__close{position:absolute;top:8px;right:12px;background:none;border:none;font-size:1.7rem;line-height:1;cursor:pointer;color:var(--c-text-muted,#666)}
.fmz-modal__title{font-family:var(--font-heading,inherit);font-size:1.1rem;font-weight:700;margin:0 28px 12px 0}
.fmz-modal__title .fmz-meta{font-size:.78rem;font-weight:500;color:var(--c-text-muted,#666);display:block;margin-top:3px}
.fmz-tiebreak{background:rgba(79,70,229,.08);border:1px solid var(--c-accent,#4f46e5);border-radius:8px;padding:8px 12px;font-size:.85rem;margin-bottom:14px}
.fmz-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
@media(max-width:620px){.fmz-grid{grid-template-columns:1fr}}
.fmz-side__head{display:flex;align-items:center;gap:8px;margin-bottom:6px;padding-bottom:6px;border-bottom:1px solid var(--c-border,#ddd)}
.fmz-sq{font-weight:700}
.fmz-mod{font-size:.7rem;color:var(--c-text-muted,#666);background:var(--c-surface,#f1f1f1);padding:1px 6px;border-radius:6px}
.fmz-tot{margin-left:auto;font-weight:700}
.fmz-tab{width:100%;border-collapse:collapse;font-size:.82rem}
.fmz-tab td{padding:2px 4px;border-bottom:1px solid rgba(128,128,128,.18)}
.fmz-ruolo{color:var(--c-text-muted,#888);width:26px;font-weight:600}
.fmz-vb{text-align:right;width:36px}
.fmz-fv{text-align:right;width:36px;font-weight:700}
.fmz-green{color:#0a7d28}
.fmz-gray{color:#9aa0a6}
.fmz-pan .fmz-nome,.fmz-pan .fmz-ruolo{color:var(--c-text-muted,#888)}
.fmz-sub{background:rgba(10,125,40,.10)}
.fmz-sub .fmz-nome{color:var(--c-text,#111);font-weight:600}
.fmz-out .fmz-nome{color:var(--c-text-faint,#9aa0a6)}
.fmz-badge{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.03em;padding:1px 5px;border-radius:4px;white-space:nowrap;vertical-align:middle}
.fmz-badge--in{background:rgba(10,125,40,.18);color:#0a7d28}
.fmz-badge--out{background:rgba(128,128,128,.2);color:var(--c-text-muted,#777)}
.fmz-divider td{font-size:.66rem;text-transform:uppercase;letter-spacing:.05em;color:var(--c-text-muted,#888);padding-top:8px;font-weight:700;border-bottom:none}
.fmz-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:11px}
.fmz-chip{display:inline-flex;align-items:center;gap:7px;font-size:.68rem;font-weight:600;padding:3px 4px 3px 9px;border-radius:999px;background:rgba(128,128,128,.1);border:1px solid rgba(128,128,128,.16);color:var(--c-text-muted,#666)}
.fmz-chip__v{font-weight:800;padding:1px 8px;border-radius:999px;background:rgba(128,128,128,.18);color:var(--c-text,#111);font-variant-numeric:tabular-nums}
.fmz-chip--pos .fmz-chip__v{background:rgba(10,125,40,.16);color:#0a7d28}
.fmz-chip--neg .fmz-chip__v{background:rgba(220,53,69,.16);color:#dc3545}
.fmz-empty{font-size:.82rem;color:var(--c-text-muted,#888)}
.fmz-legend{margin-top:14px;font-size:.72rem;color:var(--c-text-muted,#888)}
.fmz-legend .fmz-green{font-weight:700}
</style>

<div id="fmz-modal" class="fmz-modal" hidden aria-hidden="true">
  <div class="fmz-modal__backdrop" data-fmz-close></div>
  <div class="fmz-modal__panel" role="dialog" aria-modal="true" aria-labelledby="fmz-title">
    <button class="fmz-modal__close" data-fmz-close aria-label="Chiudi">&times;</button>
    <div id="fmz-title" class="fmz-modal__title"></div>
    <div id="fmz-tiebreak" class="fmz-tiebreak" hidden></div>
    <div id="fmz-body" class="fmz-grid"></div>
    <div class="fmz-legend"><span class="fmz-green">verde</span> = fantavoto che porta punteggio · voto base a sinistra</div>
  </div>
</div>

<script>
(function () {
  'use strict';
  var FS_BASE = '<?= BASE_PATH ?>';
  var modal = document.getElementById('fmz-modal');
  if (!modal) return;
  var elTitle = document.getElementById('fmz-title');
  var elBody  = document.getElementById('fmz-body');
  var elTb    = document.getElementById('fmz-tiebreak');

  function esc(s){ return String(s==null?'':s).replace(/[&<>"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];}); }
  function num(v){ return v==null ? '-' : (Math.round(v*100)/100); }

  function renderSide(side){
    if(!side){ return '<div class="fmz-side"><div class="fmz-side__head"><span class="fmz-sq">-</span></div><p class="fmz-empty">Formazione non disponibile.</p></div>'; }
    var h = '<div class="fmz-side"><div class="fmz-side__head"><span class="fmz-sq">'+esc(side.squadra)+'</span>';
    if(side.modulo) h += '<span class="fmz-mod">'+esc(side.modulo)+'</span>';
    if(side.totale!=null) h += '<span class="fmz-tot">'+num(side.totale)+'</span>';
    h += '</div><table class="fmz-tab"><tbody>';
    var pan = false;
    side.giocatori.forEach(function(p){
      if(!p.titolare && !pan){ h += '<tr class="fmz-divider"><td colspan="4">Panchina</td></tr>'; pan = true; }
      var cls = p.conta ? 'fmz-green' : 'fmz-gray';
      var rowCls = p.titolare ? 'fmz-tit' : 'fmz-pan';
      var badge = '';
      if(!p.titolare && p.conta){                                  // panchinaro subentrato (cella verde)
        rowCls += ' fmz-sub';
        badge = ' <span class="fmz-badge fmz-badge--in">subentrato</span>';
      } else if(p.titolare && p.voto==null && p.fantavoto==null){   // titolare senza voto (-, sv)
        rowCls += ' fmz-out';
        badge = ' <span class="fmz-badge fmz-badge--out">sv</span>';
      }
      h += '<tr class="'+rowCls+'">'+
           '<td class="fmz-ruolo">'+esc(p.ruolo)+'</td>'+
           '<td class="fmz-nome">'+esc(p.nome)+badge+'</td>'+
           '<td class="fmz-vb">'+num(p.voto)+'</td>'+
           '<td class="fmz-fv '+cls+'">'+num(p.fantavoto)+'</td></tr>';
    });
    h += '</tbody></table>';
    var chips='';
    [['Mod. difesa', side.mod_difesa], ['Mod. capitano', side.mod_capitano]].forEach(function(m){
      if(m[1]==null) return;
      var k = m[1]>0 ? 'fmz-chip--pos' : (m[1]<0 ? 'fmz-chip--neg' : '');
      chips += '<span class="fmz-chip '+k+'"><span class="fmz-chip__k">'+m[0]+'</span>'+
               '<span class="fmz-chip__v">'+(m[1]>0?'+':'')+num(m[1])+'</span></span>';
    });
    if(chips) h += '<div class="fmz-chips">'+chips+'</div>';
    return h + '</div>';
  }

  function open(id){
    elTitle.textContent = 'Caricamento…';
    elBody.innerHTML = ''; elTb.hidden = true; elTb.innerHTML = '';
    modal.hidden = false; modal.setAttribute('aria-hidden','false'); document.body.style.overflow = 'hidden';
    fetch(FS_BASE + 'api/formazioni.php?id_partita=' + encodeURIComponent(id))
      .then(function(r){ return r.json(); })
      .then(function(d){
        if(!d || d.error){ elTitle.textContent = (d && d.error) || 'Errore.'; return; }
        var m = d.match;
        elTitle.innerHTML = esc(m.casa)+' <b>'+m.gol_casa+'-'+m.gol_trasf+'</b> '+esc(m.trasf)+
          '<span class="fmz-meta">Giornata '+m.giornata+' · '+esc(m.tipologia)+(m.girone?(' · Girone '+esc(m.girone)):'')+'</span>';
        if(d.tiebreak){
          var t = d.tiebreak, s = '';
          if(t.metodo==='supplementari'){
            s = '<b>Supplementari:</b> '+esc(m.casa)+' +'+t.suppl_casa+', '+esc(m.trasf)+' +'+t.suppl_trasf+
                ' → <b>'+t.tot_casa+'-'+t.tot_trasf+'</b>. Vince '+esc(t.vincitore)+'.';
          } else if(t.metodo==='rigori'){
            s = '<b>Supplementari</b> ancora pari ('+t.tot_casa+'-'+t.tot_trasf+'), poi <b>rigori '+
                t.rigori.casa+'-'+t.rigori.trasf+'</b>. Vince '+esc(t.vincitore)+'.';
          }
          if(s){ elTb.innerHTML = s; elTb.hidden = false; }
        }
        elBody.innerHTML = renderSide(d.casa) + renderSide(d.trasf);
      })
      .catch(function(){ elTitle.textContent = 'Errore nel caricamento delle formazioni.'; });
  }
  function close(){ modal.hidden = true; modal.setAttribute('aria-hidden','true'); document.body.style.overflow = ''; }

  document.addEventListener('click', function(e){
    var card = e.target.closest('.js-vedi-form');
    if(card && !e.target.closest('a')){ e.preventDefault(); open(card.getAttribute('data-partita')); return; }
    if(e.target.closest('[data-fmz-close]')){ close(); }
  });
  document.addEventListener('keydown', function(e){ if(e.key==='Escape' && !modal.hidden) close(); });
  document.querySelectorAll('.js-vedi-form').forEach(function(el){ el.title = 'Clicca per vedere le formazioni schierate'; });
}());
</script>

<?php layout_end(); ?>
