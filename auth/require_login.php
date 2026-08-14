<?php
/**
 * auth/require_login.php — Protezione area privata
 *
 * Da includere all'INIZIO di ogni file in /app/, prima di qualunque output:
 *   require_once __DIR__ . '/../auth/require_login.php';
 *
 * Verifica sessione e reindirizza a /login.php se non valida.
 */

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../config/config.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(fs_session_cookie_params());
    session_start();
}

/**
 * Redirect al login con motivo opzionale.
 */
function _redir_login(string $reason = ''): never
{
    $bp = defined('BASE_PATH') ? BASE_PATH : '/';
    $qs = $reason !== '' ? '?reason=' . urlencode($reason) : '';
    header('Location: ' . $bp . 'login.php' . $qs, true, 302);
    exit;
}

// ── 1. Utente autenticato? ────────────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
    _redir_login();
}

$_now = time();

// ── 2. Absolute timeout (8 h) ─────────────────────────────────────────────────
if (($_now - (int)($_SESSION['login_time'] ?? 0)) > SESSION_ABSOLUTE_TTL) {
    session_unset();
    session_destroy();
    _redir_login('session_expired');
}

// ── 3. Idle timeout (15 min) ──────────────────────────────────────────────────
if (($_now - (int)($_SESSION['last_activity'] ?? 0)) > SESSION_IDLE_TTL) {
    session_unset();
    session_destroy();
    _redir_login('idle');
}

// ── 4. User-agent hash (blocca session hijacking di base) ─────────────────────
if (!hash_equals((string)($_SESSION['ua_hash'] ?? ''), md5($_SERVER['HTTP_USER_AGENT'] ?? ''))) {
    session_unset();
    session_destroy();
    _redir_login('session_invalid');
}

// ── 5. Rigenerazione periodica session ID ─────────────────────────────────────
if (($_now - (int)($_SESSION['last_regenerate'] ?? 0)) > SESSION_REGEN_EVERY) {
    session_regenerate_id(true);
    $_SESSION['last_regenerate'] = $_now;
}

// ── Aggiorna last_activity ────────────────────────────────────────────────────
$_SESSION['last_activity'] = $_now;
unset($_now);

// ── CSRF token ────────────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
