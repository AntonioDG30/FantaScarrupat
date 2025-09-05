<?php
/**
 * #3: Aggiorna parametro esistente
 * File: php/updateParametro.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accesso negato']);
    exit;
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!$csrfToken || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
    exit;
}

header('Content-Type: application/json');

try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $parametroId = $_POST['parametroId'] ?? null;
    $numeroParametro = $_POST['numeroParametro'] ?? null;
    $testoParametro = $_POST['testoParametro'] ?? null;
    
    if (!$parametroId || !$numeroParametro || !$testoParametro) {
        throw new Exception('Tutti i campi sono obbligatori');
    }
    
    if (!is_numeric($parametroId) || !is_numeric($numeroParametro)) {
        throw new Exception('ID e numero parametro devono essere numerici');
    }
    
    $numeroParametro = (int)$numeroParametro;
    $testoParametro = trim($testoParametro);
    
    if ($numeroParametro <= 0) {
        throw new Exception('Il numero parametro deve essere positivo');
    }
    
    if (strlen($testoParametro) < 3) {
        throw new Exception('La descrizione deve essere di almeno 3 caratteri');
    }
    
    // Verifica che il parametro esista
    $stmt = $conn->prepare("SELECT numero_parametro FROM parametri_rosa WHERE id_parametro = ?");
    $stmt->execute([$parametroId]);
    $existing = $stmt->fetch();
    
    if (!$existing) {
        throw new Exception('Parametro non trovato');
    }
    
    // Verifica che il numero non sia già utilizzato da un altro parametro
    $stmt = $conn->prepare("
        SELECT id_parametro FROM parametri_rosa 
        WHERE numero_parametro = ? AND id_parametro != ?
    ");
    $stmt->execute([$numeroParametro, $parametroId]);
    
    if ($stmt->fetch()) {
        throw new Exception("Il numero parametro {$numeroParametro} è già utilizzato");
    }
    
    // Aggiorna il parametro
    $stmt = $conn->prepare("
        UPDATE parametri_rosa 
        SET numero_parametro = ?, testo_parametro = ? 
        WHERE id_parametro = ?
    ");
    $stmt->execute([$numeroParametro, $testoParametro, $parametroId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Nessuna modifica effettuata');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Parametro aggiornato con successo',
        'data' => [
            'id' => $parametroId,
            'numero' => $numeroParametro,
            'testo' => $testoParametro
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Errore updateParametro: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>