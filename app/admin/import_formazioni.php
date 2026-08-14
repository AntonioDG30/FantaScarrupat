<?php
/**
 * app/admin/import_formazioni.php — Import formazioni da ZIP o XLSX.
 *
 * Due modalità:
 *  • CHUNKED (AJAX, default dal form): `fz_phase=start` carica il file, lo estrae
 *    in una cartella temporanea e crea un "job" in sessione; `fz_phase=step`
 *    elabora pochi file alla volta e risponde JSON con l'avanzamento REALE.
 *    Richieste brevi → niente timeout/blocchi su hosting lento (Altervista).
 *  • SINCRONA (fallback senza JS): elabora tutto in una richiesta e fa redirect.
 *
 * Ogni partita viene agganciata al calendario per (id_cd, giornata, casa, trasf)
 * per ricavarne la tipologia. REQUISITO: calendario già importato.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../auth/require_login.php';
require_once __DIR__ . '/../../auth/require_admin.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/archive.php';
require_once __DIR__ . '/lib/formazione_import.php';
require_once __DIR__ . '/lib/tiebreak.php';

// Hosting lento: margine di tempo/memoria (i blocchi restano comunque brevi).
@set_time_limit(120);
@ini_set('memory_limit', '256M');

// ── Helper ─────────────────────────────────────────────────────────────
function fz_csrf_ok(): bool
{
    return hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)($_POST['csrf_token'] ?? ''));
}
function fz_rmdir(string $d): void
{
    if (!is_dir($d)) return;
    foreach (glob($d . DIRECTORY_SEPARATOR . '*') ?: [] as $f) @unlink($f);
    @rmdir($d);
}
function fz_gc_temp(): void
{
    // rimuove job temporanei orfani più vecchi di 1 ora
    foreach (glob(fs_temp_dir() . DIRECTORY_SEPARATOR . 'fzjob_*') ?: [] as $d) {
        if (is_dir($d) && (time() - (int)@filemtime($d)) > 3600) fz_rmdir($d);
    }
}
function fz_json(array $a): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($a, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Dopo l'import delle formazioni, ricalcola il vincitore e i tie-break di tutte
 * le sfide a eliminazione (prima andava fatto a mano col bottone "Calcola vincitore",
 * e per le finali in parità senza formazioni il calcolo si asteneva). Best-effort.
 * @return string frase da appendere al messaggio di esito (vuota se nulla)
 */
function fz_ricalcola_vincitore(\PDO $db, int $id_cd): string
{
    try {
        $res = fs_calcola_vincitore($db, $id_cd);
        if ($res['vincitore'] !== null) {
            $db->prepare("UPDATE competizione_disputata SET vincitore = ? WHERE id_competizione_disputata = ?")
               ->execute([$res['vincitore'], $id_cd]);
        }
        return ' ' . fs_descrivi_esito_vincitore($res);
    } catch (\Throwable $e) {
        error_log('[FS fz_ricalcola_vincitore] ' . $e->getMessage());
        return '';
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); exit('Method not allowed');
}

$phase = $_POST['fz_phase'] ?? '';

/* ══════════════════════════════════════════════════════════════════════
   MODALITÀ A BLOCCHI (AJAX)
   ══════════════════════════════════════════════════════════════════════ */
