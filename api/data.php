<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../autoload.php';

use FantacalcioAnalyzer\Core\FantacalcioAnalyzer;
use FantacalcioAnalyzer\Utils\Cache;
use FantacalcioAnalyzer\Utils\Progress;

// CSRF validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
}

$action = $_GET['action'] ?? '';

try {
    // Inizializza i componenti di cache e progress
    Cache::init();
    
    switch ($action) {
        case 'load':
        handleLoadData();
        break;
        
        case 'clear_progress':
            Progress::clear();
            echo json_encode(['success' => true, 'message' => 'Progress cleared']);
            break;
            
        case 'status':
            handleStatus();
            break;
            
        case 'clear_cache':
            handleClearCache();
            break;
            
        case 'cache_info':
            handleCacheInfo();
            break;
            
        default:
            throw new \Exception('Invalid action');
    }
} catch (\Exception $e) {
    // Assicurati che il progress sia pulito in caso di errore
    Progress::error($e->getMessage(), $e);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

function handleLoadData(): void
{
    try {
        ignore_user_abort(true);
        @ini_set('max_execution_time', '600');
        @set_time_limit(600);

        $forceRefresh = (bool)($_GET['refresh'] ?? $_POST['refresh'] ?? false)
                    || (($_SERVER['HTTP_X_FORCE_REFRESH'] ?? '') === '1');

        $currentHash = Cache::computeInputsHash();
        
        // *** VERIFICA SE C'È GIÀ UN'OPERAZIONE IN CORSO ***
        $existingJob = getActiveJob();
        if ($existingJob) {
            // Un altro utente sta già caricando - agganciati
            echo json_encode([
                'success' => true,
                'joined_existing_job' => true,
                'job_id' => $existingJob['job_id'],
                'started_by' => $existingJob['started_by'],
                'operation' => $existingJob['operation'],
                'message' => 'Operazione già in corso da altro utente - agganciato al progress'
            ]);
            return;
        }
        
        // *** TENTATIVO DI ACQUISIRE IL LOCK GLOBALE ***
        $jobId = generateJobId();
        if (!acquireGlobalLock($jobId)) {
            // Race condition - prova a trovare il job che è riuscito ad acquisire il lock
            sleep(1);
            $existingJob = getActiveJob();
            if ($existingJob) {
                echo json_encode([
                    'success' => true,
                    'joined_existing_job' => true,
                    'job_id' => $existingJob['job_id'],
                    'message' => 'Agganciato ad operazione in corso'
                ]);
                return;
            } else {
                throw new \Exception('Impossibile acquisire lock per operazione');
            }
        }
        
        // *** QUESTO UTENTE DIVENTA IL "LEADER" ***
        registerJobLeader($jobId, $forceRefresh ? 'force_rebuild' : 'cache_check');
        
        // Pulizia e inizializzazione
        Progress::clear();
        usleep(100000);
        Progress::init(12, 'Caricamento dati');
        Progress::update(0, 'Avvio operazione...');

        Progress::update(1, 'Analisi richiesta...');

        if ($forceRefresh) {
            Progress::update(2, 'Avvio rigenerazione forzata...');
            handleFullLoad($currentHash, true, $jobId);
        } else {
            Progress::update(2, 'Verifica validità cache...');
            $cacheValid = Cache::isValid($currentHash, CACHE_TTL_SECONDS);
            
            if ($cacheValid) {
                Progress::update(2, 'Cache valida, caricamento rapido...');
                handleCachedLoad($currentHash, $jobId);
            } else {
                Progress::update(2, 'Cache scaduta, rigenerazione necessaria...');
                handleFullLoad($currentHash, false, $jobId);
            }
        }

    } catch (\Exception $e) {
        cleanupJob();
        Progress::error('Errore durante il caricamento: ' . $e->getMessage(), $e);
        throw $e;
    }
}

function generateJobId(): string
{
    return 'rebuild_' . uniqid() . '_' . time();
}

function getActiveJob(): ?array
{
    $basePath = defined('BASE_PATH') ? BASE_PATH : getcwd();
    $jobFile = $basePath . '/logs/active_job.json';
    
    if (!file_exists($jobFile)) {
        return null;
    }
    
    $content = @file_get_contents($jobFile);
    if ($content === false) {
        return null;
    }
    
    $job = json_decode($content, true);
    if (!is_array($job)) {
        return null;
    }
    
    // Verifica che il job non sia troppo vecchio (timeout 10 minuti)
    $age = time() - ($job['started_at'] ?? 0);
    if ($age > 600) {
        @unlink($jobFile);
        return null;
    }
    
    return $job;
}

function acquireGlobalLock(string $jobId): bool
{
    $basePath = defined('BASE_PATH') ? BASE_PATH : getcwd();
    $lockFile = $basePath . '/logs/.global_rebuild.lock';
    $logsDir = dirname($lockFile);
    
    if (!is_dir($logsDir)) {
        @mkdir($logsDir, 0755, true);
    }
    
    // Tentativo atomico di creare il lock
    $fp = @fopen($lockFile, 'x');
    if ($fp === false) {
        return false;
    }
    
    fwrite($fp, $jobId . '|' . time() . '|' . ($_SESSION['user_id'] ?? session_id()));
    fclose($fp);
    
    return true;
}

function registerJobLeader(string $jobId, string $operation): void
{
    $basePath = defined('BASE_PATH') ? BASE_PATH : getcwd();
    $jobFile = $basePath . '/logs/active_job.json';
    
    $job = [
        'job_id' => $jobId,
        'operation' => $operation,
        'started_by' => $_SESSION['user_id'] ?? session_id(),
        'started_at' => time(),
        'participants' => [session_id()]
    ];
    
    file_put_contents($jobFile, json_encode($job, JSON_PRETTY_PRINT));
}

function addJobParticipant(): void
{
    $basePath = defined('BASE_PATH') ? BASE_PATH : getcwd();
    $jobFile = $basePath . '/logs/active_job.json';
    
    if (!file_exists($jobFile)) {
        return;
    }
    
    $job = json_decode(file_get_contents($jobFile), true);
    if (is_array($job) && !in_array(session_id(), $job['participants'] ?? [])) {
        $job['participants'][] = session_id();
        file_put_contents($jobFile, json_encode($job, JSON_PRETTY_PRINT));
    }
}

function cleanupJob(): void
{
    $basePath = defined('BASE_PATH') ? BASE_PATH : getcwd();
    @unlink($basePath . '/logs/active_job.json');
    @unlink($basePath . '/logs/.global_rebuild.lock');
}

function handleCachedLoad(string $currentHash, string $jobId = ''): void
{
    Progress::init(3, 'Loading from cache');
    
    try {
        Progress::update(1, 'Lettura cache valida...');
        
        // Leggi i dati dalla cache
        $cachedData = [
            'lista_corrente' => Cache::read('lista_corrente'),
            'statistiche' => Cache::read('statistiche'),
            'quotazioni' => Cache::read('quotazioni'),
            'valutazioni' => Cache::read('valutazioni'),
            'metadata' => Cache::read('metadata'),
            'api_caches' => Cache::read('api_caches')
        ];

        Progress::update(2, 'Ripristino sessione...');
        
        // Verifica che tutti i dati necessari siano presenti
        foreach (['lista_corrente', 'metadata'] as $key) {
            if (empty($cachedData[$key])) {
                throw new \Exception("Cache incompleta: manca $key");
            }
        }

        // Ripristina la sessione con i dati cached
        $metadata = $cachedData['metadata'];
        
        $_SESSION['data_loaded'] = true;
        $_SESSION['lista_corrente'] = $cachedData['lista_corrente'];
        $_SESSION['statistiche'] = $cachedData['statistiche'] ?? [];
        $_SESSION['quotazioni'] = $cachedData['quotazioni'] ?? [];
        $_SESSION['valutazioni'] = $cachedData['valutazioni'] ?? [];
        
        // Metadati calcolati
        foreach (['current_year', 'last_year', 'neopromosse', 'squadre_media', 'squadre_50ga', 'team_ga_last'] as $key) {
            $_SESSION[$key] = $metadata[$key] ?? null;
        }
        
        // Cache API se presenti
        $apiCaches = $cachedData['api_caches'] ?? [];
        foreach (['api_squad_index', 'effective_nationalities', 'rigoristi_map', 'rigoristi_raw_map'] as $key) {
            $_SESSION[$key] = $apiCaches[$key] ?? [];
        }

        Progress::update(3, 'Cache caricata con successo');
        $ttlH = (CACHE_TTL_SECONDS >= 3600) ? (int)(CACHE_TTL_SECONDS/3600).'h' : (int)(CACHE_TTL_SECONDS/60).'m';
        $ttlText = (CACHE_TTL_SECONDS >= 3600)
            ? ((int)(CACHE_TTL_SECONDS/3600) . 'h')
            : ((int)(CACHE_TTL_SECONDS/60) . 'm');
        Progress::finish('Dati caricati dalla cache (≤ ' . $ttlText . ')');

        
        $cacheInfo = Cache::getInfo();
        
        echo json_encode([
            'success' => true,
            'job_id' => $jobId,
            'from_cache' => true,
            'data' => [
                'current_year' => $metadata['current_year'] ?? null,
                'last_year' => $metadata['last_year'] ?? null,
                'players_count' => count($cachedData['lista_corrente'] ?? []),
                'statistics_years' => array_keys($cachedData['statistiche'] ?? []),
                'quotazioni_years' => array_keys($cachedData['quotazioni'] ?? []),
                'valutazioni_years' => array_keys($cachedData['valutazioni'] ?? []),
                'neopromosse' => $metadata['neopromosse'] ?? [],
                'squadre_media' => $metadata['squadre_media'] ?? [],
                'squadre_50ga' => $metadata['squadre_50ga'] ?? [],
                'cache_age' => $cacheInfo['age_formatted'] ?? 'sconosciuta'
            ],
            'cache_info' => $cacheInfo,
            'performance' => [
                'from_cache' => true,
                'cache_age_seconds' => $cacheInfo['age_seconds'] ?? 0
            ]
        ]);
        
    } catch (\Exception $e) {
        Progress::error('Errore lettura cache: ' . $e->getMessage());
        error_log("Cache read error: " . $e->getMessage());

        cleanupJob();
        throw $e;
        
        // Fallback: esegui caricamento completo
        handleFullLoad($currentHash, true);
    } finally {
        cleanupJob();
    }
}

function handleFullLoad(string $currentHash, bool $forced = false, string $jobId = ''): void
{
    // Verifica/acquisisci lock per evitare esecuzioni concorrenti
    if (!Cache::acquireLock(60)) {
        throw new \Exception('Un\'altra operazione di caricamento è già in corso. Riprova tra qualche minuto.');
    }
    
    $lockReleased = false;
    
    try {
        Progress::init(12, 'Loading data');
        Progress::update(1, 'Inizializzazione analyzer...');

        $analyzer = new FantacalcioAnalyzer();
        
        Progress::update(2, 'Caricamento completo dei dati...');
        $analyzer->loadData();
        
        Progress::update(10, 'Preparazione cache...');
        
        // Ottieni tutti i dati per la cache
        $sessionData = $analyzer->getSessionData();
        
        // Salva nella cache
        Cache::write('lista_corrente', $sessionData['lista_corrente']);
        Cache::write('statistiche', $sessionData['statistiche']);
        Cache::write('quotazioni', $sessionData['quotazioni']);
        Cache::write('valutazioni', $sessionData['valutazioni']);
        
        $metadata = [
            'current_year' => $sessionData['current_year'],
            'last_year' => $sessionData['last_year'],
            'neopromosse' => $sessionData['neopromosse'],
            'squadre_media' => $sessionData['squadre_media'],
            'squadre_50ga' => $sessionData['squadre_50ga'],
            'team_ga_last' => $sessionData['team_ga_last']
        ];
        Cache::write('metadata', $metadata);
        
        $apiCaches = [
            'api_squad_index' => $sessionData['api_squad_index'],
            'effective_nationalities' => $sessionData['effective_nationalities'],
            'rigoristi_map' => $sessionData['rigoristi_map'],
            'rigoristi_raw_map' => $sessionData['rigoristi_raw_map']
        ];
        Cache::write('api_caches', $apiCaches);

        Progress::update(11, 'Aggiornamento manifest...');
        
        // Aggiorna il manifest
        Cache::setManifest($currentHash, [
            'forced' => $forced,
            'total_players' => count($sessionData['lista_corrente'] ?? []),
            'api_teams' => count($sessionData['api_squad_index'] ?? []),
            'effective_nationalities' => count($sessionData['effective_nationalities'] ?? [])
        ]);

        // Salva anche in sessione per compatibilità
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
        
        Cache::releaseLock();
        $lockReleased = true;
        
        Progress::update(12, 'Operazione completata');
        Progress::finish('Dati caricati e cache aggiornata');

        error_log("Data saved to cache with API caches: " . json_encode([
            'players' => count($_SESSION['lista_corrente'] ?? []),
            'api_squads' => count($_SESSION['api_squad_index'] ?? []),
            'effective_nat' => count($_SESSION['effective_nationalities'] ?? []),
            'rigoristi' => count($_SESSION['rigoristi_map'] ?? [])
        ]));
        
        echo json_encode([
            'success' => true,
            'job_id' => $jobId,
            'from_cache' => false,
            'data' => $analyzer->getLoadedDataSummary(),
            'cache_info' => [
                'api_squad_teams' => count($sessionData['api_squad_index']),
                'effective_nationalities' => count($sessionData['effective_nationalities']),
                'rigoristi_teams' => count($sessionData['rigoristi_map']),
                'total_api_players' => array_sum(array_map('count', $sessionData['api_squad_index']))
            ],
            'performance' => [
                'from_cache' => false,
                'forced_refresh' => $forced,
                'build_timestamp' => time()
            ]
        ]);
        
    } catch (\Exception $e) {
        if (!$lockReleased) {
            Cache::releaseLock();
        }
        
        cleanupJob();
        throw $e;

    } finally {
        cleanupJob();
    }
}

function handleStatus(): void
{
    // Controlla se abbiamo anche le cache API
    $hasApiCaches = !empty($_SESSION['api_squad_index']) && 
                   !empty($_SESSION['effective_nationalities']);
    
    $cacheInfo = Cache::getInfo();
    
    echo json_encode([
        'success' => true,
        'loaded' => $_SESSION['data_loaded'] ?? false,
        'api_caches_ready' => $hasApiCaches,
        'cache_info' => $cacheInfo,
        'cache_stats' => [
            'api_squad_teams' => count($_SESSION['api_squad_index'] ?? []),
            'effective_nationalities' => count($_SESSION['effective_nationalities'] ?? []),
            'rigoristi_teams' => count($_SESSION['rigoristi_map'] ?? [])
        ],
        'summary' => isset($_SESSION['data_loaded']) && $_SESSION['data_loaded'] 
            ? [
                'current_year' => $_SESSION['current_year'] ?? null,
                'last_year' => $_SESSION['last_year'] ?? null,
                'players_count' => count($_SESSION['lista_corrente'] ?? []),
                'statistics_years' => array_keys($_SESSION['statistiche'] ?? []),
                'quotazioni_years' => array_keys($_SESSION['quotazioni'] ?? []),
                'valutazioni_years' => array_keys($_SESSION['valutazioni'] ?? []),
                'neopromosse' => $_SESSION['neopromosse'] ?? [],
                'squadre_media' => $_SESSION['squadre_media'] ?? [],
                'squadre_50ga' => $_SESSION['squadre_50ga'] ?? []
            ]
            : null
    ]);
}

function handleClearCache(): void
{
    // Pulisce sia la cache che la sessione
    Cache::clear();
    
    unset($_SESSION['data_loaded']);
    unset($_SESSION['lista_corrente']);
    unset($_SESSION['statistiche']);
    unset($_SESSION['quotazioni']);
    unset($_SESSION['valutazioni']);
    unset($_SESSION['api_squad_index']);
    unset($_SESSION['effective_nationalities']);
    unset($_SESSION['rigoristi_map']);
    unset($_SESSION['rigoristi_raw_map']);
    
    // Pulisci anche il progress
    Progress::clear();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Cache e sessione pulite'
    ]);
}

function handleCacheInfo(): void
{
    $cacheInfo = Cache::getInfo();
    $currentHash = Cache::computeInputsHash();
    $isValid = Cache::isValid($currentHash, CACHE_TTL_SECONDS);
    
    echo json_encode([
    'success' => true,
    'cache_info' => $cacheInfo,
    'current_hash' => $currentHash,
    'is_valid' => $isValid,
    'ttl_seconds' => CACHE_TTL_SECONDS
    ]);

}
