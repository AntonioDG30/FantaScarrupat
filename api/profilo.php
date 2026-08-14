<?php
/**
 * api/profilo.php — Preferenze profilo utente
 *
 * POST action=set_theme  (CSRF required)
 *   body: { theme: 'dark'|'light'|'auto' }
 *   Salva la preferenza tema su users.theme_preference.
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

$action = $_POST['action'] ?? '';

// ── update_username ───────────────────────────────────────────────────
if ($action === 'update_username') {
    $new_username = trim($_POST['username'] ?? '');

    if ($new_username === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Username non può essere vuoto.']);
        exit;
    }
    if (strlen($new_username) < 3 || strlen($new_username) > 40) {
        http_response_code(400);
        echo json_encode(['error' => 'Username deve essere tra 3 e 40 caratteri.']);
        exit;
    }
    if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $new_username)) {
        http_response_code(400);
        echo json_encode(['error' => 'Username può contenere solo lettere, numeri, _, -, .']);
        exit;
    }

    try {
        $db = getDB();
        // Verifica unicità
        $check = $db->prepare("SELECT id_user FROM users WHERE username = ? AND id_user <> ?");
        $check->execute([$new_username, (int)$_SESSION['user_id']]);
        if ($check->fetchColumn()) {
            http_response_code(409);
            echo json_encode(['error' => 'Username già in uso.']);
            exit;
        }

        $db->prepare("UPDATE users SET username = ? WHERE id_user = ?")
           ->execute([$new_username, (int)$_SESSION['user_id']]);

        $_SESSION['username'] = $new_username;
        echo json_encode(['success' => true, 'username' => $new_username]);
    } catch (\PDOException $e) {
        error_log('[FS profilo update_username] ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Errore DB.']);
    }
    exit;
}

if ($action === 'set_theme') {
    $theme = $_POST['theme'] ?? '';
    $allowed = ['dark', 'light', 'auto'];
    if (!in_array($theme, $allowed, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid theme']);
        exit;
    }
    try {
        getDB()
            ->prepare("UPDATE users SET theme_preference = ? WHERE id_user = ?")
            ->execute([$theme, (int)$_SESSION['user_id']]);
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        error_log('[FS profilo] ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'DB error']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
