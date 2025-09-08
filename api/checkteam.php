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
require_once __DIR__ . '/../src/Utils/Logger.php';
require_once __DIR__ . '/../src/Utils/Cache.php';

use FantacalcioAnalyzer\Core\FantacalcioAnalyzer;
use FantacalcioAnalyzer\Utils\Cache;
use FantacalcioAnalyzer\Utils\Logger;

// CSRF validation
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$logger = new Logger("CheckMyTeam");

try {
    Cache::init();
    
    switch ($action) {
        case 'bootstrap':
            handleBootstrap();
            break;
            
        case 'evaluate_criteria':
            handleEvaluateCriteria();
            break;
            
        case 'cache_info':
            handleCacheInfo();
            break;
            
        default:
            throw new \Exception('Invalid action: ' . $action);
    }
} catch (\Exception $e) {
    $logger->error("CheckMyTeam API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'action' => $action
        ]
    ]);
}

/**
 * Carica i dati iniziali per CheckMyTeam
 */
function handleBootstrap(): void
{
    global $logger;
    
    $logger->info("CheckMyTeam bootstrap request initiated");
    
    // Verifica che la cache principale esista
    if (!Cache::exists()) {
        $logger->error("Bootstrap failed: main cache not available");
        echo json_encode([
            'success' => false,
            'error' => 'Cache not available',
            'message' => 'Dati non disponibili. Cache principale assente.'
        ]);
        return;
    }
    
    try {
        // Leggi i dati dalla cache principale
        $cacheData = Cache::read();
        
        // DEBUG: Log della struttura della cache per capire il problema
        $logger->info("Cache structure debug", [
            'cache_keys' => array_keys($cacheData ?? []),
            'has_lista_corrente' => isset($cacheData['lista_corrente']),
            'lista_corrente_count' => count($cacheData['lista_corrente'] ?? [])
        ]);
        
        // Prova diverse possibili strutture della cache
        $listaCorrente = null;
        
        if (isset($cacheData['lista_corrente']) && is_array($cacheData['lista_corrente'])) {
            $listaCorrente = $cacheData['lista_corrente'];
            $logger->info("Found lista_corrente directly in cache");
        } elseif (isset($cacheData['data']['lista_corrente'])) {
            $listaCorrente = $cacheData['data']['lista_corrente'];
            $logger->info("Found lista_corrente in cache.data");
        } elseif (isset($cacheData['session_data']['lista_corrente'])) {
            $listaCorrente = $cacheData['session_data']['lista_corrente'];
            $logger->info("Found lista_corrente in cache.session_data");
        } else {
            // Fallback: usa dati dalla sessione se disponibili
            if (isset($_SESSION['lista_corrente']) && is_array($_SESSION['lista_corrente'])) {
                $listaCorrente = $_SESSION['lista_corrente'];
                $logger->info("Using lista_corrente from session as fallback");
            }
        }
        
        if (!$listaCorrente || !is_array($listaCorrente) || count($listaCorrente) === 0) {
            // Debug aggiuntivo per capire cosa c'è nella cache
            $logger->error("No valid lista_corrente found", [
                'cache_data_sample' => array_slice($cacheData ?? [], 0, 5, true),
                'session_has_lista' => isset($_SESSION['lista_corrente']),
                'session_lista_count' => count($_SESSION['lista_corrente'] ?? [])
            ]);
            
            throw new \Exception('No valid lista_corrente found in cache or session');
        }
        
        $logger->info("Bootstrap: loaded " . count($listaCorrente) . " players");
        
        // Normalizza e organizza i dati per CheckMyTeam
        $players = normalizePlayersData($listaCorrente);
        $playersByRole = groupPlayersByRole($players);
        $criteriaList = getCriteriaList();
        
        $logger->info("Bootstrap data prepared", [
            'total_players' => count($players),
            'players_by_role' => array_map('count', $playersByRole),
            'criteria_count' => count($criteriaList)
        ]);
        
        echo json_encode([
            'success' => true,
            'players' => $players,
            'playersByRole' => $playersByRole,
            'criteriaList' => $criteriaList,
            'fromCache' => true,
            'stats' => [
                'total_players' => count($players),
                'players_by_role' => array_map('count', $playersByRole)
            ],
            'debug' => [
                'source' => isset($cacheData['lista_corrente']) ? 'cache_direct' : 
                           (isset($cacheData['data']['lista_corrente']) ? 'cache_data' :
                           (isset($cacheData['session_data']['lista_corrente']) ? 'cache_session_data' : 'session_fallback')),
                'raw_count' => count($listaCorrente)
            ]
        ]);
        
    } catch (\Exception $e) {
        $logger->error("CheckMyTeam bootstrap error: " . $e->getMessage());
        
        // Prova un ultimo fallback usando criteria.php
        try {
            $logger->info("Attempting fallback via criteria.php");
            
            // Usa un criterio che restituisce molti giocatori per ottenere la lista
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/criteria.php?action=run&criteria=16');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'X-CSRF-Token: ' . ($_SESSION['csrf_token'] ?? ''),
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if ($data && $data['success'] && isset($data['results'])) {
                    $players = normalizePlayersData($data['results']);
                    $playersByRole = groupPlayersByRole($players);
                    $criteriaList = getCriteriaList();
                    
                    $logger->info("Fallback successful", ['players_loaded' => count($players)]);
                    
                    echo json_encode([
                        'success' => true,
                        'players' => $players,
                        'playersByRole' => $playersByRole,
                        'criteriaList' => $criteriaList,
                        'fromCache' => false,
                        'fallback' => true,
                        'stats' => [
                            'total_players' => count($players),
                            'players_by_role' => array_map('count', $playersByRole)
                        ]
                    ]);
                    return;
                }
            }
        } catch (\Exception $fallbackError) {
            $logger->error("Fallback also failed: " . $fallbackError->getMessage());
        }
        
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'debug' => [
                'cache_exists' => Cache::exists(),
                'cache_info' => Cache::exists() ? Cache::getInfo() : null,
                'session_has_data' => isset($_SESSION['lista_corrente']),
                'session_data_count' => count($_SESSION['lista_corrente'] ?? [])
            ]
        ]);
    }
}

