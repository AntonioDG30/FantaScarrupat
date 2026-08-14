<?php
/**
 * app/admin/import_competizione.php — Import calendario competizione da XLSX
 *
 * Parsing puro PHP: ZipArchive + SimpleXML (no Composer).
 *
 * Formato XLSX atteso (stesso del vecchio sito):
 *   - Riga con tipologia (es. "Calendario", "Fase a Gironi", "Finale"…) → aggiorna tipologia corrente
 *   - Riga che inizia con una cifra → giornata += 2
 *   - Riga con col[0] = nome squadra → match offset 0
 *   - Riga con col[1] = nome squadra → match offset 1 (col[0] = girone)
 *
 * POST params:
 *   csrf_token          — token CSRF
 *   id_cd               — id_competizione_disputata target
 *   file_calendario     — XLSX file
 *   file_calendario2    — XLSX file opzionale (secondo file, es. per NBA)
 *   calc_vincitore      — '1' se calcolare il vincitore dopo l'import
 */

declare(strict_types=1);

require_once __DIR__ . '/../../auth/require_login.php';
require_once __DIR__ . '/../../auth/require_admin.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/archive.php';
require_once __DIR__ . '/lib/tiebreak.php';

// Hosting lento (Altervista): più margine di tempo/memoria.
@set_time_limit(120);
@ini_set('memory_limit', '256M');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); exit('Method not allowed');
}

$token = $_POST['csrf_token'] ?? '';
if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token)) {
    http_response_code(403); exit('CSRF mismatch');
}

$id_cd    = (int)($_POST['id_cd'] ?? 0);
$redirect = BASE_PATH . 'app/admin/index.php?tab=competizioni&open=' . $id_cd;

// Conferma esplicita per sovrascrivere un calendario già presente
$sovrascrivi = !empty($_POST['sovrascrivi']);

