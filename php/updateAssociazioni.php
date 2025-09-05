<?php
/**
 * PRIORITÀ #4: Aggiorna associazioni parametri-rose
 * File: php/updateAssociazioni.php
 */

declare(strict_types=1);

// Protezione e setup
require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../config/config.php';

// Verifica admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accesso negato']);
    exit;
}

// Verifica CSRF
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!$csrfToken || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
    exit;
}

header('Content-Type: application/json');

try {
    // Connessione database
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Validazione input
    $rosaId = $_POST['rosaId'] ?? null;
    $parametri = $_POST['parametri'] ?? [];
    
    if (!$rosaId || !is_numeric($rosaId)) {
        throw new Exception('ID rosa non valido');
    }
    
    // Verifica che la rosa esista
    $stmt = $conn->prepare("SELECT nome_fantasquadra, anno FROM rosa WHERE id_rosa = ?");
    $stmt->execute([$rosaId]);
    $rosa = $stmt->fetch();
    
    if (!$rosa) {
        throw new Exception('Rosa non trovata');
    }
    
    // Log operazione
    error_log("Aggiornamento associazioni per rosa {$rosaId} ({$rosa['nome_fantasquadra']} {$rosa['anno']})");
    
    // Inizia transazione
    $conn->beginTransaction();
    
    try {
        // Elimina associazioni esistenti
        $stmt = $conn->prepare("DELETE FROM parametri_utilizzati WHERE id_rosa = ?");
        $stmt->execute([$rosaId]);
        
        $deletedCount = $stmt->rowCount();
        error_log("Eliminate {$deletedCount} associazioni esistenti");
        
        // Inserisci nuove associazioni
        $insertedCount = 0;
        if (!empty($parametri) && is_array($parametri)) {
            $stmt = $conn->prepare("
                INSERT INTO parametri_utilizzati (id_rosa, id_parametro) 
                SELECT ?, ? 
                WHERE EXISTS (SELECT 1 FROM parametri_rosa WHERE id_parametro = ?)
            ");
            
            foreach ($parametri as $parametroId) {
                if (is_numeric($parametroId)) {
                    $stmt->execute([$rosaId, $parametroId, $parametroId]);
                    if ($stmt->rowCount() > 0) {
                        $insertedCount++;
                    }
                }
            }
        }
        
        // Commit transazione
        $conn->commit();
        
        error_log("Inserite {$insertedCount} nuove associazioni");
        
        // Successo
        echo json_encode([
            'success' => true,
            'message' => "Associazioni aggiornate: {$insertedCount} parametri associati",
            'data' => [
                'rosa_id' => $rosaId,
                'rosa_name' => $rosa['nome_fantasquadra'],
                'anno' => $rosa['anno'],
                'parametri_count' => $insertedCount,
                'operazione' => [
                    'eliminate' => $deletedCount,
                    'inserite' => $insertedCount
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback in caso di errore
        $conn->rollback();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Errore database in updateAssociazioni: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Errore database: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Errore in updateAssociazioni: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>