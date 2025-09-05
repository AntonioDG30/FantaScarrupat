<?php
// ================== php/deleteImmagine.php (#6) ==================
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
    
    if (!$imgId || !is_numeric($imgId)) {
        throw new Exception('ID immagine non valido');
    }
    
    // Ottieni info immagine prima di eliminarla
    $stmt = $conn->prepare("SELECT nome_immagine FROM immagine WHERE id_immagine = ?");
    $stmt->execute([$imgId]);
    $immagine = $stmt->fetch();
    
    if (!$immagine) {
        throw new Exception('Immagine non trovata');
    }
    
    // Elimina dal database
    $stmt = $conn->prepare("DELETE FROM immagine WHERE id_immagine = ?");
    $stmt->execute([$imgId]);
    
    // Prova a eliminare il file fisico
    $filePath = __DIR__ . '/../img/fotoGallery/' . $immagine['nome_immagine'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Immagine eliminata con successo',
        'data' => ['id' => $imgId, 'filename' => $immagine['nome_immagine']]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>