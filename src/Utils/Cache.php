<?php
declare(strict_types=1);

namespace FantacalcioAnalyzer\Utils;

/**
 * Sistema di cache intelligente con TTL e hash degli input
 */
class Cache
{
    private static string $cacheDir;
    private static Logger $logger;
    
    public static function init(string $basePath = ''): void
    {
        if (empty($basePath)) {
            $basePath = defined('BASE_PATH') ? BASE_PATH : getcwd();
        }
        
        self::$cacheDir = $basePath . '/cache';
        self::$logger = new Logger("Cache");
        
        if (!is_dir(self::$cacheDir)) {
            if (!mkdir(self::$cacheDir, 0755, true)) {
                throw new \Exception("Cannot create cache directory: " . self::$cacheDir);
            }
        }
    }
    
    /**
     * Calcola l'hash degli input per determinare se la cache è ancora valida
     */
    public static function computeInputsHash(): string
    {
        if (!isset(self::$cacheDir)) {
            self::init();
        }
        
        $basePath = dirname(self::$cacheDir);
        $dataPath = $basePath . '/data';
        
        $inputs = [];
        
        // Configurazione che influenza il caricamento
        $inputs['config'] = [
            'current_season' => defined('CURRENT_SEASON') ? CURRENT_SEASON : '2025-26',
            'last_season' => defined('LAST_SEASON') ? LAST_SEASON : '2024-25',
        ];
        
        // Scansiona tutti i file di input
        $patterns = [
            $dataPath . '/lista/*.csv',
            $dataPath . '/statistiche/*.csv',
            $dataPath . '/statistiche/*.xlsx', 
            $dataPath . '/quotazioni/*.csv',
            $dataPath . '/quotazioni/*.xlsx',
            $dataPath . '/valutazioni/*/*.csv'
        ];
        
        foreach ($patterns as $pattern) {
            $files = glob($pattern) ?: [];
            foreach ($files as $file) {
                if (is_file($file)) {
                    $relativePath = str_replace($basePath, '', $file);
                    $inputs['files'][$relativePath] = [
                        'mtime' => filemtime($file),
                        'size' => filesize($file)
                    ];
                }
            }
        }
        
        // Ordina per garantire hash deterministico
        ksort($inputs);
        if (isset($inputs['files'])) {
            ksort($inputs['files']);
        }
        
        $hash = md5(json_encode($inputs));
        self::$logger->info("Computed inputs hash: $hash");
        
        return $hash;
    }
    
    /**
     * Verifica se la cache è valida
     */
    public static function isValid(string $currentHash, int $ttlSeconds = 10800): bool
    {
        if (!isset(self::$cacheDir)) {
            self::init();
        }
        
        $manifest = self::getManifest();
        if (!$manifest) {
            return false;
        }
        
        $builtAt = $manifest['built_at'] ?? 0;
        $cachedHash = $manifest['inputs_hash'] ?? '';
        
        // Verifica TTL
        $age = time() - $builtAt;
        if ($age > $ttlSeconds) {
            self::$logger->info("Cache expired: age={$age}s > ttl={$ttlSeconds}s");
            return false;
        }
        
        // Verifica hash input
        if ($cachedHash !== $currentHash) {
            self::$logger->info("Cache invalid: hash mismatch");
            return false;
        }
        
        self::$logger->info("Cache valid: age={$age}s, hash matches");
        return true;
    }
    
    /**
     * Legge il manifest della cache
     */
    public static function getManifest(): ?array
    {
        if (!isset(self::$cacheDir)) {
            self::init();
        }
        
        $manifestPath = self::$cacheDir . '/manifest.json';
        if (!file_exists($manifestPath)) {
            return null;
        }
        
        $content = file_get_contents($manifestPath);
        if ($content === false) {
            return null;
        }
        
        $manifest = json_decode($content, true);
        return is_array($manifest) ? $manifest : null;
    }
    
    /**
     * Scrive il manifest della cache
     */
    public static function setManifest(string $inputsHash, array $additional = []): void
    {
        if (!isset(self::$cacheDir)) {
            self::init();
        }
        
        $manifest = array_merge([
            'version' => '1',
            'built_at' => time(),
            'inputs_hash' => $inputsHash,
        ], $additional);
        
        $manifestPath = self::$cacheDir . '/manifest.json';
        $content = json_encode($manifest, JSON_PRETTY_PRINT);
        
        if (file_put_contents($manifestPath, $content) === false) {
            throw new \Exception("Cannot write manifest: $manifestPath");
        }
        
        self::$logger->info("Updated cache manifest");
    }
    
    /**
     * Legge un file dalla cache
     */
    public static function read(string $key): mixed
    {
        if (!isset(self::$cacheDir)) {
            self::init();
        }
        
        $path = self::$cacheDir . '/' . $key . '.json';
        if (!file_exists($path)) {
            return null;
        }
        
        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }
        
