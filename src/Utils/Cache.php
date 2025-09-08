<?php
declare(strict_types=1);

namespace FantacalcioAnalyzer\Utils;

use FantacalcioAnalyzer\Core\FantacalcioAnalyzer;

/**
 * Sistema di cache semplificato e affidabile
 * - File JSON unico per efficienza
 * - Logica separata admin/non-admin
 * - Gestione errori con backup/restore
 */
class Cache
{
    private static string $cacheDir;
    private static string $cacheFile;
    private static string $backupFile;
    private static Logger $logger;
    
    public static function init(string $basePath = ''): void
    {
        if (empty($basePath)) {
            $basePath = defined('BASE_PATH') ? BASE_PATH : getcwd();
        }
        
        self::$cacheDir = $basePath . '/cache';
        self::$cacheFile = self::$cacheDir . '/complete_cache.json';
        self::$backupFile = self::$cacheDir . '/cache_backup.json';
        self::$logger = new Logger("Cache");
        
        if (!is_dir(self::$cacheDir)) {
            if (!mkdir(self::$cacheDir, 0755, true)) {
                throw new \Exception("Cannot create cache directory: " . self::$cacheDir);
            }
        }
    }
    
    /**
     * Verifica se la cache esiste (per tutti gli utenti)
     */
    public static function exists(): bool
    {
        if (!isset(self::$cacheFile)) {
            self::init();
        }
        
        return file_exists(self::$cacheFile) && filesize(self::$cacheFile) > 100;
    }
    
    /**
     * Legge i dati dalla cache
     */
    public static function read(): ?array
    {
        if (!self::exists()) {
            return null;
        }
        
        $content = @file_get_contents(self::$cacheFile);
        if ($content === false) {
            self::$logger->error("Cannot read cache file");
            return null;
        }
        
        $data = json_decode($content, true);
        if (!is_array($data)) {
            self::$logger->error("Invalid cache file format");
            return null;
        }
        
        self::$logger->info("Cache loaded successfully");
        return $data;
    }
    
