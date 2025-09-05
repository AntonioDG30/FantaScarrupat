<?php
// ================== php/updateImmagine.php (#6) ==================
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
    $imgId = $input['id'] ?? null;
    $name = $input['name'] ?? null;
    $description = $input['description'] ?? null;
    
    if (!$imgId || !is_numeric($imgId)) {
        throw new Exception('ID immagine non valido');
    }
    
    if (!$name || !$description) {
        throw new Exception('Nome e descrizione sono obbligatori');
    }
    
    $name = trim($name);
    $description = trim($description);
    
    if (strlen($name) < 2) {
        throw new Exception('Il nome deve essere di almeno 2 caratteri');
    }
    
    // Aggiorna immagine
    $stmt = $conn->prepare("
        UPDATE immagine 
        SET descrizione_immagine = ? 
        WHERE id_immagine = ?
    ");
    $stmt->execute([$description, $imgId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Immagine non trovata o nessuna modifica effettuata');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Immagine aggiornata con successo',
        'data' => ['id' => $imgId, 'name' => $name, 'description' => $description]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>