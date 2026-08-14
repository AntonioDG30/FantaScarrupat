<?php
/**
 * api/admin/giocatori_search.php — Autocomplete giocatori per sezione Rose
 *
 * GET ?q=testo&anno=2026
 * Risponde: [{codice_fantacalcio, nome_completo, squadra_reale, ruolo_classic, ruoli_mantra: [...]}, ...]
 * Max 30 risultati.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(fs_session_cookie_params());
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (empty($_SESSION['user_id']) || empty($_SESSION['flag_admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$q    = trim($_GET['q']    ?? '');
$anno = (int)($_GET['anno'] ?? CURRENT_ANNO);

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$db = getDB();

$stmt = $db->prepare("
    SELECT g.codice_fantacalcio, g.nome_completo, gs.id AS gs_id, gs.squadra_reale
      FROM giocatore g
      JOIN giocatore_stagione gs ON gs.codice_fantacalcio = g.codice_fantacalcio AND gs.anno = ?
     WHERE g.nome_completo LIKE ?
     ORDER BY g.nome_completo ASC
     LIMIT 30
");
$stmt->execute([$anno, '%' . $q . '%']);
$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo json_encode([]);
    exit;
}

// Fetch ruoli per ogni giocatore trovato
$gs_ids = array_column($rows, 'gs_id');
$ph     = implode(',', array_fill(0, count($gs_ids), '?'));
$rr     = $db->prepare("
    SELECT rg.id_giocatore_stagione, r.nome, r.tipo
      FROM ruoli_giocatori rg
      JOIN ruoli r ON r.id_ruolo = rg.id_ruolo
     WHERE rg.id_giocatore_stagione IN ($ph)
");
$rr->execute($gs_ids);

$ruoli_by_gs = [];
foreach ($rr->fetchAll(\PDO::FETCH_ASSOC) as $r) {
    $id = $r['id_giocatore_stagione'];
    if ($r['tipo'] === 'classic') {
        $ruoli_by_gs[$id]['classic'] = $r['nome'];
    } else {
        $ruoli_by_gs[$id]['mantra'][] = $r['nome'];
    }
}

$result = [];
foreach ($rows as $g) {
    $gs_id = $g['gs_id'];
    $result[] = [
        'codice_fantacalcio' => (int)$g['codice_fantacalcio'],
        'nome_completo'      => $g['nome_completo'],
        'squadra_reale'      => $g['squadra_reale'],
        'ruolo_classic'      => $ruoli_by_gs[$gs_id]['classic'] ?? null,
        'ruoli_mantra'       => $ruoli_by_gs[$gs_id]['mantra']  ?? [],
    ];
}

echo json_encode($result);
