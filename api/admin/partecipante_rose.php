<?php
/**
 * api/admin/partecipante_rose.php — Rose di una fantasquadra (AJAX)
 *
 * GET ?fq=NomeFantasquadra
 * Restituisce JSON: [{anno, anno_label, modalita, num_giocatori, crediti_totali}, …]
 */

declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

// _auth.php already sets Content-Type: application/json
$fq = trim($_GET['fq'] ?? '');
if ($fq === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Parametro fq mancante.']);
    exit;
}

$stmt = $db->prepare("
    SELECT r.id_rosa, r.anno, r.modalita,
           COUNT(dr.codice_fantacalcio) AS num_giocatori,
           COALESCE(SUM(dr.crediti_pagati), 0) AS crediti_totali
      FROM rosa r
 LEFT JOIN dettagli_rosa dr ON dr.id_rosa = r.id_rosa
     WHERE r.nome_fantasquadra = ?
     GROUP BY r.id_rosa, r.anno, r.modalita
     ORDER BY r.anno DESC, r.modalita ASC
");
$stmt->execute([$fq]);
$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

$result = [];
foreach ($rows as $r) {
    $anno     = (int)$r['anno'];
    $anno_end = substr((string)($anno + 1), 2);
    $result[] = [
        'anno'          => $anno,
        'anno_label'    => $anno . '/' . $anno_end,
        'modalita'      => ucfirst($r['modalita']),
        'num_giocatori' => (int)$r['num_giocatori'],
        'crediti_totali'=> (int)$r['crediti_totali'],
    ];
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
