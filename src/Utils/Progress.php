<?php
declare(strict_types=1);

namespace FantacalcioAnalyzer\Utils;

/**
 * Tracker per il progresso delle operazioni
 */
class Progress
{
    private static string $progressFile;
    private static array $state = [];
    private static Logger $logger;
    private static bool $initialized = false;
    
    public static function init(int $totalSteps, string $operation = 'Loading data'): void
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : getcwd();
        $logsDir = $basePath . '/logs';
        
        if (!is_dir($logsDir)) {
            @mkdir($logsDir, 0755, true);
        }
        
        self::$progressFile = $logsDir . '/progress.json';
        self::$logger = new Logger("Progress");
        
        self::$state = [
            'operation' => $operation,
            'step' => 0,
            'total' => $totalSteps,
            'percent' => 0,
            'label' => 'Inizializzazione...',
            'started_at' => time(),
            'updated_at' => time(),
            'error' => null,
            'completed' => false,
            'log' => []
        ];
        
        self::write();
        self::$initialized = true;
        
        self::$logger->info("Progress initialized: $operation ($totalSteps steps)");
    }
    
    public static function update(int $step, string $label, array $metadata = []): void
    {
        if (!self::$initialized) {
            self::init(10, 'Operation');
        }
        
        self::$state['step'] = min($step, self::$state['total']);
        self::$state['percent'] = self::$state['total'] > 0 
            ? round((self::$state['step'] / self::$state['total']) * 100, 1)
            : 0;
        self::$state['label'] = $label;
        self::$state['updated_at'] = time();
        
        // Aggiungi al log (mantieni solo gli ultimi 10 messaggi)
        self::$state['log'][] = [
            'step' => $step,
            'label' => $label,
            'ts' => time(),
            'metadata' => $metadata
        ];
        
        if (count(self::$state['log']) > 10) {
            self::$state['log'] = array_slice(self::$state['log'], -10);
        }
        
        self::write();
        
        self::$logger->info("Progress: " . self::$state['step'] . "/" . self::$state['total'] . " (" . self::$state['percent'] . "%) - $label");
    }
    
    public static function finish(string $message = 'Completato'): void
    {
        if (!self::$initialized) {
            return;
        }
        
        self::$state['step'] = self::$state['total'];
        self::$state['percent'] = 100;
        self::$state['label'] = $message;
        self::$state['completed'] = true;
        self::$state['updated_at'] = time();
        
        self::$state['log'][] = [
            'step' => self::$state['total'],
            'label' => $message,
            'ts' => time(),
            'metadata' => []
        ];
        
        self::write();
        
        $duration = self::$state['updated_at'] - self::$state['started_at'];
        self::$logger->info("Progress completed in {$duration}s: $message");
    }
    
    public static function error(string $message, ?\Exception $exception = null): void
    {
        if (!self::$initialized) {
            self::init(1, 'Error');
        }
        
        self::$state['error'] = $message;
        self::$state['label'] = "Errore: $message";
        self::$state['updated_at'] = time();
        
        self::$state['log'][] = [
            'step' => self::$state['step'],
            'label' => "ERRORE: $message",
            'ts' => time(),
            'metadata' => $exception ? [
                'exception' => get_class($exception),
                'file' => basename($exception->getFile()),
                'line' => $exception->getLine()
            ] : []
        ];
        
        self::write();
        
        self::$logger->error("Progress error: $message");
        if ($exception) {
            self::$logger->error("Exception: " . $exception->getMessage());
        }
    }
    
    public static function getState(): array
    {
        // Percorso file stato
        $basePath = defined('BASE_PATH') ? BASE_PATH : getcwd();
        $progressFile = $basePath . '/logs/progress.json';

        // Fallback
        $fallback = [
            'step'      => 0,
            'total'     => 1,
            'percent'   => 0,
            'label'     => 'Non inizializzato',
            'error'     => null,
            'completed' => false,
            'log'       => []
        ];

        if (!is_file($progressFile)) {
            return $fallback;
        }

        $content = @file_get_contents($progressFile);
        if ($content === false || $content === '') {
            return $fallback;
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return $fallback;
        }

        // Normalizzazione campi minimi
        $data['step']      = $data['step']      ?? 0;
        $data['total']     = $data['total']     ?? 1;
        $data['percent']   = $data['percent']   ?? 0;
        $data['label']     = $data['label']     ?? 'In corso';
        $data['error']     = $data['error']     ?? null;
        $data['completed'] = $data['completed'] ?? false;
        $data['log']       = $data['log']       ?? [];

        return $data;
    }


    
    public static function isActive(): bool
    {
        $state = self::getState();
        
        // Considera attivo se:
        // 1. Progress esiste E non è completato E non c'è errore
        // 2. OPPURE se c'è un lock cache attivo (operazione in background)
        
        $hasValidProgress = !empty($state['step']) || !empty($state['label']) || !empty($state['started_at']);
        $progressNotCompleted = !($state['completed'] ?? false);
        $noError = ($state['error'] ?? null) === null;
        
        $progressActive = $hasValidProgress && $progressNotCompleted && $noError;
        
        // Controlla anche il lock cache
        $cacheInfo = \FantacalcioAnalyzer\Utils\Cache::getInfo();
        $lockActive = $cacheInfo['lock_active'] ?? false;
        
        $isActive = $progressActive || $lockActive;
        
        // Debug log
        if (isset(self::$logger)) {
            self::$logger->debug("Progress::isActive() = " . ($isActive ? 'true' : 'false') . 
                            " (progress_active=$progressActive, lock_active=$lockActive)");
        }
        
        return $isActive;
    }

    public static function markCompleted(string $message = 'Operazione completata'): void
    {
        if (!self::$initialized) {
            return;
        }
        
        self::$state['completed'] = true;
        self::$state['percent'] = 100;
        self::$state['step'] = self::$state['total'];
        self::$state['label'] = $message;
        self::$state['updated_at'] = time();
        
        self::write();
        
        if (isset(self::$logger)) {
            self::$logger->info("Progress marked as completed: $message");
        }
    }
    
    public static function isCompleted(): bool
    {
        $state = self::getState();
        return $state['completed'] || $state['percent'] >= 100;
    }
    
    public static function hasError(): bool
    {
        $state = self::getState();
        return $state['error'] !== null;
    }
    
    public static function clear(): void
    {
        if (isset(self::$progressFile) && file_exists(self::$progressFile)) {
            @unlink(self::$progressFile);
        }
        
        self::$state = [];
        self::$initialized = false;
        
        if (isset(self::$logger)) {
            self::$logger->info("Progress cleared");
        }
    }
    
    private static function write(): void
    {
        if (!isset(self::$progressFile)) {
            return;
        }
        
        $content = json_encode(self::$state, JSON_PRETTY_PRINT);
        @file_put_contents(self::$progressFile, $content);
    }
    
    public static function addLogEntry(string $message, array $metadata = []): void
    {
        if (!self::$initialized) {
            return;
        }
        
        self::$state['log'][] = [
            'step' => self::$state['step'],
            'label' => $message,
            'ts' => time(),
            'metadata' => $metadata
        ];
        
        if (count(self::$state['log']) > 10) {
            self::$state['log'] = array_slice(self::$state['log'], -10);
        }
        
        self::write();
        self::$logger->info("Log entry: $message");
    }
    
    public static function getDuration(): int
    {
        if (!self::$initialized) {
            return 0;
        }
        
        return time() - (self::$state['started_at'] ?? time());
    }
    
    public static function getETA(): ?int
    {
        if (!self::$initialized || self::$state['step'] <= 0) {
            return null;
        }
        
        $elapsed = self::getDuration();
        $progress = self::$state['step'] / self::$state['total'];
        
        if ($progress <= 0) {
            return null;
        }
        
        $totalEstimated = $elapsed / $progress;
        $remaining = $totalEstimated - $elapsed;
        
        return max(0, (int)$remaining);
    }
}