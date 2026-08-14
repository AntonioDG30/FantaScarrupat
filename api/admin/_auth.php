<?php
/**
 * api/admin/_auth.php — Guard incluso da tutti gli endpoint admin AJAX.
 *
 * Dopo require:
 *   - $db       → PDO
 *   - $csrf_ok  → bool (token valido)
 * Risponde 403 e termina se la sessione non è admin.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(fs_session_cookie_params());
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if (empty($_SESSION['user_id']) || empty($_SESSION['flag_admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$_raw_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
$csrf_ok    = hash_equals((string)($_SESSION['csrf_token'] ?? ''), $_raw_token);

$db = getDB();
