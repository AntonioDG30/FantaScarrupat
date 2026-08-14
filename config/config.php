<?php
/**
 * config/config.php — Configurazione globale FantaScarrupat
 *
 * Incluso automaticamente da _layout.php di ogni zona.
 * Non deve produrre output.
 */

declare(strict_types=1);

if (defined('BASE_PATH')) {
    return;
}

// ── Base path ────────────────────────────────────────────────────────────────
// Rileva automaticamente: /FantaScarrupat/ su WAMP, / su Altervista.
(function (): void {
    $rootDir  = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    $docRoot  = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');

    if ($docRoot !== '' && str_starts_with($rootDir, $docRoot)) {
        $rel = substr($rootDir, strlen($docRoot));
    } else {
        $rel = '';
    }

    $base = '/' . trim($rel, '/');
    define('BASE_PATH', $base === '/' ? '/' : $base . '/');
})();

// ── Database ─────────────────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'my_fantascarrupat');
define('DB_USER',    'fantascarrupat');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ── Upload paths (web URL) ────────────────────────────────────────────────────
define('UPLOADS_URL',  BASE_PATH . 'uploads/');
define('GALLERY_URL',  BASE_PATH . 'uploads/gallery/');
define('TEAMS_URL',    BASE_PATH . 'uploads/teams/');
define('COACHES_URL',  BASE_PATH . 'uploads/coaches/');

// ── Stagione ──────────────────────────────────────────────────────────────────
// anno INT: 2025 = stagione 2024/25, 2026 = stagione 2025/26
// Cambio stagione: 1 luglio
define('CURRENT_ANNO', (int)date('n') >= 7 ? (int)date('Y') + 1 : (int)date('Y'));
define('LAST_ANNO',    CURRENT_ANNO - 1);

// ── Session security ──────────────────────────────────────────────────────────
define('SESSION_IDLE_TTL',     900);    // 15 min inattività
define('SESSION_ABSOLUTE_TTL', 28800);  // 8 h assolute
define('SESSION_REGEN_EVERY',  300);    // rigenera ID ogni 5 min

// ── App name ──────────────────────────────────────────────────────────────────
define('APP_NAME', 'FantaScarrupat');

// ── Versione asset (cache-busting) ──────────────────────────────────────────────
// Bumpare ad ogni modifica di CSS/JS per forzare il refresh nei browser.
define('ASSET_VER', '20260604-9');

// ── Rilevazione HTTPS (robusta, considera anche i reverse proxy) ───────────────
if (!function_exists('fs_is_https')) {
    function fs_is_https(): bool
    {
        return (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
    }
}

// ── Parametri condivisi del cookie di sessione ────────────────────────────────
if (!function_exists('fs_session_cookie_params')) {
    function fs_session_cookie_params(): array
    {
        return [
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => fs_is_https(),   // Secure attivo automaticamente su HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }
}

// ── URL assoluti (SEO / Open Graph / sitemap / GEO) ───────────────────────────
if (!function_exists('fs_base_url')) {
    /** URL assoluto della root del sito, con slash finale. Es. https://host/ (o /FantaScarrupat/ in locale). */
    function fs_base_url(): string
    {
        $scheme = fs_is_https() ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . BASE_PATH;
    }
}
if (!function_exists('fs_url')) {
    /** URL assoluto per un percorso relativo alla root (es. "assets/img/logo.png"). Passa attraverso gli URL già assoluti. */
    function fs_url(string $rel = ''): string
    {
        if ($rel !== '' && preg_match('#^https?://#i', $rel)) {
            return $rel;
        }
        return rtrim(fs_base_url(), '/') . '/' . ltrim($rel, '/');
    }
}
if (!function_exists('fs_canonical')) {
    /** URL canonico assoluto della richiesta corrente (per <link rel=canonical> e og:url). */
    function fs_canonical(): string
    {
        $scheme = fs_is_https() ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . ($_SERVER['REQUEST_URI'] ?? BASE_PATH);
    }
}

// ── Hardening sessione globale (applicato prima di ogni session_start) ─────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');   // rifiuta ID di sessione non inizializzati (anti session-fixation)
    ini_set('session.use_only_cookies', '1');  // mai ID di sessione nell'URL
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', fs_is_https() ? '1' : '0');
}