    /**
     * Rigenera completamente la cache (solo per admin)
     */
    public static function rebuild(): array
    {
        if (!isset(self::$cacheFile)) {
            self::init();
        }
        
        // Log inizio operazione in app.log
        error_log("[CACHE] Starting cache rebuild at " . date('Y-m-d H:i:s'));
        self::$logger->info("Starting cache rebuild");
        
        // Backup della cache esistente
        self::backup();
        
        try {
            error_log("[CACHE] Initializing FantacalcioAnalyzer...");
            
            // Inizializza analyzer
            $analyzer = new FantacalcioAnalyzer();
            error_log("[CACHE] FantacalcioAnalyzer created successfully");
            
            error_log("[CACHE] Starting loadData() call...");
            error_log("[CACHE] Memory usage before loadData: " . round(memory_get_usage(true)/1024/1024, 2) . " MB");
            
            // Questo è il punto critico dove si ferma
            $analyzer->loadData();
            
            error_log("[CACHE] loadData() completed successfully");
            error_log("[CACHE] Memory usage after loadData: " . round(memory_get_usage(true)/1024/1024, 2) . " MB");
            
            error_log("[CACHE] Getting session data...");
            
            // Prepara dati completi per la cache
            $sessionData = $analyzer->getSessionData();
            
            error_log("[CACHE] Session data retrieved successfully");
            error_log("[CACHE] Lista corrente count: " . count($sessionData['lista_corrente'] ?? []));
            error_log("[CACHE] Statistiche years: " . count($sessionData['statistiche'] ?? []));
            
            $cacheData = [
                'version' => '2.0',
                'built_at' => time(),
                'built_at_formatted' => date('Y-m-d H:i:s'),
                
                // Dati principali
                'lista_corrente' => $sessionData['lista_corrente'],
                'statistiche' => $sessionData['statistiche'],
                'quotazioni' => $sessionData['quotazioni'],
                'valutazioni' => $sessionData['valutazioni'],
                
                // Metadati calcolati
                'metadata' => [
                    'current_year' => $sessionData['current_year'],
                    'last_year' => $sessionData['last_year'],
                    'neopromosse' => $sessionData['neopromosse'],
                    'squadre_media' => $sessionData['squadre_media'],
                    'squadre_50ga' => $sessionData['squadre_50ga'],
                    'team_ga_last' => $sessionData['team_ga_last']
                ],
                
                // Cache API
                'api_caches' => [
                    'api_squad_index' => $sessionData['api_squad_index'],
                    'effective_nationalities' => $sessionData['effective_nationalities'],
                    'rigoristi_map' => $sessionData['rigoristi_map'],
                    'rigoristi_raw_map' => $sessionData['rigoristi_raw_map']
                ],
                
                // Statistiche costruzione
                'build_stats' => [
                    'total_players' => count($sessionData['lista_corrente'] ?? []),
                    'api_teams' => count($sessionData['api_squad_index'] ?? []),
                    'effective_nationalities' => count($sessionData['effective_nationalities'] ?? []),
                    'rigoristi_teams' => count($sessionData['rigoristi_map'] ?? []),
                    'statistics_years' => array_keys($sessionData['statistiche'] ?? []),
                    'quotazioni_years' => array_keys($sessionData['quotazioni'] ?? []),
                    'valutazioni_years' => array_keys($sessionData['valutazioni'] ?? [])
                ]
            ];
            
            error_log("[CACHE] Cache data structure prepared");
            error_log("[CACHE] Writing cache file atomically...");
            
            // Scrivi cache atomicamente
            $tempFile = self::$cacheFile . '.tmp';
            $content = json_encode($cacheData, JSON_PRETTY_PRINT);
            
            if (file_put_contents($tempFile, $content, LOCK_EX) === false) {
                throw new \Exception("Cannot write temporary cache file");
            }
            
            if (!rename($tempFile, self::$cacheFile)) {
                throw new \Exception("Cannot move temporary cache file");
            }
            
            $buildStats = $cacheData['build_stats'];
            error_log("[CACHE] Cache rebuilt successfully - Players: {$buildStats['total_players']}, API Teams: {$buildStats['api_teams']}");
            
            self::$logger->info("Cache rebuilt successfully", [
                'players' => $buildStats['total_players'],
                'api_teams' => $buildStats['api_teams']
            ]);
            
            // Rimuovi backup dopo successo
            self::removeBackup();
            
            return [
                'success' => true,
                'cache_info' => self::getInfo(),
                'build_stats' => $cacheData['build_stats']
            ];
            
        } catch (\Exception $e) {
            error_log("[CACHE] Rebuild FAILED: " . $e->getMessage());
            error_log("[CACHE] Error in file: " . $e->getFile() . " line: " . $e->getLine());
            self::$logger->error("Cache rebuild failed: " . $e->getMessage());
            
            // Ripristina backup se disponibile
            if (self::restore()) {
                error_log("[CACHE] Previous cache restored after rebuild failure");
                self::$logger->info("Previous cache restored after rebuild failure");
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'backup_restored' => true,
                    'message' => 'Errore durante la rigenerazione. Cache precedente ripristinata.'
                ];
            } else {
                error_log("[CACHE] No backup available after rebuild failure");
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'backup_restored' => false,
                    'message' => 'Errore durante la rigenerazione. Nessuna cache disponibile.'
                ];
            }
        } catch (\Throwable $t) {
            error_log("[CACHE] FATAL ERROR during rebuild: " . $t->getMessage());
            error_log("[CACHE] Fatal error in file: " . $t->getFile() . " line: " . $t->getLine());
            error_log("[CACHE] Stack trace: " . $t->getTraceAsString());
            
            // Ripristina backup
            if (self::restore()) {
                error_log("[CACHE] Previous cache restored after fatal error");
                return [
                    'success' => false,
                    'error' => 'Fatal error: ' . $t->getMessage(),
                    'backup_restored' => true,
                    'message' => 'Errore fatale durante la rigenerazione. Cache precedente ripristinata.'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Fatal error: ' . $t->getMessage(),
                    'backup_restored' => false,
                    'message' => 'Errore fatale durante la rigenerazione. Nessuna cache disponibile.'
                ];
            }
        }
    }
    
    /**
     * Carica dati in sessione dalla cache
     */
    public static function loadToSession(): bool
    {
        $data = self::read();
        if (!$data) {
            return false;
        }
        
        // Carica dati principali
        $_SESSION['data_loaded'] = true;
        $_SESSION['lista_corrente'] = $data['lista_corrente'] ?? [];
        $_SESSION['statistiche'] = $data['statistiche'] ?? [];
        $_SESSION['quotazioni'] = $data['quotazioni'] ?? [];
        $_SESSION['valutazioni'] = $data['valutazioni'] ?? [];
        
        // Carica metadati
        $metadata = $data['metadata'] ?? [];
        $_SESSION['current_year'] = $metadata['current_year'] ?? null;
        $_SESSION['last_year'] = $metadata['last_year'] ?? null;
        $_SESSION['neopromosse'] = $metadata['neopromosse'] ?? [];
        $_SESSION['squadre_media'] = $metadata['squadre_media'] ?? [];
        $_SESSION['squadre_50ga'] = $metadata['squadre_50ga'] ?? [];
        $_SESSION['team_ga_last'] = $metadata['team_ga_last'] ?? [];
        
        // Carica cache API
        $apiCaches = $data['api_caches'] ?? [];
        $_SESSION['api_squad_index'] = $apiCaches['api_squad_index'] ?? [];
        $_SESSION['effective_nationalities'] = $apiCaches['effective_nationalities'] ?? [];
        $_SESSION['rigoristi_map'] = $apiCaches['rigoristi_map'] ?? [];
        $_SESSION['rigoristi_raw_map'] = $apiCaches['rigoristi_raw_map'] ?? [];
        
        self::$logger->info("Data loaded to session from cache");
        return true;
    }
    
    /**
     * Informazioni sulla cache
     */
    public static function getInfo(): array
    {
        if (!self::exists()) {
            return [
                'exists' => false,
                'status' => 'empty'
            ];
        }
        
        $data = self::read();
        if (!$data) {
            return [
                'exists' => true,
                'status' => 'corrupted'
            ];
        }
        
        $builtAt = $data['built_at'] ?? 0;
        $age = time() - $builtAt;
        
        return [
            'exists' => true,
            'status' => 'valid',
            'built_at' => $builtAt,
            'built_at_formatted' => $data['built_at_formatted'] ?? date('Y-m-d H:i:s', $builtAt),
            'age_seconds' => $age,
            'age_formatted' => self::formatDuration($age),
            'version' => $data['version'] ?? '1.0',
            'build_stats' => $data['build_stats'] ?? []
        ];
    }
    
    /**
     * Status per tipo utente
     */
    public static function getStatusForUser(bool $isAdmin): array
    {
        $info = self::getInfo();
        
        if (!$info['exists']) {
            return [
                'exists' => false,
                'message' => $isAdmin 
                    ? 'Cache assente. Utilizzare i comandi della navbar per rigenerare.'
                    : 'Dati non disponibili. Contattare l\'amministratore.',
                'suggestion' => $isAdmin ? 'rebuild' : null
            ];
        }
        
        if ($info['status'] === 'corrupted') {
            return [
                'exists' => true,
                'status' => 'corrupted',
                'message' => $isAdmin
                    ? 'Cache corrotta. Rigenerazione necessaria.'
                    : 'Errore nei dati. Contattare l\'amministratore.',
                'suggestion' => $isAdmin ? 'rebuild' : null
            ];
        }
        
        $result = [
            'exists' => true,
            'status' => 'valid',
            'built_at_formatted' => $info['built_at_formatted'],
            'age_formatted' => $info['age_formatted'],
            'build_stats' => $info['build_stats']
        ];
        
        // Solo admin vedono statistiche dettagliate e suggerimenti
        if ($isAdmin) {
            $age = $info['age_seconds'];
            if ($age > 86400) { // 24 ore
                $result['suggestion'] = 'Cache più vecchia di 24h. Considerare rigenerazione.';
            }
        }
        
        return $result;
    }
    
    /**
     * Backup della cache esistente
     */
    private static function backup(): bool
    {
        if (!self::exists()) {
            return false;
        }
        
        return copy(self::$cacheFile, self::$backupFile);
    }
    
    /**
     * Ripristina cache dal backup
     */
    private static function restore(): bool
    {
        if (!file_exists(self::$backupFile)) {
            return false;
        }
        
        return copy(self::$backupFile, self::$cacheFile);
    }
    
    /**
     * Rimuove backup
     */
    private static function removeBackup(): void
    {
        if (file_exists(self::$backupFile)) {
            @unlink(self::$backupFile);
        }
    }
    
    /**
     * Formatta durata
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
     * Ottiene il percorso del file cache (per data.php compatibility)
     */
    public static function getCacheFile(): string
    {
        if (!isset(self::$cacheFile)) {
            self::init();
        }
        return self::$cacheFile;
    }
    
    /**
     * Ottiene il percorso del file backup (per data.php compatibility)
     */
    public static function getBackupFile(): string
    {
        if (!isset(self::$backupFile)) {
            self::init();
        }
        return self::$backupFile;
    }
}