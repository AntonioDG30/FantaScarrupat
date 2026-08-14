<?php
/**
 * api/auth.php — Endpoint sessione
 *
 * GET  ?action=check  → {valid, idle_left, absolute_left}
 * POST action=logout  (CSRF) → {success} + distrugge sessione
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(fs_session_cookie_params());
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// ── CHECK SESSION ─────────────────────────────────────────────────────────────
if ($action === 'check') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['valid' => false]);
        exit;
    }

    $now      = time();
    $idle_l   = SESSION_IDLE_TTL     - ($now - (int)($_SESSION['last_activity'] ?? 0));
    $abs_l    = SESSION_ABSOLUTE_TTL - ($now - (int)($_SESSION['login_time']    ?? 0));

    if ($idle_l <= 0 || $abs_l <= 0) {
        echo json_encode(['valid' => false]);
        exit;
    }

    // Aggiorna last_activity (heartbeat leggero)
    $_SESSION['last_activity'] = $now;

    echo json_encode([
        'valid'         => true,
        'idle_left'     => max(0, $idle_l),
        'absolute_left' => max(0, $abs_l),
    ]);
    exit;
}

// ── LOGOUT AJAX ───────────────────────────────────────────────────────────────
if ($action === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF mismatch']);
        exit;
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
                  $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
