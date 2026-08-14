<?php
/**
 * api/ruota.php — Sorteggio asta
 *
 * POST action=shuffle (CSRF required)
 *   Ritorna { partecipanti: ['Nome1', 'Nome2', ...] } in ordine casuale.
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

if (($_POST['action'] ?? '') !== 'shuffle') {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
    exit;
}

try {
    $rows = getDB()
        ->query("SELECT fantallenatore FROM fantasquadra WHERE flag_attuale = 1 ORDER BY fantallenatore ASC")
        ->fetchAll(\PDO::FETCH_COLUMN);

    // Fisher-Yates shuffle usando random_int crittograficamente sicuro
    $arr = array_values($rows);
    for ($i = count($arr) - 1; $i > 0; $i--) {
        $j          = random_int(0, $i);
        [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]];
    }

    echo json_encode(['partecipanti' => $arr]);
} catch (\PDOException $e) {
    error_log('[FS ruota] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'DB error']);
}
