<?php
/**
 * api/formazioni.php — Endpoint pubblico (sola lettura): formazioni di una partita.
 *
 * GET ?id_partita=N → JSON con le due formazioni schierate (XI + panchina + voti)
 * e, per le finali in parità, il dettaglio di supplementari/rigori.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/admin/lib/tiebreak.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$id_partita = filter_input(INPUT_GET, 'id_partita', FILTER_VALIDATE_INT);
if (!$id_partita) {
    http_response_code(400);
    echo json_encode(['error' => 'id_partita non valido']);
    exit;
}

$db = getDB();

$p = $db->prepare("
    SELECT id_competizione_disputata, giornata, tipologia,
           nome_fantasquadra_casa, nome_fantasquadra_trasferta,
           gol_casa, gol_trasferta, punteggio_casa, punteggio_trasferta, girone
      FROM partita_avvessario WHERE id_partita = ?");
$p->execute([$id_partita]);
$m = $p->fetch(\PDO::FETCH_ASSOC);
if (!$m) {
    http_response_code(404);
    echo json_encode(['error' => 'Partita non trovata']);
    exit;
}

/** Carica una formazione (lista giocatori completa) o null. */
function fs_api_load_side(\PDO $db, array $m, string $team): ?array
{
    $f = $db->prepare("SELECT id_formazione, modulo, modificatore_difesa, modificatore_capitano, totale, inserita_il
        FROM formazione WHERE id_competizione_disputata = ? AND giornata = ? AND tipologia = ? AND nome_fantasquadra = ?");
    $f->execute([$m['id_competizione_disputata'], $m['giornata'], $m['tipologia'], $team]);
    $fr = $f->fetch(\PDO::FETCH_ASSOC);
    if (!$fr) return null;

    $g = $db->prepare("SELECT ruolo, nome_giocatore, titolare, conta, voto_base, fantavoto
        FROM formazione_giocatore WHERE id_formazione = ? ORDER BY titolare DESC, ordine");
    $g->execute([(int)$fr['id_formazione']]);
    $players = $g->fetchAll(\PDO::FETCH_ASSOC);

    $map = fn(array $x) => [
        'ruolo'     => $x['ruolo'],
        'nome'      => $x['nome_giocatore'],
        'voto'      => $x['voto_base'] !== null ? (float)$x['voto_base'] : null,
        'fantavoto' => $x['fantavoto'] !== null ? (float)$x['fantavoto'] : null,
        'titolare'  => (bool)$x['titolare'],
        'conta'     => (bool)$x['conta'],
    ];

    return [
        'squadra'      => $team,
        'modulo'       => $fr['modulo'],
        'mod_difesa'   => $fr['modificatore_difesa'] !== null ? (float)$fr['modificatore_difesa'] : null,
        'mod_capitano' => $fr['modificatore_capitano'] !== null ? (float)$fr['modificatore_capitano'] : null,
        'totale'       => $fr['totale'] !== null ? (float)$fr['totale'] : null,
        'inserita'     => $fr['inserita_il'],
        'giocatori'    => array_map($map, $players),
    ];
}

$casa  = fs_api_load_side($db, $m, $m['nome_fantasquadra_casa']);
$trasf = fs_api_load_side($db, $m, $m['nome_fantasquadra_trasferta']);

$out = [
    'match' => [
        'casa'      => $m['nome_fantasquadra_casa'],
        'trasf'     => $m['nome_fantasquadra_trasferta'],
        'gol_casa'  => (int)$m['gol_casa'],
        'gol_trasf' => (int)$m['gol_trasferta'],
        'pt_casa'   => (float)$m['punteggio_casa'],
        'pt_trasf'  => (float)$m['punteggio_trasferta'],
        'giornata'  => (int)$m['giornata'],
        'tipologia' => $m['tipologia'],
        'girone'    => $m['girone'],
    ],
    'casa'     => $casa,
    'trasf'    => $trasf,
    'tiebreak' => null,
];

// Dettaglio supplementari/rigori: solo per finale in parità con formazioni presenti
if ($m['tipologia'] === 'Finale'
    && (int)$m['gol_casa'] === (int)$m['gol_trasferta']
    && $casa !== null && $trasf !== null) {

    $sC = fs_form_suppl_gol($casa['giocatori']);
    $sT = fs_form_suppl_gol($trasf['giocatori']);
    $tot_c = (int)$m['gol_casa'] + $sC;
    $tot_t = (int)$m['gol_trasferta'] + $sT;

    $tb = ['suppl_casa' => $sC, 'suppl_trasf' => $sT, 'tot_casa' => $tot_c, 'tot_trasf' => $tot_t, 'rigori' => null, 'metodo' => 'supplementari'];

    if ($tot_c === $tot_t) {
        [$rc, $rt, $win] = fs_form_rigori($casa['giocatori'], $trasf['giocatori']);
        $tb['rigori'] = ['casa' => $rc, 'trasf' => $rt];
        $tb['metodo'] = 'rigori';
        $tb['vincitore'] = $win === 'casa' ? $m['nome_fantasquadra_casa'] : $m['nome_fantasquadra_trasferta'];
    } else {
        $tb['vincitore'] = $tot_c > $tot_t ? $m['nome_fantasquadra_casa'] : $m['nome_fantasquadra_trasferta'];
    }
    $out['tiebreak'] = $tb;
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);
