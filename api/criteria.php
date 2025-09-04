<?php
declare(strict_types=1);

session_start();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
if ($action !== 'export') {
    header('Content-Type: application/json');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../autoload.php'; 
require_once __DIR__ . '/../src/Core/FantacalcioAnalyzer.php';
require_once __DIR__ . '/../src/Core/FootballDataFetcher.php';
require_once __DIR__ . '/../src/Core/SerieAWikiFetcher.php';
require_once __DIR__ . '/../src/Utils/Logger.php';
require_once __DIR__ . '/../src/Utils/DataLoader.php';
require_once __DIR__ . '/../src/Utils/SimpleXLSXGen.php';

use FantacalcioAnalyzer\Core\FantacalcioAnalyzer;
use FantacalcioAnalyzer\Utils\SimpleXLSXGen;

// CSRF validation
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $analyzer = new FantacalcioAnalyzer();
    
    // **CONTROLLO CACHE OTTIMIZZATO**
    $hasCache = isset($_SESSION['data_loaded']) && $_SESSION['data_loaded'] && 
                isset($_SESSION['lista_corrente']) && 
                isset($_SESSION['api_squad_index']) &&  // Verifica cache API
                isset($_SESSION['effective_nationalities']);
    
    if ($hasCache) {
        // **RIPRISTINO OTTIMIZZATO CON CACHE API**
        $sessionData = [
            'lista_corrente' => $_SESSION['lista_corrente'],
            'statistiche' => $_SESSION['statistiche'] ?? [],
            'quotazioni' => $_SESSION['quotazioni'] ?? [],
            'valutazioni' => $_SESSION['valutazioni'] ?? [],
            'current_year' => $_SESSION['current_year'] ?? null,
            'last_year' => $_SESSION['last_year'] ?? null,
            'neopromosse' => $_SESSION['neopromosse'] ?? [],
            'squadre_media' => $_SESSION['squadre_media'] ?? [],
            'squadre_50ga' => $_SESSION['squadre_50ga'] ?? [],
            'team_ga_last' => $_SESSION['team_ga_last'] ?? [],
            // **RIPRISTINA CACHE API**
            'api_squad_index' => $_SESSION['api_squad_index'] ?? [],
            'effective_nationalities' => $_SESSION['effective_nationalities'] ?? [],
            'rigoristi_map' => $_SESSION['rigoristi_map'] ?? [],
            'rigoristi_raw_map' => $_SESSION['rigoristi_raw_map'] ?? []
        ];
        
        $analyzer->restoreFromSession($sessionData);
        
        error_log("Session restored with API caches - Players: " . count($analyzer->listaCorrente ?? []) . 
                  ", API teams: " . count($analyzer->apiSquadIndex) . 
                  ", Effective nationalities: " . count($analyzer->effectiveNationalities));
    } else {
        // **CARICAMENTO COMPLETO CON CACHE**
        error_log("Loading fresh data with API caches...");
        $analyzer->loadData();
        
        // Salva TUTTE le cache in sessione
        $sessionData = $analyzer->getSessionData();
        $_SESSION['data_loaded'] = true;
        $_SESSION['lista_corrente'] = $sessionData['lista_corrente'];
        $_SESSION['statistiche'] = $sessionData['statistiche'];
        $_SESSION['quotazioni'] = $sessionData['quotazioni'];
        $_SESSION['valutazioni'] = $sessionData['valutazioni'];
        $_SESSION['current_year'] = $sessionData['current_year'];
        $_SESSION['last_year'] = $sessionData['last_year'];
        $_SESSION['neopromosse'] = $sessionData['neopromosse'];
        $_SESSION['squadre_media'] = $sessionData['squadre_media'];
        $_SESSION['squadre_50ga'] = $sessionData['squadre_50ga'];
        $_SESSION['team_ga_last'] = $sessionData['team_ga_last'];
        $_SESSION['api_squad_index'] = $sessionData['api_squad_index'];
        $_SESSION['effective_nationalities'] = $sessionData['effective_nationalities'];
        $_SESSION['rigoristi_map'] = $sessionData['rigoristi_map'];
        $_SESSION['rigoristi_raw_map'] = $sessionData['rigoristi_raw_map'];
        
        error_log("Fresh data loaded and cached with API data");
    }
    
    switch ($action) {
        case 'run':
            $criteriaId = $_GET['criteria'] ?? '';
            if (empty($criteriaId)) {
                throw new Exception('No criteria specified');
            }
            
            if (!preg_match('/^(?:[1-9]|[12][0-9]|3[0-4])$/', $criteriaId)) {
                throw new Exception('Invalid criteria ID: ' . $criteriaId);
            }
            
            // **PERFORMANCE LOGGING**
            $startTime = microtime(true);
            
            // Verifica che i dati necessari siano presenti
            if (empty($analyzer->listaCorrente)) {
                throw new Exception('Lista corrente non caricata');
            }
            
            // **CONTROLLI SPECIFICI PER CRITERI CHE USANO CACHE API**
            $apiDependentCriteria = ['5', '6', '7', '30']; // Criteri che dipendono dalle cache API
            if (in_array($criteriaId, $apiDependentCriteria)) {
                if (empty($analyzer->effectiveNationalities) && in_array($criteriaId, ['5', '6', '7'])) {
                    throw new Exception("Criterio $criteriaId richiede cache nazionalità effettive non disponibile");
                }
                if (empty($analyzer->rigoristiMap) && $criteriaId === '30') {
                    throw new Exception("Criterio 30 richiede cache rigoristi non disponibile");
                }
            }
            
            // Per criteri che richiedono statistiche, verifica disponibilità
            $statsRequired = ['11','12','13','14','15','19','20','21','22','23','24','25','26','27','28','29','31','34'];
            if (in_array($criteriaId, $statsRequired) && 
                (!$analyzer->lastYear || !isset($analyzer->statistiche[$analyzer->lastYear]))) {
                throw new Exception("Criterio $criteriaId richiede dati statistiche per {$analyzer->lastYear}");
            }

            // NEW: Criterio 18 — Rosa stagione precedente + crediti attuali (lista corrente)
            if ($criteriaId === '18') {
                $fantasquadra = trim($_GET['fantasquadra'] ?? $_POST['fantasquadra'] ?? '');
                if ($fantasquadra === '') {
                    throw new Exception('Seleziona una fantasquadra per il criterio 18');
                }

                // CHANGED: usa l'anno precedente corretto
                $annoPrecedente = (int)($_SESSION['current_year'] ?? 0);
                if (!$annoPrecedente) { throw new Exception('Anno precedente non disponibile'); }

                // Connessione DB
                $pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                    DB_USER, DB_PASS,
                    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]
                );

                // Verifica esistenza rosa per quella fantasquadra nell'anno precedente
                $chk = $pdo->prepare("SELECT 1 FROM rosa WHERE nome_fantasquadra = :fsq AND anno = :anno LIMIT 1");
                $chk->execute([':fsq' => $fantasquadra, ':anno' => $annoPrecedente]);
                if (!$chk->fetchColumn()) {
                    echo json_encode(['success'=>false,'error'=>'Nessuna rosa trovata per la stagione precedente']);
                    exit;
                }

                // Estrai i giocatori della rosa (Schema B: rosa/dettagli_rosa/giocatore)
                $sql = "
                    SELECT 
                        g.id_giocatore       AS id,
                        g.codice_fantacalcio AS codice_fantacalcio,
                        g.nome_giocatore     AS nome_completo,
                        g.ruolo              AS ruolo_classic,
                        g.squadra_reale      AS squadra
                    FROM rosa r
                    JOIN dettagli_rosa dr ON dr.id_rosa = r.id_rosa
                    JOIN giocatore g      ON g.id_giocatore = dr.id_giocatore
                    WHERE r.nome_fantasquadra = :fsq
                    AND r.anno = :anno
                    ORDER BY g.nome_giocatore ASC
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':fsq' => $fantasquadra, ':anno' => $annoPrecedente]);
                $results = $stmt->fetchAll();

                // === Mappa crediti attuali ===
                $prezziMap = [];

                // 1) Preferisci la lista in sessione (caricata dall'Analyzer)
                if (!empty($_SESSION['lista_corrente']) && is_array($_SESSION['lista_corrente'])) {
                    foreach ($_SESSION['lista_corrente'] as $row) {
                        // CHANGED: il codice nella lista è 'id' (non 'codice_fantacalcio')
                        $code = (string)($row['id'] ?? '');
                        if ($code !== '') {
                            // 'quota_attuale_classic' è già mappata dall'Analyzer
                            $val = $row['quota_attuale_classic'] ?? null;
                            // opzionale: fallback su 'quotazione' se presente
                            if ($val === null || $val === '') { $val = $row['quotazione'] ?? null; }
                            $prezziMap[$code] = $val;
                        }
                    }
                }
                // 2) Fallback sul CSV se la lista non è in sessione
                if (empty($prezziMap)) {
                    $csvPath = BASE_PATH . '/data/lista/Lista-FantaAsta-Fantacalcio.csv';
                    if (is_file($csvPath) && is_readable($csvPath)) {
                        if (($fh = fopen($csvPath, 'r')) !== false) {
                            // prova prima con ',' poi con ';' e rileva se c'è header o dati
                            $try = function($sep) use (&$prezziMap, $fh) {
                                rewind($fh);
                                $first = fgetcsv($fh, 0, $sep);
                                if ($first === false) return false;

                                // rileva header
                                $lower = array_map(function($s){ return mb_strtolower(trim((string)$s)); }, $first);
                                $hasHeader = in_array('codice_fantacalcio', $lower, true) || in_array('id', $lower, true);

                                if ($hasHeader) {
                                    $map = array_flip($lower);
                                    $idxCode = $map['codice_fantacalcio'] ?? $map['id'] ?? null;
                                    // colonna 'quota_attuale_classic' secondo mapping Analyzer: se non c'è, prova 'quotazione'
                                    $idxQuota = $map['quota_attuale_classic'] ?? ($map['quotazione'] ?? null);
                                    if ($idxCode === null || $idxQuota === null) return false;

                                    while (($row = fgetcsv($fh, 0, $sep)) !== false) {
                                        $code = (string)($row[$idxCode] ?? '');
                                        if ($code !== '') {
                                            $val = $row[$idxQuota] ?? null;
                                            $prezziMap[$code] = $val;
                                        }
                                    }
                                    return true;
                                } else {
                                    // **NESSUN HEADER**: il file reale ha dati subito.
                                    // Mapping coerente con Analyzer:
                                    // 0 = id, 6 = quota_attuale_classic
                                    $process = function($row) use (&$prezziMap) {
                                        if (!is_array($row)) return;
                                        $code = (string)($row[0] ?? '');
                                        if ($code !== '') {
                                            $val = $row[6] ?? null; // CHANGED: indice 6 = quota_attuale_classic
                                            $prezziMap[$code] = $val;
                                        }
                                    };
                                    // processa la prima riga e le successive
                                    $process($first);
                                    while (($row = fgetcsv($fh, 0, $sep)) !== false) { $process($row); }
                                    return true;
                                }
                            };

                            // tenta ',' poi ';'
                            if (!$try(',')) { $try(';'); }
                            fclose($fh);
                        }
                    }
                }

                // Arricchisci i risultati con 'quota_attuale_classic' o messaggio in rosso
                foreach ($results as &$r) {
                    // CHANGED: i record DB hanno sia 'codice_fantacalcio' che (indirettamente) corrisponde a 'id' della lista
                    $code = (string)($r['codice_fantacalcio'] ?? '');
                    if ($code !== '' && isset($prezziMap[$code]) && $prezziMap[$code] !== null && $prezziMap[$code] !== '') {
                        $r['quota_attuale_classic'] = $prezziMap[$code];
                    } else {
                        $r['quota_attuale_classic'] = '<span class=\"text-danger\">giocatore non acquistabile</span>';
                    }
                }
                unset($r);

                echo json_encode([
                    'success'  => true,
                    'criteria' => '18',
                    'results'  => array_values($results),
                    'count'    => count($results)
                ], JSON_UNESCAPED_UNICODE);
                exit; // non proseguire con l'Analyzer
            }

            
            // **ESECUZIONE OTTIMIZZATA**
            $results = $analyzer->runCriteria($criteriaId);
            
            // Log performance
            logPerformance("Criterio $criteriaId", $startTime);
            
            error_log("Criterio $criteriaId completato: " . count($results) . " risultati in " . 
                     round((microtime(true) - $startTime) * 1000, 2) . "ms");
            
            // Rimuovi chiavi preservate da array_filter
            $results = array_values($results);
            
            // Aggiungi metadati utili
            $metadata = [
                'criteria_id' => $criteriaId,
                'current_year' => $analyzer->currentYear,
                'last_year' => $analyzer->lastYear,
                'execution_time' => date('Y-m-d H:i:s'),
                'total_players' => count($analyzer->listaCorrente ?? []),
                'used_api_cache' => !empty($analyzer->effectiveNationalities) || !empty($analyzer->rigoristiMap),
                'performance_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
            
            echo json_encode([
                'success' => true,
                'results' => $results,
                'count' => count($results),
                'metadata' => $metadata
            ]);
            break;
            
        case 'export':
            $input = json_decode(file_get_contents('php://input'), true);
            $criteriaId = $input['criteria'] ?? '';
            $format = $input['format'] ?? 'csv';
            $results = $input['results'] ?? [];
            
            if (empty($results)) {
                throw new Exception('No results to export');
            }
            
            if (!in_array($format, ['csv', 'xlsx', 'pdf'])) {
                throw new Exception('Invalid export format: ' . $format);
            }

            $cols    = getExportColumnsForCriteria($criteriaId);
            $results = normalizeResultsForExport($results, $cols);

            switch ($format) {
                case 'csv':
                    exportCsv($results, $criteriaId);
                    break;
                case 'xlsx':
                    exportExcel($results, $criteriaId);
                    break;
                case 'pdf':
                    exportPdf($results, $criteriaId);
                    break;
            }
            break;
            
        case 'cache_status':
            // **NUOVO: Endpoint per controllare stato cache**
            echo json_encode([
                'success' => true,
                'cache_status' => [
                    'data_loaded' => $_SESSION['data_loaded'] ?? false,
                    'has_lista' => !empty($_SESSION['lista_corrente']),
                    'has_api_cache' => !empty($_SESSION['api_squad_index']),
                    'has_nationalities' => !empty($_SESSION['effective_nationalities']),
                    'has_rigoristi' => !empty($_SESSION['rigoristi_map']),
                    'api_teams_count' => count($_SESSION['api_squad_index'] ?? []),
                    'nationalities_count' => count($_SESSION['effective_nationalities'] ?? []),
                    'rigoristi_teams_count' => count($_SESSION['rigoristi_map'] ?? [])
                ]
            ]);
            break;
            
        case 'clear_api_cache':
            // **NUOVO: Pulizia specifica delle cache API**
            unset($_SESSION['api_squad_index']);
            unset($_SESSION['effective_nationalities']);
            unset($_SESSION['rigoristi_map']);
            unset($_SESSION['rigoristi_raw_map']);
            
            echo json_encode([
                'success' => true,
                'message' => 'API caches cleared. Data will be reloaded on next criteria execution.'
            ]);
            break;
            
        case 'debug':
            echo json_encode([
                'success' => true,
                'debug' => [
                    'data_loaded' => $_SESSION['data_loaded'] ?? false,
                    'lista_count' => count($analyzer->listaCorrente ?? []),
                    'current_year' => $analyzer->currentYear,
                    'last_year' => $analyzer->lastYear,
                    'statistics_years' => array_keys($analyzer->statistiche),
                    'neopromosse' => $analyzer->neopromosse,
                    'squadre_media' => $analyzer->squadreMediaClassifica,
                    'squadre_50ga' => $analyzer->squadre50GolSubiti,
                    // **DEBUG CACHE API**
                    'api_cache_status' => [
                        'api_squad_teams' => array_keys($analyzer->apiSquadIndex),
                        'effective_nationalities_count' => count($analyzer->effectiveNationalities),
                        'rigoristi_teams' => array_keys($analyzer->rigoristiMap),
                        'sample_nationalities' => array_slice($analyzer->effectiveNationalities, 0, 5, true)
                    ]
                ]
            ]);
            break;

        case 'list_prev_teams':
            // NEW: restituisce l’elenco delle fantasquadre che hanno una rosa nell’anno precedente
            try {
                $annoPrecedente = (int)($_SESSION['current_year'] ?? 0);
                if (!$annoPrecedente) {
                    throw new Exception('Anno precedente non disponibile');
                }

                // Connessione DB (stessa impostazione di auth.php)
                $pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );

                $stmt = $pdo->prepare("
                    SELECT DISTINCT r.nome_fantasquadra
                    FROM rosa r
                    WHERE r.anno = :anno
                    ORDER BY r.nome_fantasquadra ASC
                ");
                $stmt->execute([':anno' => $annoPrecedente]);
                $teams = $stmt->fetchAll(PDO::FETCH_COLUMN);

                echo json_encode(['success' => true, 'teams' => $teams], JSON_UNESCAPED_UNICODE);
            } catch (Throwable $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

            
        
        
            default:
            throw new Exception('Invalid action: ' . $action);
    }
} catch (Exception $e) {
    error_log("Criteria API error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'has_session_cache' => isset($_SESSION['data_loaded']),
            'has_api_cache' => isset($_SESSION['api_squad_index']),
            'trace' => explode("\n", $e->getTraceAsString())
        ]
    ]);
}