/**
 * Valuta un singolo criterio e restituisce i giocatori che lo rispettano
 */
function handleEvaluateCriteria(): void
{
    global $logger;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $criteriaId = $input['criteriaId'] ?? '';
    
    if (empty($criteriaId)) {
        throw new \Exception('criteriaId is required');
    }
    
    if (!preg_match('/^(?:[1-9]|[12][0-9]|3[0-4])$/', $criteriaId)) {
        throw new \Exception('Invalid criteria ID: ' . $criteriaId);
    }
    
    $logger->info("Evaluating criteria: $criteriaId");
    
    try {
        // Usa criteria.php per eseguire il criterio (stesso sistema di FindPlayer)
        // Questo assicura compatibilità e coerenza
        $analyzer = new FantacalcioAnalyzer();
        
        // Verifica se i dati sono già caricati in sessione
        $hasSessionData = isset($_SESSION['data_loaded']) && $_SESSION['data_loaded'] && 
                         isset($_SESSION['lista_corrente']);
        
        if ($hasSessionData) {
            // Ripristina dati dalla sessione
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
                'api_squad_index' => $_SESSION['api_squad_index'] ?? [],
                'effective_nationalities' => $_SESSION['effective_nationalities'] ?? [],
                'rigoristi_map' => $_SESSION['rigoristi_map'] ?? [],
                'rigoristi_raw_map' => $_SESSION['rigoristi_raw_map'] ?? []
            ];
            
            $analyzer->restoreFromSession($sessionData);
            $logger->info("Data restored from session for criteria evaluation");
        } else {
            // Se non ci sono dati in sessione, prova a caricarli dalla cache
            if (!Cache::exists()) {
                throw new \Exception('No cache available for criteria evaluation');
            }
            
            $analyzer->loadData();
            $logger->info("Data loaded fresh for criteria evaluation");
        }
        
        // Esegui il criterio
        $results = $analyzer->runCriteria($criteriaId);
        $playerIds = array_map(fn($player) => (int)($player['id'] ?? 0), $results);
        $playerIds = array_filter($playerIds, fn($id) => $id > 0);
        
        $logger->info("Criteria $criteriaId evaluated", [
            'matched_players' => count($playerIds)
        ]);
        
        echo json_encode([
            'success' => true,
            'criteriaId' => $criteriaId,
            'playerIds' => $playerIds,
            'count' => count($playerIds),
            'fromCache' => false
        ]);
        
    } catch (\Exception $e) {
        $logger->error("Criteria evaluation error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Informazioni sulla cache
 */
function handleCacheInfo(): void
{
    $cacheInfo = Cache::getInfo();
    
    echo json_encode([
        'success' => true,
        'cache_info' => $cacheInfo,
        'cache_exists' => Cache::exists()
    ]);
}

/**
 * Normalizza i dati dei giocatori per CheckMyTeam
 */
function normalizePlayersData(array $players): array
{
    $normalized = [];
    
    foreach ($players as $player) {
        // Salta giocatori senza dati essenziali
        if (empty($player['id']) || empty($player['ruolo_classic'])) {
            continue;
        }
        
        $normalized[] = [
            'id' => (int)$player['id'],
            'nome_completo' => trim($player['nome_completo'] ?? ''),
            'ruolo_classic' => trim($player['ruolo_classic']),
            'squadra' => trim($player['squadra'] ?? ''),
            'quota_attuale_classic' => (float)($player['quota_attuale_classic'] ?? 0),
            'nazionalita' => trim($player['nazionalita'] ?? ''),
            'data_nascita' => $player['data_nascita'] ?? null
        ];
    }
    
    // Ordina per nome per consistenza
    usort($normalized, fn($a, $b) => strcmp($a['nome_completo'], $b['nome_completo']));
    
    return $normalized;
}

/**
 * Raggruppa i giocatori per ruolo
 */
function groupPlayersByRole(array $players): array
{
    $byRole = ['P' => [], 'D' => [], 'C' => [], 'A' => []];
    
    foreach ($players as $player) {
        $role = $player['ruolo_classic'];
        if (isset($byRole[$role])) {
            $byRole[$role][] = $player;
        }
    }
    
    return $byRole;
}

/**
 * Lista dei criteri disponibili
 */
function getCriteriaList(): array
{
    return [
        '1' => ['name' => 'Under 23 (al 1° luglio)', 'description' => 'Giocatori nati dopo il 1° luglio 2002'],
        '2' => ['name' => 'Over 32 (al 1° luglio)', 'description' => 'Giocatori nati prima del 1° luglio 1993'],
        '3' => ['name' => 'Prima stagione in Serie A', 'description' => 'Giocatori senza presenze storiche'],
        '4' => ['name' => 'Più di 200 presenze in Serie A', 'description' => 'Giocatori esperti'],
        '5' => ['name' => 'Giocatori sudamericani', 'description' => 'Nazionalità sudamericane (risolte via API)'],
        '6' => ['name' => 'Giocatori africani', 'description' => 'Nazionalità africane (risolte via API)'],
        '7' => ['name' => 'Europei non italiani', 'description' => 'Nazionalità europee escl. Italia (risolte via API)'],
        '8' => ['name' => 'Squadre neopromosse', 'description' => 'Squadre promosse quest\'anno'],
        '9' => ['name' => 'Squadre 10°—17° scorsa stagione', 'description' => 'Squadre di media classifica'],
        '10' => ['name' => 'Portieri squadre con GA ≥ 50', 'description' => 'Portieri squadre difese deboli'],
        '11' => ['name' => 'Difensori con almeno 1 gol', 'description' => 'Difensori che segnano'],
        '12' => ['name' => 'Centrocampisti con almeno 3 assist', 'description' => 'Centrocampisti creativi'],
        '13' => ['name' => 'Attaccanti con massimo 5 gol', 'description' => 'Attaccanti poco prolifici'],
        '14' => ['name' => 'Meno di 10 presenze', 'description' => 'Giocatori poco utilizzati'],
        '15' => ['name' => 'Media voto < 6', 'description' => 'Giocatori con rendimento basso'],
        '16' => ['name' => 'Quotazione ≤ 6', 'description' => 'Giocatori economici'],
        '17' => ['name' => 'Quotazione ≤ 3', 'description' => 'Giocatori molto economici'],
        '18' => ['name' => 'Rosa stagione precedente', 'description' => 'Giocatori della rosa dell\'anno scorso'],
        '19' => ['name' => 'Ritorno in Serie A', 'description' => 'Giocatori che tornano dopo assenza'],
        '20' => ['name' => 'Almeno 5 partite \'6*\' (S.V.)', 'description' => 'Giocatori spesso senza voto'],
        '21' => ['name' => 'Più di 7 ammonizioni', 'description' => 'Giocatori indisciplinati'],
        '22' => ['name' => 'Cambiato squadra', 'description' => 'Nuovi acquisti'],
        '23' => ['name' => 'Almeno 1 autogol', 'description' => 'Giocatori sfortunati'],
        '24' => ['name' => 'Almeno 34 presenze', 'description' => 'Giocatori sempre utilizzati'],
        '25' => ['name' => 'Almeno un rigore sbagliato', 'description' => 'Rigoristi imprecisi'],
        '26' => ['name' => 'Zero ammonizioni/espulsioni', 'description' => 'Giocatori disciplinati'],
        '27' => ['name' => 'Presenti ultime 3 stagioni', 'description' => 'Giocatori costanti'],
        '28' => ['name' => 'Gol+Assist ≥ 5', 'description' => 'Giocatori offensivi produttivi'],
        '29' => ['name' => 'Alto rapporto cartellini/presenze', 'description' => 'Giocatori indisciplinati'],
        '30' => ['name' => 'Rigoristi designati', 'description' => 'Tiratori di rigori (da cache ottimizzata)'],
        '31' => ['name' => 'Cambio ruolo ufficiale', 'description' => 'Giocatori che cambiano posizione'],
        '32' => ['name' => 'Esordienti assoluti', 'description' => 'Prima volta in Serie A']
    ];
}