// Modalità AJAX (form unico calendario+formazioni): risponde JSON invece di redirect+flash.
$AJAX = !empty($_POST['ajax']);
function ic_done(bool $ok, string $msg, ?string $redirect, bool $ajax): never {
    if ($ajax) {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode(['ok' => $ok, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    } else {
        $_SESSION['admin_flash'] = ['type' => $ok ? 'success' : 'error', 'msg' => $msg];
        header('Location: ' . (string)$redirect);
    }
    exit;
}

if ($id_cd === 0) {
    ic_done(false, 'ID competizione non valido.', BASE_PATH . 'app/admin/index.php?tab=competizioni', $AJAX);
}

// ── XLSX reader ───────────────────────────────────────────────────────

/**
 * Converte lettera colonna Excel in indice 0-based.
 * 'A'→0, 'B'→1, 'Z'→25, 'AA'→26, 'AB'→27, …
 */
function col_to_idx(string $col): int
{
    $col = strtoupper($col);
    $idx = 0;
    for ($i = 0, $len = strlen($col); $i < $len; $i++) {
        $idx = $idx * 26 + (ord($col[$i]) - 64); // 'A'=65, so ord-64=1
    }
    return $idx - 1; // 0-based
}

/**
 * Converte riga con chiavi colonna-lettera in array indicizzato a 0.
 * I buchi vengono riempiti con ''.
 */
function row_to_indexed(array $cells): array
{
    if (empty($cells)) return [];
    $indexed = [];
    $maxIdx  = 0;
    foreach ($cells as $col => $val) {
        $idx = col_to_idx($col);
        $indexed[$idx] = $val;
        if ($idx > $maxIdx) $maxIdx = $idx;
    }
    $result = [];
    for ($i = 0; $i <= $maxIdx; $i++) {
        $result[$i] = $indexed[$i] ?? '';
    }
    return $result;
}

/**
 * Legge un XLSX e ritorna array di righe, ogni riga è un array con
 * chiave = lettera colonna (es. 'A', 'B', 'AA').
 */
function xlsx_read_rows_cal(string $path): array
{
    if (!class_exists('ZipArchive')) return [];
    $zip = new \ZipArchive();
    if ($zip->open($path) !== true) return [];

    $shared = [];
    $ssXml  = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml) {
        $ss = @simplexml_load_string($ssXml, 'SimpleXMLElement', LIBXML_NONET);
        if ($ss) {
            foreach ($ss->si as $si) {
                if (isset($si->t)) {
                    $shared[] = (string)$si->t;
                } else {
                    $text = '';
                    if (isset($si->r)) {
                        foreach ($si->r as $r) {
                            $text .= (string)($r->t ?? '');
                        }
                    }
                    $shared[] = $text;
                }
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if (!$sheetXml) return [];

    $sheet = @simplexml_load_string($sheetXml, 'SimpleXMLElement', LIBXML_NONET);
    if (!$sheet || !isset($sheet->sheetData)) return [];

    $rows = [];
    foreach ($sheet->sheetData->row as $xmlRow) {
        $cells = [];
        foreach ($xmlRow->c as $c) {
            $ref  = preg_replace('/[0-9]/', '', (string)($c['r'] ?? ''));
            $type = (string)($c['t'] ?? '');
            $val  = (string)($c->v ?? '');
            $cells[$ref] = ($type === 's') ? ($shared[(int)$val] ?? '') : $val;
        }
        $rows[] = row_to_indexed($cells);
    }
    return $rows;
}

// ── Core parsing ──────────────────────────────────────────────────────

function fs_insert_partita(
    \PDO   $db,
    int    $id_cd,
    string $casa,
    string $trasf,
    int    $gol_c,
    int    $gol_t,
    float  $pt_c,
    float  $pt_t,
    int    $giornata,
    string $tipologia,
    ?string $girone
): void {
    if ($girone !== null) {
        $db->prepare("
            INSERT INTO partita_avvessario
                (id_competizione_disputata, nome_fantasquadra_casa, nome_fantasquadra_trasferta,
                 gol_casa, gol_trasferta, punteggio_casa, punteggio_trasferta,
                 giornata, tipologia, girone)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$id_cd, $casa, $trasf, $gol_c, $gol_t, $pt_c, $pt_t, $giornata, $tipologia, $girone]);
    } else {
        $db->prepare("
            INSERT INTO partita_avvessario
                (id_competizione_disputata, nome_fantasquadra_casa, nome_fantasquadra_trasferta,
                 gol_casa, gol_trasferta, punteggio_casa, punteggio_trasferta,
                 giornata, tipologia)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$id_cd, $casa, $trasf, $gol_c, $gol_t, $pt_c, $pt_t, $giornata, $tipologia]);
    }
}

/**
 * Estrae uno o due match da una riga dati.
 * @param array   $data     riga 0-indexed
 * @param int     $startI   0 = match inizia in col 0; 1 = col 0 è girone
 * @param int     &$giornata
 * @param string  $tipologia
 * @param string|null &$girone
 */
function fs_estrai_valori(
    array  $data,
    int    $startI,
    int    &$giornata,
    string $tipologia,
    ?string &$girone,
    int    $id_cd,
    \PDO   $db
): void {
    // Girone
    if ($startI === 1 && !empty($data[0])) {
        $girone = (string)$data[0];
    } else {
        $girone = null;
    }

    $i = $startI;

    $nome_casa   = (string)($data[$i++] ?? '');
    $pt_c        = (float)($data[$i++] ?? 0);
    $pt_t        = (float)($data[$i++] ?? 0);
    $nome_trasf  = (string)($data[$i++] ?? '');
    $score_raw   = (string)($data[$i]   ?? '0-0');
    $parts       = explode('-', $score_raw);
    $gol_c       = (int)($parts[0] ?? 0);
    $gol_t       = (int)($parts[1] ?? 0);
    $i          += 2; // salta la cella del risultato + una cella vuota

    if ($nome_casa !== '' && $nome_trasf !== '') {
        fs_insert_partita($db, $id_cd, $nome_casa, $nome_trasf, $gol_c, $gol_t, $pt_c, $pt_t, $giornata, $tipologia, $girone);
    }

    // Eventuale girone per secondo match (solo se startI=1, i=7)
    if ($i === 7) {
        if (!empty($data[7])) {
            $girone = (string)$data[7];
        }
        $i++;
    } else {
        $girone = null;
    }

    // Secondo match sulla stessa riga
    if (!empty($data[$i])) {
        $giornata++;
        $nome_casa2  = (string)($data[$i++] ?? '');
        $pt_c2       = (float)($data[$i++] ?? 0);
        $pt_t2       = (float)($data[$i++] ?? 0);
        $nome_trasf2 = (string)($data[$i++] ?? '');
        $score_raw2  = (string)($data[$i]   ?? '0-0');
        $parts2      = explode('-', $score_raw2);
        $gol_c2      = (int)($parts2[0] ?? 0);
        $gol_t2      = (int)($parts2[1] ?? 0);

        if ($nome_casa2 !== '' && $nome_trasf2 !== '') {
            fs_insert_partita($db, $id_cd, $nome_casa2, $nome_trasf2, $gol_c2, $gol_t2, $pt_c2, $pt_t2, $giornata, $tipologia, $girone);
        }
        $giornata--;
    }
}

/**
 * Processa un array di righe XLSX (già 0-indexed) e inserisce le partite.
 */
function fs_leggi_righe(
    array  $allRows,
    int    $id_cd,
    array  $nomiSquadre,
    array  $tipologieValide,
    \PDO   $db
): int {
    $giornata  = -1;
    $tipologia = 'Calendario';
    $girone    = null;
    $inseriti  = 0;

    $nomiSet = array_flip($nomiSquadre);

    foreach ($allRows as $data) {
        $cell0 = trim((string)($data[0] ?? ''));
        $cell1 = trim((string)($data[1] ?? ''));

        if ($cell0 === '' && $cell1 === '') continue;

        if (in_array($cell0, $tipologieValide, true)) {
            // Riga tipologia
            $tipologia = $cell0;
        } elseif (isset($nomiSet[$cell0])) {
            // Match offset 0
            $before = $inseriti;
            fs_estrai_valori($data, 0, $giornata, $tipologia, $girone, $id_cd, $db);
            $inseriti++;
        } elseif (isset($nomiSet[$cell1])) {
            // Match offset 1 (cell0 = girone)
            fs_estrai_valori($data, 1, $giornata, $tipologia, $girone, $id_cd, $db);
            $inseriti++;
        } elseif ($cell0 !== '' && preg_match('/^\d/', $cell0)) {
            // Riga giornata
            $giornata += 2;
        }
    }

    return $inseriti;
}

// ── Carica dati di riferimento ────────────────────────────────────────
$db          = getDB();
$nomiSquadre = $db->query("SELECT nome_fantasquadra FROM fantasquadra")->fetchAll(\PDO::FETCH_COLUMN);
$tipologie   = $db->query("SELECT tipologia FROM tipologia_partita")->fetchAll(\PDO::FETCH_COLUMN);

// Quante partite esistono già per questa edizione (per sovrascrittura)
$cntStmt = $db->prepare("SELECT COUNT(*) FROM partita_avvessario WHERE id_competizione_disputata = ?");
$cntStmt->execute([$id_cd]);
$n_esistenti = (int)$cntStmt->fetchColumn();

// ── Valida file principale ────────────────────────────────────────────
if (!isset($_FILES['file_calendario']) || $_FILES['file_calendario']['error'] !== UPLOAD_ERR_OK) {
    $err = $_FILES['file_calendario']['error'] ?? -1;
    ic_done(false, 'Errore upload (codice ' . $err . ').', $redirect, $AJAX);
}

$tmp1 = $_FILES['file_calendario']['tmp_name'];
$ext1 = strtolower(pathinfo($_FILES['file_calendario']['name'], PATHINFO_EXTENSION));
if ($ext1 !== 'xlsx') {
    ic_done(false, 'Solo file .xlsx accettati.', $redirect, $AJAX);
}

// File opzionale (es. NBA secondo sheet)
$tmp2 = null;
if (isset($_FILES['file_calendario2']) && $_FILES['file_calendario2']['error'] === UPLOAD_ERR_OK) {
    $ext2 = strtolower(pathinfo($_FILES['file_calendario2']['name'], PATHINFO_EXTENSION));
    if ($ext2 === 'xlsx') {
        $tmp2 = $_FILES['file_calendario2']['tmp_name'];
    }
}

// ── Import ────────────────────────────────────────────────────────────
$rows1 = xlsx_read_rows_cal($tmp1);
if (empty($rows1)) {
    ic_done(false, 'File XLSX non leggibile o vuoto.', $redirect, $AJAX);
}

// ── Conferma sovrascrittura: se ci sono partite e non è confermato, blocca ──
if ($n_esistenti > 0 && !$sovrascrivi) {
    ic_done(false, "Esistono già {$n_esistenti} partite per questa competizione. "
        . "Per reimportare il calendario spunta «Sostituisci il calendario esistente».", $redirect, $AJAX);
}

$db->beginTransaction();
try {
    // Sovrascrittura: azzera le partite dell'edizione prima del reload dal file
    if ($sovrascrivi && $n_esistenti > 0) {
        $db->prepare("DELETE FROM partita_avvessario WHERE id_competizione_disputata = ?")
           ->execute([$id_cd]);
    }

    $totaleInseriti = fs_leggi_righe($rows1, $id_cd, $nomiSquadre, $tipologie, $db);

    if ($tmp2 !== null) {
        $rows2 = xlsx_read_rows_cal($tmp2);
        if (!empty($rows2)) {
            $totaleInseriti += fs_leggi_righe($rows2, $id_cd, $nomiSquadre, $tipologie, $db);
        }
    }

    $db->commit();
} catch (\Exception $e) {
    $db->rollBack();
    error_log('[FS import_competizione] ' . $e->getMessage());
    ic_done(false, 'Errore durante l\'import del calendario. Controlla i log.', $redirect, $AJAX);
}

// Calcola vincitore se richiesto (logica unica in lib/tiebreak.php)
$calc_msg = '';
if (!empty($_POST['calc_vincitore'])) {
    $res = fs_calcola_vincitore($db, $id_cd);
    if ($res['vincitore'] !== null) {
        $db->prepare("UPDATE competizione_disputata SET vincitore = ? WHERE id_competizione_disputata = ?")
           ->execute([$res['vincitore'], $id_cd]);
    }
    $calc_msg = ' ' . fs_descrivi_esito_vincitore($res);
}

// Archivia i file calendario caricati (best-effort, non blocca l'esito)
$edInfo = $db->prepare("SELECT nome_competizione, anno FROM competizione_disputata WHERE id_competizione_disputata = ?");
$edInfo->execute([$id_cd]);
if ($edRow = $edInfo->fetch(\PDO::FETCH_ASSOC)) {
    $slug = fs_slug((string)$edRow['nome_competizione']) . '_' . fs_stagione_slug((int)$edRow['anno']);
    fs_archivia_caricamento($tmp1, 'competizioni', $slug . '.xlsx');
    if ($tmp2 !== null) {
        fs_archivia_caricamento($tmp2, 'competizioni', $slug . '_2.xlsx');
    }
}

$base_msg = ($sovrascrivi && $n_esistenti > 0)
    ? "Calendario sostituito: {$n_esistenti} partite rimosse, {$totaleInseriti} inserite."
    : "Calendario importato: {$totaleInseriti} partite inserite.";
ic_done(true, $base_msg . $calc_msg, $redirect, $AJAX);