/** Colonne da esportare = base UI + dettaglio per criterio */
function getExportColumnsForCriteria(string $criteriaId): array {
    // colonne base mostrate in tabella
    $base = ['nome_completo','ruolo_classic','squadra','quota_attuale_classic'];
    // estrai il primo numero utile (es. "12&27" -> "12")
    $id = preg_match('/\d+/', $criteriaId, $m) ? $m[0] : $criteriaId;
    $extra = (defined('CRITERION_DETAIL_COLS') && isset(CRITERION_DETAIL_COLS[$id]))
        ? CRITERION_DETAIL_COLS[$id] : [];
    // de-dup mantenendo l’ordine
    $seen = []; $cols = [];
    foreach (array_merge($base, $extra) as $c) {
        if (!isset($seen[$c])) { $seen[$c] = true; $cols[] = $c; }
    }
    return $cols;
}

/** Rimodella le righe tenendo SOLO le colonne scelte e nell’ordine voluto */
function normalizeResultsForExport(array $results, array $columns): array {
    $out = [];
    foreach ($results as $row) {
        $line = [];
        foreach ($columns as $c) {
            $line[$c] = $row[$c] ?? '';
        }
        $out[] = $line;
    }
    return $out;
}


// Funzioni di export (invariate)
function exportCsv(array $results, string $criteriaId): void
{
    $filename = "criterio_{$criteriaId}_" . date('Ymd_His') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    
    $output = fopen('php://output', 'w');
    
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    if (!empty($results)) {
        $headers = array_keys($results[0]);
        fputcsv($output, $headers, ';');
    }
    
    foreach ($results as $row) {
        $values = array_values($row);
        fputcsv($output, $values, ';');
    }
    
    fclose($output);
    exit;
}

