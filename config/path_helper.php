<?php
declare(strict_types=1);

/**
 * Helper per la gestione dei percorsi web del progetto
 * Rileva automaticamente il percorso base sia in root che in sottocartelle
 */

if (!function_exists('getProjectBasePath')) {
    /**
     * Determina il percorso base del progetto nel web server
     * Funziona sia se il progetto è in root che in sottocartelle
     * 
     * @return string Il percorso base (es: "/" oppure "/sottocartella/")
     */
    function getProjectBasePath(): string {
        static $basePath = null;
        
        if ($basePath !== null) {
            return $basePath;
        }
        
        // Usa la directory dello script corrente come riferimento
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        
        // Se siamo in una sottocartella del progetto (es: api/, auth/), 
        // risali fino alla root del progetto
        $currentFile = $_SERVER['SCRIPT_NAME'] ?? '';
        
        // Lista delle sottocartelle note del progetto
        $subDirs = ['api', 'auth', 'assets', 'config', 'src', 'setup'];
        
        $basePath = '/';
        
        // Se il file corrente è in una sottocartella del progetto
        foreach ($subDirs as $subDir) {
            if (strpos($currentFile, "/{$subDir}/") !== false) {
                // Rimuovi la parte della sottocartella per ottenere la base
                $basePath = str_replace("/{$subDir}/", '/', dirname($currentFile) . '/');
                break;
            }
        }
        
        // Se non siamo in una sottocartella, usa la directory dello script
        if ($basePath === '/') {
            $basePath = rtrim($scriptDir, '/') . '/';
            
            // Se siamo nella root del web server
            if ($basePath === '/') {
                $basePath = '/';
            }
        }
        
        // Assicurati che inizi e finisca con /
        $basePath = '/' . trim($basePath, '/') . '/';
        
        // Se il risultato è solo //, riduci a /
        if ($basePath === '//') {
            $basePath = '/';
        }
        
        return $basePath;
    }
}

if (!function_exists('url')) {
    /**
     * Genera URL completi basati sul percorso del progetto
     * 
     * @param string $path Percorso relativo (es: "login.php", "api/auth.php")
     * @return string URL completo
     */
    function url(string $path): string {
        $basePath = getProjectBasePath();
        $path = ltrim($path, '/');
        
        return $basePath . $path;
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect con percorso corretto del progetto
     * 
     * @param string $path Percorso relativo
     * @param int $code Codice HTTP (default: 302)
     */
    function redirect(string $path, int $code = 302): void {
        $url = url($path);
        http_response_code($code);
        header("Location: {$url}");
        exit;
    }
}

if (!function_exists('currentUrl')) {
    /**
     * Ottiene l'URL corrente completo
     * 
     * @return string
     */
    function currentUrl(): string {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        return $protocol . '://' . $host . $uri;
    }
}

if (!function_exists('asset')) {
    /**
     * Genera URL per asset (CSS, JS, immagini)
     * 
     * @param string $path Percorso dell'asset (es: "css/style.css")
     * @return string URL completo dell'asset
     */
    function asset(string $path): string {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('isCurrentPage')) {
    /**
     * Verifica se siamo sulla pagina specificata
     * 
     * @param string $page Nome del file della pagina (es: "login.php")
     * @return bool
     */
    function isCurrentPage(string $page): bool {
        $currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
        return $currentScript === $page;
    }
}

// Definisci costanti per comodità
if (!defined('BASE_URL')) {
    define('BASE_URL', getProjectBasePath());
}