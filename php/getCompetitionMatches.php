<?php
// ================== php/getCompetitionMatches.php (#5) ==================
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
    
    $competitionId = $_GET['id'] ?? '';
    if (!$competitionId || !is_numeric($competitionId)) {
        throw new Exception('ID competizione non valido');
    }
    
    // Info competizione
    $stmt = $conn->prepare("
        SELECT cd.nome_competizione, cd.anno
        FROM competizione_disputata cd
        WHERE cd.id_competizione_disputata = ?
    ");
    $stmt->execute([$competitionId]);
    $competizione = $stmt->fetch();
    
    if (!$competizione) {
        throw new Exception('Competizione non trovata');
    }
    
    // Partite della competizione
    $stmt = $conn->prepare("
        SELECT pa.squadra_casa, pa.squadra_ospite, pa.data_partita, pa.risultato
        FROM partita_avvessario pa
        WHERE pa.id_competizione_disputata = ?
        ORDER BY pa.data_partita ASC
    ");
    $stmt->execute([$competitionId]);
    $partite = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => array_merge($competizione, ['partite' => $partite])
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>