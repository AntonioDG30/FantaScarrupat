<?php
declare(strict_types=1);

/**
 * Middleware di autenticazione con hardening sessioni
 * Controlli: inattività, scadenza assoluta, user-agent, rigenerazione ID
 */

// Carica helper percorsi
require_once __DIR__ . '/../config/path_helper.php';
require_once __DIR__ . '/../config/config.php';

// Configura sessione sicura PRIMA di avviarla
if (session_status() === PHP_SESSION_NONE) {
    // Cookie di sessione (scade alla chiusura browser)
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_lifetime', '0');  // Session cookie
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    
    if (SESSION_SECURE_COOKIE) {
        ini_set('session.cookie_secure', '1');
    }
    
    session_start();
}

// Funzione helper per invalidare sessione
function invalidateSession(string $reason = 'expired'): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

// === CONTROLLO 1: Utente loggato ===
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? url('HomeParametri.php');
    redirect('login.php');
    exit; // IMPORTANTE: ferma l'esecuzione
}

// === CONTROLLO 2: User-Agent ===
if (SESSION_STRICT_UA) {
    $current_ua_hash = md5($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
    
    if (!isset($_SESSION['ua_hash'])) {
        // Prima volta, memorizza
        $_SESSION['ua_hash'] = $current_ua_hash;
    } elseif ($_SESSION['ua_hash'] !== $current_ua_hash) {
        // User-agent cambiato = possibile hijacking
        error_log("Session hijacking detected for user {$_SESSION['user_id']}: UA mismatch");
        invalidateSession('ua_mismatch');
        header('Location: ' . url('login.php?error=session_invalid'));
        exit; // IMPORTANTE: ferma l'esecuzione
    }
}

// === CONTROLLO 3: Timeout inattività ===
$now = time();
$last_activity = $_SESSION['last_activity'] ?? $now;

if (($now - $last_activity) > SESSION_IDLE_TTL) {
    invalidateSession('idle_timeout');
    header('Location: ' . url('login.php?expired=1'));
    exit; // IMPORTANTE: ferma l'esecuzione
}

// === CONTROLLO 4: Scadenza assoluta ===
$login_time = $_SESSION['login_time'] ?? $now;

if (($now - $login_time) > SESSION_ABSOLUTE_TTL) {
    invalidateSession('absolute_timeout');
    header('Location: ' . url('login.php?expired=1&reason=session_max'));
    exit; // IMPORTANTE: ferma l'esecuzione
}

// === CONTROLLO 5: Rigenerazione ID periodica ===
$last_regen = $_SESSION['last_regenerate'] ?? $now;

if (($now - $last_regen) > SESSION_REGEN_INTERVAL) {
    session_regenerate_id(true);
    $_SESSION['last_regenerate'] = $now;
}

// === Aggiorna timestamp attività ===
$_SESSION['last_activity'] = $now;