if ($phase === 'start' || $phase === 'step') {
    if (!fz_csrf_ok()) { http_response_code(403); fz_json(['ok' => false, 'message' => 'Token di sicurezza non valido.']); }
    $db = getDB();

    // ── START: carica il file, estrai, crea il job ──────────────────────
    if ($phase === 'start') {
        $id_cd = (int)($_POST['id_cd'] ?? 0);
        $sovr  = !empty($_POST['sovrascrivi']);
        if ($id_cd === 0) fz_json(['ok' => false, 'message' => 'ID competizione non valido.']);

        if (!isset($_FILES['file_formazioni']) || $_FILES['file_formazioni']['error'] !== UPLOAD_ERR_OK) {
            fz_json(['ok' => false, 'message' => 'Errore upload del file.']);
        }
        $tmp  = $_FILES['file_formazioni']['tmp_name'];
        $orig = (string)$_FILES['file_formazioni']['name'];
        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!in_array($ext, ['zip', 'xlsx'], true)) fz_json(['ok' => false, 'message' => 'Solo file .zip o .xlsx.']);

        fz_gc_temp();

        $dir = fs_temp_dir() . DIRECTORY_SEPARATOR . 'fzjob_' . bin2hex(random_bytes(6));
        @mkdir($dir, 0700, true);

        $paths = [];
        if ($ext === 'zip') {
            $estratti = fs_estrai_xlsx_da_zip($tmp);   // [nome => percorso tmp]
            $k = 0;
            foreach ($estratti as $p) {
                $dest = $dir . DIRECTORY_SEPARATOR . ($k++) . '.xlsx';
                if (!@rename($p, $dest)) @copy($p, $dest);
                $paths[] = $dest;
            }
            if (empty($paths)) { fz_rmdir($dir); fz_json(['ok' => false, 'message' => 'Lo zip non contiene file .xlsx.']); }
        } else {
            $dest = $dir . DIRECTORY_SEPARATOR . '0.xlsx';
            @copy($tmp, $dest);
            $paths[] = $dest;
        }

        // Archivia il file originale caricato (best-effort)
        $edInfo = $db->prepare("SELECT nome_competizione, anno FROM competizione_disputata WHERE id_competizione_disputata = ?");
        $edInfo->execute([$id_cd]);
        if ($ed = $edInfo->fetch(\PDO::FETCH_ASSOC)) {
            $slug = fs_slug((string)$ed['nome_competizione']) . '_' . fs_stagione_slug((int)$ed['anno']);
            fs_archivia_caricamento($tmp, 'formazioni', 'formazioni_' . $slug . '.' . $ext);
        }

        $_SESSION['fz_job'] = [
            'dir' => $dir, 'paths' => $paths, 'i' => 0, 'id_cd' => $id_cd, 'sovr' => $sovr,
            'tot' => ['inserite' => 0, 'sostituite' => 0, 'gia_presenti' => 0, 'saltate' => 0, 'giocatori' => 0, 'avvisi' => []],
        ];
        fz_json(['ok' => true, 'total' => count($paths), 'message' => count($paths) . ' file da elaborare']);
    }

    // ── STEP: elabora un blocco di file ─────────────────────────────────
    $job = $_SESSION['fz_job'] ?? null;
    if (!$job) fz_json(['ok' => false, 'message' => 'Sessione di import scaduta. Riprova.']);

    $CHUNK = 3;
    $slice = array_slice($job['paths'], (int)$job['i'], $CHUNK);
    $map = [];
    foreach ($slice as $p) $map[basename($p)] = $p;

    try {
        $db->beginTransaction();
        $s = fs_importa_formazioni($db, (int)$job['id_cd'], $map, (bool)$job['sovr']);
        $db->commit();
    } catch (\Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('[FS import_formazioni chunk] ' . $e->getMessage());
        fz_json(['ok' => false, 'message' => 'Errore durante l\'elaborazione di un blocco.']);
    }

    foreach (['inserite', 'sostituite', 'gia_presenti', 'saltate', 'giocatori'] as $k) {
        $job['tot'][$k] += $s[$k];
    }
    $job['tot']['avvisi'] = array_merge($job['tot']['avvisi'], $s['avvisi']);
    $job['i'] += count($slice);
    $_SESSION['fz_job'] = $job;

    $tot  = $job['tot'];
    $done = $job['i'] >= count($job['paths']);
    $msg  = "{$s['inserite']} inserite"
          . ($s['sostituite'] ? ", {$s['sostituite']} sostituite" : '')
          . ($s['gia_presenti'] ? ", {$s['gia_presenti']} già presenti" : '')
          . ($s['saltate'] ? ", {$s['saltate']} saltate" : '');

    $resp = [
        'ok' => true, 'done' => $done,
        'processed' => $job['i'], 'total' => count($job['paths']),
        'message' => $msg, 'stats' => $tot,
    ];

    if ($done) {
        fz_rmdir($job['dir']);
        unset($_SESSION['fz_job']);
        $parti = [];
        if ($tot['inserite'])     $parti[] = "{$tot['inserite']} inserite";
        if ($tot['sostituite'])   $parti[] = "{$tot['sostituite']} sostituite";
        if ($tot['gia_presenti']) $parti[] = "{$tot['gia_presenti']} già presenti";
        $finale = 'Formazioni: ' . ($parti ? implode(', ', $parti) : 'nessuna') . ". {$tot['giocatori']} giocatori.";
        if ($tot['saltate']) $finale .= " {$tot['saltate']} partite saltate (calendario mancante o squadre non riconosciute).";
        // Ricalcolo automatico vincitore + tie-break ora che le formazioni ci sono
        $finale .= fz_ricalcola_vincitore($db, (int)$job['id_cd']);
        $resp['final_message'] = $finale;
        $resp['avvisi'] = array_slice($tot['avvisi'], 0, 30);
        // flash per la pagina che si ricaricherà
        $_SESSION['admin_flash'] = ['type' => ($tot['inserite'] || $tot['sostituite'] || !$tot['saltate']) ? 'success' : 'error', 'msg' => $finale];
        if (!empty($tot['avvisi'])) $_SESSION['import_formazioni_avvisi'] = array_slice($tot['avvisi'], 0, 30);
    }
    fz_json($resp);
}