function exportExcel(array $results, string $criteriaId): void
{
    $filename = "criterio_{$criteriaId}_" . date('Ymd_His') . ".xlsx";

    // Prepara array 2D: intestazioni + righe
    $data = [];
    if (!empty($results)) {
        $data[] = array_keys($results[0]);
        foreach ($results as $row) { $data[] = array_values($row); }
    }

    beforeBinaryDownload();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header('Cache-Control: max-age=0');

    $xlsx = SimpleXLSXGen::fromArray($data);
    echo $xlsx->toString(); // stampa il binario .xlsx
    exit;
}

function exportPdf(array $results, string $criteriaId): void
{
    $criteriaNames = getCriteriaNames();
    $criteriaName  = $criteriaNames[$criteriaId] ?? "Criterio $criteriaId";
    $baseName      = "criterio_{$criteriaId}_" . date('Ymd_His');

    $headers = !empty($results) ? array_keys($results[0]) : [];
    $pdf = buildMinimalPdf($criteriaName, $headers, $results);

    beforeBinaryDownload();
    header('Content-Type: application/pdf');
    header("Content-Disposition: attachment; filename=\"{$baseName}.pdf\"");
    echo $pdf;
    exit;
}

/** Escape sicuro per stringhe PDF ((), \ e whitespace) */
function pdf_escape(string $t): string {
    $t = str_replace(["\\","(",")"], ["\\\\","\\(","\\)"], $t);
    $t = preg_replace('/\s+/u', ' ', $t);
    if (mb_strlen($t) > 120) $t = mb_substr($t, 0, 117) . '…';
    return $t;
}

