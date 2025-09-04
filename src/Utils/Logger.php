<?php
declare(strict_types=1);

namespace FantacalcioAnalyzer\Utils;

/**
 * Logger semplice (wrapper su error_log()).
 * Configurato il logging.
 */
class Logger
{
    private string $name;
    private string $level;
    private static array $levels = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3
    ];

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->level = defined('LOG_LEVEL') ? LOG_LEVEL : 'INFO';
    }

    /**
     * Log a message at the specified level.
     */
    private function log(string $level, string $message): void
    {
        if (self::$levels[$level] >= self::$levels[$this->level]) {
            $timestamp = date('Y-m-d H:i:s');
            $formatted = "[$timestamp] [$level] [{$this->name}] $message";
            
            if (defined('LOG_FILE')) {
                $dir = dirname(LOG_FILE);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0777, true);
                }
                @error_log($formatted . PHP_EOL, 3, LOG_FILE);
            } else {
                error_log($formatted);
            }
        }
    }
    public function debug(string $message): void
    {
        $this->log('DEBUG', $message);
    }

    public function info(string $message): void
    {
        $this->log('INFO', $message);
    }

    public function warning(string $message): void
    {
        $this->log('WARNING', $message);
    }

    public function error(string $message): void
    {
        $this->log('ERROR', $message);
    }
}