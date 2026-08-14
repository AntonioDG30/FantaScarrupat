<?php
/**
 * public/dettagli-rosa.php — Rosa di una fantasquadra per stagione
 *
 * URL: ?squadra=<nome_fantasquadra>&anno=<anno>
 *   oppure: ?id=<id_rosa>
 */

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../config/database.php';

$db = getDB();

// ── Risolvi la rosa ────────────────────────────────────────────────────────────
$id_rosa_param = filter_input(INPUT_GET, 'id',    FILTER_VALIDATE_INT);
$squadra_param = trim(filter_input(INPUT_GET, 'squadra', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$anno_param    = filter_input(INPUT_GET, 'anno',  FILTER_VALIDATE_INT);

if ($id_rosa_param > 0) {
    $stmt = $db->prepare("SELECT id_rosa, nome_fantasquadra, anno, modalita FROM rosa WHERE id_rosa = ?");
    $stmt->execute([$id_rosa_param]);
} elseif ($squadra_param !== '' && $anno_param > 0) {
    $stmt = $db->prepare("SELECT id_rosa, nome_fantasquadra, anno, modalita FROM rosa
                          WHERE nome_fantasquadra = ? AND anno = ?");
    $stmt->execute([$squadra_param, $anno_param]);
} else {
    // Nessun parametro valido
    http_response_code(404);
    layout_start('Rosa non trovata', '');
    echo '<section class="section"><div class="container"><div class="alert alert-danger">'
       . '<span class="material-symbols-rounded">error</span>'
       . 'Rosa non trovata. Specifica <code>squadra</code> e <code>anno</code> nell\'URL.</div></div></section>';
    layout_end();
    exit;
}

$rosa = $stmt->fetch();

if (!$rosa) {
    http_response_code(404);
    layout_start('Rosa non trovata', '');
    echo '<section class="section"><div class="container"><div class="alert alert-danger">'
       . '<span class="material-symbols-rounded">error</span>'
       . 'Rosa non trovata. <a href="' . BASE_PATH . 'public/partecipanti.php">Torna ai partecipanti.</a></div></div></section>';
    layout_end();
    exit;
}

$id_rosa  = (int)$rosa['id_rosa'];
$modalita = $rosa['modalita'];
$stagione = anno_to_stagione((int)$rosa['anno']);

// ── Dati fantasquadra ─────────────────────────────────────────────────────────
$fsq = $db->prepare("SELECT * FROM fantasquadra WHERE nome_fantasquadra = ?");
$fsq->execute([$rosa['nome_fantasquadra']]);
$fantasquadra = $fsq->fetch();

// ── Giocatori della rosa ───────────────────────────────────────────────────────
// Per ogni giocatore: nome, ruolo/i per l'anno della rosa, squadra reale, crediti
$tipo_ruolo = $modalita === 'mantra' ? 'mantra' : 'classic';

$giocatori = $db->prepare("
    SELECT
        g.codice_fantacalcio,
        g.nome_completo,
        dr.crediti_pagati,
        gs.squadra_reale,
        GROUP_CONCAT(r.nome ORDER BY r.nome SEPARATOR ',') AS ruoli
    FROM dettagli_rosa dr
    JOIN giocatore g ON g.codice_fantacalcio = dr.codice_fantacalcio
    LEFT JOIN giocatore_stagione gs
          ON gs.codice_fantacalcio = dr.codice_fantacalcio AND gs.anno = ?
    LEFT JOIN ruoli_giocatori rg ON rg.id_giocatore_stagione = gs.id
    LEFT JOIN ruoli r ON r.id_ruolo = rg.id_ruolo AND r.tipo = ?
    WHERE dr.id_rosa = ?
    GROUP BY g.codice_fantacalcio, g.nome_completo, dr.crediti_pagati, gs.squadra_reale
    ORDER BY MIN(r.nome) IS NULL, MIN(r.nome), g.nome_completo
");
$giocatori->execute([$rosa['anno'], $tipo_ruolo, $id_rosa]);
$all_players = $giocatori->fetchAll();

// ── Aggregati ─────────────────────────────────────────────────────────────────
$totale_crediti = array_sum(array_column($all_players, 'crediti_pagati'));
$n_giocatori    = count($all_players);
$budget_totale  = 1000; // budget standard fantacalcio

// ── Raggruppa per ruolo principale ───────────────────────────────────────────
// Ordine display: P/Por → D/Dc/Dd/Ds/B → C/E/M/T/W → A/Pc
$order_classic = ['P' => 0, 'D' => 1, 'C' => 2, 'A' => 3];
$order_mantra  = ['Por' => 0, 'Dc' => 1, 'Dd' => 1, 'Ds' => 1, 'B' => 1,
                  'E' => 2, 'M' => 2, 'C' => 2, 'T' => 2, 'W' => 2,
                  'A' => 3, 'Pc' => 3];
$order_map     = $modalita === 'mantra' ? $order_mantra : $order_classic;

$per_ruolo = [];
foreach ($all_players as $p) {
    $ruoli_arr    = $p['ruoli'] ? explode(',', $p['ruoli']) : ['?'];
    $ruolo_primo  = $ruoli_arr[0];
    $slot         = $order_map[$ruolo_primo] ?? 9;
    $per_ruolo[$slot][] = array_merge($p, ['ruoli_arr' => $ruoli_arr, 'ruolo_primo' => $ruolo_primo]);
}
ksort($per_ruolo);

$label_slot_classic = [0 => 'Portieri', 1 => 'Difensori', 2 => 'Centrocampisti', 3 => 'Attaccanti', 9 => 'Senza ruolo'];
$label_slot_mantra  = [0 => 'Portieri', 1 => 'Difensori', 2 => 'Centrocampisti', 3 => 'Attaccanti', 9 => 'Senza ruolo'];
$labels             = $modalita === 'mantra' ? $label_slot_mantra : $label_slot_classic;

// ── Rose della stessa squadra (altre stagioni) ────────────────────────────────
$altre_rose = $db->prepare("
    SELECT id_rosa, anno FROM rosa
    WHERE nome_fantasquadra = ? AND id_rosa <> ?
    ORDER BY anno DESC
");
$altre_rose->execute([$rosa['nome_fantasquadra'], $id_rosa]);
$altre = $altre_rose->fetchAll();

// ── Parametri obbligatori (solo stagione corrente = CURRENT_ANNO → 2025/26) ───
$parametri = [];
if ((int)$rosa['anno'] === CURRENT_ANNO) {
    $pstmt = $db->prepare("
        SELECT pr.numero_parametro, pr.testo_parametro
        FROM user_parametro_assegnato upa
        JOIN parametri_rosa pr ON pr.id_parametro = upa.id_parametro
        JOIN users u ON u.id_user = upa.id_user
        WHERE u.nome_fantasquadra = ?
        ORDER BY pr.numero_parametro
    ");
    $pstmt->execute([$rosa['nome_fantasquadra']]);
    $parametri = $pstmt->fetchAll();
}

// ── Stagione in numeri ────────────────────────────────────────────────────────
$_anno_r  = (int)$rosa['anno'];
$_nome_sq = $rosa['nome_fantasquadra'];

// Trofei vinti in questa stagione
$_stmt_tr = $db->prepare("
    SELECT nome_competizione
    FROM competizione_disputata
    WHERE vincitore = ? AND anno = ?
    ORDER BY CASE nome_competizione
        WHEN 'Serie A'          THEN 1
        WHEN 'Champions League' THEN 2
        WHEN 'NBA'              THEN 3
        WHEN 'Supercoppa'       THEN 4
        ELSE 99 END
");
$_stmt_tr->execute([$_nome_sq, $_anno_r]);
$trofei_stagione = $_stmt_tr->fetchAll(PDO::FETCH_COLUMN);

// Tutte le competizioni (avversario) in cui questa squadra ha giocato
$_stmt_cn = $db->prepare("
    SELECT DISTINCT cd.nome_competizione
    FROM partita_avvessario pa
    JOIN competizione_disputata cd ON cd.id_competizione_disputata = pa.id_competizione_disputata
    WHERE cd.anno = ? AND (pa.nome_fantasquadra_casa = ? OR pa.nome_fantasquadra_trasferta = ?)
    ORDER BY CASE cd.nome_competizione
        WHEN 'Serie A'          THEN 1
        WHEN 'Champions League' THEN 2
        WHEN 'NBA'              THEN 3
        WHEN 'Supercoppa'       THEN 4
        ELSE 99 END, cd.nome_competizione
");
$_stmt_cn->execute([$_anno_r, $_nome_sq, $_nome_sq]);
$_comp_names = $_stmt_cn->fetchAll(PDO::FETCH_COLUMN);

// Priorità fasi per calcolo fase_uscita nelle competizioni ad eliminazione
$_fase_prio = ['Finale'=>6,'Semifinali'=>5,'Quarti'=>4,'Ottavi'=>3,'Fase a Gironi'=>2,'Calendario'=>1];

// Per ogni competizione: record partite + classifica completa
$comp_stats = [];
foreach ($_comp_names as $_cn) {
    // Tipologia della competizione (A Calendario / Eliminazione Diretta / ecc.)
    $_sq_t = $db->prepare("
        SELECT c.tipologia FROM competizione c
        JOIN competizione_disputata cd ON cd.nome_competizione = c.nome_competizione
        WHERE cd.nome_competizione = ? AND cd.anno = ? LIMIT 1
    ");
    $_sq_t->execute([$_cn, $_anno_r]);
    $_comp_tipo = (string)($_sq_t->fetchColumn() ?: '');

    // Partite di questa squadra in questa competizione (con fase/tipologia singola partita)
    $_sq = $db->prepare("
        SELECT pa.nome_fantasquadra_casa, pa.nome_fantasquadra_trasferta,
               pa.gol_casa, pa.gol_trasferta, pa.punteggio_casa, pa.punteggio_trasferta,
               pa.tipologia AS fase
        FROM partita_avvessario pa
        JOIN competizione_disputata cd ON cd.id_competizione_disputata = pa.id_competizione_disputata
        WHERE cd.nome_competizione = ? AND cd.anno = ?
          AND (pa.nome_fantasquadra_casa = ? OR pa.nome_fantasquadra_trasferta = ?)
    ");
    $_sq->execute([$_cn, $_anno_r, $_nome_sq, $_nome_sq]);
    $_pm_list = $_sq->fetchAll();

    $_v = $_p = $_s = $_gf = $_ga = 0;
    $_pts_arr = [];
    foreach ($_pm_list as $_pm) {
        $_ih = $_pm['nome_fantasquadra_casa'] === $_nome_sq;
        $_g1 = $_ih ? (int)$_pm['gol_casa']      : (int)$_pm['gol_trasferta'];
        $_g2 = $_ih ? (int)$_pm['gol_trasferta']  : (int)$_pm['gol_casa'];
        $_gf += $_g1; $_ga += $_g2;
        $_pts_arr[] = $_ih ? (float)$_pm['punteggio_casa'] : (float)$_pm['punteggio_trasferta'];
        if ($_g1 > $_g2) $_v++; elseif ($_g1 === $_g2) $_p++; else $_s++;
    }
    $_pl = $_v + $_p + $_s;
    if ($_pl === 0) continue;

    $_pt_tot = round(array_sum($_pts_arr), 2);
    $_avg    = round($_pt_tot / $_pl, 2);

    // Classifica completa (per posizione + confronto pt_totale)
    $_sq2 = $db->prepare("
        SELECT pa.nome_fantasquadra_casa, pa.nome_fantasquadra_trasferta,
               pa.gol_casa, pa.gol_trasferta, pa.punteggio_casa, pa.punteggio_trasferta
        FROM partita_avvessario pa
        JOIN competizione_disputata cd ON cd.id_competizione_disputata = pa.id_competizione_disputata
        WHERE cd.nome_competizione = ? AND cd.anno = ?
    ");
    $_sq2->execute([$_cn, $_anno_r]);
    $_clas = [];
    foreach ($_sq2->fetchAll() as $_p2) {
        $_h2 = $_p2['nome_fantasquadra_casa']; $_a2 = $_p2['nome_fantasquadra_trasferta'];
        if (!isset($_clas[$_h2])) $_clas[$_h2] = ['pt'=>0,'gf'=>0,'ga'=>0,'pt_tot'=>0.0];
        if (!isset($_clas[$_a2])) $_clas[$_a2] = ['pt'=>0,'gf'=>0,'ga'=>0,'pt_tot'=>0.0];
        $_clas[$_h2]['gf']     += (int)$_p2['gol_casa'];
        $_clas[$_h2]['ga']     += (int)$_p2['gol_trasferta'];
        $_clas[$_h2]['pt_tot'] += (float)$_p2['punteggio_casa'];
        $_clas[$_a2]['gf']     += (int)$_p2['gol_trasferta'];
        $_clas[$_a2]['ga']     += (int)$_p2['gol_casa'];
        $_clas[$_a2]['pt_tot'] += (float)$_p2['punteggio_trasferta'];
        if ((int)$_p2['gol_casa'] > (int)$_p2['gol_trasferta'])       $_clas[$_h2]['pt'] += 3;
        elseif ((int)$_p2['gol_casa'] === (int)$_p2['gol_trasferta']) { $_clas[$_h2]['pt']++; $_clas[$_a2]['pt']++; }
        else                                                            $_clas[$_a2]['pt'] += 3;
    }
    uasort($_clas, fn($x,$y) =>
        $y['pt'] !== $x['pt'] ? $y['pt'] <=> $x['pt'] : ($y['gf']-$y['ga']) <=> ($x['gf']-$x['ga'])
    );

    // Posizione finale: solo per competizioni a calendario
    $_pos_final = null;
    if ($_comp_tipo === 'A Calendario') {
        $_pos_n = 1;
        foreach ($_clas as $_t => $_) { if ($_t === $_nome_sq) { $_pos_final = $_pos_n; break; } $_pos_n++; }
    }

    // Fase di uscita: per competizioni ad eliminazione (la fase più avanzata giocata)
    // Viene mostrata solo se la squadra non ha vinto la competizione
    $_fase_uscita = null;
    if ($_comp_tipo !== 'A Calendario') {
        $_bf = -1;
        foreach ($_pm_list as $_pmf) {
            $_fp = $_fase_prio[$_pmf['fase']] ?? 0;
            if ($_fp > $_bf) { $_bf = $_fp; $_fase_uscita = $_pmf['fase']; }
        }
    }

    // Confronto pt_totale: verde = massimo, rosso = minimo (solo se almeno 2 squadre)
    $_all_pt = array_column($_clas, 'pt_tot');
    $_my_pt  = round($_clas[$_nome_sq]['pt_tot'] ?? 0.0, 2);
    $_is_max = count($_all_pt) > 1 && abs($_my_pt - max($_all_pt)) < 0.01;
    $_is_min = count($_all_pt) > 1 && abs($_my_pt - min($_all_pt)) < 0.01;

    $comp_stats[$_cn] = [
        'v' => $_v, 'p' => $_p, 's' => $_s,
        'gf' => $_gf, 'ga' => $_ga,
        'pt_totale'   => $_pt_tot,
        'avg_pts'     => $_avg,
        'played'      => $_pl,
        'tipo_comp'   => $_comp_tipo,
        'posizione'   => $_pos_final,
        'fase_uscita' => $_fase_uscita,
        'is_max'      => $_is_max,
        'is_min'      => $_is_min,
        'vinta'       => in_array($_cn, $trofei_stagione),
    ];
}

// Budget per reparto (da $per_ruolo già calcolato sopra)
$_slot_short = [0=>'P', 1=>'D', 2=>'C', 3=>'A', 9=>'?'];
$budget_ruoli = [];
foreach ($per_ruolo as $_sl => $_pls) {
    $_spesa = array_sum(array_column($_pls, 'crediti_pagati'));
    if ($_spesa <= 0) continue;
    $budget_ruoli[] = [
        'label' => $labels[$_sl] ?? 'Altro',
        'short' => $_slot_short[$_sl] ?? '?',
        'spesa' => $_spesa,
        'pct'   => $totale_crediti > 0 ? round($_spesa / $totale_crediti * 100) : 0,
    ];
}

$page_title = htmlspecialchars($rosa['nome_fantasquadra']) . ' · ' . $stagione;

// ── SEO / GEO: descrizione dinamica + dati strutturati SportsTeam ──
$_r_nome = (string)$rosa['nome_fantasquadra'];
$_r_all  = (string)($fantasquadra['fantallenatore'] ?? '');
$_r_desc = 'Rosa di ' . $_r_nome . ($_r_all !== '' ? ' (fantallenatore ' . $_r_all . ')' : '')
         . ' nella stagione ' . $stagione . ' di FantaScarrupat: ' . count($all_players) . ' giocatori, '
         . (int)$totale_crediti . ' crediti spesi, modalità ' . $rosa['modalita'] . '.';
$_r_team = [
    '@type'       => 'SportsTeam',
    'name'        => $_r_nome,
    'sport'       => 'Fantacalcio',
    'description' => $_r_desc,
    'memberOf'    => ['@id' => fs_url() . '#organization'],
];
if ($_r_all !== '') {
    $_r_team['coach'] = ['@type' => 'Person', 'name' => $_r_all];
}
if (!empty($fantasquadra['scudetto']) && $fantasquadra['scudetto'] !== 'NonDisponibile.png') {
    $_r_team['logo'] = fs_url('uploads/teams/' . $fantasquadra['scudetto']);
}
$_r_opts = [
    'description' => $_r_desc,
    'og_type'     => 'profile',
    'breadcrumb'  => [
        ['name' => 'Home',         'url' => 'public/index.php'],
        ['name' => 'Partecipanti', 'url' => 'public/partecipanti.php'],
        ['name' => $_r_nome . ' · ' . $stagione],
    ],
    'jsonld'      => [$_r_team],
];
if (!empty($fantasquadra['scudetto']) && $fantasquadra['scudetto'] !== 'NonDisponibile.png') {
    $_r_opts['og_image'] = 'uploads/teams/' . $fantasquadra['scudetto'];
}
layout_start($page_title, '', $_r_opts);
?>

<!-- ── Hero ──────────────────────────────────────────────────────────────────── -->
<section class="hero" aria-label="Rosa">
  <div class="container">

    <p class="hero__eyebrow">
      <a href="<?= BASE_PATH ?>public/partecipanti.php"
         style="color:var(--c-accent);text-decoration:none">Partecipanti</a>
      &nbsp;/&nbsp; <?= htmlspecialchars($rosa['nome_fantasquadra']) ?>
    </p>

    <h1 class="hero__title">
      <?= htmlspecialchars($rosa['nome_fantasquadra']) ?>
    </h1>

    <p class="hero__subtitle">
      Rosa stagione <?= $stagione ?>
      &nbsp;&middot;&nbsp;
      Modalità: <strong style="color:oklch(90% 0.010 250)">
        <?= $modalita === 'mantra' ? 'Mantra' : 'Classic' ?>
      </strong>
    </p>

    <?php if ($fantasquadra): ?>
    <p style="margin-top:var(--sp-4);font-size:0.9375rem;color:oklch(75% 0.015 250)">
      Fantallenatore: <?= htmlspecialchars($fantasquadra['fantallenatore']) ?>
    </p>
    <?php endif; ?>

  </div>
</section>

<!-- ── Rosa ───────────────────────────────────────────────────────────────────── -->
<section class="section">
  <div class="container">

    <!-- Meta bar -->
    <div class="rosa-meta">
      <div class="rosa-meta__item">
        <span class="label">Giocatori</span>
        <span class="value"><?= $n_giocatori ?></span>
      </div>
      <div class="rosa-meta__divider"></div>
      <div class="rosa-meta__item">
        <span class="label">Crediti spesi</span>
        <span class="value"><?= number_format($totale_crediti) ?></span>
      </div>
      <div class="rosa-meta__divider"></div>
      <div class="rosa-meta__item">
        <span class="label">Crediti rimasti</span>
        <span class="value"
              style="color:<?= ($budget_totale - $totale_crediti) >= 0 ? 'var(--c-success)' : 'var(--c-danger)' ?>">
          <?= $budget_totale - $totale_crediti ?>
        </span>
      </div>
      <div class="rosa-meta__divider"></div>
      <div class="budget-bar">
        <span class="label" style="font-size:0.75rem;font-weight:600;letter-spacing:0.06em;
                                   text-transform:uppercase;color:var(--c-text-muted)">
          Budget usato
        </span>
        <div class="budget-bar__track">
          <div class="budget-bar__fill"
               style="width:<?= min(100, round($totale_crediti / $budget_totale * 100)) ?>%"></div>
        </div>
        <span style="font-size:0.75rem;color:var(--c-text-muted)">
          <?= round($totale_crediti / $budget_totale * 100) ?>%
        </span>
      </div>
    </div>

    <!-- Tabella giocatori — unica tabella, separatori di ruolo come righe -->
    <div class="table-scroll">
      <table class="fs-table fs-table--rosa">
        <thead>
          <tr>
            <th>Giocatore</th>
            <th>Ruolo<?= $modalita === 'mantra' ? '/i' : '' ?></th>
            <th>Squadra reale</th>
            <th style="text-align:right">Crediti</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($per_ruolo as $slot => $players): ?>
          <tr class="rosa-role-sep">
            <td colspan="4">
              <?= $labels[$slot] ?? 'Altro' ?>
              <span style="font-weight:400;opacity:0.5;margin-left:var(--sp-2)">(<?= count($players) ?>)</span>
            </td>
          </tr>
          <?php foreach ($players as $p): ?>
          <tr>
            <td style="font-weight:500"><?= htmlspecialchars($p['nome_completo']) ?></td>
            <td>
              <?php foreach ($p['ruoli_arr'] as $r):
                $badge_class = 'badge-' . $r;
                if ($modalita === 'mantra' && in_array($r, ['C', 'A'])) {
                    $badge_class = 'badge-' . $r . '-m';
                }
              ?>
              <span class="badge <?= htmlspecialchars($badge_class) ?>" style="margin-right:2px">
                <?= htmlspecialchars($r) ?>
              </span>
              <?php endforeach; ?>
              <?php if ($p['ruoli_arr'] === ['?']): ?>
                <span class="badge badge-neutral">n.d.</span>
              <?php endif; ?>
            </td>
            <td style="color:var(--c-text-muted)">
              <?= $p['squadra_reale'] ? htmlspecialchars($p['squadra_reale']) : '' ?>
            </td>
            <td style="text-align:right;font-family:var(--font-heading);
                       font-size:1rem;font-weight:700;
                       color:<?= $p['crediti_pagati'] >= 100 ? 'var(--c-gold)' : 'var(--c-text)' ?>">
              <?= $p['crediti_pagati'] ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Parametri obbligatori (solo stagione corrente) -->
    <?php if (!empty($parametri)): ?>
    <div class="rosa-parametri">
      <h3 class="rosa-parametri__title">
        <span class="material-symbols-rounded" style="font-size:16px;vertical-align:-3px;font-variation-settings:'FILL' 1">rule</span>
        Parametri obbligatori
      </h3>
      <ol class="rosa-parametri__list">
        <?php foreach ($parametri as $par): ?>
        <li>
          <span class="rosa-parametri__num"><?= (int)$par['numero_parametro'] ?></span>
          <span class="rosa-parametri__text"><?= htmlspecialchars($par['testo_parametro']) ?></span>
        </li>
        <?php endforeach; ?>
      </ol>
    </div>
    <?php endif; ?>

    <!-- Stagione in numeri -->
    <?php
      // Trofei vinti in competizioni senza dati partita (edge case: visibili come pill standalone)
      $_trofei_standalone = array_values(array_filter($trofei_stagione, fn($_t) => !isset($comp_stats[$_t])));
      $_show_stagione = !empty($comp_stats) || !empty($_trofei_standalone) || !empty($budget_ruoli);
    ?>
    <?php if ($_show_stagione): ?>
    <div class="rosa-stagione">

      <div class="rosa-stagione__header">
        <span class="material-symbols-rounded" style="font-variation-settings:'FILL' 1">analytics</span>
        Stagione in numeri
      </div>

      <?php foreach ($comp_stats as $_cn => $_cs): ?>
      <div class="rosa-sn-comp<?= $_cs['vinta'] ? ' rosa-sn-comp--won' : '' ?>">

        <div class="rosa-sn-comp__head">
          <span class="rosa-sn-comp__name"><?= htmlspecialchars($_cn) ?></span>
          <?php if ($_cs['vinta']): ?>
          <span class="rosa-sn-comp__crown">
            <span class="material-symbols-rounded" style="font-size:12px;font-variation-settings:'FILL' 1">emoji_events</span>
            Campione
          </span>
          <?php elseif ($_cs['tipo_comp'] !== 'A Calendario' && $_cs['fase_uscita'] !== null): ?>
          <span class="rosa-sn-comp__elim">
            <span class="material-symbols-rounded" style="font-size:11px">close</span>
            <?= htmlspecialchars($_cs['fase_uscita']) ?>
          </span>
          <?php endif; ?>
          <span class="rosa-sn-comp__played"><?= $_cs['played'] ?> partite</span>
        </div>

        <div class="rosa-sn-record">
          <div class="rosa-sn-stat rosa-sn-stat--win">
            <span class="rosa-sn-stat__val"><?= $_cs['v'] ?></span>
            <span class="rosa-sn-stat__lbl">V</span>
          </div>
          <div class="rosa-sn-stat rosa-sn-stat--draw">
            <span class="rosa-sn-stat__val"><?= $_cs['p'] ?></span>
            <span class="rosa-sn-stat__lbl">P</span>
          </div>
          <div class="rosa-sn-stat rosa-sn-stat--loss">
            <span class="rosa-sn-stat__val"><?= $_cs['s'] ?></span>
            <span class="rosa-sn-stat__lbl">S</span>
          </div>

          <div class="rosa-sn-divider"></div>

          <div class="rosa-sn-stat">
            <span class="rosa-sn-stat__val"><?= $_cs['gf'] ?></span>
            <span class="rosa-sn-stat__lbl">GF</span>
          </div>
          <div class="rosa-sn-stat">
            <span class="rosa-sn-stat__val"><?= $_cs['ga'] ?></span>
            <span class="rosa-sn-stat__lbl">GS</span>
          </div>
          <?php $_dr = $_cs['gf'] - $_cs['ga']; ?>
          <div class="rosa-sn-stat">
            <span class="rosa-sn-stat__val"
                  style="color:<?= $_dr>0?'var(--c-success)':($_dr<0?'var(--c-danger)':'var(--c-text-muted)') ?>">
              <?= $_dr>0?'+'.$_dr:$_dr ?>
            </span>
            <span class="rosa-sn-stat__lbl">DR</span>
          </div>

          <div class="rosa-sn-divider"></div>

          <div class="rosa-sn-stat<?= $_cs['is_max']?' rosa-sn-stat--best':($_cs['is_min']?' rosa-sn-stat--worst':'') ?>">
            <span class="rosa-sn-stat__val"
                  style="color:<?= $_cs['is_max']?'var(--c-success)':($_cs['is_min']?'var(--c-danger)':'var(--c-text)') ?>">
              <?= number_format($_cs['pt_totale'], 2, '.', '') ?>
            </span>
            <span class="rosa-sn-stat__lbl">Pt tot</span>
          </div>

          <div class="rosa-sn-stat">
            <span class="rosa-sn-stat__val"><?= number_format($_cs['avg_pts'], 2, '.', '') ?></span>
            <span class="rosa-sn-stat__lbl">Media</span>
          </div>

          <?php if ($_cs['tipo_comp'] === 'A Calendario' && $_cs['posizione'] !== null): ?>
          <div class="rosa-sn-divider"></div>
          <div class="rosa-sn-stat<?= $_cs['posizione']===1?' rosa-sn-stat--pos1':'' ?>">
            <span class="rosa-sn-stat__val rosa-sn-pos"><?= $_cs['posizione'] ?>°</span>
            <span class="rosa-sn-stat__lbl">Pos.</span>
          </div>
          <?php endif; ?>
        </div>

      </div>
      <?php endforeach; ?>

      <?php if (!empty($_trofei_standalone)): ?>
      <div class="rosa-sn-comp">
        <div class="rosa-sn-comp__head">
          <span class="rosa-sn-comp__name">Trofei vinti</span>
        </div>
        <div class="rosa-sn-trofei">
          <?php foreach ($_trofei_standalone as $_t): ?>
          <span class="rosa-sn-trophy">
            <span class="material-symbols-rounded" style="font-size:13px;font-variation-settings:'FILL' 1">emoji_events</span>
            <?= htmlspecialchars($_t) ?>
          </span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($budget_ruoli) && $totale_crediti > 0): ?>
      <div class="rosa-sn-comp">
        <div class="rosa-sn-comp__head">
          <span class="rosa-sn-comp__name">Budget per reparto</span>
        </div>
        <div class="rosa-sn-budget">
          <?php foreach ($budget_ruoli as $_br): ?>
          <div class="rosa-sn-budget-row">
            <span class="rosa-sn-budget-row__label"><?= htmlspecialchars($_br['label']) ?></span>
            <div class="rosa-sn-budget-row__track">
              <div class="rosa-sn-budget-row__fill rosa-sn-budget-row__fill--<?= strtolower(htmlspecialchars($_br['short'])) ?>"
                   style="width:<?= $_br['pct'] ?>%"></div>
            </div>
            <span class="rosa-sn-budget-row__pct"><?= $_br['pct'] ?>%</span>
            <span class="rosa-sn-budget-row__val"><?= $_br['spesa'] ?> cr</span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div>
    <?php endif; ?>

    <!-- Altre stagioni -->
    <?php if (!empty($altre)): ?>
    <div style="border-top:1px solid var(--c-border);padding-top:var(--sp-8);margin-top:var(--sp-6)">
      <h3 style="font-size:1rem;margin-bottom:var(--sp-4)">
        Altre stagioni: <?= htmlspecialchars($rosa['nome_fantasquadra']) ?>
      </h3>
      <div style="display:flex;flex-wrap:wrap;gap:var(--sp-3)">
        <?php foreach ($altre as $a): ?>
        <a href="<?= BASE_PATH ?>public/dettagli-rosa.php?id=<?= $a['id_rosa'] ?>"
           class="btn btn-outline btn-sm">
          <?= anno_to_stagione((int)$a['anno']) ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</section>

<?php layout_end(); ?>