/**
 * Generatore PDF minimale (Helvetica, WinAnsi, multipagina).
 * Nessuna libreria esterna.
 */
function buildMinimalPdf(string $title, array $headers, array $rows): string {
    // A4 landscape in punti
    $w = 842; $h = 595; $margin = 36;
    $fontSize = 9; $lineH = 12;

    $colCount = max(1, count($headers));
    $usableW  = $w - 2*$margin;
    $colW     = array_fill(0, $colCount, $usableW / $colCount);

    // — Costruzione stream contenuti (una pagina alla volta) —
    $pages = [];
    $y = $h - $margin;

    $newPage = function() use (&$pages, &$y, $h, $margin, $fontSize) {
        if (!empty($pages)) $pages[count($pages)-1] .= "ET\n";
        $y = $h - $margin;
        // BT, selezione font e size
        $pages[] = "BT\n/F1 {$fontSize} Tf\n";
    };

    $drawRow = function(array $cells, float $yRow) use (&$pages, $headers, $colW, $margin) {
        $x = $margin;
        foreach ($cells as $txt) {
            $pages[count($pages)-1] .= sprintf("1 0 0 1 %.2f %.2f Tm (%s) Tj\n",
                $x, $yRow, pdf_escape($txt));
            $x += current($colW); next($colW);
        }
        reset($colW);
    };

    $newPage();

    // Titolo
    $pages[count($pages)-1] .= sprintf("1 0 0 1 %.2f %.2f Tm (%s) Tj\n",
        $margin, $y, pdf_escape("FantaScarrupat Analyzer — $title"));
    $y -= ($lineH + 6);

    // Header
    if (!empty($headers)) {
        $hdr = array_map(fn($h) => strtoupper(str_replace('_',' ',$h)), $headers);
        $drawRow($hdr, $y);
        $y -= $lineH;
    }

    // Righe
    foreach ($rows as $row) {
        if ($y < $margin + 2*$lineH) {
            $newPage();
            if (!empty($headers)) {
                $hdr = array_map(fn($h) => strtoupper(str_replace('_',' ',$h)), $headers);
                $drawRow($hdr, $y);
                $y -= $lineH;
            }
        }
        $cells = [];
        foreach ($headers as $hname) {
            $val = isset($row[$hname]) ? (string)$row[$hname] : '';
            $cells[] = $val;
        }
        $drawRow($cells, $y);
        $y -= $lineH;
    }
    if (!empty($pages)) $pages[count($pages)-1] .= "ET\n";

    // — Oggetti PDF —
    $objs = [];
    $addObj = function(string $s) use (&$objs) { $objs[] = $s; return count($objs); };

    // Font Type1 standard Helvetica con WinAnsi (accenti italiani ok)
    $fontId = $addObj("<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>");

    // Stream contenuti + Page objects
    $pageIds = [];
    foreach ($pages as $stream) {
        $content = "<< /Length ".strlen($stream)." >>\nstream\n".$stream."endstream";
        $contentId = $addObj($content);
        $page = "<< /Type /Page /Parent 0 0 R /MediaBox [0 0 $w $h] " .
                "/Resources << /Font << /F1 $fontId 0 R >> >> " .
                "/Contents $contentId 0 R >>";
        $pageIds[] = $addObj($page);
    }

    // Nodo Pages
    $kidsRef = implode(' 0 R ', $pageIds) . ' 0 R';
    $pagesDict = "<< /Type /Pages /Kids [ $kidsRef ] /Count ".count($pageIds)." >>";
    $pagesId = $addObj($pagesDict);

    // Fissa /Parent nei Page
    foreach ($pageIds as $pid) {
        $objs[$pid-1] = str_replace('/Parent 0 0 R', '/Parent '.$pagesId.' 0 R', $objs[$pid-1]);
    }

    // Catalogo
    $catalogId = $addObj("<< /Type /Catalog /Pages $pagesId 0 R >>");

    // — Serializza + xref —
    $out = "%PDF-1.4\n%âãÏÓ\n";
    $ofs = [0];
    foreach ($objs as $i => $obj) {
        $ofs[] = strlen($out);
        $out  .= ($i+1)." 0 obj\n".$obj."\nendobj\n";
    }
    $xrefPos = strlen($out);
    $out .= "xref\n0 ".(count($objs)+1)."\n";
    $out .= "0000000000 65535 f \n";
    for ($i=1; $i<=count($objs); $i++) {
        $out .= sprintf("%010d 00000 n \n", $ofs[$i]);
    }
    $out .= "trailer\n<< /Size ".(count($objs)+1)." /Root $catalogId 0 R >>\nstartxref\n".$xrefPos."\n%%EOF";
    return $out;
}


