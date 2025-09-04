<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../autoload.php';

use FantacalcioAnalyzer\Utils\Progress;
use FantacalcioAnalyzer\Utils\Cache;

try {
    Cache::init();

    addJobParticipantIfExists();
    
    // *** STATO UNIFICATO: Combina Progress + Cache info ***
    $progressState = Progress::getState();
    $cacheInfo = Cache::getInfo();
    $activeJob = getActiveJobInfo();
    
    // *** LOGICA PIU' RIGOROSA PER DETERMINARE SE OPERAZIONE E' ATTIVA ***
    $progressActive = Progress::isActive();
    $lockActive = $cacheInfo['lock_active'] ?? false;
    $isOperationActive = $progressActive || $lockActive;
    
    // Se il progress dice "completato" MA c'è ancora un lock, non è davvero finita
    $trueCompleted = ($progressState['completed'] ?? false) && !$lockActive;
    
    $response = [
        'step' => $progressState['step'] ?? 0,
        'total' => $progressState['total'] ?? 1,
        'percent' => $progressState['percent'] ?? 0,
        'label' => $progressState['label'] ?? ($isOperationActive ? 'Elaborazione...' : 'In attesa...'),
        'error' => $progressState['error'] ?? null,
        'completed' => $trueCompleted,
        'ts' => time(),
        'job_info' => $activeJob ? [
            'job_id' => $activeJob['job_id'],
            'started_by_me' => ($_SESSION['user_id'] ?? session_id()) === $activeJob['started_by'],
            'participants_count' => count($activeJob['participants'] ?? []),
            'operation_type' => $activeJob['operation'] ?? 'unknown'
        ] : null,
        'duration' => ($progressState['updated_at'] ?? time()) - ($progressState['started_at'] ?? time()),
        'cache_info' => $cacheInfo,
        'cache_state' => determineCacheState($cacheInfo),
        'operation_active' => $isOperationActive,
        'progress_active' => $progressActive,
        'lock_active' => $lockActive
    ];
    
    // Debug info
    error_log("Progress status: completed=" . ($trueCompleted ? 'true' : 'false') . 
              ", operation_active=" . ($isOperationActive ? 'true' : 'false') .
              ", progress_active=" . ($progressActive ? 'true' : 'false') .
              ", lock_active=" . ($lockActive ? 'true' : 'false'));
    
    // Aggiungi log se disponibile
    if (!empty($progressState['log'])) {
        $response['log'] = array_slice($progressState['log'], -5);
    }

    if (!empty($progressState['log'])) {
        $response['log'] = array_slice($progressState['log'], -5);
    }
    
    // Calcola ETA se operazione in corso
    if ($isOperationActive && ($progressState['step'] ?? 0) > 0) {
        $eta = calculateETA($progressState);
        if ($eta !== null) {
            $response['eta_seconds'] = $eta;
            $response['eta_formatted'] = formatDuration($eta);
        }
    }
    
    // Se completato, aggiungi info finali cache
    if ($trueCompleted && !$response['error']) {
        if ($cacheInfo['status'] === 'exists') {
            $response['cache_final_info'] = [
                'built_at' => $cacheInfo['built_at_formatted'] ?? null,
                'age' => $cacheInfo['age_formatted'] ?? null
            ];
        }
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'step' => 0,
        'total' => 1,
        'percent' => 0,
        'label' => 'Errore del server',
        'error' => $e->getMessage(),
        'completed' => false,
        'operation_active' => false,
        'ts' => time()
    ]);
}

function determineCacheState(array $cacheInfo): string
{
    if ($cacheInfo['lock_active'] ?? false) {
        return 'rebuilding';
    }
    
    if ($cacheInfo['status'] !== 'exists') {
        return 'empty';
    }
    
    $currentHash = Cache::computeInputsHash();
    $isValid = Cache::isValid($currentHash, CACHE_TTL_SECONDS);
    
    return $isValid ? 'fresh' : 'stale';
}

function calculateETA(array $progressState): ?int
{
    $step = $progressState['step'] ?? 0;
    $total = $progressState['total'] ?? 1;
    $startTime = $progressState['started_at'] ?? time();
    
    if ($step <= 0 || $total <= 0) {
        return null;
    }
    
    $elapsed = time() - $startTime;
    $progress = $step / $total;
    
    if ($progress <= 0) {
        return null;
    }
    
    $totalEstimated = $elapsed / $progress;
    $remaining = $totalEstimated - $elapsed;
    
    return max(0, (int)$remaining);
}

function formatDuration(int $seconds): string
{
    if ($seconds < 60) {
        return "{$seconds}s";
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return $secs > 0 ? "{$minutes}m {$secs}s" : "{$minutes}m";
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
    }
}

function getActiveJobInfo(): ?array
{
    $basePath = defined('BASE_PATH') ? BASE_PATH : getcwd();
    $jobFile = $basePath . '/logs/active_job.json';
    
    if (!file_exists($jobFile)) {
        return null;
    }
    
    return json_decode(file_get_contents($jobFile), true);
}

function addJobParticipantIfExists(): void
{
    $job = getActiveJobInfo();
    if ($job && !in_array(session_id(), $job['participants'] ?? [])) {
        $job['participants'][] = session_id();
        $basePath = defined('BASE_PATH') ? BASE_PATH : getcwd();
        file_put_contents($basePath . '/logs/active_job.json', json_encode($job, JSON_PRETTY_PRINT));
    }
}