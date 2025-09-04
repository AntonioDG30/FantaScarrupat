<?php
declare(strict_types=1);

/**
 * API endpoint per operazioni di autenticazione
 */

// Headers di sicurezza
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

// CORS restrictive
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $allowed_origins = [
        parse_url($_SERVER['HTTP_HOST'] ?? '', PHP_URL_HOST)
    ];
    $origin = parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST);
    if (in_array($origin, $allowed_origins)) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    }
}

session_start();

require_once __DIR__ . '/../config/config.php';

// Funzione di risposta JSON
function respond($success, $data = null, $error = null, $http_code = 200) {
    http_response_code($http_code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'timestamp' => time()
    ]);
    exit;
}

// Verifica metodo
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, null, 'Method not allowed', 405);
}

// Verifica CSRF token
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    respond(false, null, 'Invalid JSON payload', 400);
}

$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!$csrf_token || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    respond(false, null, 'CSRF token mismatch', 403);
}

$action = $input['action'] ?? '';

try {
    // Connessione database
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    switch ($action) {
        case 'heartbeat':
            if (!isset($_SESSION['user_id'])) {
                respond(false, null, 'Session not valid', 401);
            }
            
            $now = time();
            
            // IMPORTANTE: NON aggiornare last_activity qui!
            // Il heartbeat deve solo VERIFICARE, non RESETTARE il timer
            
            // Verifica timeout inattività (usa il valore esistente, non aggiornarlo)
            $last_activity = $_SESSION['last_activity'] ?? $now;
            if (($now - $last_activity) > SESSION_IDLE_TTL) {
                // Sessione scaduta per inattività
                session_destroy();
                respond(false, ['session_valid' => false, 'reason' => 'idle_timeout'], null, 401);
            }
            
            // Verifica scadenza assoluta
            $login_time = $_SESSION['login_time'] ?? $now;
            if (($now - $login_time) > SESSION_ABSOLUTE_TTL) {
                // Sessione scaduta per durata massima
                session_destroy();
                respond(false, ['session_valid' => false, 'reason' => 'absolute_timeout'], null, 401);
            }
            
            // NON aggiornare $_SESSION['last_activity'] qui!
            
            // Update solo last_heartbeat nel DB (non last_activity)
            try {
                $stmt = $pdo->prepare("UPDATE users SET last_heartbeat = NOW() WHERE id_user = ?");
                $stmt->execute([$_SESSION['user_id']]);
            } catch (PDOException $e) {
                error_log("Heartbeat DB update failed: " . $e->getMessage());
            }
            
            respond(true, [
                'session_valid' => true,
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'] ?? null,
                'remaining_idle' => SESSION_IDLE_TTL - ($now - $last_activity),
                'remaining_absolute' => SESSION_ABSOLUTE_TTL - ($now - $login_time),
                'last_activity' => $last_activity,
                'current_time' => $now
            ]);
            break;
            
        case 'update_activity':
            // Questa azione aggiorna REALMENTE l'attività (chiamata solo su azioni utente reali)
            if (!isset($_SESSION['user_id'])) {
                respond(false, null, 'Not authenticated', 401);
            }
            
            $now = time();
            $_SESSION['last_activity'] = $now;
            
            // Aggiorna anche nel database
            try {
                $stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id_user = ?");
                $stmt->execute([$_SESSION['user_id']]);
            } catch (PDOException $e) {
                error_log("Activity update failed: " . $e->getMessage());
            }
            
            respond(true, [
                'last_activity' => $_SESSION['last_activity'],
                'session_remaining' => SESSION_IDLE_TTL - (time() - $_SESSION['last_activity'])
            ]);
            break;
            
        case 'check_session':
            // Verifica se la sessione è valida
            if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
                respond(true, ['valid' => false]);
            }
            
            // Verifica timeout sessione
            $now = time();
            $last_activity = $_SESSION['last_activity'] ?? $now;
            
            if (($now - $last_activity) > SESSION_IDLE_TTL) {
                // Sessione scaduta
                session_destroy();
                respond(true, ['valid' => false, 'reason' => 'timeout']);
            }
            
            // Verifica che l'utente esista ancora nel database
            try {
                $stmt = $pdo->prepare("SELECT id_user FROM users WHERE id_user = ? AND username = ?");
                $stmt->execute([$_SESSION['user_id'], $_SESSION['username']]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    // Utente non più esistente
                    session_destroy();
                    respond(true, ['valid' => false, 'reason' => 'user_not_found']);
                }
            } catch (PDOException $e) {
                error_log("Session check DB error: " . $e->getMessage());
                respond(false, null, 'Database error', 500);
            }
            
            respond(true, [
                'valid' => true,
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'is_admin' => $_SESSION['is_admin'] ?? false,
                'session_remaining' => SESSION_IDLE_TTL - ($now - $last_activity)
            ]);
            break;
            
        case 'logout':
            // Logout esplicito
            $user_id = $_SESSION['user_id'] ?? null;
            $username = $_SESSION['username'] ?? null;
            
            // Log dell'operazione
            if ($user_id && $username) {
                error_log("User logout: ID={$user_id}, Username={$username}");
                
                // Opzionalmente, aggiorna ultimo logout nel database
                try {
                    $stmt = $pdo->prepare("UPDATE users SET last_logout = NOW() WHERE id_user = ?");
                    $stmt->execute([$user_id]);
                } catch (PDOException $e) {
                    error_log("Logout DB update failed: " . $e->getMessage());
                }
            }
            
            // Distrugge sessione
            session_destroy();
            
            respond(true, ['message' => 'Logout successful']);
            break;
            
        case 'get_user_info':
            // Restituisce informazioni utente corrente
            if (!isset($_SESSION['user_id'])) {
                respond(false, null, 'Not authenticated', 401);
            }
            
            try {
                $stmt = $pdo->prepare("SELECT username, flag_admin, nome_fantasquadra FROM users WHERE id_user = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    respond(false, null, 'User not found', 404);
                }
                
                respond(true, [
                    'user_id' => $_SESSION['user_id'],
                    'username' => $user['username'],
                    'is_admin' => (bool)$user['flag_admin'],
                    'nome_fantasquadra' => $user['nome_fantasquadra']
                ]);
                
            } catch (PDOException $e) {
                error_log("Get user info DB error: " . $e->getMessage());
                respond(false, null, 'Database error', 500);
            }
            break;
            
        default:
            respond(false, null, 'Invalid action', 400);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Auth API database error: " . $e->getMessage());
    respond(false, null, 'Database connection error', 500);
} catch (Exception $e) {
    error_log("Auth API error: " . $e->getMessage());
    respond(false, null, 'Internal server error', 500);
}