function getCriteriaList(): array
{
    return [
        ['id' => '1', 'name' => 'Under 23 (al 1° luglio)', 'description' => 'Giocatori nati dopo il 1° luglio 2002'],
        ['id' => '2', 'name' => 'Over 32 (al 1° luglio)', 'description' => 'Giocatori nati prima del 1° luglio 1993'],
        ['id' => '3', 'name' => 'Prima stagione in Serie A', 'description' => 'Giocatori senza presenze storiche'],
        ['id' => '4', 'name' => 'Più di 200 presenze in Serie A', 'description' => 'Giocatori esperti'],
        ['id' => '5', 'name' => 'Giocatori sudamericani', 'description' => 'Nazionalità sudamericane (risolte via API)'],
        ['id' => '6', 'name' => 'Giocatori africani', 'description' => 'Nazionalità africane (risolte via API)'],
        ['id' => '7', 'name' => 'Europei non italiani', 'description' => 'Nazionalità europee escl. Italia (risolte via API)'],
        ['id' => '8', 'name' => 'Squadre neopromosse', 'description' => 'Squadre promosse quest\'anno'],
        ['id' => '9', 'name' => 'Squadre 10°–17° scorsa stagione', 'description' => 'Squadre di media classifica'],
        ['id' => '10', 'name' => 'Portieri squadre con GA ≥ 50', 'description' => 'Portieri squadre difese deboli'],
        ['id' => '11', 'name' => 'Difensori con almeno 1 gol', 'description' => 'Difensori che segnano'],
        ['id' => '12', 'name' => 'Centrocampisti con almeno 3 assist', 'description' => 'Centrocampisti creativi'],
        ['id' => '13', 'name' => 'Attaccanti con massimo 5 gol', 'description' => 'Attaccanti poco prolifici'],
        ['id' => '14', 'name' => 'Meno di 10 presenze', 'description' => 'Giocatori poco utilizzati'],
        ['id' => '15', 'name' => 'Media voto < 6', 'description' => 'Giocatori con rendimento basso'],
        ['id' => '16', 'name' => 'Quotazione ≤ 6', 'description' => 'Giocatori economici'],
        ['id' => '17', 'name' => 'Quotazione ≤ 3', 'description' => 'Giocatori molto economici'],
        ['id' => '18', 'name' => '>10 gol nella scorsa stagione', 'description' => 'Giocatori prolifici'],
        ['id' => '19', 'name' => 'Ritorno in Serie A', 'description' => 'Giocatori che tornano dopo assenza'],
        ['id' => '20', 'name' => "Almeno 5 partite '6*' (S.V.)", 'description' => 'Giocatori spesso senza voto'],
        ['id' => '21', 'name' => 'Più di 7 ammonizioni', 'description' => 'Giocatori indisciplinati'],
        ['id' => '22', 'name' => 'Cambiato squadra', 'description' => 'Nuovi acquisti'],
        ['id' => '23', 'name' => 'Almeno 1 autogol', 'description' => 'Giocatori sfortunati'],
        ['id' => '24', 'name' => 'Almeno 34 presenze', 'description' => 'Giocatori sempre utilizzati'],
        ['id' => '25', 'name' => 'Almeno un rigore sbagliato', 'description' => 'Rigoristi imprecisi'],
        ['id' => '26', 'name' => 'Zero ammonizioni/espulsioni', 'description' => 'Giocatori disciplinati'],
        ['id' => '27', 'name' => 'Presenti ultime 3 stagioni', 'description' => 'Giocatori costanti'],
        ['id' => '28', 'name' => 'Gol+Assist ≥ 5', 'description' => 'Giocatori offensivi produttivi'],
        ['id' => '29', 'name' => 'Alto rapporto cartellini/presenze', 'description' => 'Giocatori indisciplinati'],
        ['id' => '30', 'name' => 'Rigoristi designati', 'description' => 'Tiratori di rigori (da cache ottimizzata)'],
        ['id' => '31', 'name' => 'Cambio ruolo ufficiale', 'description' => 'Giocatori che cambiano posizione'],
        ['id' => '32', 'name' => 'Esordienti assoluti', 'description' => 'Prima volta in Serie A'],
    ];
}

function getCriteriaNames(): array
{
    $list = getCriteriaList();
    $names = [];
    foreach ($list as $item) {
        $names[$item['id']] = $item['name'];
    }
    return $names;
}

function beforeBinaryDownload(): void {
    // Chiudi e pulisci TUTTI i buffer per evitare byte spurii/BOM
    while (ob_get_level() > 0) { ob_end_clean(); }
    // Disattiva eventuale compressione zlib
    if (ini_get('zlib.output_compression')) { ini_set('zlib.output_compression', 'Off'); }
    header_remove('Content-Encoding');
    header_remove('Transfer-Encoding');
}

