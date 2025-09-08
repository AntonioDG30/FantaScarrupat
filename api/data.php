<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../autoload.php';

use FantacalcioAnalyzer\Utils\Cache;
use FantacalcioAnalyzer\Utils\Logger;

// CSRF validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
}

$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
$logger = new Logger("DataAPI");
$action = $_GET['action'] ?? '';

try {
    Cache::init();
    
    switch ($action) {
        case 'cache_status':
            handleCacheStatus();
            break;
            
        case 'load_from_cache':
            handleLoadFromCache();
            break;
            
        case 'rebuild_cache':
            handleRebuildCache();
            break;
            
        case 'cache_info':
            handleCacheInfo();
            break;
            
        // Compatibilità con endpoint esistenti
        case 'load':
            handleLegacyLoad();
            break;
            
        case 'status':
            handleLegacyStatus();
            break;
            
        case 'clear_cache':
            handleClearCache();
            break;
            
        default:
            throw new \Exception('Invalid action: ' . $action);
    }
} catch (\Exception $e) {
    $logger->error("Data API error: " . $e->getMessage());
    
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
 * Verifica stato cache per tipo utente
 */
function handleCacheStatus(): void
{
    global $isAdmin;
    
    $status = Cache::getStatusForUser($isAdmin);
    
    echo json_encode([
        'success' => true,
        'is_admin' => $isAdmin,
        'cache_status' => $status
    ]);
}

/**
 * Carica dati dalla cache in sessione
 */
function handleLoadFromCache(): void
{
    global $logger;
    
    if (!Cache::exists()) {
        echo json_encode([
            'success' => false,
            'error' => 'Cache not found'
        ]);
        return;
    }
    
    if (!Cache::loadToSession()) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to load cache to session'
        ]);
        return;
    }
    
    $cacheInfo = Cache::getInfo();
    
    echo json_encode([
        'success' => true,
        'message' => 'Data loaded from cache',
        'cache_info' => $cacheInfo,
        'stats' => [
            'players_count' => count($_SESSION['lista_corrente'] ?? []),
            'statistics_years' => array_keys($_SESSION['statistiche'] ?? []),
            'quotazioni_years' => array_keys($_SESSION['quotazioni'] ?? []),
            'valutazioni_years' => array_keys($_SESSION['valutazioni'] ?? []),
            'api_teams' => count($_SESSION['api_squad_index'] ?? []),
            'effective_nationalities' => count($_SESSION['effective_nationalities'] ?? [])
        ]
    ]);
    
    $logger->info("Data loaded from cache to session");
}

/**
 * Rigenera cache (solo admin)
 */
function handleRebuildCache(): void
{
    global $isAdmin, $logger;
    
    if (!$isAdmin) {
        error_log("[DATA] Unauthorized cache rebuild attempt");
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized: admin access required'
        ]);
        return;
    }
    
    error_log("[DATA] Admin cache rebuild requested at " . date('Y-m-d H:i:s'));
    $logger->info("Cache rebuild requested by admin");
    
    // Aumenta limiti per operazione pesante
    @ini_set('max_execution_time', '600');
    @set_time_limit(600);
    @ini_set('memory_limit', '512M');
    
    error_log("[DATA] Calling Cache::rebuild()...");
    
    $startTime = microtime(true);
    $result = Cache::rebuild();
    $duration = microtime(true) - $startTime;
    
    error_log("[DATA] Cache rebuild completed in " . round($duration, 2) . " seconds");
    
    if ($result['success']) {
        // Carica immediatamente in sessione i nuovi dati
        error_log("[DATA] Loading rebuilt cache to session...");
        Cache::loadToSession();
        error_log("[DATA] Cache rebuild operation completed successfully");
        $logger->info("Cache rebuilt and loaded to session");
    } else {
        error_log("[DATA] Cache rebuild operation failed: " . ($result['error'] ?? 'Unknown error'));
    }
    
    echo json_encode($result);
}

/**
 * Informazioni dettagliate cache
 */
function handleCacheInfo(): void
{
    global $isAdmin;
    
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized: admin access required'
        ]);
        return;
    }
    
    $info = Cache::getInfo();
    
    echo json_encode([
        'success' => true,
        'cache_info' => $info
    ]);
}

/**
 * Compatibilità: endpoint load legacy
 */
function handleLegacyLoad(): void
{
    global $isAdmin, $logger;
    
    if (!$isAdmin) {
        // Non-admin: verifica solo esistenza cache
        if (!Cache::exists()) {
            echo json_encode([
                'success' => false,
                'error' => 'cache_missing_non_admin',
                'message' => 'Cache assente. Contattare l\'admin.'
            ]);
            return;
        }
        
        // Carica dalla cache
        if (Cache::loadToSession()) {
            $cacheInfo = Cache::getInfo();
            echo json_encode([
                'success' => true,
                'from_cache' => true,
                'cache_info' => $cacheInfo
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to load from cache'
            ]);
        }
        return;
    }
    
    // Admin: verifica cache o rigenera se richiesto
    $forceRefresh = (bool)($_GET['refresh'] ?? $_POST['refresh'] ?? false)
                || (($_SERVER['HTTP_X_FORCE_REFRESH'] ?? '') === '1');
    
    if ($forceRefresh || !Cache::exists()) {
        $logger->info("Admin requested cache rebuild");
        
        $result = Cache::rebuild();
        
        if ($result['success']) {
            Cache::loadToSession();
        }
        
        echo json_encode($result);
    } else {
        // Carica dalla cache esistente
        if (Cache::loadToSession()) {
            $cacheInfo = Cache::getInfo();
            echo json_encode([
                'success' => true,
                'from_cache' => true,
                'cache_info' => $cacheInfo
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to load from cache'
            ]);
        }
    }
}

/**
 * Compatibilità: status legacy
 */
function handleLegacyStatus(): void
{
    $hasCache = Cache::exists();
    $hasSession = isset($_SESSION['data_loaded']) && $_SESSION['data_loaded'];
    
    echo json_encode([
        'success' => true,
        'loaded' => $hasSession,
        'cache_available' => $hasCache,
        'cache_info' => $hasCache ? Cache::getInfo() : null,
        'summary' => $hasSession ? [
            'current_year' => $_SESSION['current_year'] ?? null,
            'last_year' => $_SESSION['last_year'] ?? null,
            'players_count' => count($_SESSION['lista_corrente'] ?? []),
            'statistics_years' => array_keys($_SESSION['statistiche'] ?? []),
            'quotazioni_years' => array_keys($_SESSION['quotazioni'] ?? []),
            'valutazioni_years' => array_keys($_SESSION['valutazioni'] ?? [])
        ] : null
    ]);
}

/**
 * Pulizia cache
 */
function handleClearCache(): void
{
    global $isAdmin;
    
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized: admin access required'
        ]);
        return;
    }
    
    // Pulisce cache file
    $cacheFile = Cache::getCacheFile();
    if (file_exists($cacheFile)) {
        @unlink($cacheFile);
    }
    
    // Pulisce backup
    $backupFile = Cache::getBackupFile();
    if (file_exists($backupFile)) {
        @unlink($backupFile);
    }
    
    // Pulisce sessione
    unset($_SESSION['data_loaded']);
    unset($_SESSION['lista_corrente']);
    unset($_SESSION['statistiche']);
    unset($_SESSION['quotazioni']);
    unset($_SESSION['valutazioni']);
    unset($_SESSION['api_squad_index']);
    unset($_SESSION['effective_nationalities']);
    unset($_SESSION['rigoristi_map']);
    unset($_SESSION['rigoristi_raw_map']);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Cache e sessione pulite'
    ]);
}