<?php
// ================== php/deleteParametro.php (#3) ==================
declare(strict_types=1);
require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $input = json_decode(file_get_contents('php://input'), true);
    $parametroId = $input['id'] ?? null;
    
    if (!$parametroId || !is_numeric($parametroId)) {
        throw new Exception('ID parametro non valido');
    }
    
    // Verifica utilizzi
    $stmt = $conn->prepare("SELECT COUNT(*) FROM parametri_utilizzati WHERE id_parametro = ?");
    $stmt->execute([$parametroId]);
    $utilizzi = $stmt->fetchColumn();
    
    if ($utilizzi > 0) {
        throw new Exception("Impossibile eliminare: parametro utilizzato in {$utilizzi} rose");
    }
    
    // Elimina parametro
    $stmt = $conn->prepare("DELETE FROM parametri_rosa WHERE id_parametro = ?");
    $stmt->execute([$parametroId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Parametro non trovato');
    }
    
    echo json_encode(['success' => true, 'message' => 'Parametro eliminato con successo']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>