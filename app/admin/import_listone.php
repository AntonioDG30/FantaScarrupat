<?php
/**
 * app/admin/import_listone.php — POST handler import listone giocatori da XLSX
 *
 * Parsing puro PHP: ZipArchive + SimpleXML (no Composer richiesto).
 * Colonne attese: Id, Nome, Squadra, R (classic), RM (mantra, ; separato).
 */

declare(strict_types=1);

require_once __DIR__ . '/../../auth/require_login.php';
require_once __DIR__ . '/../../auth/require_admin.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/archive.php';

// Hosting lento (Altervista): più margine di tempo/memoria per gli import pesanti.
@set_time_limit(120);
@ini_set('memory_limit', '256M');

// ── Validazioni base ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); exit('Method not allowed');
}

$token = $_POST['csrf_token'] ?? '';
if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token)) {
    http_response_code(403); exit('CSRF mismatch');
}

$redirect_tab = in_array($_POST['redirect_tab'] ?? '', ['giocatori'], true)
    ? $_POST['redirect_tab']
    : 'giocatori';

$anno = (int)($_POST['anno'] ?? CURRENT_ANNO);
if ($anno < 2016 || $anno > (int)date('Y') + 2) {
    $_SESSION['import_result'] = ['error' => 'Anno non valido.'];
    header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=' . $redirect_tab);
    exit;
}

// ── Validazione upload ────────────────────────────────────────────────
if (!isset($_FILES['xlsx_file']) || $_FILES['xlsx_file']['error'] !== UPLOAD_ERR_OK) {
    $err = $_FILES['xlsx_file']['error'] ?? -1;
    $_SESSION['import_result'] = ['error' => 'Errore upload file (codice ' . $err . ').'];
    header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=' . $redirect_tab);
    exit;
}

$tmp = $_FILES['xlsx_file']['tmp_name'];
$ext = strtolower(pathinfo($_FILES['xlsx_file']['name'], PATHINFO_EXTENSION));
if ($ext !== 'xlsx') {
    $_SESSION['import_result'] = ['error' => 'Solo file .xlsx accettati.'];
    header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=' . $redirect_tab);
    exit;
}

// ── XLSX reader ───────────────────────────────────────────────────────
/**
 * Legge un XLSX e ritorna array di righe, ogni riga è un array
 * con chiavi = lettera colonna ('A','B',...,'AA',...).
 */
function xlsx_read_rows(string $path): array
{
    if (!class_exists('ZipArchive')) {
        return [];
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return [];
    }

    // 1. Shared strings
    $shared = [];
    $ssXml  = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml) {
        $ss = @simplexml_load_string($ssXml, 'SimpleXMLElement', LIBXML_NONET);
        if ($ss) {
            foreach ($ss->si as $si) {
                if (isset($si->t)) {
                    $shared[] = (string) $si->t;
                } else {
                    $text = '';
                    if (isset($si->r)) {
                        foreach ($si->r as $r) {
                            $text .= (string) ($r->t ?? '');
                        }
                    }
                    $shared[] = $text;
                }
            }
        }
    }

    // 2. First worksheet
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    if (!$sheetXml) return [];

    $sheet = @simplexml_load_string($sheetXml, 'SimpleXMLElement', LIBXML_NONET);
    if (!$sheet || !isset($sheet->sheetData)) return [];

    $rows = [];
    foreach ($sheet->sheetData->row as $row) {
        $cells = [];
        foreach ($row->c as $c) {
            // Column letter from cell ref (e.g. "B3" → "B", "AA12" → "AA")
            $ref = preg_replace('/[0-9]/', '', (string) ($c['r'] ?? ''));
            $type = (string) ($c['t'] ?? '');
            $val  = (string) ($c->v ?? '');

            if ($type === 's') {
                $cells[$ref] = $shared[(int) $val] ?? '';
            } else {
                $cells[$ref] = $val;
            }
        }
        $rows[] = $cells;
    }
    return $rows;
}

// ── Import logic ──────────────────────────────────────────────────────
$rows = xlsx_read_rows($tmp);

if (empty($rows)) {
    $_SESSION['import_result'] = ['error' => 'File non leggibile o vuoto. Assicurati sia un XLSX valido.'];
    header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=' . $redirect_tab);
    exit;
}

