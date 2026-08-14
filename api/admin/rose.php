<?php
/**
 * api/admin/rose.php — CRUD rose (form POST → redirect)
 *
 * POST actions:
 *   crea_rosa   — crea record rosa se non esiste
 *   aggiungi    — aggiungi giocatore a rosa (dettagli_rosa)
 *   rimuovi     — rimuovi giocatore da rosa
 */

declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

// ── GET: export CSV di una rosa ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action  = $_GET['action'] ?? '';
    if ($action !== 'export') { http_response_code(405); exit; }

    $id_rosa = (int)($_GET['id_rosa'] ?? 0);
    if ($id_rosa === 0) { http_response_code(400); exit; }

    // Dati rosa
    $stmt = $db->prepare("
        SELECT r.nome_fantasquadra, r.anno,
               g.nome_completo,
               gs.squadra_reale,
               dr.crediti_pagati,
               GROUP_CONCAT(DISTINCT rl.nome ORDER BY rl.nome SEPARATOR ';' ) AS ruoli_classic,
               GROUP_CONCAT(DISTINCT rm.nome ORDER BY rm.nome SEPARATOR ';' ) AS ruoli_mantra
          FROM rosa r
          JOIN dettagli_rosa dr ON r.id_rosa = dr.id_rosa
          JOIN giocatore g ON g.codice_fantacalcio = dr.codice_fantacalcio
     LEFT JOIN giocatore_stagione gs ON gs.codice_fantacalcio = g.codice_fantacalcio AND gs.anno = r.anno
     LEFT JOIN ruoli_giocatori rg ON rg.id_giocatore_stagione = gs.id
     LEFT JOIN ruoli rl ON rl.id_ruolo = rg.id_ruolo AND rl.tipo = 'classic'
     LEFT JOIN ruoli_giocatori rm2 ON rm2.id_giocatore_stagione = gs.id
     LEFT JOIN ruoli rm ON rm.id_ruolo = rm2.id_ruolo AND rm.tipo = 'mantra'
         WHERE r.id_rosa = ?
         GROUP BY dr.id_rosa, dr.codice_fantacalcio, g.codice_fantacalcio, g.nome_completo, gs.squadra_reale, dr.crediti_pagati
         ORDER BY g.nome_completo ASC
    ");
    $stmt->execute([$id_rosa]);
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    if (empty($rows)) { http_response_code(404); exit; }

    $fq   = $rows[0]['nome_fantasquadra'];
    $anno = (int)$rows[0]['anno'];
    $fn   = preg_replace('/[^a-zA-Z0-9_\-]/', '_', "rosa_{$fq}_{$anno}") . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fn . '"');
    header('Cache-Control: no-store');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Giocatore', 'Squadra', 'Ruolo Classic', 'Ruoli Mantra', 'Crediti']);
    foreach ($rows as $r) {
        fputcsv($out, [
            csv_safe($r['nome_completo']),
            csv_safe($r['squadra_reale'] ?? ''),
            csv_safe($r['ruoli_classic'] ?? ''),
            csv_safe($r['ruoli_mantra']  ?? ''),
            (int)$r['crediti_pagati'],
        ]);
    }
    fclose($out);
    exit;
}

// Override content-type (non è AJAX)
header('Content-Type: text/html; charset=utf-8', true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

if (!$csrf_ok) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Token di sicurezza non valido.'];
    header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=rose');
    exit;
}

$action   = $_POST['action']   ?? '';
$redirect = $_POST['redirect'] ?? (BASE_PATH . 'app/admin/index.php?tab=rose');

// Sanitize redirect: solo path interni. Deve iniziare con un singolo '/'
// seguito da un carattere diverso da '/' o '\' (blocca '//evil' e '/\evil' →
// open redirect), e non contenere caratteri di controllo.
if (!preg_match('#^/[^/\\\\]#', $redirect) || preg_match('/[\x00-\x1f]/', $redirect)) {
    $redirect = BASE_PATH . 'app/admin/index.php?tab=rose';
}

// ── crea_rosa ─────────────────────────────────────────────────────────
if ($action === 'crea_rosa') {
    $fq      = trim($_POST['nome_fantasquadra'] ?? '');
    $anno    = (int)($_POST['anno']     ?? 0);
    $mod     = in_array($_POST['modalita'] ?? '', ['classic','mantra'], true) ? $_POST['modalita'] : 'classic';

    if ($fq === '' || $anno === 0) {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Parametri mancanti.'];
        header('Location: ' . $redirect); exit;
    }

    try {
        $db->prepare("
            INSERT IGNORE INTO rosa (nome_fantasquadra, anno, modalita) VALUES (?, ?, ?)
        ")->execute([$fq, $anno, $mod]);
        $_SESSION['admin_flash'] = ['type' => 'success', 'msg' => 'Rosa creata.'];
    } catch (\PDOException $e) {
        error_log('[FS rose] crea: ' . $e->getMessage());
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Errore DB.'];
    }
    header('Location: ' . $redirect);
    exit;
}

// ── aggiungi ──────────────────────────────────────────────────────────
if ($action === 'aggiungi') {
    $id_rosa = (int)($_POST['id_rosa']             ?? 0);
    $codice  = (int)($_POST['codice_fantacalcio']  ?? 0);
    $crediti = (int)($_POST['crediti_pagati']       ?? 0);

    if ($id_rosa === 0 || $codice === 0) {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Parametri mancanti.'];
        header('Location: ' . $redirect); exit;
    }

    try {
        $db->prepare("
            INSERT INTO dettagli_rosa (id_rosa, codice_fantacalcio, crediti_pagati)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE crediti_pagati = VALUES(crediti_pagati)
        ")->execute([$id_rosa, $codice, max(0, $crediti)]);
        $_SESSION['admin_flash'] = ['type' => 'success', 'msg' => 'Giocatore aggiunto.'];
    } catch (\PDOException $e) {
        error_log('[FS rose] aggiungi: ' . $e->getMessage());
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Errore DB. Giocatore già presente?'];
    }
    header('Location: ' . $redirect);
    exit;
}

// ── rimuovi ───────────────────────────────────────────────────────────
if ($action === 'rimuovi') {
    $id_rosa = (int)($_POST['id_rosa'] ?? 0);
    $codice  = (int)($_POST['codice']  ?? 0);

    if ($id_rosa === 0 || $codice === 0) {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Parametri mancanti.'];
        header('Location: ' . $redirect); exit;
    }

    $db->prepare("DELETE FROM dettagli_rosa WHERE id_rosa = ? AND codice_fantacalcio = ?")
       ->execute([$id_rosa, $codice]);

    $_SESSION['admin_flash'] = ['type' => 'success', 'msg' => 'Giocatore rimosso.'];
    header('Location: ' . $redirect);
    exit;
}

http_response_code(400);
echo 'Unknown action';
