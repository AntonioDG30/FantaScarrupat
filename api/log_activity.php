<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// Verifica metodo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodo non consentito']);
    exit;
}

// Verifica CSRF
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF token non valido']);
    exit;
}

// Leggi input JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['activity_type']) || !isset($data['description'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parametri mancanti']);
    exit;
}

$activity_type = trim($data['activity_type']);
$description = trim($data['description']);

// Validazione
if (empty($activity_type) || empty($description)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parametri non validi']);
    exit;
}

// Tipi di attività consentiti
$allowed_types = ['search', 'export', 'login', 'logout', 'profile_update', 'password_change'];
if (!in_array($activity_type, $allowed_types)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tipo attività non valido']);
    exit;
}

try {
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
    
    // Verifica se la tabella user_activities esiste
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_activities'");
    if ($stmt->rowCount() === 0) {
        // Crea tabella se non esiste
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `user_activities` (
                `id` int NOT NULL AUTO_INCREMENT,
                `id_user` int NOT NULL,
                `activity_type` varchar(50) NOT NULL,
                `description` text NOT NULL,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_user_created` (`id_user`, `created_at`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4
        ");
    }
    
    // Inserisci attività
    $stmt = $pdo->prepare("
        INSERT INTO user_activities (id_user, activity_type, description) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $activity_type, $description]);
    
    // Aggiorna contatore ricerche se è una search
    if ($activity_type === 'search') {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET total_searches = COALESCE(total_searches, 0) + 1 
            WHERE id_user = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Attività registrata con successo'
    ]);
    
} catch (PDOException $e) {
    error_log("Activity log error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Errore nel salvataggio attività'
    ]);
}