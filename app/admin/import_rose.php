<?php
/**
 * app/admin/import_rose.php — Import rose da CSV
 *
 * Formato CSV atteso (no header): NomeFantasquadra, CodiceFantacalcio, CreditiPagati
 * Un file può contenere rose di più squadre e più stagioni.
 *
 * POST params:
 *   csrf_token   — token CSRF
 *   anno         — anno rosa (default: CURRENT_ANNO)
 *   modalita     — 'classic' | 'mantra' (default: 'classic')
 *   file_rose    — file CSV
 */

declare(strict_types=1);

require_once __DIR__ . '/../../auth/require_login.php';
require_once __DIR__ . '/../../auth/require_admin.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/archive.php';

// Hosting lento (Altervista): margine di tempo/memoria.
@set_time_limit(120);
@ini_set('memory_limit', '256M');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); exit('Method not allowed');
}

$token = $_POST['csrf_token'] ?? '';
if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token)) {
    http_response_code(403); exit('CSRF mismatch');
}

$anno    = (int)($_POST['anno']     ?? CURRENT_ANNO);
$modalita = in_array($_POST['modalita'] ?? 'classic', ['classic', 'mantra'], true)
    ? ($_POST['modalita'] ?? 'classic')
    : 'classic';

$redirect = BASE_PATH . 'app/admin/index.php?tab=rose';

// Validazione upload
if (!isset($_FILES['file_rose']) || $_FILES['file_rose']['error'] !== UPLOAD_ERR_OK) {
    $err = $_FILES['file_rose']['error'] ?? -1;
    $_SESSION['import_result'] = ['error' => 'Errore upload (codice ' . $err . ').'];
    header('Location: ' . $redirect); exit;
}

$tmp = $_FILES['file_rose']['tmp_name'];
$ext = strtolower(pathinfo($_FILES['file_rose']['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    $_SESSION['import_result'] = ['error' => 'Solo file .csv accettati.'];
    header('Location: ' . $redirect); exit;
}

$handle = fopen($tmp, 'r');
if (!$handle) {
    $_SESSION['import_result'] = ['error' => 'Impossibile aprire il file CSV.'];
    header('Location: ' . $redirect); exit;
}

$db = getDB();

// Elenco nomi fantasquadre validi
$nomiSquadre = $db->query("SELECT nome_fantasquadra FROM fantasquadra")
                  ->fetchAll(\PDO::FETCH_COLUMN);
$nomiSet = array_flip($nomiSquadre);

$stmtCheckRosa  = $db->prepare("SELECT id_rosa FROM rosa WHERE nome_fantasquadra = ? AND anno = ? AND modalita = ?");
$stmtInsertRosa = $db->prepare("INSERT INTO rosa (nome_fantasquadra, anno, modalita) VALUES (?, ?, ?)");
$stmtLastRosa   = $db->prepare("SELECT MAX(id_rosa) AS id_rosa FROM rosa WHERE nome_fantasquadra = ? AND anno = ? AND modalita = ?");
$stmtCheckGioc  = $db->prepare("SELECT codice_fantacalcio FROM giocatore WHERE codice_fantacalcio = ?");
$stmtCheckDett  = $db->prepare("
    SELECT 1 FROM dettagli_rosa WHERE id_rosa = ? AND codice_fantacalcio = ?
");
$stmtInsDett    = $db->prepare("
    INSERT INTO dettagli_rosa (id_rosa, codice_fantacalcio, crediti_pagati)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE crediti_pagati = VALUES(crediti_pagati)
");

// Lettura riga per riga (tutta in memoria per fare 2 passate)
$csvRows = [];
while (($row = fgetcsv($handle, 1000, ',')) !== false) {
    if (count($row) < 3) continue;
    $nome     = trim($row[0]);
    $codice   = (int)trim($row[1]);
    $crediti  = (float)trim($row[2]);
    if ($nome === '' || $codice <= 0 || !isset($nomiSet[$nome])) continue;
    $csvRows[] = [$nome, $codice, $crediti];
}
fclose($handle);

if (empty($csvRows)) {
    $_SESSION['import_result'] = ['error' => 'Nessuna riga valida nel CSV. Controlla il formato: NomeFantasquadra, CodiceFantacalcio, CreditiPagati.'];
    header('Location: ' . $redirect); exit;
}

$db->beginTransaction();
$inserted  = 0;
$skipped   = 0;
$errori    = [];

try {
    // Passata 1: crea le rose mancanti
    $squadreViste = [];
    foreach ($csvRows as [$nome]) {
        if (isset($squadreViste[$nome])) continue;
        $squadreViste[$nome] = true;
        $stmtCheckRosa->execute([$nome, $anno, $modalita]);
        if (!$stmtCheckRosa->fetchColumn()) {
            $stmtInsertRosa->execute([$nome, $anno, $modalita]);
        }
    }

    // Passata 2: inserisci dettagli_rosa
    foreach ($csvRows as [$nome, $codice, $crediti]) {
        // Verifica giocatore esiste
        $stmtCheckGioc->execute([$codice]);
        if (!$stmtCheckGioc->fetchColumn()) {
            $skipped++;
            continue;
        }

        $stmtLastRosa->execute([$nome, $anno, $modalita]);
        $id_rosa = (int)$stmtLastRosa->fetchColumn();
        if (!$id_rosa) { $skipped++; continue; }

        // Controlla duplicato
        $stmtCheckDett->execute([$id_rosa, $codice]);
        if ($stmtCheckDett->fetchColumn()) { $skipped++; continue; }

        $stmtInsDett->execute([$id_rosa, $codice, max(0, (int)$crediti)]);
        $inserted++;
    }

    $db->commit();

    // Archivia il file caricato (best-effort, non blocca l'esito)
    fs_archivia_caricamento($tmp, 'rose', 'rose_' . fs_stagione_slug($anno) . '_' . $modalita . '.csv');

    $_SESSION['import_result'] = [
        'upserted' => $inserted,
        'errors'   => $skipped,
        'msg'      => "Rose importate: {$inserted} giocatori inseriti, {$skipped} saltati.",
    ];
    header('Location: ' . $redirect . '&anno=' . $anno);
    exit;

} catch (\Exception $e) {
    $db->rollBack();
    error_log('[FS import_rose] ' . $e->getMessage());
    $_SESSION['import_result'] = ['error' => 'Errore durante l\'import. Controlla i log del server.'];
    header('Location: ' . $redirect); exit;
}