// Row 0 = headers → map header name to column letter
$header_row = $rows[0];
$col = array_flip($header_row); // ['Id' => 'A', 'Nome' => 'D', ...]

$colId    = $col['Id']      ?? null;
$colNome  = $col['Nome']    ?? null;
$colSquad = $col['Squadra'] ?? null;
$colR     = $col['R']       ?? null;
$colRM    = $col['RM']      ?? null;

if (!$colId || !$colNome) {
    $_SESSION['import_result'] = [
        'error' => 'Colonne Id e/o Nome non trovate. Intestazioni rilevate: '
            . implode(', ', array_values($header_row))
    ];
    header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=' . $redirect_tab);
    exit;
}

// Load ruoli lookup
$db = getDB();
$ruoliMap = [];
foreach ($db->query("SELECT id_ruolo, nome, tipo FROM ruoli") as $r) {
    $ruoliMap[$r['tipo']][$r['nome']] = (int) $r['id_ruolo'];
}

// Prepare statements
$stmtUpsertG = $db->prepare("
    INSERT INTO giocatore (codice_fantacalcio, nome_completo)
    VALUES (?, ?)
    ON DUPLICATE KEY UPDATE nome_completo = VALUES(nome_completo)
");
$stmtUpsertS = $db->prepare("
    INSERT INTO giocatore_stagione (codice_fantacalcio, anno, squadra_reale)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE squadra_reale = VALUES(squadra_reale)
");
$stmtGetSId  = $db->prepare("
    SELECT id FROM giocatore_stagione WHERE codice_fantacalcio = ? AND anno = ?
");
$stmtDelR    = $db->prepare("
    DELETE FROM ruoli_giocatori WHERE id_giocatore_stagione = ?
");
$stmtAddR    = $db->prepare("
    INSERT IGNORE INTO ruoli_giocatori (id_giocatore_stagione, id_ruolo) VALUES (?, ?)
");

$upserted = 0;
$errors   = 0;

$db->beginTransaction();
try {
    for ($i = 1; $i < count($rows); $i++) {
        $row    = $rows[$i];
        $codice = (int) ($row[$colId] ?? 0);
        if ($codice === 0) continue;

        $nome    = trim($row[$colNome]  ?? '');
        $squadra = $colSquad ? trim($row[$colSquad] ?? '') : '';
        $r       = $colR    ? strtoupper(trim($row[$colR] ?? '')) : '';
        $rm      = $colRM   ? trim($row[$colRM] ?? '') : '';

        if ($nome === '') continue;

        try {
            $stmtUpsertG->execute([$codice, $nome]);
            $stmtUpsertS->execute([$codice, $anno, $squadra]);

            $stmtGetSId->execute([$codice, $anno]);
            $stagId = (int) $stmtGetSId->fetchColumn();
            if (!$stagId) {
                $errors++;
                continue;
            }

            // Re-insert ruoli
            $stmtDelR->execute([$stagId]);

            if ($r !== '' && isset($ruoliMap['classic'][$r])) {
                $stmtAddR->execute([$stagId, $ruoliMap['classic'][$r]]);
            }

            if ($rm !== '') {
                foreach (explode(';', $rm) as $mr) {
                    $mr = trim($mr);
                    if ($mr !== '' && isset($ruoliMap['mantra'][$mr])) {
                        $stmtAddR->execute([$stagId, $ruoliMap['mantra'][$mr]]);
                    }
                }
            }

            $upserted++;
        } catch (\PDOException $e) {
            error_log('[FS import] row ' . $i . ': ' . $e->getMessage());
            $errors++;
        }
    }
    $db->commit();
} catch (\Exception $e) {
    $db->rollBack();
    error_log('[FS import] transaction: ' . $e->getMessage());
    $_SESSION['import_result'] = ['error' => 'Errore DB durante l\'import. Verifica i log.'];
    header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=' . $redirect_tab);
    exit;
}

// Archivia il file caricato (best-effort, non blocca l'esito)
fs_archivia_caricamento($tmp, 'listone', 'listone_' . fs_stagione_slug($anno) . '.xlsx');

$_SESSION['import_result'] = ['upserted' => $upserted, 'errors' => $errors];
header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=' . $redirect_tab . '&anno=' . $anno);
exit;
