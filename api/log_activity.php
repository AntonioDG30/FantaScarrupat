<?php
/**
 * api/log_activity.php — Heartbeat: aggiorna last_activity nel DB
 *
 * POST (CSRF required) — chiamato da session-monitor.js ogni N secondi.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(fs_session_cookie_params());
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token)) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF mismatch']);
    exit;
}

try {
    getDB()
        ->prepare("UPDATE users SET last_activity = NOW() WHERE id_user = ?")
        ->execute([(int)$_SESSION['user_id']]);

    echo json_encode(['success' => true]);
} catch (\PDOException $e) {
    error_log('[FS log_activity] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'DB error']);
}
