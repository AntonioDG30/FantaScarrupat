<?php
/**
 * app/admin/lib/tiebreak.php — Calcolo del vincitore, supplementari e rigori.
 *
 * Logica unica usata sia dall'import calendario sia dall'azione "Calcola vincitore"
 * dell'admin (prima era duplicata). Per le competizioni a eliminazione diretta, su
 * finale in parità applica i supplementari e gli eventuali rigori secondo l'art. 9.5
 * del regolamento, leggendo le formazioni schierate dal DB.
 */
declare(strict_types=1);

/**
 * Gol da media supplementari (art. 9.5.1):
 * 0–6.49 → 0 ; 6.50–6.99 → 1 ; 7.00–7.49 → 2 ; +0.50 → +1 gol.
 */
function fs_gol_da_media(float $m): int
{
    return $m < 6.5 ? 0 : (int)floor(($m - 6.5) / 0.5) + 1;
}

/**
 * Gol supplementari di una squadra: media dei migliori 4 panchinari (esclusi i
 * portieri) NON subentrati, integrando con voti d'ufficio 5,5 fino a 4.
 * @param array $giocatori righe formazione_giocatore (chiavi: ruolo, titolare, conta, fantavoto)
 */
function fs_form_suppl_gol(array $giocatori): int
{
    $cand = [];
    foreach ($giocatori as $p) {
        if (!empty($p['titolare'])) continue;          // solo panchina
        if ($p['ruolo'] === 'P' || $p['ruolo'] === 'Por') continue; // escluso portiere
        if (!empty($p['conta'])) continue;             // subentrato → escluso
        if ($p['fantavoto'] === null || $p['fantavoto'] === '') continue;
        $cand[] = (float)$p['fantavoto'];
    }
    rsort($cand);
    $top = array_slice($cand, 0, 4);
    while (count($top) < 4) $top[] = 5.5;              // voti d'ufficio
    return fs_gol_da_media(array_sum($top) / 4);
}

/**
 * Sequenza dei voti puri dei rigoristi (art. 9.5.2):
 * attaccanti titolari, poi centrocampisti, difensori, portiere, poi subentrati.
 * Le righe devono essere già ordinate per `ordine`.
 */
function fs_form_sequenza_rigoristi(array $giocatori): array
{
    $tit = array_values(array_filter($giocatori, fn($p) => !empty($p['titolare'])));
    $sub = array_values(array_filter($giocatori, fn($p) => empty($p['titolare']) && !empty($p['conta'])));

    $seq = [];
    foreach (['A', 'Pc', 'C', 'T', 'W', 'M', 'E', 'D', 'Dc', 'Dd', 'Ds', 'B', 'P', 'Por'] as $ruolo) {
        foreach ($tit as $p) {
            if ($p['ruolo'] === $ruolo && $p['voto_base'] !== null && $p['voto_base'] !== '') {
                $seq[] = (float)$p['voto_base'];
            }
        }
    }
    foreach ($sub as $p) {
        if ($p['voto_base'] !== null && $p['voto_base'] !== '') $seq[] = (float)$p['voto_base'];
    }
    return $seq;
}

/**
 * Esegue i rigori: voto puro ≥ 6 segna, < 6 sbaglia. 5 tiri a testa, poi a oltranza.
 * @return array [gol_casa, gol_trasferta, 'casa'|'trasf']
 */
function fs_form_rigori(array $gCasa, array $gTrasf): array
{
    $sC = fs_form_sequenza_rigoristi($gCasa);
    $sT = fs_form_sequenza_rigoristi($gTrasf);

    $gc = 0; $gt = 0;
    for ($i = 0; $i < 5; $i++) {
        if (($sC[$i] ?? 0) >= 6) $gc++;
        if (($sT[$i] ?? 0) >= 6) $gt++;
    }
    if ($gc !== $gt) return [$gc, $gt, $gc > $gt ? 'casa' : 'trasf'];

    // oltranza: un rigore a testa finché qualcuno prevale
    $n = max(count($sC), count($sT));
    for ($i = 5; $i < $n; $i++) {
        $c = (($sC[$i] ?? 0) >= 6) ? 1 : 0;
        $t = (($sT[$i] ?? 0) >= 6) ? 1 : 0;
        $gc += $c; $gt += $t;
        if ($c !== $t) return [$gc, $gt, $c > $t ? 'casa' : 'trasf'];
    }
    return [$gc, $gt, $gc >= $gt ? 'casa' : 'trasf']; // parità totale (rarissimo)
}