        return json_decode($content, true);
    }
    
    /**
     * Scrive un file nella cache
     */
    public static function write(string $key, mixed $data): void
    {
        if (!isset(self::$cacheDir)) {
            self::init();
        }
        
        $path = self::$cacheDir . '/' . $key . '.json';
        $content = json_encode($data, JSON_PRETTY_PRINT);
        
        if (file_put_contents($path, $content) === false) {
            throw new \Exception("Cannot write cache file: $path");
        }
        
        self::$logger->info("Cached: $key");
    }
    
    /**
     * Pulisce la cache
     */
    public static function clear(): void
    {
        if (!isset(self::$cacheDir)) {
            self::init();
        }
        
        $files = glob(self::$cacheDir . '/*.json') ?: [];
        foreach ($files as $file) {
            @unlink($file);
        }
        
        self::$logger->info("Cache cleared");
    }
    
    /**
     * Ottiene informazioni sulla cache
     */
    public static function getInfo(): array
    {
        if (!isset(self::$cacheDir)) {
            self::init();
        }
        
        $manifest = self::getManifest();
        if (!$manifest) {
            return ['status' => 'empty'];
        }
        
        $builtAt = $manifest['built_at'] ?? 0;
        $age = time() - $builtAt;
        
        $lockActive = file_exists(self::$cacheDir . '/.load.lock');

        return [
            'status' => 'exists',
            'built_at' => $builtAt,
            'built_at_formatted' => date('Y-m-d H:i:s', $builtAt),
            'age_seconds' => $age,
            'age_formatted' => self::formatDuration($age),
            'inputs_hash' => $manifest['inputs_hash'] ?? '',
            'version' => $manifest['version'] ?? '0',
            'lock_active' => $lockActive
        ];

    }
    
    /**
     * Formatta una durata in secondi in formato human-readable
     */
    private static function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return "{$minutes}m";
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
        }
    }
    
    /**
     * Verifica e crea un lock per evitare esecuzioni concorrenti
     */
    public static function acquireLock(int $timeoutSeconds = 30): bool
    {
        if (!isset(self::$cacheDir)) {
            self::init();
        }
        
        $lockPath = self::$cacheDir . '/.load.lock';
        
        // Verifica se c'è già un lock attivo
        if (file_exists($lockPath)) {
            $lockTime = filemtime($lockPath);
            $age = time() - $lockTime;
            
            // Se il lock è troppo vecchio, lo consideriamo stale
            if ($age > $timeoutSeconds) {
                @unlink($lockPath);
                self::$logger->warning("Removed stale lock (age: {$age}s)");
            } else {
                self::$logger->info("Lock already exists (age: {$age}s)");
                return false;
            }
        }
        
        // Crea il lock
        if (file_put_contents($lockPath, time()) === false) {
            self::$logger->error("Cannot create lock file");
            return false;
        }
        
        self::$logger->info("Lock acquired");
        return true;
    }
    
    /**
     * Rilascia il lock
     */
    public static function releaseLock(): void
    {
        if (!isset(self::$cacheDir)) {
            self::init();
        }
        
        $lockPath = self::$cacheDir . '/.load.lock';
        if (file_exists($lockPath)) {
            @unlink($lockPath);
            self::$logger->info("Lock released");
        }
    }

    public static function getStatus(int $ttlSeconds = CACHE_TTL_SECONDS, ?string $currentHash = null): array
    {
        if (!isset(self::$cacheDir)) self::init();

        $manifest = self::getManifest(); // già esistente nella tua classe
        $exists   = is_array($manifest);
        $builtAt  = $exists ? (int)($manifest['built_at'] ?? 0) : 0;
        $age      = $exists ? max(0, time() - $builtAt) : 0;

        $lockPath   = self::$cacheDir . '/.load.lock';
        $lockActive = file_exists($lockPath);

        $cachedHash = $exists ? ($manifest['inputs_hash'] ?? null) : null;
        if ($currentHash === null) {
            // Non costa molto: lo calcolo se non fornito
            $currentHash = self::computeInputsHash();
        }

        $isValid = $exists
            && $age <= $ttlSeconds
            && $cachedHash === $currentHash;

        $status =
            !$exists         ? 'empty' :
            ($lockActive     ? 'rebuilding' :
            ($isValid        ? 'valid' : 'expired'));

        return [
            'status'        => $status,           // valid | expired | rebuilding | empty
            'built_at'      => $builtAt,
            'age_seconds'   => $age,
            'age_formatted' => self::formatAge($age),
            'inputs_hash'   => $cachedHash,
            'lock_active'   => $lockActive,
            'ttl_seconds'   => $ttlSeconds,
        ];
    }

    private static function formatAge(int $seconds): string
    {
        if ($seconds < 60) return $seconds . 's';
        if ($seconds < 3600) return floor($seconds/60) . 'm';
        $h = floor($seconds/3600);
        $m = floor(($seconds%3600)/60);
        return sprintf('%dh %dm', $h, $m);
    }

    /**
     * Verifica solo esistenza cache (per non-admin)
     */
    public static function existsOnly(): bool
    {
        if (!isset(self::$cacheDir)) {
            self::init();
        }
        
        $manifest = self::getManifest();
        return $manifest !== null && isset($manifest['built_at']);
    }

    /**
     * Status cache personalizzato per tipo utente
     */
    public static function getStatusForUser(bool $isAdmin): array
    {
        if (!isset(self::$cacheDir)) {
            self::init();
        }
        
        $manifest = self::getManifest();
        $exists = $manifest !== null && isset($manifest['built_at']);
        
        if (!$exists) {
            return [
                'exists' => false,
                'last_build_at' => null,
                'age_seconds' => null,
                'age_formatted' => null,
                'suggestion' => $isAdmin ? 'Cache assente - rigenerare dai comandi navbar' : null
            ];
        }
        
        $builtAt = $manifest['built_at'];
        $ageSeconds = time() - $builtAt;
        
        $result = [
            'exists' => true,
            'last_build_at' => date('Y-m-d H:i:s', $builtAt),
            'age_seconds' => $ageSeconds,
            'age_formatted' => self::formatDuration($ageSeconds)
        ];
        
        // Solo admin vedono età e suggerimenti
        if ($isAdmin) {
            $result['suggestion'] = $ageSeconds > 86400 ? // 24 ore
                'Cache più vecchia di 24h: valutare rigenerazione' : null;
        }
        
        return $result;
    }

}