/* ══════════════════════════════════════════════════════════════════════
   FALLBACK SINCRONO (senza JavaScript) — elabora tutto e fa redirect
   ══════════════════════════════════════════════════════════════════════ */
$id_cd       = (int)($_POST['id_cd'] ?? 0);
$sovrascrivi = !empty($_POST['sovrascrivi']);
$redirect    = BASE_PATH . 'app/admin/index.php?tab=competizioni&open=' . $id_cd;

if (!fz_csrf_ok()) { http_response_code(403); exit('CSRF mismatch'); }
if ($id_cd === 0) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'ID competizione non valido.'];
    header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=competizioni'); exit;
}
if (!isset($_FILES['file_formazioni']) || $_FILES['file_formazioni']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Errore upload del file.'];
    header('Location: ' . $redirect); exit;
}

$tmp = $_FILES['file_formazioni']['tmp_name'];
$ext = strtolower(pathinfo((string)$_FILES['file_formazioni']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['zip', 'xlsx'], true)) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Solo file .zip o .xlsx accettati.'];
    header('Location: ' . $redirect); exit;
}

$db = getDB();
$cleanup = [];
if ($ext === 'zip') {
    $xlsxPaths = fs_estrai_xlsx_da_zip($tmp);
    $cleanup   = array_values($xlsxPaths);
    if (empty($xlsxPaths)) {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Lo zip non contiene file .xlsx.'];
        header('Location: ' . $redirect); exit;
    }
} else {
    $xlsxPaths = [((string)$_FILES['file_formazioni']['name']) => $tmp];
}

$db->beginTransaction();
try {
    $stats = fs_importa_formazioni($db, $id_cd, $xlsxPaths, $sovrascrivi);
    $db->commit();
} catch (\Throwable $e) {
    $db->rollBack();
    error_log('[FS import_formazioni] ' . $e->getMessage());
    foreach ($cleanup as $f) @unlink($f);
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Errore durante l\'import delle formazioni.'];
    header('Location: ' . $redirect); exit;
}
foreach ($cleanup as $f) @unlink($f);

$edInfo = $db->prepare("SELECT nome_competizione, anno FROM competizione_disputata WHERE id_competizione_disputata = ?");
$edInfo->execute([$id_cd]);
if ($edRow = $edInfo->fetch(\PDO::FETCH_ASSOC)) {
    $slug = fs_slug((string)$edRow['nome_competizione']) . '_' . fs_stagione_slug((int)$edRow['anno']);
    fs_archivia_caricamento($tmp, 'formazioni', 'formazioni_' . $slug . '.' . $ext);
}

$parti = [];
if ($stats['inserite'] > 0)     $parti[] = "{$stats['inserite']} inserite";
if ($stats['sostituite'] > 0)   $parti[] = "{$stats['sostituite']} sostituite";
if ($stats['gia_presenti'] > 0) $parti[] = "{$stats['gia_presenti']} già presenti (non sovrascritte)";
$msg = 'Formazioni: ' . ($parti ? implode(', ', $parti) : 'nessuna') . ".";
if ($stats['giocatori'] > 0) $msg .= " {$stats['giocatori']} giocatori.";
$type = 'success';
if ($stats['saltate'] > 0) {
    $msg .= " {$stats['saltate']} partite saltate (calendario mancante o squadre non riconosciute).";
    if ($stats['inserite'] === 0 && $stats['sostituite'] === 0) $type = 'error';
}
// Ricalcolo automatico vincitore + tie-break ora che le formazioni ci sono
$msg .= fz_ricalcola_vincitore($db, $id_cd);
$_SESSION['import_formazioni_avvisi'] = array_slice($stats['avvisi'], 0, 30);
$_SESSION['admin_flash'] = ['type' => $type, 'msg' => $msg];
header('Location: ' . $redirect);
exit;