/**
 * Carica i giocatori di una formazione (o null se non presente).
 */
function fs_carica_formazione(\PDO $db, int $id_cd, int $giornata, string $tipologia, string $squadra): ?array
{
    $f = $db->prepare("SELECT id_formazione FROM formazione
        WHERE id_competizione_disputata = ? AND giornata = ? AND tipologia = ? AND nome_fantasquadra = ?");
    $f->execute([$id_cd, $giornata, $tipologia, $squadra]);
    $idf = $f->fetchColumn();
    if ($idf === false) return null;

    $g = $db->prepare("SELECT ruolo, nome_giocatore, titolare, conta, voto_base, fantavoto
        FROM formazione_giocatore WHERE id_formazione = ? ORDER BY ordine");
    $g->execute([(int)$idf]);
    return $g->fetchAll(\PDO::FETCH_ASSOC);
}

/**
 * Raggruppa le partite di una fase in sfide andata/ritorno per coppia di squadre.
 * @return array di ['andata'=>row, 'ritorno'=>row|null] (andata = giornata minore)
 */
function fs_raggruppa_sfide(array $partite): array
{
    $map = [];
    foreach ($partite as $p) {
        $t = [$p['nome_fantasquadra_casa'], $p['nome_fantasquadra_trasferta']];
        sort($t);
        $map[$t[0] . '|' . $t[1]][] = $p;
    }
    $sfide = [];
    foreach ($map as $ps) {
        usort($ps, fn($a, $b) => (int)$a['giornata'] <=> (int)$b['giornata']);
        $sfide[] = ['andata' => $ps[0] ?? null, 'ritorno' => $ps[1] ?? null];
    }
    return $sfide;
}

/**
 * Risolve una sfida a eliminazione (1 o 2 gare). Il tie-break (supplementari →
 * rigori) si calcola dalla gara di RITORNO — o dall'unica gara — trattandola come
 * una finale, leggendo le formazioni schierate. NON scrive sul DB.
 *
 * @return array {
 *   teamA, teamB,                  // squadre (dall'andata: casa, trasferta)
 *   aggA, aggB,                    // gol aggregati nei tempi regolamentari
 *   suppA, suppB,                  // gol supplementari (o null)
 *   rigA, rigB,                    // rigori (o null), riferiti a teamA/teamB
 *   winner,                        // squadra vincente o null
 *   metodo,                        // regolamentari|supplementari|rigori|pareggio
 *   leg,                           // riga della gara decisiva (per id_partita)
 *   has_form                       // true se le formazioni della decisiva c'erano
 * }
 */
function fs_risolvi_sfida(\PDO $db, int $id_cd, array $andata, ?array $ritorno): array
{
    $teamA = $andata['nome_fantasquadra_casa'];
    $teamB = $andata['nome_fantasquadra_trasferta'];
    $leg   = $ritorno ?? $andata;   // gara decisiva per l'eventuale tie-break

    $goals = [$teamA => 0, $teamB => 0];
    foreach (array_filter([$andata, $ritorno]) as $g) {
        $goals[$g['nome_fantasquadra_casa']]      += (int)$g['gol_casa'];
        $goals[$g['nome_fantasquadra_trasferta']] += (int)$g['gol_trasferta'];
    }
    $aggA = $goals[$teamA]; $aggB = $goals[$teamB];

    $res = [
        'teamA' => $teamA, 'teamB' => $teamB,
        'aggA'  => $aggA,  'aggB'  => $aggB,
        'suppA' => null,   'suppB' => null,
        'rigA'  => null,   'rigB'  => null,
        'winner' => null,  'metodo' => 'regolamentari',
        'leg'    => $leg,  'has_form' => false,
    ];

    // 1) decisa nei tempi regolamentari (o all'aggregato andata+ritorno)
    if ($aggA !== $aggB) {
        $res['winner'] = $aggA > $aggB ? $teamA : $teamB;
        return $res;
    }

    // 2) parità → supplementari dalle formazioni della gara decisiva
    $formA = fs_carica_formazione($db, $id_cd, (int)$leg['giornata'], (string)$leg['tipologia'], $teamA);
    $formB = fs_carica_formazione($db, $id_cd, (int)$leg['giornata'], (string)$leg['tipologia'], $teamB);
    if ($formA === null || $formB === null) {
        $res['metodo'] = 'pareggio';   // non determinabile senza formazioni
        return $res;
    }
    $res['has_form'] = true;
    $sA = fs_form_suppl_gol($formA); $sB = fs_form_suppl_gol($formB);
    $res['suppA'] = $sA; $res['suppB'] = $sB;
    $totA = $aggA + $sA; $totB = $aggB + $sB;
    if ($totA !== $totB) {
        $res['metodo'] = 'supplementari';
        $res['winner'] = $totA > $totB ? $teamA : $teamB;
        return $res;
    }

    // 3) ancora parità → rigori (voto puro ≥ 6 segna)
    [$rc, $rt, $win] = fs_form_rigori($formA, $formB); // 'casa' = teamA
    $res['rigA'] = $rc; $res['rigB'] = $rt;
    $res['metodo'] = 'rigori';
    $res['winner'] = ($win === 'casa') ? $teamA : $teamB;
    return $res;
}

/**
 * Scrive i flag supplementari/rigori/gol_rigori sulla gara DECISIVA di ogni sfida
 * a eliminazione in parità (gironi esclusi). Idempotente: azzera i flag sulle
 * sfide decise nei regolamentari. I gol_rigori sono salvati relativi al
 * casa/trasferta FISICI della gara decisiva (come si aspetta la vista pubblica).
 */
function fs_aggiorna_flag_eliminatorie(\PDO $db, int $id_cd): void
{
    $rows = $db->prepare("
        SELECT id_partita, giornata, tipologia,
               nome_fantasquadra_casa, nome_fantasquadra_trasferta, gol_casa, gol_trasferta
          FROM partita_avvessario
         WHERE id_competizione_disputata = ?
           AND tipologia IN ('Ottavi','Quarti','Semifinali','Finale')
         ORDER BY giornata ASC, id_partita ASC");
    $rows->execute([$id_cd]);

    $byFase = [];
    foreach ($rows->fetchAll(\PDO::FETCH_ASSOC) as $p) {
        $byFase[$p['tipologia']][] = $p;
    }

    $upd = $db->prepare("UPDATE partita_avvessario
            SET supplementari = ?, rigori = ?, gol_rigori_casa = ?, gol_rigori_trasferta = ?
          WHERE id_partita = ?");

    foreach ($byFase as $partite) {
        foreach (fs_raggruppa_sfide($partite) as $s) {
            if (!$s['andata']) continue;
            $res   = fs_risolvi_sfida($db, $id_cd, $s['andata'], $s['ritorno']);
            $legId = (int)$res['leg']['id_partita'];

            if ($res['metodo'] === 'supplementari') {
                $upd->execute([1, 0, null, null, $legId]);
            } elseif ($res['metodo'] === 'rigori') {
                $legCasaIsA = ($res['leg']['nome_fantasquadra_casa'] === $res['teamA']);
                $rc = $legCasaIsA ? $res['rigA'] : $res['rigB'];
                $rt = $legCasaIsA ? $res['rigB'] : $res['rigA'];
                $upd->execute([1, 1, $rc, $rt, $legId]);
            } elseif ($res['metodo'] === 'regolamentari') {
                $upd->execute([0, 0, null, null, $legId]);
            }
            // 'pareggio' (parità senza formazioni): non tocco i flag esistenti
        }
    }
}

/**
 * Calcola il vincitore di un'edizione.
 * @return array ['vincitore'=>?string, 'metodo'=>string, 'dettaglio'=>?array, 'avviso'=>?string]
 *   metodo: 'classifica' | 'regolamentari' | 'supplementari' | 'rigori'
 */
function fs_calcola_vincitore(\PDO $db, int $id_cd): array
{
    $r = ['vincitore' => null, 'metodo' => '', 'dettaglio' => null, 'avviso' => null];

    $compStmt = $db->prepare("
        SELECT c.tipologia FROM competizione_disputata cd
          JOIN competizione c ON cd.nome_competizione = c.nome_competizione
         WHERE cd.id_competizione_disputata = ?");
    $compStmt->execute([$id_cd]);
    $tipoComp = $compStmt->fetchColumn();
    if ($tipoComp === false) { $r['avviso'] = 'Edizione non trovata.'; return $r; }

    // ── Campionato: classifica (3/1/0 punti, spareggio sul punteggio totale) ──
    if ($tipoComp === 'A Calendario') {
        $st = $db->prepare("
            SELECT squadra, SUM(punti) AS tp, SUM(ps) AS ts FROM (
              SELECT nome_fantasquadra_casa AS squadra,
                     SUM(CASE WHEN gol_casa > gol_trasferta THEN 3 WHEN gol_casa = gol_trasferta THEN 1 ELSE 0 END) AS punti,
                     SUM(punteggio_casa) AS ps
                FROM partita_avvessario WHERE id_competizione_disputata = ? GROUP BY nome_fantasquadra_casa
              UNION ALL
              SELECT nome_fantasquadra_trasferta,
                     SUM(CASE WHEN gol_trasferta > gol_casa THEN 3 WHEN gol_trasferta = gol_casa THEN 1 ELSE 0 END),
                     SUM(punteggio_trasferta)
                FROM partita_avvessario WHERE id_competizione_disputata = ? GROUP BY nome_fantasquadra_trasferta
            ) t GROUP BY squadra ORDER BY tp DESC, ts DESC LIMIT 1");
        $st->execute([$id_cd, $id_cd]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);
        if ($row) { $r['vincitore'] = $row['squadra']; $r['metodo'] = 'classifica'; }
        else $r['avviso'] = 'Nessuna partita su cui calcolare la classifica.';
        return $r;
    }

    // ── Eliminazione diretta ──────────────────────────────────────────────
    // 1) Aggiorna i flag tie-break su TUTTE le sfide (anche ottavi/quarti/semifinali)
    fs_aggiorna_flag_eliminatorie($db, $id_cd);

    // 2) Vincitore = vincitore della Finale (gara singola o andata/ritorno)
    $finRows = $db->prepare("
        SELECT id_partita, giornata, tipologia,
               nome_fantasquadra_casa, nome_fantasquadra_trasferta, gol_casa, gol_trasferta
          FROM partita_avvessario
         WHERE id_competizione_disputata = ? AND tipologia = 'Finale'
         ORDER BY giornata ASC, id_partita ASC");
    $finRows->execute([$id_cd]);
    $finPartite = $finRows->fetchAll(\PDO::FETCH_ASSOC);
    if (!$finPartite) { $r['avviso'] = 'Nessuna finale registrata per questa edizione.'; return $r; }

    $sfideFin = fs_raggruppa_sfide($finPartite);
    $sf  = end($sfideFin);   // l'ultima sfida finale registrata
    $res = fs_risolvi_sfida($db, $id_cd, $sf['andata'], $sf['ritorno']);

    if ($res['winner'] === null) {
        $r['avviso'] = ($res['metodo'] === 'pareggio')
            ? 'Finale in parità (' . $res['aggA'] . '-' . $res['aggB'] . '): per supplementari e rigori '
              . 'devi prima importare le formazioni di quella giornata.'
            : 'Finale ancora in parità: imposta il vincitore a mano.';
        return $r;
    }

    $r['vincitore'] = $res['winner'];
    $r['metodo']    = $res['metodo'];
    $r['dettaglio'] = [
        'gc'    => $res['aggA'], 'gt'    => $res['aggB'],
        'sc'    => $res['suppA'], 'st'   => $res['suppB'],
        'tot_c' => $res['aggA'] + (int)$res['suppA'],
        'tot_t' => $res['aggB'] + (int)$res['suppB'],
        'rc'    => $res['rigA'], 'rt'    => $res['rigB'],
    ];
    return $r;
}

/**
 * Compone una frase leggibile dall'esito di fs_calcola_vincitore().
 */
function fs_descrivi_esito_vincitore(array $res): string
{
    if ($res['vincitore'] === null) return (string)($res['avviso'] ?? 'Vincitore non determinabile.');
    $d = $res['dettaglio'] ?? [];
    switch ($res['metodo']) {
        case 'classifica':
            return "Vincitore: {$res['vincitore']} (primo in classifica).";
        case 'regolamentari':
            return "Vincitore: {$res['vincitore']} (finale {$d['gc']}-{$d['gt']}).";
        case 'supplementari':
            return "Vincitore: {$res['vincitore']} ai supplementari ({$d['tot_c']}-{$d['tot_t']}, dai {$d['gc']}-{$d['gt']} regolamentari).";
        case 'rigori':
            return "Vincitore: {$res['vincitore']} ai rigori ({$d['rc']}-{$d['rt']}).";
        default:
            return "Vincitore: {$res['vincitore']}.";
    }
}
